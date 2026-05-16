{{-- app/Modules/Crm/resources/views/email/campaigns/default.blade.php --}}

@php
    /** @var \App\Modules\Crm\Models\Campaign $campaign */
    /** @var \App\Modules\Crm\Models\CampaignRecipient $recipient */

    $campaign  = $campaign  ?? null;
    $recipient = $recipient ?? null;

    if (! $campaign || ! $recipient) {
        echo 'Errore rendering template campagna.';
        return;
    }

    // HTML base della campagna (dall’editor)
    $body = (string) ($campaign->html_body ?? '');

    // Placeholder base
    $replacements = [
        '{{name}}'  => $recipient->name ?: $recipient->email,
        '{{email}}' => $recipient->email,
    ];
    $body = str_replace(array_keys($replacements), array_values($replacements), $body);

    // ============================================================
    // 1) TRACKING CLICK: riscriviamo tutti i link <a href="...">
    //    perché passino da crm.campaigns.click
    // ============================================================

    $clickBase = route('crm.campaigns.click', [
        'recipient' => $recipient->id,
        'hash'      => $recipient->hash,
    ]);

    $bodyTracked = preg_replace_callback(
        '/<a\s+[^>]*href=(["\'])(.*?)\1[^>]*>/i',
        function ($m) use ($clickBase) {
            $fullTag      = $m[0];           // l'intero <a ...>
            $originalHref = $m[2] ?? '';     // valore dell'href

            if ($originalHref === '') {
                return $fullTag;
            }

            $lower = strtolower($originalHref);

            // non tracciamo:
            // - anchor interne
            // - mailto, tel, javascript
            if (
                str_starts_with($originalHref, '#') ||
                str_starts_with($lower, 'mailto:') ||
                str_starts_with($lower, 'tel:') ||
                str_starts_with($lower, 'javascript:')
            ) {
                return $fullTag;
            }

            // Costruisco la URL di tracking
            $tracked = $clickBase . '?url=' . urlencode($originalHref);
            $trackedEsc = htmlspecialchars($tracked, ENT_QUOTES, 'UTF-8');

            // Rimpiazzo solo la parte href="..."
            $newTag = preg_replace(
                '/href=(["\'])(.*?)\1/i',
                'href="' . $trackedEsc . '"',
                $fullTag
            );

            return $newTag ?: $fullTag;
        },
        $body
    );

    if ($bodyTracked !== null) {
        $body = $bodyTracked;
    }

    // ============================================================
    // 2) LINK DI DISISCRIZIONE (NON tracciato come click)
    // ============================================================
    $unsubUrl = route('crm.campaigns.unsubscribe', [
        'recipient' => $recipient->id,
        'hash'      => $recipient->hash,
    ]);

    $unsubscribeHtml =
        '<hr style="margin-top:24px;border:none;border-top:1px solid #dddddd;">' .
        '<p style="font-size:12px;color:#777777;margin:8px 0 0 0;">' .
        'Se non vuoi più ricevere queste comunicazioni ' .
        '<a href="' . e($unsubUrl) . '" style="color:#0d6efd;">clicca qui per disiscriverti</a>.' .
        '</p>';

    // ============================================================
    // 3) PIXEL DI APERTURA
    // ============================================================
    $openUrl = route('crm.campaigns.open', [
        'recipient' => $recipient->id,
        'hash'      => $recipient->hash,
    ]);

    // Aggiungo un timestamp per evitare cache
    $openUrlWithTs = $openUrl . '?t=' . time();

    $openPixel =
        '<img src="' . e($openUrlWithTs) . '"' .
        ' width="1" height="1" alt=""' .
        ' style="border:0;outline:none;text-decoration:none;display:block;" />';

    // ============================================================
    // 4) Inietto footer + pixel prima di </body>, se presente
    // ============================================================
    if (stripos($body, '</body>') !== false) {
        $body = str_ireplace(
            '</body>',
            $unsubscribeHtml . $openPixel . '</body>',
            $body
        );
    } else {
        $body .= $unsubscribeHtml . $openPixel;
    }
@endphp

{!! $body !!}
