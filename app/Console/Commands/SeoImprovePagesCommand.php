<?php

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Modules\Crm\Http\Controllers\AiSeoController;

class SeoImprovePagesCommand extends Command
{
    protected $signature = 'seo:improve-pages
                            {--page_id= : Ottimizza una sola pagina}
                            {--location=Olbia : Località SEO}
                            {--keyword= : Keyword primaria opzionale}
                            {--only-published=1 : Considera solo pagine pubblicate (1/0)}';

    protected $description = 'Genera e salva suggerimenti SEO per le pagine del sito';

    public function handle(): int
    {
        $pageId = $this->option('page_id');
        $location = (string) $this->option('location');
        $keyword = $this->option('keyword');
        $onlyPublished = (string) $this->option('only-published') === '1';

        $query = Page::query();

        if ($onlyPublished) {
            $query->published();
        }

        if (!empty($pageId)) {
            $query->where('id', (int) $pageId);
        }

        $pages = $query->orderBy('id')->get();

        if ($pages->isEmpty()) {
            $this->warn('Nessuna pagina trovata.');
            return self::SUCCESS;
        }

        $this->info('Pagine trovate: ' . $pages->count());
        $this->info('Location SEO: ' . $location);
        $this->info('Solo pubblicate: ' . ($onlyPublished ? 'si' : 'no'));

        if (!empty($pageId)) {
            $this->line('Filtro page_id: ' . $pageId);
        }

        if (!empty($keyword)) {
            $this->line('Keyword forzata: ' . $keyword);
        }

        $this->newLine();

        $controller = app(AiSeoController::class);

        foreach ($pages as $page) {
            try {
                $payload = [
                    'page_id' => $page->id,
                    'location' => $location,
                ];

                if (!empty($keyword)) {
                    $payload['primary_keyword'] = $keyword;
                }

                $request = Request::create('/api/ai/seo/improve-save', 'POST', $payload);
                $request->headers->set('X-AI-KEY', (string) config('services.ai_gateway.key', ''));
                $request->headers->set('Accept', 'application/json');

                $response = $controller->improveAndSave($request);
                $data = $response->getData(true);

                if (($data['ok'] ?? false) === true) {
                    $this->info("OK pagina {$page->id} - {$page->title}");
                } else {
                    $this->error("Errore pagina {$page->id} - {$page->title}");
                }
            } catch (\Throwable $e) {
                $this->error("KO pagina {$page->id} - {$page->title}: " . $e->getMessage());
            }
        }

        $this->info('Ottimizzazione completata.');
        return self::SUCCESS;
    }
}
