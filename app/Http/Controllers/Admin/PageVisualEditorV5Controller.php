<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Seo\OgImageGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageVisualEditorV5Controller extends Controller
{
    public function edit(Page $page)
    {
        return view('admin.pages.editV5', compact('page'));
    }

    public function preview(Page $page)
    {
        return view('page.show', [
            'page' => $page,
            'preview' => true,
        ]);
    }

    public function generateOgImage(Request $request, Page $page, OgImageGeneratorService $generator): JsonResponse
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:180',
            'subtitle' => 'nullable|string|max:280',
        ]);

        $result = $generator->generateForPage(
            $page,
            $data['title'] ?? null,
            $data['subtitle'] ?? null
        );

        $meta = is_array($page->meta ?? null) ? $page->meta : [];
        $seo = is_array(data_get($meta, 'seo')) ? data_get($meta, 'seo') : [];

        $seo['og'] = array_replace_recursive(is_array($seo['og'] ?? null) ? $seo['og'] : [], [
            'image' => $result['url'],
        ]);

        $seo['twitter'] = array_replace_recursive(is_array($seo['twitter'] ?? null) ? $seo['twitter'] : [], [
            'image' => $result['url'],
            'card' => 'summary_large_image',
        ]);

        $meta['seo'] = $seo;
        $page->meta = $meta;
        $page->save();

        return response()->json([
            'ok' => true,
            'url' => $result['url'],
            'path' => $result['path'],
            'width' => $result['width'],
            'height' => $result['height'],
            'message' => 'Immagine OG 1200x630 generata e assegnata alla pagina.',
        ]);
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'excerpt' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'is_homepage' => 'sometimes|boolean',
            'published_at' => 'nullable|date',
            'visual_html' => 'nullable|string',
            'visual_css' => 'nullable|string',
            'visual_json' => 'nullable',
            'meta' => 'nullable|array',
        ]);

        $metaCurrent = is_array($page->meta) ? $page->meta : [];
        $metaIn = (array) ($data['meta'] ?? []);
        $meta = array_replace_recursive($metaCurrent, $metaIn);
        $meta['visual_editor_version'] = 'v5';

        $pageTitle = trim((string) ($metaIn['page_title'] ?? data_get($metaCurrent, 'page_title', $data['title'])));
        $pageTitle = $pageTitle !== '' ? $pageTitle : $data['title'];
        $pageExcerpt = $metaIn['page_excerpt'] ?? data_get($metaCurrent, 'page_excerpt', $data['excerpt'] ?? null);

        $slugSource = $data['slug'] ?? Str::slug($pageTitle);
        $slug = $this->uniqueSlug($slugSource, $page->id);
        $status = $data['status'];
        $publishedAt = $data['published_at'] ?? null;

        if ($status === 'published') {
            $publishedAt = $publishedAt ?: ($page->published_at ?? now());
        }

        if ($status === 'draft') {
            $publishedAt = null;
        }

        $seoTitle = $metaIn['seo_title'] ?? data_get($metaCurrent, 'seo_title', data_get($metaCurrent, 'title'));
        $seoDescription = $metaIn['seo_description'] ?? data_get($metaCurrent, 'seo_description', data_get($metaCurrent, 'description'));
        $seoKeywords = $metaIn['seo_keywords'] ?? data_get($metaCurrent, 'seo_keywords', data_get($metaCurrent, 'keywords'));

        $layoutIn = is_array($metaIn['layout'] ?? null) ? $metaIn['layout'] : [];
        $layoutCurrent = is_array(data_get($metaCurrent, 'layout')) ? data_get($metaCurrent, 'layout') : [];
        $layoutWidth = $layoutIn['width'] ?? data_get($layoutCurrent, 'width', 'standard');
        $layoutWidth = in_array($layoutWidth, ['standard', 'boxed', 'full'], true) ? $layoutWidth : 'standard';
        $layoutGutter = (int) ($layoutIn['gutter'] ?? data_get($layoutCurrent, 'gutter', 24));
        $layoutTop = (int) ($layoutIn['top'] ?? data_get($layoutCurrent, 'top', 0));

        $pageBgIn = is_array($metaIn['page_bg'] ?? null) ? $metaIn['page_bg'] : [];
        $pageBgCurrent = is_array(data_get($metaCurrent, 'page_bg')) ? data_get($metaCurrent, 'page_bg') : [];
        $pageBgType = (string) ($pageBgIn['type'] ?? data_get($pageBgCurrent, 'type', 'none'));
        $pageBgType = in_array($pageBgType, ['none', 'color', 'gradient', 'image'], true) ? $pageBgType : 'none';

        $pageBg = ['type' => $pageBgType];
        if ($pageBgType === 'color') {
            $pageBg['color'] = (string) ($pageBgIn['color'] ?? data_get($pageBgCurrent, 'color', '#ffffff'));
        } elseif ($pageBgType === 'gradient') {
            $pageBg['from'] = (string) ($pageBgIn['from'] ?? data_get($pageBgCurrent, 'from', '#0d6efd'));
            $pageBg['to'] = (string) ($pageBgIn['to'] ?? data_get($pageBgCurrent, 'to', '#ffffff'));
            $pageBg['angle'] = max(0, min(360, (int) ($pageBgIn['angle'] ?? data_get($pageBgCurrent, 'angle', 135))));
        } elseif ($pageBgType === 'image') {
            $imageIn = is_array($pageBgIn['image'] ?? null) ? $pageBgIn['image'] : [];
            $imageCurrent = is_array(data_get($pageBgCurrent, 'image')) ? data_get($pageBgCurrent, 'image') : [];
            $overlayIn = is_array($imageIn['overlay'] ?? null) ? $imageIn['overlay'] : [];
            $overlayCurrent = is_array(data_get($imageCurrent, 'overlay')) ? data_get($imageCurrent, 'overlay') : [];

            $pageBg['image'] = [
                'src' => trim((string) ($imageIn['src'] ?? data_get($imageCurrent, 'src', ''))),
                'size' => in_array(($imageIn['size'] ?? data_get($imageCurrent, 'size', 'cover')), ['cover', 'contain', 'auto'], true) ? ($imageIn['size'] ?? data_get($imageCurrent, 'size', 'cover')) : 'cover',
                'position' => (string) ($imageIn['position'] ?? data_get($imageCurrent, 'position', 'center center')),
                'repeat' => in_array(($imageIn['repeat'] ?? data_get($imageCurrent, 'repeat', 'no-repeat')), ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'], true) ? ($imageIn['repeat'] ?? data_get($imageCurrent, 'repeat', 'no-repeat')) : 'no-repeat',
                'attachment' => in_array(($imageIn['attachment'] ?? data_get($imageCurrent, 'attachment', 'scroll')), ['scroll', 'fixed'], true) ? ($imageIn['attachment'] ?? data_get($imageCurrent, 'attachment', 'scroll')) : 'scroll',
                'overlay' => [
                    'enabled' => ! empty($overlayIn['enabled']),
                    'color' => (string) ($overlayIn['color'] ?? data_get($overlayCurrent, 'color', '#000000')),
                    'opacity' => max(0, min(0.9, (float) ($overlayIn['opacity'] ?? data_get($overlayCurrent, 'opacity', 0.35)))),
                ],
            ];
        }

        $meta['page_title'] = $pageTitle;
        $meta['page_excerpt'] = $pageExcerpt;
        $meta['seo_title'] = $seoTitle;
        $meta['seo_description'] = $seoDescription;
        $meta['seo_keywords'] = $seoKeywords;
        $meta['layout'] = [
            'width' => $layoutWidth,
            'gutter' => max(0, min(120, $layoutGutter)),
            'top' => max(0, min(240, $layoutTop)),
        ];
        $meta['page_bg'] = $pageBg;

        $meta['title'] = $seoTitle ?: $pageTitle;
        $meta['description'] = $seoDescription ?: $pageExcerpt;
        $meta['keywords'] = $seoKeywords;

        foreach (['show_title', 'show_excerpt', 'show_pubdate', 'show_author', 'show_breadcrumbs'] as $key) {
            $inputKey = 'meta.' . $key;
            if ($request->has($inputKey)) {
                $meta[$key] = $request->boolean($inputKey);
            } elseif (! array_key_exists($key, $meta)) {
                $meta[$key] = in_array($key, ['show_title', 'show_pubdate', 'show_author', 'show_breadcrumbs'], true);
            }
        }

        $visualHtml = $this->withV5PublicRuntimes($data['visual_html'] ?? null);

        $page->fill([
            'title' => $pageTitle,
            'slug' => $slug,
            'excerpt' => $pageExcerpt ?: null,
            'status' => $status,
            'is_homepage' => $request->boolean('is_homepage'),
            'published_at' => $publishedAt,
            'editor_mode' => 'visual',
            'visual_html' => $visualHtml,
            'visual_css' => $data['visual_css'] ?? null,
            'visual_json' => $request->input('visual_json', []),
            'meta' => $meta,
        ]);

        $page->save();

        if ($page->is_homepage) {
            Page::where('id', '<>', $page->id)->where('is_homepage', true)->update(['is_homepage' => false]);
        }

        return back()->with('ok', 'Pagina Editor V5 aggiornata.');
    }

    private function withV5PublicRuntimes(?string $html): ?string
    {
        if (! is_string($html) || trim($html) === '') {
            return $html;
        }

        $html = $this->stripV5RuntimeClasses($html);

        foreach ([
            'r4v5-slider-pro-public-runtime',
            'r4v5-background-slider-public-runtime',
            'r4v5-animations-public-runtime',
            'r4v5-animations-public-fallback',
            'r4v5-widgets-pro-public-runtime',
        ] as $id) {
            $html = $this->removeRuntimeScript($html, $id);
        }

        $html = $this->removeRuntimeLink($html, 'r4v5-widgets-pro-public-style');

        if (str_contains($html, 'data-r4v5-slider-pro')) {
            $html .= "\n" . '<script id="r4v5-slider-pro-public-runtime" src="' . asset('assets/admin/visual-editor-v5/runtime/slider-pro-runtime.js') . '?v=20260506-v5-slider-pro-public" defer></script>';
        }

        if (str_contains($html, 'data-r4v5-bg-slider')) {
            $html .= "\n" . '<script id="r4v5-background-slider-public-runtime" src="' . asset('assets/admin/visual-editor-v5/runtime/background-slider-runtime.js') . '?v=20260506-v5-bg-slider-public" defer></script>';
        }

        if (str_contains($html, 'data-r4-animation') || str_contains($html, 'data-r4-bg-animation')) {
            $html .= "\n" . '<script id="r4v5-animations-public-runtime" src="' . asset('assets/editor-v5/runtime/public-animations.js') . '?v=20260506-v5-public-timing-repeat" defer></script>';
        }

        if ($this->hasWidgetsProMarkup($html)) {
            $html .= "\n" . '<link id="r4v5-widgets-pro-public-style" rel="stylesheet" href="' . asset('assets/editor-v5/runtime/widgets-pro.css') . '?v=20260508-v5-widgets-pro-public-detection">';
            $html .= "\n" . '<script id="r4v5-widgets-pro-public-runtime" src="' . asset('assets/editor-v5/runtime/widgets-pro.js') . '?v=20260508-v5-widgets-pro-public-detection" defer></script>';
        }

        return $html;
    }

    private function hasWidgetsProMarkup(string $html): bool
    {
        $markers = [
            'r4v5-pro-',
            'r4v5-list-pro',
            'data-r4v5-list-pro',
            'data-r4v5-faq-accordion',
            'data-r4v5-count',
            'data-r4v5-basic-block',
            'data-r4v5-section',
            'data-r4v5-widget',
        ];

        foreach ($markers as $marker) {
            if (str_contains($html, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function removeRuntimeScript(string $html, string $id): string
    {
        $quoted = preg_quote($id, '/');
        return preg_replace('/<script\b[^>]*\bid=["\']' . $quoted . '["\'][^>]*>[\s\S]*?<\/script>/i', '', $html) ?? $html;
    }

    private function removeRuntimeLink(string $html, string $id): string
    {
        $quoted = preg_quote($id, '/');
        return preg_replace('/<link\b[^>]*\bid=["\']' . $quoted . '["\'][^>]*>/i', '', $html) ?? $html;
    }

    private function stripV5RuntimeClasses(string $html): string
    {
        $runtimeClasses = [
            'is-r4-prepared',
            'is-animated',
            'r4-animation-visible',
            'is-r4-bg-animation-ready',
        ];

        return preg_replace_callback('/\sclass=("|\')([^"\']*)(\1)/i', function (array $matches) use ($runtimeClasses) {
            $classes = preg_split('/\s+/', trim($matches[2])) ?: [];
            $classes = array_values(array_filter($classes, fn ($class) => $class !== '' && ! in_array($class, $runtimeClasses, true)));

            if (empty($classes)) {
                return '';
            }

            return ' class=' . $matches[1] . implode(' ', $classes) . $matches[1];
        }, $html) ?? $html;
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug) ?: 'pagina';
        $candidate = $base;
        $counter = 2;

        while (
            Page::withTrashed()
                ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base . '-' . $counter;
            $counter++;

            if ($counter > 9999) {
                $candidate = $base . '-' . Str::random(6);
            }
        }

        return $candidate;
    }
}
