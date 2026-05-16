<?php

namespace App\Services\Seo;

use App\Models\Page;
use Illuminate\Support\Str;

class SeoSchemaService
{
    public function forPage(?Page $page, array $seoData = []): string
    {
        $customJson = trim((string) ($seoData['custom_json'] ?? ''));
        if ($customJson !== '') {
            $decoded = json_decode($customJson, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && ! empty($decoded)) {
                return '<script type="application/ld+json">' . json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
            }
        }

        $baseUrl = $this->siteUrl((string) ($seoData['base_url'] ?? ''));
        $requestedType = (string) ($seoData['type'] ?? 'Auto');
        $canonical = (string) ($seoData['canonical'] ?? $this->absoluteUrl('/' . ltrim((string) request()->path(), '/'), $baseUrl));
        $canonical = $this->normalizeCanonical($canonical, $baseUrl);
        $title = $this->clean((string) ($seoData['title'] ?? $page?->title ?? config('app.name', 'R4Software')));
        $description = $this->clean((string) ($seoData['description'] ?? $page?->excerpt ?? setting('seo.meta_description', '')));
        $image = $this->absoluteUrl((string) ($seoData['image'] ?? ''), $baseUrl);

        $organizationId = $baseUrl . '/#organization';
        $websiteId = $baseUrl . '/#website';
        $pageId = $this->fragmentId($canonical, 'webpage');
        $html = (string) ($page?->visual_html ?? '');
        $detectedTypes = $this->detectedTypes($requestedType, $page, $html, $title, $description);

        $organization = $this->organizationNode($organizationId, $baseUrl);
        $website = $this->websiteNode($websiteId, $organizationId, $baseUrl);
        $pageNode = $this->webPageNode($page, $pageId, $canonical, $title, $description, $image, $websiteId, $organizationId);
        $breadcrumb = $this->breadcrumb($page, $canonical, $title, $baseUrl);

        $graph = [$organization, $website, $pageNode, $breadcrumb];

        if (in_array('FAQPage', $detectedTypes, true)) {
            $faq = $this->faqNode($page, $pageId);
            if (! empty($faq)) {
                $graph[] = $faq;
            }
        }

        if (in_array('Product', $detectedTypes, true)) {
            foreach ($this->productNodes($page, $canonical, $title, $description, $image, $organizationId, $baseUrl) as $product) {
                $graph[] = $product;
            }
        }

        if (in_array('Service', $detectedTypes, true)) {
            foreach ($this->serviceNodes($page, $organizationId, $baseUrl) as $service) {
                $graph[] = $service;
            }
        }

        if (in_array('SoftwareApplication', $detectedTypes, true)) {
            $graph[] = $this->softwareApplicationNode($page, $canonical, $title, $description, $image, $organizationId);
        }

        if (in_array('Article', $detectedTypes, true)) {
            $graph[] = $this->articleNode($page, $pageId, $canonical, $title, $description, $image, $organizationId, $websiteId);
        }

        $graph = array_values(array_filter($graph));

        $json = json_encode([
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! $json) {
            return '';
        }

        return '<script type="application/ld+json">' . $json . '</script>';
    }

    protected function organizationNode(string $organizationId, string $baseUrl): array
    {
        return $this->filter([
            '@type' => ['LocalBusiness', 'ProfessionalService', 'Organization'],
            '@id' => $organizationId,
            'name' => setting('company.name', 'R4Software'),
            'url' => $baseUrl,
            'telephone' => setting('company.phone', '+39 328 0439803'),
            'email' => setting('company.email', 'info@r4software.it'),
            'vatID' => setting('company.vat_id', 'IT02825080902'),
            'priceRange' => setting('company.price_range', '€€'),
            'description' => setting('seo.organization_description', 'Software house a Olbia specializzata in sviluppo software su misura, CRM, CMS, siti web professionali, app mobile, soluzioni IoT e consulenza digitale.'),
            'address' => $this->filter([
                '@type' => 'PostalAddress',
                'streetAddress' => setting('company.address.street', 'Via del Ghiozzo 7'),
                'postalCode' => setting('company.address.postal_code', '07026'),
                'addressLocality' => setting('company.address.locality', 'Olbia'),
                'addressRegion' => setting('company.address.region', 'SS'),
                'addressCountry' => setting('company.address.country', 'IT'),
            ]),
            'areaServed' => [
                ['@type' => 'City', 'name' => 'Olbia'],
                ['@type' => 'AdministrativeArea', 'name' => 'Sardegna'],
                ['@type' => 'Country', 'name' => 'Italia'],
            ],
            'sameAs' => $this->sameAsLinks(),
        ]);
    }

    protected function websiteNode(string $websiteId, string $organizationId, string $baseUrl): array
    {
        return $this->filter([
            '@type' => 'WebSite',
            '@id' => $websiteId,
            'url' => $baseUrl,
            'name' => setting('seo.website_name', 'R4Software'),
            'description' => setting('seo.website_description', 'Sito ufficiale R4Software: sviluppo software, CRM, CMS, siti web professionali, app mobile, marketing digitale e soluzioni IoT.'),
            'publisher' => ['@id' => $organizationId],
            'inLanguage' => 'it-IT',
        ]);
    }

    protected function webPageNode(?Page $page, string $pageId, string $canonical, string $title, string $description, string $image, string $websiteId, string $organizationId): array
    {
        return $this->filter([
            '@type' => 'WebPage',
            '@id' => $pageId,
            'url' => $canonical,
            'name' => $title,
            'headline' => $title,
            'description' => $description,
            'isPartOf' => ['@id' => $websiteId],
            'about' => ['@id' => $organizationId],
            'inLanguage' => 'it-IT',
            'image' => $image ?: null,
            'datePublished' => $page?->published_at?->toAtomString(),
            'dateModified' => $page?->updated_at?->toAtomString(),
        ]);
    }

    protected function articleNode(?Page $page, string $pageId, string $canonical, string $title, string $description, string $image, string $organizationId, string $websiteId): array
    {
        return $this->filter([
            '@type' => 'Article',
            '@id' => $this->fragmentIdFromPageId($pageId, 'article'),
            'mainEntityOfPage' => ['@id' => $pageId],
            'url' => $canonical,
            'headline' => $title,
            'name' => $title,
            'description' => $description,
            'image' => $image ?: null,
            'author' => ['@id' => $organizationId],
            'publisher' => ['@id' => $organizationId],
            'isPartOf' => ['@id' => $websiteId],
            'inLanguage' => 'it-IT',
            'datePublished' => $page?->published_at?->toAtomString(),
            'dateModified' => $page?->updated_at?->toAtomString(),
        ]);
    }

    protected function softwareApplicationNode(?Page $page, string $canonical, string $title, string $description, string $image, string $organizationId): array
    {
        return $this->filter([
            '@type' => 'SoftwareApplication',
            '@id' => $this->fragmentId($canonical, 'softwareapplication'),
            'name' => $this->productName($page, $title),
            'url' => $canonical,
            'description' => $description,
            'image' => $image ?: null,
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web, iOS, Android',
            'publisher' => ['@id' => $organizationId],
        ]);
    }

    protected function breadcrumb(?Page $page, string $canonical, string $title, string $baseUrl): array
    {
        $items = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => $baseUrl,
            ],
        ];

