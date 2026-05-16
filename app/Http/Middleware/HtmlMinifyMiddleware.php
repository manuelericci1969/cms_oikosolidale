<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HtmlMinifyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        // agiamo solo su HTML
        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        // solo in produzione e senza debug
        if (! app()->environment('production') || config('app.debug')) {
            return $response;
        }

        $html = $response->getContent();
        $html = $this->minifyHtml($html);

        $response->setContent($html);

        // opzionale: header di debug
        $response->headers->set('X-HTML-MINIFY', 'on');

        return $response;
    }

    protected function minifyHtml(string $html): string
    {
        // 1) togli solo i commenti HTML (non condizionali)
        $html = preg_replace('/<!--(?!\[if).*?-->/', '', $html);

        // 2) togli solo spazi/a-capo TRA i tag, non dentro
        $html = preg_replace('/>\s+</', '><', $html);

        // 3) togli spazi inutili all'inizio/fine
        $html = trim($html);

        return $html;
    }
}
