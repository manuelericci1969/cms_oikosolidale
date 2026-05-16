<?php

namespace App\Modules\Crm\Services\Telephony;

use RuntimeException;

class TelnyxWebhookSignatureVerifier
{
    public function verify(string $rawBody, ?string $signature, ?string $timestamp): void
    {
        $publicKey = config('services.telnyx.public_key');
        $tolerance = (int) config('services.telnyx.webhook_tolerance', 300);

        if (!$publicKey) {
            throw new RuntimeException('TELNYX_PUBLIC_KEY non configurata.');
        }

        if (!$signature || !$timestamp) {
            throw new RuntimeException('Header firma webhook Telnyx mancanti.');
        }

        if (!ctype_digit((string) $timestamp)) {
            throw new RuntimeException('Timestamp webhook Telnyx non valido.');
        }

        $now = time();
        $ts = (int) $timestamp;

        if (abs($now - $ts) > $tolerance) {
            throw new RuntimeException('Timestamp webhook Telnyx fuori tolleranza.');
        }

        $message = $timestamp . '|' . $rawBody;

        $signatureBin = base64_decode($signature, true);
        if ($signatureBin === false) {
            throw new RuntimeException('Firma webhook Telnyx non decodificabile.');
        }

        $publicKeyBin = $this->decodePublicKey($publicKey);

        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            throw new RuntimeException('Estensione sodium non disponibile sul server.');
        }

        $ok = sodium_crypto_sign_verify_detached(
            $signatureBin,
            $message,
            $publicKeyBin
        );

        if (!$ok) {
            throw new RuntimeException('Firma webhook Telnyx non valida.');
        }
    }

    protected function decodePublicKey(string $publicKey): string
    {
        $value = trim($publicKey);

        // supporta base64
        $base64 = base64_decode($value, true);
        if ($base64 !== false && strlen($base64) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return $base64;
        }

        // supporta hex
        if (ctype_xdigit($value) && strlen($value) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES * 2) {
            $bin = hex2bin($value);
            if ($bin !== false) {
                return $bin;
            }
        }

        throw new RuntimeException('TELNYX_PUBLIC_KEY non valida: atteso formato base64 o hex Ed25519.');
    }
}
