<?php

namespace App\Services\Seo;

use App\Models\Page;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class OgImageGeneratorService
{
    public const WIDTH = 1200;
    public const HEIGHT = 630;

    /**
     * Genera una immagine Open Graph 1200x630 per una pagina.
     *
     * @return array{path:string,public_path:string,url:string,width:int,height:int}
     */
    public function generateForPage(Page $page, ?string $title = null, ?string $subtitle = null): array
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('Estensione PHP GD non disponibile: impossibile generare immagine OG PNG.');
        }

        $title = $this->cleanText($title ?: data_get($page->meta, 'seo.title') ?: data_get($page->meta, 'seo_title') ?: $page->title ?: 'R4Software');
        $subtitle = $this->cleanText($subtitle ?: data_get($page->meta, 'seo.description') ?: data_get($page->meta, 'seo_description') ?: $page->excerpt ?: 'Software, siti web, CRM, CMS, app mobile, SEO e soluzioni IoT per aziende.');

        $relativeDir = 'storage/uploads/seo/og';
        $publicDir = public_path($relativeDir);
        File::ensureDirectoryExists($publicDir);

        $slug = Str::slug((string) ($page->slug ?: $page->title ?: 'pagina')) ?: 'pagina';
        $fileName = 'page-' . $page->id . '-' . $slug . '-1200x630.png';
        $publicPath = $publicDir . DIRECTORY_SEPARATOR . $fileName;
        $relativePath = $relativeDir . '/' . $fileName;

        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        if (! $image) {
            throw new RuntimeException('Impossibile creare canvas immagine OG.');
        }

        imageantialias($image, true);
        $this->drawBackground($image);
        $this->drawDecorations($image);
        $this->drawText($image, $title, $subtitle);

        if (! imagepng($image, $publicPath, 8)) {
            imagedestroy($image);
            throw new RuntimeException('Impossibile salvare immagine OG in: ' . $publicPath);
        }

        imagedestroy($image);

        return [
            'path' => $relativePath,
            'public_path' => $publicPath,
            'url' => asset($relativePath),
            'width' => self::WIDTH,
            'height' => self::HEIGHT,
        ];
    }

    protected function drawBackground($image): void
    {
        $height = self::HEIGHT;
        $width = self::WIDTH;

        $from = [15, 23, 42];
        $mid = [13, 110, 253];
        $to = [2, 6, 23];

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / max(1, $height - 1);
            if ($ratio < 0.58) {
                $local = $ratio / 0.58;
                $rgb = $this->mix($from, $mid, $local * 0.42);
            } else {
                $local = ($ratio - 0.58) / 0.42;
                $rgb = $this->mix($mid, $to, $local * 0.74);
            }

            $color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
            imageline($image, 0, $y, $width, $y, $color);
        }
    }

    protected function drawDecorations($image): void
    {
        $blue = imagecolorallocatealpha($image, 59, 130, 246, 72);
        $cyan = imagecolorallocatealpha($image, 125, 211, 252, 85);
        $white = imagecolorallocatealpha($image, 255, 255, 255, 108);
        $dark = imagecolorallocatealpha($image, 15, 23, 42, 42);

        imagefilledellipse($image, 1040, 110, 420, 420, $blue);
        imagefilledellipse($image, 100, 560, 360, 360, $cyan);
        imagefilledellipse($image, 880, 550, 520, 180, $dark);

        for ($i = 0; $i < 9; $i++) {
            $x = 760 + ($i * 46);
            imageline($image, $x, 0, $x - 220, self::HEIGHT, $white);
        }

        $card = imagecolorallocatealpha($image, 255, 255, 255, 110);
        imagefilledroundedrectangle($image, 72, 74, 1128, 556, 34, $card);
    }

    protected function drawText($image, string $title, string $subtitle): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        $soft = imagecolorallocate($image, 219, 234, 254);
        $blue = imagecolorallocate($image, 147, 197, 253);
        $muted = imagecolorallocate($image, 191, 219, 254);

        $fontBold = $this->fontPath(true);
        $fontRegular = $this->fontPath(false);

        if ($fontBold && $fontRegular) {
            imagettftext($image, 24, 0, 112, 142, $blue, $fontBold, 'R4SOFTWARE · OLBIA · SARDEGNA');

            $titleLines = $this->wrapTtf($title, $fontBold, 54, 980, 3);
            $y = 245;
            foreach ($titleLines as $line) {
                imagettftext($image, 54, 0, 112, $y, $white, $fontBold, $line);
                $y += 66;
            }

            $subtitleLines = $this->wrapTtf($subtitle, $fontRegular, 27, 940, 3);
            $y += 8;
            foreach ($subtitleLines as $line) {
                imagettftext($image, 27, 0, 112, $y, $soft, $fontRegular, $line);
                $y += 40;
            }

            imagettftext($image, 22, 0, 112, 514, $muted, $fontBold, 'Siti web · Software · CRM · CMS · App · SEO · IoT');
            imagettftext($image, 22, 0, 806, 514, $muted, $fontBold, 'www.r4software.it');
            return;
        }

        imagestring($image, 5, 112, 120, 'R4SOFTWARE - OLBIA - SARDEGNA', $blue);
        $titleLines = $this->wrapPlain($title, 56, 4);
        $y = 205;
        foreach ($titleLines as $line) {
            imagestring($image, 5, 112, $y, $line, $white);
            $y += 28;
        }
        foreach ($this->wrapPlain($subtitle, 78, 4) as $line) {
            imagestring($image, 4, 112, $y + 12, $line, $soft);
            $y += 24;
        }
        imagestring($image, 5, 112, 514, 'Siti web - Software - CRM - CMS - App - SEO - IoT', $muted);
        imagestring($image, 5, 850, 514, 'www.r4software.it', $muted);
    }

    protected function cleanText(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');
        return $value !== '' ? $value : 'R4Software';
    }

    protected function mix(array $a, array $b, float $ratio): array
    {
        $ratio = max(0, min(1, $ratio));
        return [
            (int) round($a[0] + (($b[0] - $a[0]) * $ratio)),
            (int) round($a[1] + (($b[1] - $a[1]) * $ratio)),
            (int) round($a[2] + (($b[2] - $a[2]) * $ratio)),
        ];
    }

    protected function fontPath(bool $bold): ?string
    {
        /*
         * Compatibile con Plesk/open_basedir:
         * controlla solo font locali dentro il progetto.
         * Se nessun font TTF locale è disponibile, drawText() usa il fallback imagestring().
         */
        $candidates = $bold ? [
            public_path('fonts/R4Software-Bold.ttf'),
            public_path('fonts/Poppins-Bold.ttf'),
            public_path('fonts/Inter-Bold.ttf'),
            public_path('fonts/DejaVuSans-Bold.ttf'),
            base_path('resources/fonts/R4Software-Bold.ttf'),
            base_path('resources/fonts/Poppins-Bold.ttf'),
            base_path('resources/fonts/Inter-Bold.ttf'),
            base_path('resources/fonts/DejaVuSans-Bold.ttf'),
            storage_path('app/fonts/R4Software-Bold.ttf'),
            storage_path('app/fonts/Poppins-Bold.ttf'),
            storage_path('app/fonts/Inter-Bold.ttf'),
            storage_path('app/fonts/DejaVuSans-Bold.ttf'),
        ] : [
            public_path('fonts/R4Software-Regular.ttf'),
            public_path('fonts/Poppins-Regular.ttf'),
            public_path('fonts/Inter-Regular.ttf'),
            public_path('fonts/DejaVuSans.ttf'),
            base_path('resources/fonts/R4Software-Regular.ttf'),
            base_path('resources/fonts/Poppins-Regular.ttf'),
            base_path('resources/fonts/Inter-Regular.ttf'),
            base_path('resources/fonts/DejaVuSans.ttf'),
            storage_path('app/fonts/R4Software-Regular.ttf'),
            storage_path('app/fonts/Poppins-Regular.ttf'),
            storage_path('app/fonts/Inter-Regular.ttf'),
            storage_path('app/fonts/DejaVuSans.ttf'),
        ];

        foreach ($candidates as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    protected function wrapTtf(string $text, string $font, int $size, int $maxWidth, int $maxLines): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = trim($line . ' ' . $word);
            $box = imagettfbbox($size, 0, $font, $candidate);
            $width = is_array($box) ? abs($box[2] - $box[0]) : 0;

            if ($width > $maxWidth && $line !== '') {
                $lines[] = $line;
                $line = $word;
                if (count($lines) >= $maxLines) {
                    break;
                }
            } else {
                $line = $candidate;
            }
        }

        if ($line !== '' && count($lines) < $maxLines) {
            $lines[] = $line;
        }

        if (count($lines) === $maxLines && count($words) > 0) {
            $last = array_pop($lines);
            $lines[] = rtrim(mb_substr((string) $last, 0, 88), ' .,;:') . '…';
        }

        return $lines;
    }

    /**
     * @return string[]
     */
    protected function wrapPlain(string $text, int $length, int $maxLines): array
    {
        $lines = explode("\n", wordwrap($text, $length, "\n", false));
        $lines = array_slice($lines, 0, $maxLines);
        if (count($lines) === $maxLines) {
            $lines[$maxLines - 1] = rtrim($lines[$maxLines - 1], ' .,;:') . '...';
        }
        return $lines;
    }
}

if (! function_exists('imagefilledroundedrectangle')) {
    function imagefilledroundedrectangle($image, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }
}
