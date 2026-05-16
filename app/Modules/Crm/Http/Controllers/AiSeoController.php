<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiSeoController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    protected function ensureAiKey(Request $request): void
    {
        $expectedKey = (string) config('services.ai_gateway.key', '');
        $providedKey = (string) $request->header('X-AI-KEY', '');

        if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
            abort(response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401));
        }
    }

    public function pages(Request $request): JsonResponse
    {
        $this->ensureAiKey($request);

        $pages = Page::published()
            ->orderBy('title')
            ->get()
            ->map(function (Page $page) {
                return [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'url' => $page->getUrl(),
                    'meta_title' => $page->getMetaTitle(),
                    'meta_description' => $page->getMetaDescription(),
                    'meta_keywords' => $page->getMetaKeywords(),
                    'excerpt' => $page->excerpt,
                    'updated_at' => optional($page->updated_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'type' => 'seo_pages',
            'count' => $pages->count(),
            'items' => $pages,
        ]);
    }

    public function audit(Request $request): JsonResponse
    {
        $this->ensureAiKey($request);

        $validated = $request->validate([
            'page_id' => 'required|integer|exists:pages,id',
        ]);

        $page = Page::findOrFail($validated['page_id']);

        $metaTitle = $page->getMetaTitle();
        $metaDescription = $page->getMetaDescription();
        $metaKeywords = $page->getMetaKeywords();

        $contentText = '';
        if (is_array($page->content)) {
            $contentText = json_encode($page->content, JSON_UNESCAPED_UNICODE);
        } else {
            $contentText = (string) $page->content;
        }

        $issues = [];
        $score = 100;

        if (mb_strlen(trim($metaTitle)) < 30 || mb_strlen(trim($metaTitle)) > 65) {
            $issues[] = 'SEO title fuori range ideale (30-65 caratteri)';
            $score -= 10;
        }

        if (mb_strlen(trim($metaDescription)) < 120 || mb_strlen(trim($metaDescription)) > 160) {
            $issues[] = 'Meta description fuori range ideale (120-160 caratteri)';
            $score -= 10;
        }

        if (trim($metaKeywords) === '') {
            $issues[] = 'Meta keywords mancanti';
            $score -= 5;
        }

        if (trim(strip_tags($contentText)) === '' || mb_strlen(strip_tags($contentText)) < 300) {
            $issues[] = 'Contenuto troppo breve o insufficiente';
            $score -= 20;
        }

        if (!str_contains($page->slug, '-')) {
            $issues[] = 'Slug poco descrittivo';
            $score -= 5;
        }

        return response()->json([
            'ok' => true,
            'type' => 'seo_audit',
            'item' => [
                'page_id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'url' => $page->getUrl(),
                'score' => max($score, 0),
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'meta_keywords' => $metaKeywords,
                'issues' => $issues,
            ],
        ]);
    }

    public function improve(Request $request): JsonResponse
    {
        $this->ensureAiKey($request);

        $validated = $request->validate([
            'page_id' => 'required|integer|exists:pages,id',
            'primary_keyword' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $page = Page::findOrFail($validated['page_id']);

        $currentTitle = trim((string) $page->getMetaTitle());
        $currentDescription = trim((string) $page->getMetaDescription());
        $currentKeywords = trim((string) $page->getMetaKeywords());

        $requestedKeyword = trim((string) ($validated['primary_keyword'] ?? ''));
        $pageTitle = trim((string) $page->title);
        $pageSlug = trim((string) $page->slug);

        $primaryKeyword = $requestedKeyword;

        if ($primaryKeyword === '') {
            if (in_array(mb_strtolower($pageTitle), ['home', 'homepage'], true) || $pageSlug === 'home') {
                $primaryKeyword = 'sviluppo software, app mobile e siti web';
            } else {
                $primaryKeyword = $pageTitle !== '' ? $pageTitle : str_replace('-', ' ', $pageSlug);
            }
        }

        $location = trim((string) ($validated['location'] ?? 'Olbia'));

        $normalizedKeyword = preg_replace('/\s+/', ' ', (string) $primaryKeyword);
        $normalizedKeyword = trim((string) $normalizedKeyword);

        $keywordLower = mb_strtolower($normalizedKeyword);
        $locationLower = mb_strtolower($location);

        $hasLocationAlready =
            str_contains($keywordLower, $locationLower) ||
            str_contains($keywordLower, 'sardegna');

        $keywordWithLocation = $hasLocationAlready
            ? $normalizedKeyword
            : $normalizedKeyword . ' a ' . $location;

        $suggestedTitle = $keywordWithLocation . ' | R4Software';
        $suggestedTitle = mb_substr($suggestedTitle, 0, 65);

        $baseDescription = trim((string) $currentDescription);

        if ($baseDescription === '') {
            $baseDescription = trim((string) $page->excerpt);
        }

        if ($baseDescription === '') {
            $baseDescription = 'Soluzioni software, siti web, automazioni e consulenza digitale su misura per aziende e professionisti.';
        }

        $suggestedDescription = $keywordWithLocation . '. ' . $baseDescription . ' Contatta R4Software.';
        $maxDescriptionLength = 160;

        if (mb_strlen($suggestedDescription) > $maxDescriptionLength) {
            $trimmed = mb_substr($suggestedDescription, 0, $maxDescriptionLength);

            $lastSpace = mb_strrpos($trimmed, ' ');
            if ($lastSpace !== false) {
                $trimmed = mb_substr($trimmed, 0, $lastSpace);
            }

            $suggestedDescription = rtrim($trimmed, " \t\n\r\0\x0B,.;:-") . '…';
        }

        $suggestedKeywords = collect([
            $normalizedKeyword,
            $hasLocationAlready ? null : $normalizedKeyword . ' ' . $location,
            'R4Software',
            'software su misura',
            'siti web',
            'seo',
        ])->filter()->unique()->values()->implode(', ');

        $suggestedH1 = $keywordWithLocation;

        $suggestedH2 = [
            'Perché scegliere R4Software',
            'Vantaggi per aziende e professionisti',
            'Come lavoriamo',
            'Domande frequenti',
            'Richiedi una consulenza',
        ];

        $suggestedFaqs = [
            [
                'question' => 'Quanto tempo serve per realizzare il progetto?',
                'answer' => 'Dipende dalla complessità del progetto, dagli obiettivi e dalle integrazioni richieste.',
            ],
            [
                'question' => 'Lavorate solo a Olbia?',
                'answer' => 'No, lavoriamo a Olbia, in Sardegna e anche su progetti nazionali.',
            ],
            [
                'question' => 'È possibile richiedere una consulenza iniziale?',
                'answer' => 'Sì, è possibile richiedere un confronto iniziale per valutare obiettivi, tempi e strategia.',
            ],
        ];

        return response()->json([
            'ok' => true,
            'type' => 'seo_improvement',
            'item' => [
                'page_id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'url' => $page->getUrl(),

                'current' => [
                    'meta_title' => $currentTitle,
                    'meta_description' => $currentDescription,
                    'meta_keywords' => $currentKeywords,
                ],

                'suggested' => [
                    'meta_title' => $suggestedTitle,
                    'meta_description' => $suggestedDescription,
                    'meta_keywords' => $suggestedKeywords,
                    'h1' => $suggestedH1,
                    'h2' => $suggestedH2,
                    'faqs' => $suggestedFaqs,
                    'cta' => 'Contatta R4Software per una consulenza personalizzata.',
                ],
            ],
        ]);
    }

    public function saveSuggestion(Request $request): JsonResponse
    {
        $this->ensureAiKey($request);

        $validated = $request->validate([
            'page_id' => 'required|integer|exists:pages,id',
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string|max:500',
            'meta_keywords' => 'nullable|string|max:1000',
            'h1' => 'nullable|string|max:255',
            'h2' => 'nullable|array',
            'faqs' => 'nullable|array',
            'cta' => 'nullable|string|max:1000',
        ]);

        $page = Page::findOrFail($validated['page_id']);

        $meta = is_array($page->meta) ? $page->meta : [];

        $meta['title'] = $validated['meta_title'];
        $meta['description'] = $validated['meta_description'];
        $meta['keywords'] = (string) ($validated['meta_keywords'] ?? '');

        $meta['seo_suggestion'] = [
            'saved_at' => now()->toDateTimeString(),
            'h1' => $validated['h1'] ?? null,
            'h2' => $validated['h2'] ?? [],
            'faqs' => $validated['faqs'] ?? [],
            'cta' => $validated['cta'] ?? null,
            'source' => 'ai_seo_agent',
        ];

        $page->meta = $meta;
        $page->save();

        return response()->json([
            'ok' => true,
            'type' => 'seo_suggestion_saved',
            'item' => [
                'page_id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'meta_title' => $page->meta['title'] ?? null,
                'meta_description' => $page->meta['description'] ?? null,
                'meta_keywords' => $page->meta['keywords'] ?? null,
                'seo_suggestion' => $page->meta['seo_suggestion'] ?? null,
            ],
        ]);
    }


    public function improveAndSave(Request $request): JsonResponse
    {
        $this->ensureAiKey($request);

        $validated = $request->validate([
            'page_id' => 'required|integer|exists:pages,id',
            'primary_keyword' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $improveResponse = $this->improve($request);
        $improveData = $improveResponse->getData(true);

        $suggested = $improveData['item']['suggested'] ?? null;
        if (!$suggested) {
            return response()->json([
                'ok' => false,
                'message' => 'Impossibile generare il suggerimento SEO.',
            ], 422);
        }

        $page = Page::findOrFail($validated['page_id']);

        $meta = is_array($page->meta) ? $page->meta : [];

        $meta['title'] = (string) ($suggested['meta_title'] ?? '');
        $meta['description'] = (string) ($suggested['meta_description'] ?? '');
        $meta['keywords'] = (string) ($suggested['meta_keywords'] ?? '');

        $meta['seo_suggestion'] = [
            'saved_at' => now()->toDateTimeString(),
            'h1' => $suggested['h1'] ?? null,
            'h2' => $suggested['h2'] ?? [],
            'faqs' => $suggested['faqs'] ?? [],
            'cta' => $suggested['cta'] ?? null,
            'source' => 'ai_seo_agent',
            'mode' => 'improve_and_save',
        ];

        $page->meta = $meta;
        $page->save();

        return response()->json([
            'ok' => true,
            'type' => 'seo_improved_and_saved',
            'item' => [
                'page_id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'url' => $page->getUrl(),
                'meta_title' => $page->meta['title'] ?? null,
                'meta_description' => $page->meta['description'] ?? null,
                'meta_keywords' => $page->meta['keywords'] ?? null,
                'seo_suggestion' => $page->meta['seo_suggestion'] ?? null,
            ],
        ]);
    }

    protected function extractImagesFromContent(array $content): array
    {
        $images = [];

        $walk = function ($node, string $path = '') use (&$walk, &$images) {
            if (is_array($node)) {
                $isImageNode =
                    isset($node['src']) ||
                    isset($node['url']) ||
                    isset($node['image']) ||
                    (isset($node['type']) && in_array($node['type'], ['image', 'hero', 'gallery', 'media'], true));

                if ($isImageNode) {
                    $src = null;
                    $alt = null;

                    if (!empty($node['src']) && is_string($node['src'])) {
                        $src = $node['src'];
                    } elseif (!empty($node['url']) && is_string($node['url'])) {
                        $src = $node['url'];
                    } elseif (isset($node['image']) && is_array($node['image'])) {
                        $src = $node['image']['src'] ?? $node['image']['url'] ?? null;
                        $alt = $node['image']['alt'] ?? null;
                    }

                    $alt = $alt ?? ($node['alt'] ?? null);

                    if ($src) {
                        $images[] = [
                            'path' => $path,
                            'src' => $src,
                            'alt' => is_string($alt) ? $alt : '',
                        ];
                    }
                }

                foreach ($node as $key => $value) {
                    $childPath = $path === '' ? (string) $key : $path . '.' . $key;
                    $walk($value, $childPath);
                }
            }
        };

        $walk($content);

        return $images;
    }

    protected function applyAltToContent(array &$content, array $altUpdates): void
    {
        $apply = function (&$node) use (&$apply, $altUpdates) {
            if (!is_array($node)) {
                return;
            }

            $src = null;

            if (!empty($node['src']) && is_string($node['src'])) {
                $src = $node['src'];
            } elseif (!empty($node['url']) && is_string($node['url'])) {
                $src = $node['url'];
            } elseif (isset($node['image']) && is_array($node['image'])) {
                $src = $node['image']['src'] ?? $node['image']['url'] ?? null;
            }

            if ($src && isset($altUpdates[$src])) {
                $newAlt = trim((string) $altUpdates[$src]);

                if (isset($node['image']) && is_array($node['image'])) {
                    $node['image']['alt'] = $newAlt;
                } else {
                    $node['alt'] = $newAlt;
                }
            }

            foreach ($node as &$child) {
                $apply($child);
            }
        };

        $apply($content);
    }

    public function auditImages(Request $request): JsonResponse
    {
        $this->ensureAiKey($request);

        $validated = $request->validate([
            'page_id' => 'required|integer|exists:pages,id',
        ]);

        $page = Page::findOrFail($validated['page_id']);
        $content = is_array($page->content) ? $page->content : [];
        $images = $this->extractImagesFromContent($content);

        $missingAlt = collect($images)->filter(fn ($img) => trim((string) ($img['alt'] ?? '')) === '')->values();

        return response()->json([
            'ok' => true,
            'type' => 'seo_images_audit',
            'item' => [
                'page_id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'total_images' => count($images),
                'missing_alt_count' => $missingAlt->count(),
                'images' => $images,
            ],
        ]);
    }

    public function saveAltSuggestion(Request $request): JsonResponse
    {
        $this->ensureAiKey($request);

        $validated = $request->validate([
            'page_id' => 'required|integer|exists:pages,id',
            'images' => 'required|array|min:1',
            'images.*.src' => 'required|string|max:2000',
            'images.*.alt' => 'required|string|max:255',
        ]);

        $page = Page::findOrFail($validated['page_id']);
        $meta = is_array($page->meta) ? $page->meta : [];

        $meta['seo_alt_suggestions'] = [
            'saved_at' => now()->toDateTimeString(),
            'source' => 'ai_seo_agent',
            'items' => collect($validated['images'])->map(function ($img) {
                return [
                    'src' => $img['src'],
                    'alt' => trim((string) $img['alt']),
                ];
            })->values()->all(),
        ];

        $page->meta = $meta;
        $page->save();

        return response()->json([
            'ok' => true,
            'type' => 'seo_alt_suggestions_saved',
            'item' => [
                'page_id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'seo_alt_suggestions' => $page->meta['seo_alt_suggestions'] ?? null,
            ],
        ]);
    }

    public function applyAltSuggestion(Request $request): JsonResponse
    {
        $this->ensureAiKey($request);

        $validated = $request->validate([
            'page_id' => 'required|integer|exists:pages,id',
            'images' => 'nullable|array',
            'images.*.src' => 'required_with:images|string|max:2000',
            'images.*.alt' => 'required_with:images|string|max:255',
        ]);

        $page = Page::findOrFail($validated['page_id']);
        $content = is_array($page->content) ? $page->content : [];

        $altUpdates = [];

        if (!empty($validated['images'])) {
            foreach ($validated['images'] as $img) {
                $altUpdates[$img['src']] = trim((string) $img['alt']);
            }
        } else {
            $saved = $page->meta['seo_alt_suggestions']['items'] ?? [];
            foreach ($saved as $img) {
                if (!empty($img['src']) && array_key_exists('alt', $img)) {
                    $altUpdates[$img['src']] = trim((string) $img['alt']);
                }
            }
        }

        $this->applyAltToContent($content, $altUpdates);

        $page->content = $content;
        $page->save();

        return response()->json([
            'ok' => true,
            'type' => 'seo_alt_applied',
            'item' => [
                'page_id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'updated_images' => count($altUpdates),
            ],
        ]);
    }

}
