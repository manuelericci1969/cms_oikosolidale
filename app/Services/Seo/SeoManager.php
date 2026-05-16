<?php

namespace App\Services\Seo;

use App\Models\Page;
use Illuminate\Support\Str;

class SeoManager
{
    public function forPage(?Page $page = null): array
    {
        $meta = $this->meta($page);
        $seo = $this->seoMeta($page);

        $title = $this->clean((string) (
            data_get($seo, 'title')
            ?: data_get($meta, 'seo_title')
            ?: data_get($meta, 'title')
            ?: $page?->title
            ?: setting('seo.meta_title', config('app.name', 'R4Software'))
        ));

        $description = $this->clean((string) (
            data_get($seo, 'description')
            ?: data_get($meta, 'seo_description')
            ?: data_get($meta, 'description')
            ?: $page?->excerpt
            ?: setting('seo.meta_description', '')
        ));

        $keywords = $this->clean((string) (
            data_get($seo, 'focus_keyword')
            ?: data_get($seo, 'keywords')
            ?: data_get($meta, 'seo_keywords')
            ?: data_get($meta, 'keywords')
            ?: setting('seo.meta_keywords', '')
        ));

        $canonical = $this->canonical($page, $seo);
        $robots = $this->robots($page, $seo);

        $ogTitle = $this->clean((string) (data_get($seo, 'og.title') ?: data_get($seo, 'og_title') ?: $title));
        $ogDescription = $this->clean((string) (data_get($seo, 'og.description') ?: data_get($seo, 'og_description') ?: $description));
        $ogImage = $this->absoluteUrl((string) (data_get($seo, 'og.image') ?: data_get($seo, 'og_image') ?: data_get($seo, 'og_image_url') ?: setting('seo.og_image_url', '')));
        $ogType = $this->validChoice((string) (data_get($seo, 'og.type') ?: data_get($seo, 'og_type') ?: ($this->isHomepage($page) ? 'website' : 'article')), ['website', 'article', 'product'], $this->isHomepage($page) ? 'website' : 'article');
        $ogUrl = $this->openGraphUrl($page, $seo, $canonical);

        $twitterTitle = $this->clean((string) (data_get($seo, 'twitter.title') ?: data_get($seo, 'twitter_title') ?: $ogTitle));
        $twitterDescription = $this->clean((string) (data_get($seo, 'twitter.description') ?: data_get($seo, 'twitter_description') ?: $ogDescription));
        $twitterImage = $this->absoluteUrl((string) (data_get($seo, 'twitter.image') ?: data_get($seo, 'twitter_image') ?: $ogImage));
        $twitterCard = $this->validChoice((string) (data_get($seo, 'twitter.card') ?: data_get($seo, 'twitter_card') ?: ($twitterImage ? 'summary_large_image' : 'summary')), ['summary', 'summary_large_image'], $twitterImage ? 'summary_large_image' : 'summary');

        $schemaType = $this->validChoice((string) data_get($seo, 'schema.type', 'Auto'), [
            'Auto',
            'WebPage',
            'Article',
            'LocalBusiness',
            'SoftwareApplication',
            'Product',
            'FAQPage',
            'Service',
        ], 'Auto');

        $schemaEnabled = filter_var(data_get($seo, 'schema.enabled', true), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($schemaEnabled === null) {
            $schemaEnabled = true;
        }

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'focus_keyword' => (string) data_get($seo, 'focus_keyword', ''),
            'canonical' => $canonical,
            'robots' => $robots,
            'og_type' => $ogType,
            'og_title' => $ogTitle,
            'og_description' => $ogDescription,
            'og_image' => $ogImage,
            'og_url' => $ogUrl,
            'twitter_card' => $twitterCard,
            'twitter_title' => $twitterTitle,
            'twitter_description' => $twitterDescription,
            'twitter_image' => $twitterImage,
            'schema_type' => $schemaType,
            'schema_enabled' => $schemaEnabled,
            'schema' => $schemaEnabled ? app(SeoSchemaService::class)->forPage($page, [
                'type' => $schemaType,
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'image' => $ogImage,
                'base_url' => $this->siteUrl(),
                'custom_json' => data_get($seo, 'schema.custom_json', ''),
            ]) : '',
        ];
    }

    public function meta(?Page $page): array
    {
        return ($page && is_array($page->meta ?? null)) ? $page->meta : [];
    }

    public function seoMeta(?Page $page): array
    {
        $seo = data_get($this->meta($page), 'seo', []);
        return is_array($seo) ? $seo : [];
    }