        if ($page && ! $page->is_homepage) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $title,
                'item' => $canonical,
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            '@id' => $this->fragmentId($canonical, 'breadcrumb'),
            'itemListElement' => $items,
        ];
    }

    protected function faqNode(?Page $page, string $pageId): array
    {
        $entities = $this->extractFaqEntities((string) ($page?->visual_html ?? ''));

        if (empty($entities)) {
            return [];
        }

        return [
            '@type' => 'FAQPage',
            '@id' => $this->fragmentIdFromPageId($pageId, 'faq'),
            'mainEntity' => $entities,
        ];
    }

    protected function productNodes(?Page $page, string $canonical, string $title, string $description, string $image, string $organizationId, string $baseUrl): array
    {
        $content = $this->pageSearchText($page, $title . ' ' . $description);
        $nodes = [];

        if ($this->containsAny($content, ['hmfluxus', 'hm fluxus'])) {
            $nodes[] = $this->productNode(
                'HMFluxus',
                'Sistema IoT per monitoraggio consumi idrici, rilevazione anomalie e prevenzione perdite con dashboard e app.',
                $canonical,
                $image,
                $organizationId,
                $baseUrl,
                '690'
            );
        }

        if ($this->containsAny($content, ['hmobile ble key', 'ble key', 'accesso ble', 'hmobile'])) {
            $nodes[] = $this->productNode(
                'HMobile BLE Key',
                'Soluzione BLE per controllo accessi da smartphone, pensata per strutture, parcheggi e aree riservate.',
                $canonical,
                $image,
                $organizationId,
                $baseUrl,
                null
            );
        }

        if (empty($nodes) && $this->looksLikeProductPage($content)) {
            $nodes[] = $this->productNode(
                $this->productName($page, $title),
                $description,
                $canonical,
                $image,
                $organizationId,
                $baseUrl,
                $this->extractPrice($content)
            );
        }

        return $nodes;
    }

    protected function productNode(string $name, string $description, string $canonical, string $image, string $organizationId, string $baseUrl, ?string $price): array
    {
        $offer = [
            '@type' => 'Offer',
            'url' => $canonical,
            'priceCurrency' => 'EUR',
            'availability' => 'https://schema.org/InStock',
            'seller' => ['@id' => $organizationId],
        ];

        if ($price !== null && $price !== '') {
            $offer['price'] = $price;
        }

        return $this->filter([
            '@type' => 'Product',
            '@id' => $baseUrl . '/#' . Str::slug($name),
            'name' => $name,
            'description' => $description,
            'image' => $image ?: null,
            'brand' => ['@id' => $organizationId],
            'manufacturer' => ['@id' => $organizationId],
            'offers' => $this->filter($offer),
        ]);
    }

    protected function serviceNodes(?Page $page, string $organizationId, string $baseUrl): array
    {
        $content = $this->pageSearchText($page);
        $services = [];

        $map = [
            'Realizzazione siti web professionali' => ['siti web', 'realizzazione siti', 'web design', 'sito web'],
            'Sviluppo software su misura' => ['sviluppo software', 'software su misura', 'gestionale', 'applicativo'],
            'CRM e CMS aziendali' => ['crm', 'cms', 'gestione clienti', 'content management'],
            'SEO e marketing digitale' => ['seo', 'marketing', 'social media', 'posizionamento'],
            'App mobile e soluzioni digitali' => ['app mobile', 'flutter', 'ios', 'android'],
            'Soluzioni IoT per imprese' => ['iot', 'hmfluxus', 'sensori', 'monitoraggio'],
        ];

        foreach ($map as $name => $needles) {
            if ($this->containsAny($content, $needles)) {
                $services[] = $this->filter([
                    '@type' => 'Service',
                    '@id' => $baseUrl . '/#service-' . Str::slug($name),
                    'name' => $name,
                    'provider' => ['@id' => $organizationId],
                    'areaServed' => [
                        ['@type' => 'City', 'name' => 'Olbia'],
                        ['@type' => 'AdministrativeArea', 'name' => 'Sardegna'],
                        ['@type' => 'Country', 'name' => 'Italia'],
                    ],
                    'serviceType' => $name,
                ]);
            }
        }

        return $services;
    }

    protected function detectedTypes(string $requestedType, ?Page $page, string $html, string $title, string $description): array
    {
        $requestedType = trim($requestedType) !== '' ? trim($requestedType) : 'Auto';
        $content = $this->pageSearchText($page, $title . ' ' . $description . ' ' . $html);
        $types = [];

        if (! in_array($requestedType, ['Auto', 'WebPage'], true)) {
            $types[] = $requestedType;
        }

        if (in_array($requestedType, ['Auto', 'WebPage', 'FAQPage'], true) && ! empty($this->extractFaqEntities($html))) {
            $types[] = 'FAQPage';
        }

        if (in_array($requestedType, ['Auto', 'WebPage', 'Product'], true) && ($this->containsAny($content, ['hmfluxus', 'hm fluxus', 'hmobile ble key', 'ble key']) || $this->looksLikeProductPage($content))) {
            $types[] = 'Product';
        }

        if (in_array($requestedType, ['Auto', 'WebPage', 'Service'], true) && $this->containsAny($content, ['siti web', 'sviluppo software', 'crm', 'cms', 'seo', 'marketing', 'app mobile', 'iot'])) {
            $types[] = 'Service';
        }

        if (in_array($requestedType, ['Auto', 'WebPage', 'SoftwareApplication'], true) && $this->containsAny($content, ['app mobile', 'software application', 'dashboard', 'ios', 'android'])) {
            $types[] = 'SoftwareApplication';
        }

        if (in_array($requestedType, ['Auto', 'Article'], true) && $this->containsAny($content, ['blog', 'articolo', 'news', 'approfondimento'])) {
            $types[] = 'Article';
        }

        return array_values(array_unique($types));
    }

    protected function extractFaqEntities(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $entities = [];
        $seen = [];

        preg_match_all('/<details\b[^>]*>\s*<summary\b[^>]*>(.*?)<\/summary>(.*?)<\/details>/is', $html, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $this->pushFaqEntity($entities, $seen, (string) ($match[1] ?? ''), (string) ($match[2] ?? ''));
        }

        preg_match_all('/<button\b[^>]*class=["\'][^"\']*accordion-button[^"\']*["\'][^>]*>(.*?)<\/button>\s*<\/h[2-6]>\s*<div\b[^>]*class=["\'][^"\']*accordion-collapse[^"\']*["\'][\s\S]*?<div\b[^>]*class=["\'][^"\']*accordion-body[^"\']*["\'][^>]*>(.*?)<\/div>/is', $html, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $this->pushFaqEntity($entities, $seen, (string) ($match[1] ?? ''), (string) ($match[2] ?? ''));
        }

        preg_match_all('/<[^>]+(?:data-r4v5-faq-question|data-r4-faq-question|class=["\'][^"\']*(?:r4v5-faq-question|faq-question|r4-faq-question)[^"\']*["\'])[^>]*>(.*?)<\/[^>]+>\s*<[^>]+(?:data-r4v5-faq-answer|data-r4-faq-answer|class=["\'][^"\']*(?:r4v5-faq-answer|faq-answer|r4-faq-answer)[^"\']*["\'])[^>]*>(.*?)<\/[^>]+>/is', $html, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $this->pushFaqEntity($entities, $seen, (string) ($match[1] ?? ''), (string) ($match[2] ?? ''));
        }

        preg_match_all('/<(?:article|div)\b[^>]*class=["\'][^"\']*(?:r4seo2026-faq-item|r4v5-faq-item|faq-item)[^"\']*["\'][^>]*>[\s\S]*?<button\b[^>]*>(.*?)<\/button>\s*<div\b[^>]*>(.*?)<\/div>[\s\S]*?<\/(?:article|div)>/is', $html, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $this->pushFaqEntity($entities, $seen, (string) ($match[1] ?? ''), (string) ($match[2] ?? ''));
        }

        preg_match_all('/<h[2-4]\b[^>]*>\s*(?:FAQ|Domande frequenti|Domande e risposte)\s*<\/h[2-4]>([\s\S]{0,12000})/i', $html, $sections);
        foreach (($sections[1] ?? []) as $section) {
            preg_match_all('/<h[3-6]\b[^>]*>(.*?)<\/h[3-6]>\s*<(?:p|div|section|article)\b[^>]*>(.*?)<\/(?:p|div|section|article)>/is', (string) $section, $pairs, PREG_SET_ORDER);
            foreach ($pairs as $pair) {
                $this->pushFaqEntity($entities, $seen, (string) ($pair[1] ?? ''), (string) ($pair[2] ?? ''));
            }
        }

        preg_match_all('/<h[3-6]\b[^>]*>([^<]*\?[^<]*)<\/h[3-6]>\s*<(?:p|div|section|article)\b[^>]*>(.*?)<\/(?:p|div|section|article)>/is', $html, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $this->pushFaqEntity($entities, $seen, (string) ($match[1] ?? ''), (string) ($match[2] ?? ''));
        }

        preg_match_all('/<(?:strong|b)\b[^>]*>([^<]*\?[^<]*)<\/(?:strong|b)>\s*<(?:p|div)\b[^>]*>(.*?)<\/(?:p|div)>/is', $html, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $this->pushFaqEntity($entities, $seen, (string) ($match[1] ?? ''), (string) ($match[2] ?? ''));
        }

        return array_slice($entities, 0, 20);
    }

    protected function pushFaqEntity(array &$entities, array &$seen, string $question, string $answer): void
    {
        $question = $this->clean($question);
        $answer = $this->clean($answer);

        if ($question === '' || $answer === '') {
            return;
        }

        if (! str_contains($question, '?') && ! preg_match('/^(come|cosa|quanto|quando|dove|perché|chi|quale|quali)\b/i', $question)) {
            return;
        }

        $key = mb_strtolower($question);
        if (isset($seen[$key])) {
            return;
        }

        $seen[$key] = true;
        $entities[] = [
            '@type' => 'Question',
            'name' => $question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $answer,
            ],
        ];
    }

    protected function sameAsLinks(): array
    {
        $configured = setting('seo.same_as', null);

        if (is_string($configured) && trim($configured) !== '') {
            return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $configured) ?: [])));
        }

        if (is_array($configured)) {
            return array_values(array_filter(array_map('trim', $configured)));
        }

        return [
            'https://it.trustpilot.com/review/r4software.it',
            'https://www.facebook.com/p/R4Software-61570207204014/',
            'https://www.linkedin.com/company/r4-software/',
        ];
    }

    protected function siteUrl(string $preferred = ''): string
    {
        $preferred = trim($preferred);
        if ($preferred !== '' && preg_match('#^https?://#i', $preferred)) {
            return rtrim($preferred, '/');
        }

        $configured = trim((string) (setting('seo.base_url', '') ?: config('app.url', '')));
        if ($configured !== '' && preg_match('#^https?://#i', $configured)) {
            return rtrim($configured, '/');
        }

        if (app()->runningInConsole()) {
            return rtrim(url('/'), '/');
        }

        return rtrim(request()->getSchemeAndHttpHost(), '/');
    }

    protected function absoluteUrl(string $url, string $baseUrl): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
    }

    protected function pageSearchText(?Page $page, string $extra = ''): string
    {
        $parts = [
            $page?->title,
            $page?->slug,
            $page?->excerpt,
            $page?->visual_html,
            $page?->visual_css,
            $extra,
        ];

        return mb_strtolower($this->clean(implode(' ', array_filter(array_map(static fn ($value) => is_scalar($value) ? (string) $value : '', $parts)))));
    }

    protected function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, mb_strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    protected function looksLikeProductPage(string $content): bool
    {
        return $this->containsAny($content, ['prodotto', 'soluzione', 'prezzo', 'da €', '€ + iva', 'iva']) && $this->extractPrice($content) !== null;
    }

    protected function extractPrice(string $content): ?string
    {
        if (preg_match('/(?:da\s*)?(?:€|euro)\s*([0-9]+(?:[\.,][0-9]{1,2})?)/i', $content, $match)) {
            return str_replace(',', '.', $match[1]);
        }

        if (preg_match('/(?:da\s*)?([0-9]+(?:[\.,][0-9]{1,2})?)\s*(?:€|euro)/i', $content, $match)) {
            return str_replace(',', '.', $match[1]);
        }

        return null;
    }

    protected function productName(?Page $page, string $fallback): string
    {
        $content = $this->pageSearchText($page, $fallback);

        if ($this->containsAny($content, ['hmfluxus', 'hm fluxus'])) {
            return 'HMFluxus';
        }

        if ($this->containsAny($content, ['hmobile ble key', 'ble key', 'hmobile'])) {
            return 'HMobile BLE Key';
        }

        return $fallback !== '' ? $fallback : 'Prodotto R4Software';
    }

    protected function normalizeCanonical(string $canonical, string $baseUrl): string
    {
        $canonical = trim($canonical);

        if ($canonical === '') {
            return $baseUrl;
        }

        if (! Str::startsWith($canonical, ['http://', 'https://'])) {
            return $this->absoluteUrl($canonical, $baseUrl);
        }

        return rtrim($canonical, '/') === rtrim($baseUrl, '/') ? rtrim($baseUrl, '/') : rtrim($canonical, '/');
    }

    protected function fragmentBase(string $url): string
    {
        $url = preg_replace('/#.*$/', '', trim($url)) ?? trim($url);
        $url = preg_replace('/\?.*$/', '', $url) ?? $url;
        $url = rtrim($url, '/');

        return $url . '/';
    }

    protected function fragmentId(string $url, string $fragment): string
    {
        return $this->fragmentBase($url) . '#' . ltrim($fragment, '#');
    }

    protected function fragmentIdFromPageId(string $pageId, string $fragment): string
    {
        $base = preg_replace('/#.*$/', '', trim($pageId)) ?? trim($pageId);

        return $this->fragmentId($base, $fragment);
    }

    protected function filter(array $data): array
    {
        return array_filter($data, static fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    protected function clean(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }
}
