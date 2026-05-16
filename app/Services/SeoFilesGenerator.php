<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class SeoFilesGenerator
{
    /**
     * Genera un robots.txt statico in public/robots.txt
     */
    public function generateRobots(): void
    {
        $lines = [];

        $lines[] = 'User-agent: *';
        // Blocchiamo backend e pagine non pubbliche (come nella tua route)
        $lines[] = 'Disallow: /admin';
        $lines[] = 'Disallow: /login';
        $lines[] = 'Disallow: /register';
        $lines[] = 'Disallow: /dashboard';
        $lines[] = 'Disallow: /profile';
        $lines[] = 'Disallow: /debug-page';

        // Sitemap assoluta
        $lines[] = 'Sitemap: ' . url('/sitemap.xml');

        // Eventuali righe extra da settings (come già fai)
        $extra = trim((string) setting('seo.robots_extra', ''));
        if ($extra !== '') {
            $lines[] = '';
            $lines[] = '# Extra';
            foreach (preg_split('/\r\n|\r|\n/', $extra) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        $content = implode(PHP_EOL, $lines);

        File::put(public_path('robots.txt'), $content);
    }

    /**
     * Genera una sitemap.xml statica in public/sitemap.xml
     * riusando la tua view sitemap.xml
     */
    public function generateSitemap(): void
    {
        $pages = Page::published()->get();

        // Riutilizzi la blade esistente (resources/views/sitemap.xml.blade.php)
        $xml = View::make('sitemap.xml', compact('pages'))->render();

        File::put(public_path('sitemap.xml'), $xml);
    }
}
