<?php

namespace App\Modules\Crm\Services;

use App\Models\Setting;
use App\Modules\Crm\Models\BillingProfile;
use App\Modules\Crm\Models\Quote;

class BillingProfileDataService
{
    public function companyDataForQuote(Quote $quote): array
    {
        $snapshot = is_array($quote->billing_profile_snapshot ?? null)
            ? $quote->billing_profile_snapshot
            : null;

        if (!$snapshot && $quote->billingProfile) {
            $snapshot = $quote->billingProfile->snapshot();
        }

        if ($snapshot) {
            return $this->normalizeCompanyData($snapshot, (string) ($quote->bank_details ?? ''));
        }

        return $this->fallbackCompanyData((string) ($quote->bank_details ?? ''));
    }

    public function snapshotForProfile(?BillingProfile $profile): ?array
    {
        return $profile ? $profile->snapshot() : null;
    }

    public function fallbackCompanyData(?string $quoteBankDetails = null): array
    {
        $bankDetails = trim((string) ($quoteBankDetails ?: Setting::get('crm.bank_details', '')));

        $data = [
            'name'         => Setting::get('company.name'),
            'legal_name'   => Setting::get('company.name'),
            'vat'          => Setting::get('company.vat'),
            'tax_code'     => Setting::get('company.tax_code'),
            'address'      => Setting::get('company.address'),
            'city'         => Setting::get('company.city'),
            'zip'          => Setting::get('company.zip'),
            'province'     => Setting::get('company.province'),
            'country'      => Setting::get('company.country', 'IT'),
            'email'        => Setting::get('company.email'),
            'phone'        => Setting::get('company.phone'),
            'pec'          => Setting::get('company.pec'),
            'sdi'          => Setting::get('company.sdi'),
            'bank_details' => $bankDetails,
            'bank'         => Setting::get('company.bank'),
            'iban'         => Setting::get('company.iban'),
            'bic'          => Setting::get('company.bic'),
        ];

        return $this->normalizeCompanyData($data, $bankDetails);
    }

    public function defaultProfile(int $clientId): ?BillingProfile
    {
        return BillingProfile::where('client_id', $clientId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->first();
    }

    protected function normalizeCompanyData(array $data, ?string $bankDetails = null): array
    {
        $bankDetails = trim((string) ($bankDetails ?: ($data['bank_details'] ?? '')));
        $iban = trim((string) ($data['iban'] ?? '')) ?: $this->extractIbanFromDetails($bankDetails);
        $bic = trim((string) ($data['bic'] ?? '')) ?: $this->extractBicFromDetails($bankDetails);
        $pec = trim((string) ($data['pec'] ?? ''));
        $sdi = trim((string) ($data['sdi'] ?? ''));
        $bank = trim((string) ($data['bank'] ?? ''));

        $paymentCoordinates = $this->buildPaymentCoordinates($bank, $iban, $bic, $bankDetails);

        return [
            'name'                => $data['legal_name'] ?? $data['name'] ?? 'Il Fornitore',
            'legal_name'          => $data['legal_name'] ?? $data['name'] ?? 'Il Fornitore',
            'vat'                 => $data['vat'] ?? null,
            'tax_code'            => $data['tax_code'] ?? null,
            'address'             => $data['address'] ?? null,
            'city'                => $data['city'] ?? null,
            'zip'                 => $data['zip'] ?? null,
            'province'            => $data['province'] ?? null,
            'country'             => $data['country'] ?? 'IT',
            'email'               => $data['email'] ?? null,
            'phone'               => $data['phone'] ?? null,
            'pec'                 => $pec ?: null,
            'sdi'                 => $sdi ?: null,
            'bank'                => $bank ?: null,
            'iban'                => $iban ?: null,
            'bic'                 => $bic ?: null,
            'bank_details'        => $bankDetails ?: null,
            'payment_coordinates' => $paymentCoordinates,
        ];
    }

    protected function buildPaymentCoordinates(?string $bank, ?string $iban, ?string $bic, ?string $bankDetails): ?string
    {
        $bankDetails = trim((string) $bankDetails);

        if ($bankDetails !== '') {
            return $this->normalizeBankDetailsText($bankDetails);
        }

        $parts = [];

        if ($bank) {
            $parts[] = 'Banca: ' . trim($bank);
        }

        if ($iban) {
            $parts[] = 'IBAN: ' . trim($iban);
        }

        if ($bic) {
            $parts[] = 'BIC/SWIFT: ' . trim($bic);
        }

        return $parts ? implode(' – ', $parts) : null;
    }

    protected function normalizeBankDetailsText(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[ \t]+/', ' ', $value);
        $value = preg_replace('/\s*(?:–|—|;)\s*/u', ' – ', $value);
        $value = preg_replace('/\s*,\s*/', ', ', $value);

        return trim($value);
    }

    protected function extractIbanFromDetails(string $details): ?string
    {
        // IBAN italiano: 27 caratteri. Manteniamo gli spazi interni solo per intercettarlo,
        // poi li rimuoviamo. Evita di catturare anche parole successive come "BIC".
        if (preg_match('/\bIT\s*\d{2}\s*[A-Z]\s*\d{5}\s*\d{5}\s*[A-Z0-9]{12}\b/i', $details, $matches)) {
            return strtoupper(preg_replace('/\s+/', '', $matches[0]));
        }

        // Fallback europeo più prudente: massimo 34 caratteri e stop su separatori comuni.
        if (preg_match('/\b[A-Z]{2}\s*\d{2}(?:\s*[A-Z0-9]){11,30}\b(?![A-Z0-9])/i', $details, $matches)) {
            return strtoupper(preg_replace('/\s+/', '', $matches[0]));
        }

        return null;
    }

    protected function extractBicFromDetails(string $details): ?string
    {
        if (preg_match('/\b[A-Z]{6}[A-Z0-9]{2}(?:[A-Z0-9]{3})?\b/i', $details, $matches)) {
            $bic = strtoupper($matches[0]);

            // Evita falsi positivi se la regex incontra parole comuni.
            if (!str_starts_with($bic, 'IBAN')) {
                return $bic;
            }
        }

        return null;
    }
}
