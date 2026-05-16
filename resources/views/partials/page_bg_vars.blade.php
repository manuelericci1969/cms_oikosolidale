@php
    /**
     * Partial che calcola:
     * - $bodyStyle
     * - $overlayEnabled
     * - $overlayStyle
     *
     * Compatibile con:
     * - gradient salvato come from/to/angle (NUOVO)
     * - gradient salvato come gradient[from|to|angle] (LEGACY)
     */

    $pageModel = $page ?? null;
    $pageMeta  = ($pageModel && is_array($pageModel->meta ?? null)) ? $pageModel->meta : [];

    $bg = is_array(data_get($pageMeta, 'page_bg', null)) ? data_get($pageMeta, 'page_bg', []) : [];
    $bgType = strtolower((string)($bg['type'] ?? 'none'));

    $bodyStyle = '';
    $overlayEnabled = false;
    $overlayStyle   = '';

    // helper: rende un URL sicuro dentro url('...')
    $cssUrl = function(string $u): string {
        $u = trim($u);
        if ($u === '') return '';
        // niente quote nel CSS url('...')
        $u = str_replace(["\n","\r","'","\""], '', $u);
        return $u;
    };

    // Liste consentite (se vuoi essere restrittivo)
    $allowedPos  = ['center center','top center','bottom center','center left','center right'];
    $allowedSize = ['cover','contain','auto'];
    $allowedRep  = ['no-repeat','repeat','repeat-x','repeat-y'];
    $allowedAtt  = ['scroll','fixed'];

    if ($bgType === 'color') {
        $c = (string)($bg['color'] ?? '#ffffff');
        if ($c !== '') $bodyStyle .= "background-color:{$c};";

    } elseif ($bgType === 'gradient') {
        // ✅ NUOVO: from/to/angle direttamente su page_bg
        // ✅ LEGACY: page_bg.gradient.from/to/angle
        $legacy = is_array($bg['gradient'] ?? null) ? $bg['gradient'] : [];

        $from  = (string)($bg['from']  ?? ($legacy['from'] ?? '#0d6efd'));
        $to    = (string)($bg['to']    ?? ($legacy['to']   ?? '#6610f2'));
        $angle = is_numeric($bg['angle'] ?? null)
            ? (int)$bg['angle']
            : (is_numeric($legacy['angle'] ?? null) ? (int)$legacy['angle'] : 135);

        $angle = $angle % 360;
        $bodyStyle .= "background-image:linear-gradient({$angle}deg, {$from}, {$to});";

    } elseif ($bgType === 'image') {
        $img = is_array($bg['image'] ?? null) ? $bg['image'] : [];
        $src = (string)($img['src'] ?? '');
        $src = $cssUrl($src);

        if ($src !== '') {
            $pos = (string)($img['position'] ?? 'center center');
            if (!in_array($pos, $allowedPos, true)) $pos = 'center center';

            $size = (string)($img['size'] ?? 'cover');
            if (!in_array($size, $allowedSize, true)) $size = 'cover';

            $rep = (string)($img['repeat'] ?? 'no-repeat');
            if (!in_array($rep, $allowedRep, true)) $rep = 'no-repeat';

            $att = (string)($img['attachment'] ?? 'scroll');
            if (!in_array($att, $allowedAtt, true)) $att = 'scroll';

            $baseColor = (string)($bg['color'] ?? '');
            if ($baseColor !== '') $bodyStyle .= "background-color:{$baseColor};";

            $bodyStyle .= "background-image:url('{$src}');";
            $bodyStyle .= "background-position:{$pos};";
            $bodyStyle .= "background-size:{$size};";
            $bodyStyle .= "background-repeat:{$rep};";
            $bodyStyle .= "background-attachment:{$att};";

            $ov = is_array(data_get($img, 'overlay', null)) ? data_get($img, 'overlay', []) : [];
            $overlayEnabled = (bool)($ov['enabled'] ?? false);

            if ($overlayEnabled) {
                $ovColor = (string)($ov['color'] ?? '#000000');
                $ovOp    = (float)($ov['opacity'] ?? 0.35);
                if ($ovOp < 0) $ovOp = 0;
                if ($ovOp > 0.9) $ovOp = 0.9;
                $overlayStyle = "background-color:{$ovColor};opacity:{$ovOp};";
            }
        }
    }
@endphp
