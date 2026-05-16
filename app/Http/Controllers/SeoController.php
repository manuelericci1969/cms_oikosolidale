<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Support\Facades\File;

class SeoController extends Controller
{
    /**
     * /robots.txt pubblico
     * - se esiste il file fisico, serve quello
     * - se NON esiste, lo genera al volo e lo salva
     */
    public function robots()
    {
        $path = public_path('robots.txt');

        if (!File::exists($path)) {
            $content = $this->buildRobotsContent();
            File::put($path, $content);
        }

        return response()->file($path, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * /sitemap.xml pubblico
     * - se esiste il file fisico, serve quello
     * - se NON esiste, lo genera al volo e lo salva
     */
    public function sitemap()
    {
        $path = public_path('sitemap.xml');

        if (!File::exists($path)) {
            $xml = $this->buildSitemapXml();
            File::put($path, $xml);
        }

        return response()->file($path, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Endpoint ADMIN: rigenera robots.txt e sitemap.xml
     * da usare dopo modifiche alle impostazioni SEO.
     */
    public function regenerate()
    {
        // Rigenera robots.txt
        $robots = $this->buildRobotsContent();
        File::put(public_path('robots.txt'), $robots);

        // Rigenera sitemap.xml
        $xml = $this->buildSitemapXml();
        File::put(public_path('sitemap.xml'), $xml);

        return back()->with('ok', 'robots.txt e sitemap.xml rigenerati correttamente.');
    }

    /**
     * Costruisce il contenuto del robots.txt in base alle impostazioni
     */
    protected function buildRobotsContent(): string
    {
        $lines = [];

        $lines[] = 'User-agent: *';
        $lines[] = 'Disallow: /admin';
        $lines[] = 'Disallow: /login';
        $lines[] = 'Disallow: /register';
        $lines[] = 'Disallow: /dashboard';
        $lines[] = 'Disallow: /profile';
        $lines[] = 'Disallow: /debug-page';

        $lines[] = 'Sitemap: ' . url('/sitemap.xml');

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

        return implode(PHP_EOL, $lines);
    }

    /**
     * Costruisce l'XML della sitemap usando la tua view Blade
     */
    protected function buildSitemapXml(): string
    {
        $pages = Page::published()->get();

        return view('sitemap.xml', compact('pages'))->render();
    }
}
