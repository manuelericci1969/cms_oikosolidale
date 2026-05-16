<?php

namespace App\Http\Middleware;

use App\Models\Page;
use App\Services\Seo\SeoManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectPageSeoHeadMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        if (! $request->isMethod('GET') || $request->is('api*')) {
            return $response;
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        $isAllowedAdminPreview = $routeName === 'admin.pages.preview_v5';

        if ($request->is('admin*') && ! $isAllowedAdminPreview) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if ($contentType !== '' && stripos($contentType, 'text/html') === false) {
            return $response;
        }

        $content = (string) $response->getContent();
        if ($content === '' || stripos($content, '</head>') === false) {
            return $response;
        }

        $page = $this->resolvePage($request);
        if (! $page) {
            return $response;
        }

        $seo = app(SeoManager::class)->forPage($page);
        $head = $this->buildHead($seo);

        if ($head === '') {
            return $response;
        }

        $content = $this->normalizeHtmlLanguage($content);
        $content = $this->removeManagedTags($content);
        $content = str_ireplace('</head>', $head . PHP_EOL . '</head>', $content);
        $response->setContent($content);

        return $response;
    }

    protected function resolvePage(Request $request): ?Page
    {
        $route = $request->route();
        $name = $route?->getName();

        if ($name === 'admin.pages.preview_v5') {
            $page = $route?->parameter('page');
            return $page instanceof Page ? $page : null;
        }

        if ($name === 'home' || trim($request->path(), '/') === '') {
            return Page::homepage()->published()->first();
        }

        if ($name === 'page.show') {
            $slug = (string) ($route?->parameter('slug') ?? '');
            if ($slug !== '') {
                return Page::where('slug', $slug)->published()->first();
            }
        }

        return null;
    }

    protected function buildHead(array $seo): string
    {
        $tags = [];

        if (filled($seo['canonical'] ?? '')) {
            $tags[] = '<link rel="canonical" href="' . e((string) $seo['canonical']) . '">';
        }

        if (filled($seo['robots'] ?? '')) {
            $tags[] = '<meta name="robots" content="' . e((string) $seo['robots']) . '">';
        }

        if (filled($seo['og_type'] ?? '')) {
            $tags[] = '<meta property="og:type" content="' . e((string) $seo['og_type']) . '">';
        }

        if (filled($seo['og_title'] ?? '')) {
            $tags[] = '<meta property="og:title" content="' . e((string) $seo['og_title']) . '">';
        }

        if (filled($seo['og_description'] ?? '')) {
            $tags[] = '<meta property="og:description" content="' . e((string) $seo['og_description']) . '">';
        }

        if (filled($seo['og_url'] ?? '')) {
            $tags[] = '<meta property="og:url" content="' . e((string) $seo['og_url']) . '">';
        }

        $tags[] = '<meta property="og:site_name" content="R4Software">';
        $tags[] = '<meta property="og:locale" content="it_IT">';

        if (filled($seo['og_image'] ?? '')) {
            $ogImage = (string) $seo['og_image'];
            $tags[] = '<meta property="og:image" content="' . e($ogImage) . '">';
            $tags[] = '<meta property="og:image:secure_url" content="' . e($ogImage) . '">';
            $tags[] = '<meta property="og:image:alt" content="' . e((string) ($seo['og_title'] ?? $seo['title'] ?? 'R4Software')) . '">';

            $dimensions = $this->detectOgImageDimensions($ogImage, $seo);
            if ($dimensions !== null) {
                $tags[] = '<meta property="og:image:width" content="' . $dimensions['width'] . '">';
                $tags[] = '<meta property="og:image:height" content="' . $dimensions['height'] . '">';
            }
        }

        if (filled($seo['twitter_card'] ?? '')) {
            $tags[] = '<meta name="twitter:card" content="' . e((string) $seo['twitter_card']) . '">';
        }

        if (filled($seo['twitter_title'] ?? '')) {
            $tags[] = '<meta name="twitter:title" content="' . e((string) $seo['twitter_title']) . '">';
        }

        if (filled($seo['twitter_description'] ?? '')) {
            $tags[] = '<meta name="twitter:description" content="' . e((string) $seo['twitter_description']) . '">';
        }

        if (filled($seo['twitter_image'] ?? '')) {
            $tags[] = '<meta name="twitter:image" content="' . e((string) $seo['twitter_image']) . '">';
        }

        if (filled($seo['schema'] ?? '')) {
            $tags[] = (string) $seo['schema'];
        }

        return implode(PHP_EOL, $tags);
    }

    protected function detectOgImageDimensions(string $ogImage, array $seo): ?array
    {
        $configuredWidth = (int) ($seo['og_image_width'] ?? $seo['image_width'] ?? 0);
        $configuredHeight = (int) ($seo['og_image_height'] ?? $seo['image_height'] ?? 0);

        if ($configuredWidth > 0 && $configuredHeight > 0) {
            return [
                'width' => $configuredWidth,
                'height' => $configuredHeight,
            ];
        }

        if (preg_match('/(?:^|[-_\/])(?<width>\d{3,5})x(?<height>\d{3,5})(?:\D|$)/i', $ogImage, $match)) {
            $width = (int) ($match['width'] ?? 0);
            $height = (int) ($match['height'] ?? 0);

            if ($width > 0 && $height > 0) {
                return [
                    'width' => $width,
                    'height' => $height,
                ];
            }
        }

        return null;
    }

    protected function normalizeHtmlLanguage(string $content): string
    {
        if (preg_match('/<html\b([^>]*)\blang=["\'][^"\']*["\']([^>]*)>/i', $content)) {
            return preg_replace('/<html\b([^>]*)\blang=["\'][^"\']*["\']([^>]*)>/i', '<html$1lang="it"$2>', $content, 1) ?? $content;
        }

        return preg_replace('/<html\b([^>]*)>/i', '<html$1 lang="it">', $content, 1) ?? $content;
    }

    protected function removeManagedTags(string $content): string
    {
        $patterns = [
            '/<link\b[^>]*rel=["\']canonical["\'][^>]*>\s*/i',
            '/<meta\b[^>]*name=["\']robots["\'][^>]*>\s*/i',
            '/<meta\b[^>]*property=["\']og:[^"\']+["\'][^>]*>\s*/i',
            '/<meta\b[^>]*name=["\']twitter:[^"\']+["\'][^>]*>\s*/i',
            '/<script\b[^>]*type=["\']application\/ld\+json["\'][^>]*>[\s\S]*?<\/script>\s*/i',
        ];

        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content) ?? $content;
        }

        return $content;
    }
}
