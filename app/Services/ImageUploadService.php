<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImageUploadService
{
    /**
     * Carica un'immagine, valida dimensione e formato, crea varianti e salva Media.
     *
     * Opzioni riconosciute (tutte facoltative):
     * - disk: string (default 'public')
     * - dir:  string (default 'uploads/Y/m')
     * - max_mb: int (default 20)
     * - allowed: array estensioni (default jpg,jpeg,png,webp,svg)
     * - quality: int qualità base per FULL raster (default 82)
     * - convert_to_webp: bool (default false)  // forza salvataggio come .webp
     * - visibility: 'public'|'private' (default 'public')
     * - eager: bool (default true)             // se false genera solo full+thumb
     * - debug_timing: bool (default false)
     *
     * NUOVE opzioni per loghi:
     * - profile: 'photo' | 'logo' (default 'photo')
     * - fit: 'contain' | 'cover'  (default: 'contain' per logo, 'cover' per photo)
     * - logo_thumb_box: [max_w, max_h] (default [800, 120])  // bounding-box per thumb logo
     */
    public function storeWithVariants(UploadedFile $file, array $opts = []): Media
    {
        $t0 = microtime(true);

        $disk        = $opts['disk']        ?? 'public';
        $baseDir     = rtrim($opts['dir']   ?? ('uploads/'.now()->format('Y/m')), '/');
        $maxMB       = (int)($opts['max_mb'] ?? 20);
        $allowed     = $opts['allowed']     ?? ['jpg','jpeg','png','webp','svg'];
        $fullQ       = (int)($opts['quality'] ?? 82);
        $forceWebp   = (bool)($opts['convert_to_webp'] ?? false);
        $visibility  = $opts['visibility']  ?? 'public';
        $eager       = array_key_exists('eager', $opts) ? (bool)$opts['eager'] : true;
        $debug       = (bool)($opts['debug_timing'] ?? false);

        // Profilo & adattamento
        $profile     = $opts['profile'] ?? 'photo'; // 'photo' | 'logo'
        $isLogo      = $profile === 'logo';
        $fitPref     = $opts['fit'] ?? ($isLogo ? 'contain' : 'cover'); // 'contain' = non tagliare, 'cover' = taglia
        $logoBox     = $opts['logo_thumb_box'] ?? [800, 120]; // bounding box thumb per logo

        // Validazioni base
        $extIn = strtolower($file->getClientOriginalExtension());
        if (!in_array($extIn, $allowed, true)) {
            throw new \RuntimeException('Formato immagine non supportato (consentiti: '.implode(', ', $allowed).').');
        }
        $sizeMB = $file->getSize() / 1024 / 1024;
        if ($sizeMB > $maxMB) {
            throw new \RuntimeException('Il file supera la dimensione massima di '.$maxMB.' MB.');
        }

        // Se SVG: salviamo "as-is" senza varianti (Intervention non serve)
        if ($extIn === 'svg') {
            $uuid     = (string) Str::uuid();
            $basename = $uuid;
            $path     = "{$baseDir}/{$basename}.svg";
            Storage::disk($disk)->putFileAs($baseDir, $file, "{$basename}.svg", $visibility);

            $size = Storage::disk($disk)->size($path) ?: 0;

            $media = Media::create([
                'disk'          => $disk,
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime'          => 'image/svg+xml',
                'size'          => $size,
                'width'         => null,
                'height'        => null,
                'variants'      => [ 'full' => ['path' => $path, 'width'=>null, 'height'=>null, 'size'=>$size] ],
                'title'         => $opts['title'] ?? null,
                'alt'           => $opts['alt'] ?? null,
                'created_by'    => Auth::id(),
            ]);

            if ($debug) {
                Log::info(sprintf('[ImageUpload] SVG salvato as-is in %.1fms', (microtime(true)-$t0)*1000));
            }

            return $media;
        }

        // Decodifica + orienta (raster)
        $img = Image::make($file->getRealPath())->orientate();
        $w   = $img->width();
        $h   = $img->height();

        $uuid     = (string) Str::uuid();
        $basename = $uuid;
        $extOut   = $forceWebp ? 'webp' : $extIn;

        // Mime coerente all'output
        $mimeMap  = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',  'webp' => 'image/webp',
            'gif' => 'image/gif',  'bmp'  => 'image/bmp',
        ];
        $mimeOut  = $mimeMap[$extOut] ?? ($file->getClientMimeType() ?: 'image/jpeg');

        /**
         * Definizione varianti
         * - PHOTO: full (max 1920w), scale 75/59/25, thumb 320x320 CROP (cover)
         * - LOGO:  full (max 2400w, contain), scale 75/59/25 (contain), thumb dentro box (default 800x120) SENZA TAGLIO (contain),
         *          se fit='cover' anche il thumb usa crop.
         */
        if ($isLogo) {
            [$lbW, $lbH] = [(int)$logoBox[0], (int)$logoBox[1]];
            $allVariants = [
                'full' => ['mode' => 'box',   'max_w' => 2400, 'max_h' => 1200, 'quality' => $fullQ], // contain
                '75'   => ['mode' => 'scale', 'scale' => 0.75, 'quality' => 80],
                '59'   => ['mode' => 'scale', 'scale' => 0.59, 'quality' => 78],
                '25'   => ['mode' => 'scale', 'scale' => 0.25, 'quality' => 75],
                // thumb: se fit='cover' usa crop (fit), altrimenti contain in bounding-box (box)
                'thumb'=> $fitPref === 'cover'
                    ? ['mode' => 'fit', 'fit' => [$lbW, $lbH], 'quality' => 78] // crop
                    : ['mode' => 'box', 'max_w' => $lbW, 'max_h' => $lbH, 'quality' => 78], // contain
            ];
        } else {
            // PHOTO
            $allVariants = [
                'full' => ['mode' => 'resize', 'max_width' => 1920, 'quality' => $fullQ],
                '75'   => ['mode' => 'scale',  'scale'     => 0.75, 'quality' => 80],
                '59'   => ['mode' => 'scale',  'scale'     => 0.59, 'quality' => 78],
                '25'   => ['mode' => 'scale',  'scale'     => 0.25, 'quality' => 75],
                // thumb classico quadrato croppato
                'thumb'=> ['mode' => 'fit',    'fit'       => [320, 320], 'quality' => 75],
            ];
        }

        // Se eager=false generiamo solo full+thumb
        $variants = $eager ? $allVariants : [
            'full'  => $allVariants['full'],
            'thumb' => $allVariants['thumb'],
        ];

        // Prepara un backup per riusare lo stesso buffer decodificato
        $img->backup();

        $storedMeta    = [];
        $originalMeta  = null;

        foreach ($variants as $key => $rule) {
            $vt0 = microtime(true);
            $img->reset();

            $mode = $rule['mode'] ?? 'resize';

            if ($mode === 'fit') {
                // CROP per riempire esattamente W x H
                [$fw, $fh] = $rule['fit'];
                $img->fit((int)$fw, (int)$fh, function ($c) {
                    $c->upsize();
                }, 'center');
            } elseif ($mode === 'box') {
                // CONTAIN dentro una bounding box (nessun taglio)
                $maxW = $rule['max_w'] ?? null;
                $maxH = $rule['max_h'] ?? null;
                if ($maxW || $maxH) {
                    $img->resize($maxW ?: null, $maxH ?: null, function ($c) {
                        $c->aspectRatio();
                        $c->upsize();
                    });
                }
            } elseif ($mode === 'scale') {
                $scale   = (float)($rule['scale'] ?? 1.0);
                $targetW = max(1, (int) floor($w * $scale));
                $img->resize($targetW, null, function ($c) {
                    $c->aspectRatio();
                    $c->upsize();
                });
            } else { // 'resize' by max_width
                $targetW = (int)($rule['max_width'] ?? $w);
                $img->resize($targetW, null, function ($c) {
                    $c->aspectRatio();
                    $c->upsize();
                });
            }

            // JPEG progressive / strip metadati se possibile
            try {
                /** @var \Intervention\Image\Image $img */
                $core = $img->getCore();
                if (method_exists($core, 'stripImage')) {
                    $core->stripImage();
                }
                if (method_exists($core, 'setImageInterlaceScheme') && $extOut === 'jpg' || $extOut === 'jpeg') {
                    $core->setImageInterlaceScheme(\Imagick::INTERLACE_JPEG);
                } elseif (method_exists($img, 'interlace') && ($extOut === 'jpg' || $extOut === 'jpeg')) {
                    $img->interlace(true);
                }
            } catch (\Throwable $e) {
                // opzionale
            }

            $qualityOut = (int)($rule['quality'] ?? 80);
            $path = "{$baseDir}/{$basename}_{$key}.{$extOut}";

            $binary = (string) $img->encode($extOut, $qualityOut);
            Storage::disk($disk)->put($path, $binary, $visibility);

            $storedMeta[$key] = [
                'path'   => $path,
                'width'  => $img->width(),
                'height' => $img->height(),
                'size'   => Storage::disk($disk)->size($path) ?: 0,
            ];

            if ($key === 'full') {
                $originalMeta = $storedMeta[$key];
            }

            if ($debug) {
                Log::info(sprintf('[ImageUpload] variante %s salvata in %.1fms', $key, (microtime(true)-$vt0)*1000));
            }
        }

        if (!$originalMeta) {
            throw new \RuntimeException('Salvataggio variante "full" non riuscito.');
        }

        // Salva record Media principale puntando alla variante "full"
        $media = Media::create([
            'disk'          => $disk,
            'path'          => $originalMeta['path'],
            'original_name' => $file->getClientOriginalName(),
            'mime'          => $mimeOut,
            'size'          => $originalMeta['size'],
            'width'         => $originalMeta['width'],
            'height'        => $originalMeta['height'],
            'variants'      => $storedMeta,
            'title'         => $opts['title'] ?? null,
            'alt'           => $opts['alt'] ?? null,
            'created_by'    => Auth::id(),
        ]);

        if ($debug) {
            Log::info(sprintf('[ImageUpload] totale %.1fms (w=%d h=%d eager=%s, webp=%s, profile=%s, fit=%s)',
                (microtime(true)-$t0)*1000, $w, $h, $eager?'yes':'no', $forceWebp?'yes':'no', $profile, $fitPref));
        }

        return $media;
    }

    /**
     * Cancella original + varianti.
     */
    public function deleteWithVariants(Media $media): void
    {
        $disk = $media->disk;
        $paths = [$media->path];
        foreach (($media->variants ?? []) as $v) {
            if (!empty($v['path'])) $paths[] = $v['path'];
        }
        foreach (array_unique($paths) as $p) {
            Storage::disk($disk)->delete($p);
        }
        $media->delete();
    }
}