    public function canonical(?Page $page, array $seo = []): string
    {
        if ($this->isHomepage($page)) {
            return $this->siteUrl();
        }

        $manual = trim((string) (data_get($seo, 'canonical_url') ?: data_get($seo, 'canonical') ?: data_get($this->meta($page), 'canonical_url', '')));

        if ($manual !== '') {
            return $this->absolutePageUrl($manual);
        }

        if (! $page) {
            return $this->absolutePageUrl('/' . ltrim(request()->path(), '/'));
        }

        return $this->absolutePageUrl('/' . ltrim((string) $page->slug, '/'));
    }

    public function robots(?Page $page, array $seo = []): string
    {
        $index = $this->validChoice((string) data_get($seo, 'robots.index', data_get($seo, 'robots_index', 'index')), ['index', 'noindex'], 'index');
        $follow = $this->validChoice((string) data_get($seo, 'robots.follow', data_get($seo, 'robots_follow', 'follow')), ['follow', 'nofollow'], 'follow');

        if ($page && ! $page->isPublished()) {
            $index = 'noindex';
            $follow = 'nofollow';
        }

        $advanced = data_get($seo, 'robots.advanced', data_get($seo, 'robots_advanced', []));
        $advanced = is_array($advanced) ? $advanced : [];

        $tokens = [$index, $follow];

        foreach (['noarchive', 'nosnippet', 'noimageindex'] as $flag) {
            if (! empty($advanced[$flag])) {
                $tokens[] = $flag;
            }
        }

        $maxSnippet = trim((string) data_get($advanced, 'max_snippet', ''));
        if ($maxSnippet !== '' && is_numeric($maxSnippet)) {
            $tokens[] = 'max-snippet:' . (int) $maxSnippet;
        }

        $maxImagePreview = $this->validChoice((string) data_get($advanced, 'max_image_preview', ''), ['none', 'standard', 'large'], '');
        if ($maxImagePreview !== '') {
            $tokens[] = 'max-image-preview:' . $maxImagePreview;
        }

        $maxVideoPreview = trim((string) data_get($advanced, 'max_video_preview', ''));
        if ($maxVideoPreview !== '' && is_numeric($maxVideoPreview)) {
            $tokens[] = 'max-video-preview:' . (int) $maxVideoPreview;
        }

        return implode(',', array_values(array_unique(array_filter($tokens))));
    }

    public function siteUrl(): string
    {
        $configured = trim((string) (setting('seo.base_url', '') ?: config('app.url', '')));
        if ($configured !== '' && preg_match('#^https?://#i', $configured)) {
            return rtrim($configured, '/');
        }

        if (app()->runningInConsole()) {
            return rtrim(url('/'), '/');
        }

        return rtrim(request()->getSchemeAndHttpHost(), '/');
    }

    protected function openGraphUrl(?Page $page, array $seo, string $canonical): string
    {
        if ($this->isHomepage($page) || $this->isSiteRootUrl($canonical)) {
            return $this->siteUrl();
        }

        $manual = trim((string) (data_get($seo, 'og.url') ?: data_get($seo, 'og_url') ?: ''));

        return $manual !== '' ? $this->absolutePageUrl($manual) : $canonical;
    }

    protected function isHomepage(?Page $page): bool
    {
        if ($page === null) {
            return false;
        }

        if ((bool) ($page->is_homepage ?? false)) {
            return true;
        }

        $slug = trim((string) ($page->slug ?? ''), '/');

        return $slug === '' || in_array(strtolower($slug), ['home', 'homepage', 'index'], true);
    }

    protected function isSiteRootUrl(string $url): bool
    {
        return rtrim($this->absolutePageUrl($url), '/') === rtrim($this->siteUrl(), '/');
    }

    protected function absoluteUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return $this->siteUrl() . '/' . ltrim($url, '/');
    }

    protected function absolutePageUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (! Str::startsWith($url, ['http://', 'https://'])) {
            return $this->siteUrl() . '/' . ltrim($url, '/');
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return $this->siteUrl();
        }

        $path = '/' . ltrim((string) ($parts['path'] ?? ''), '/');
        if ($path === '/') {
            $path = '';
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) && $parts['fragment'] !== '' ? '#' . $parts['fragment'] : '';

        return rtrim($this->siteUrl(), '/') . $path . $query . $fragment;
    }

    protected function validChoice(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    protected function clean(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');
    }
}
