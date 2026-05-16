<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $query = Page::with(['creator', 'updater']);

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('search')) {
            $s = (string) $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', '%' . $s . '%')
                    ->orWhere('slug', 'like', '%' . $s . '%');
            });
        }

        $pages = $query->latest()->paginate(20);

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        $templates = PageTemplate::active()->get();
        $page = new Page();

        return view('admin.pages.create', compact('templates', 'page'));
    }

    public function edit(Page $page)
    {
        $page->load('menuItems.menu');
        $pm = app(\App\Services\PluginManager::class);
        $pluginAdminAssets    = $pm->adminAssets();
        $pluginBlocksRegistry = $pm->buildBlocksRegistry();

        return view('admin.pages.edit', compact('page', 'pluginAdminAssets', 'pluginBlocksRegistry'));
    }

    public function editV2(Page $page)
    {
        return view('admin.pages.editV2', compact('page'));
    }

    public function editV3(Page $page)
    {
        return view('admin.pages.editV3', compact('page'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'excerpt'      => 'nullable|string',
            'content'      => 'nullable',
            'meta'         => 'nullable|array',
            'status'       => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'is_homepage'  => 'sometimes|boolean',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['slug'] = $this->ensureUniqueSlug($slug);

        $validated['content'] = $this->normalizeContent($validated['content'] ?? null);
        $validated['is_homepage'] = $request->boolean('is_homepage');

        $metaIn  = (array) ($validated['meta'] ?? []);
        $metaOut = $metaIn;

        $metaOut['title']       = $metaIn['title'] ?? null;
        $metaOut['description'] = $metaIn['description'] ?? null;
        $metaOut['keywords']    = $metaIn['keywords'] ?? null;

        $metaOut['show_title'] = $request->has('meta.show_title')
            ? $request->boolean('meta.show_title')
            : true;

        $metaOut['show_excerpt'] = $request->has('meta.show_excerpt')
            ? $request->boolean('meta.show_excerpt')
            : false;

        $metaOut['show_pubdate'] = $request->has('meta.show_pubdate')
            ? $request->boolean('meta.show_pubdate')
            : true;

        $metaOut['show_author'] = $request->has('meta.show_author')
            ? $request->boolean('meta.show_author')
            : true;

        $metaOut['show_breadcrumbs'] = $request->has('meta.show_breadcrumbs')
            ? $request->boolean('meta.show_breadcrumbs')
            : true;

        $layoutIn = is_array($metaIn['layout'] ?? null) ? $metaIn['layout'] : [];
        $metaOut['layout'] = $this->normalizeLayout($layoutIn);

        $bgIn = is_array($metaIn['page_bg'] ?? null) ? $metaIn['page_bg'] : [];
        if (!empty($bgIn)) {
            $metaOut['page_bg'] = $this->normalizePageBg($bgIn);
        }

        $validated['meta'] = $metaOut;

        if (($validated['status'] ?? 'draft') === 'published') {
            $validated['published_at'] = $validated['published_at'] ?? now();
        } elseif (($validated['status'] ?? 'draft') === 'draft') {
            $validated['published_at'] = null;
        }

        $page = Page::create($validated);

        if ($page->is_homepage) {
            Page::where('id', '<>', $page->id)
                ->where('is_homepage', true)
                ->update(['is_homepage' => false]);
        }

        return redirect()
            ->route('admin.pages.edit_v5', $page)
            ->with('ok', 'Pagina creata con successo!');
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'excerpt'      => 'nullable|string',
            'content'      => 'nullable',
            'meta'         => 'nullable|array',
            'status'       => 'required|in:draft,published,archived',
            'is_homepage'  => 'sometimes|boolean',
            'published_at' => 'nullable|date',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['slug'] = $this->ensureUniqueSlug($slug, $page->id);

        $newContent = null;
        if ($request->has('content')) {
            $newContent = $this->normalizeContent($request->input('content'));
        }

        $validated['is_homepage'] = $request->boolean('is_homepage');

        if (($validated['status'] ?? 'draft') === 'published') {
            $validated['published_at'] = $validated['published_at'] ?? ($page->published_at ?? now());
        } elseif (($validated['status'] ?? 'draft') === 'draft') {
            $validated['published_at'] = null;
        }

        $metaCurrent = is_array($page->meta) ? $page->meta : [];
        $metaIn      = (array) ($validated['meta'] ?? []);
        $metaOut     = array_replace_recursive($metaCurrent, $metaIn);

        $metaOut['title']       = $metaIn['title'] ?? ($metaCurrent['title'] ?? null);
        $metaOut['description'] = $metaIn['description'] ?? ($metaCurrent['description'] ?? null);
        $metaOut['keywords']    = array_key_exists('keywords', $metaIn)
            ? $metaIn['keywords']
            : ($metaCurrent['keywords'] ?? null);

        $metaOut['show_title'] = $request->has('meta.show_title')
            ? $request->boolean('meta.show_title')
            : ($metaCurrent['show_title'] ?? true);

        $metaOut['show_excerpt'] = $request->has('meta.show_excerpt')
            ? $request->boolean('meta.show_excerpt')
            : ($metaCurrent['show_excerpt'] ?? false);

        $metaOut['show_pubdate'] = $request->has('meta.show_pubdate')
            ? $request->boolean('meta.show_pubdate')
            : ($metaCurrent['show_pubdate'] ?? true);

        $metaOut['show_author'] = $request->has('meta.show_author')
            ? $request->boolean('meta.show_author')
            : ($metaCurrent['show_author'] ?? true);

        $metaOut['show_breadcrumbs'] = $request->has('meta.show_breadcrumbs')
            ? $request->boolean('meta.show_breadcrumbs')
            : ($metaCurrent['show_breadcrumbs'] ?? true);

        $layout = is_array($metaOut['layout'] ?? null) ? $metaOut['layout'] : [];
        if (!empty($layout) || array_key_exists('layout', $metaIn)) {
            $metaOut['layout'] = $this->normalizeLayout($layout);
        }

        $bg = is_array($metaOut['page_bg'] ?? null) ? $metaOut['page_bg'] : [];
        if (!empty($bg) || array_key_exists('page_bg', $metaIn)) {
            $metaOut['page_bg'] = $this->normalizePageBg($bg);
        }

        $page->fill([
            'title'        => $validated['title'],
            'slug'         => $validated['slug'],
            'excerpt'      => $validated['excerpt'] ?? null,
            'status'       => $validated['status'],
            'is_homepage'  => $validated['is_homepage'],
            'published_at' => $validated['published_at'] ?? null,
        ]);

        if (!is_null($newContent)) {
            $page->content = $newContent;
        }

        $page->meta = $metaOut;
        $page->save();

        if ($page->is_homepage) {
            Page::where('id', '<>', $page->id)
                ->where('is_homepage', true)
                ->update(['is_homepage' => false]);
        }

        $message = match ($validated['status']) {
            'published' => 'Pagina pubblicata con successo!',
            'draft'     => 'Pagina salvata come bozza.',
            'archived'  => 'Pagina archiviata.',
            default     => 'Pagina aggiornata!',
        };

        if ($page->is_homepage) {
            $message .= ' Impostata come Homepage.';
        }

        return back()->with('ok', $message);
    }

    public function updateV3(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'excerpt'      => 'nullable|string',
            'meta'         => 'nullable|array',
            'status'       => 'required|in:draft,published,archived',
            'is_homepage'  => 'sometimes|boolean',
            'published_at' => 'nullable|date',

            'editor_mode'  => 'required|in:structured,visual',
            'visual_html'  => 'nullable|string',
            'visual_css'   => 'nullable|string',
            'visual_json'  => 'nullable',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['slug'] = $this->ensureUniqueSlug($slug, $page->id);

        $validated['is_homepage'] = $request->boolean('is_homepage');

        if (($validated['status'] ?? 'draft') === 'published') {
            $validated['published_at'] = $validated['published_at'] ?? ($page->published_at ?? now());
        } elseif (($validated['status'] ?? 'draft') === 'draft') {
            $validated['published_at'] = null;
        }

        $metaCurrent = is_array($page->meta) ? $page->meta : [];
        $metaIn      = (array) ($validated['meta'] ?? []);
        $metaOut     = array_replace_recursive($metaCurrent, $metaIn);

        $metaOut['title']       = $metaIn['title'] ?? ($metaCurrent['title'] ?? null);
        $metaOut['description'] = $metaIn['description'] ?? ($metaCurrent['description'] ?? null);
        $metaOut['keywords']    = array_key_exists('keywords', $metaIn)
            ? $metaIn['keywords']
            : ($metaCurrent['keywords'] ?? null);

        $metaOut['show_title'] = $request->has('meta.show_title')
            ? $request->boolean('meta.show_title')
            : ($metaCurrent['show_title'] ?? true);

        $metaOut['show_excerpt'] = $request->has('meta.show_excerpt')
            ? $request->boolean('meta.show_excerpt')
            : ($metaCurrent['show_excerpt'] ?? false);

        $metaOut['show_pubdate'] = $request->has('meta.show_pubdate')
            ? $request->boolean('meta.show_pubdate')
            : ($metaCurrent['show_pubdate'] ?? true);

        $metaOut['show_author'] = $request->has('meta.show_author')
            ? $request->boolean('meta.show_author')
            : ($metaCurrent['show_author'] ?? true);

        $metaOut['show_breadcrumbs'] = $request->has('meta.show_breadcrumbs')
            ? $request->boolean('meta.show_breadcrumbs')
            : ($metaCurrent['show_breadcrumbs'] ?? true);

        $layout = is_array($metaOut['layout'] ?? null) ? $metaOut['layout'] : [];
        if (!empty($layout) || array_key_exists('layout', $metaIn)) {
            $metaOut['layout'] = $this->normalizeLayout($layout);
        } elseif (!isset($metaOut['layout'])) {
            $metaOut['layout'] = $this->normalizeLayout([]);
        }

        $bg = is_array($metaOut['page_bg'] ?? null) ? $metaOut['page_bg'] : [];
        if (!empty($bg) || array_key_exists('page_bg', $metaIn)) {
            $metaOut['page_bg'] = $this->normalizePageBg($bg);
        }

        $page->fill([
            'title'        => $validated['title'],
            'slug'         => $validated['slug'],
            'excerpt'      => $validated['excerpt'] ?? null,
            'status'       => $validated['status'],
            'is_homepage'  => $validated['is_homepage'],
            'published_at' => $validated['published_at'] ?? null,

            'editor_mode'  => $validated['editor_mode'],
            'visual_html'  => $validated['visual_html'] ?? null,
            'visual_css'   => $validated['visual_css'] ?? null,
            'visual_json'  => $validated['visual_json'] ?? [],
        ]);

        $page->meta = $metaOut;
        $page->save();

        if ($page->is_homepage) {
            Page::where('id', '<>', $page->id)
                ->where('is_homepage', true)
                ->update(['is_homepage' => false]);
        }

        $message = match ($validated['status']) {
            'published' => 'Pagina visuale pubblicata con successo!',
            'draft'     => 'Pagina visuale salvata come bozza.',
            'archived'  => 'Pagina visuale archiviata.',
            default     => 'Pagina visuale aggiornata!',
        };

        if ($page->is_homepage) {
            $message .= ' Impostata come Homepage.';
        }

        return back()->with('ok', $message);
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('ok', 'Pagina eliminata!');
    }

    public function duplicate(Page $page)
    {
        $newPage = $page->replicate();
        $newPage->title = $page->title . ' (Copia)';
        $baseSlug = $page->slug ? ($page->slug . '-copia') : Str::slug($newPage->title);
        $newPage->slug = $this->ensureUniqueSlug($baseSlug);
        $newPage->status = 'draft';
        $newPage->published_at = null;
        $newPage->is_homepage = false;
        $newPage->save();

        return redirect()
            ->route('admin.pages.edit_v4', $newPage)
            ->with('ok', 'Pagina duplicata!');
    }

    private function normalizeContent($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function ensureUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug) ?: 'pagina';
        $candidate = $base;
        $i = 2;

        while (
        Page::withTrashed()
            ->when($ignoreId, fn ($q) => $q->where('id', '<>', $ignoreId))
            ->where('slug', $candidate)
            ->exists()
        ) {
            $candidate = $base . '-' . $i;
            $i++;

            if ($i > 9999) {
                $candidate = $base . '-' . Str::random(6);
                break;
            }
        }

        return $candidate;
    }

    private function cleanCssVal($v, int $maxLen = 250): string
    {
        $v = trim((string) $v);
        if ($v === '') {
            return '';
        }

        $v = mb_substr($v, 0, $maxLen);
        $v = str_replace(["\n", "\r", '"', "'"], '', $v);

        return trim($v);
    }

    private function cleanUrl($v, int $maxLen = 800): string
    {
        $v = trim((string) $v);
        if ($v === '') {
            return '';
        }

        $v = mb_substr($v, 0, $maxLen);
        $v = str_replace(["\n", "\r", '"', "'"], '', $v);

        return trim($v);
    }

    private function normalizeLayout(array $layoutIn): array
    {
        $widthRaw = $layoutIn['width'] ?? 'standard';

        $widthMap = [
            'standard'   => 'standard',
            'container'  => 'standard',
            'boxed'      => 'boxed',
            'full'       => 'full',
            'fullwidth'  => 'full',
            'full_width' => 'full',
        ];

        $width = $widthMap[$widthRaw] ?? 'standard';

        $gutter = isset($layoutIn['gutter']) ? (int) $layoutIn['gutter'] : 24;
        $gutter = max(0, min(200, $gutter));

        $top = isset($layoutIn['top']) ? (int) $layoutIn['top'] : 0;
        $top = max(0, min(600, $top));

        return array_merge($layoutIn, [
            'width'  => $width,
            'gutter' => $gutter,
            'top'    => $top,
        ]);
    }

    private function normalizePageBg(array $bgIn): array
    {
        $type = strtolower((string) ($bgIn['type'] ?? 'none'));
        if (!in_array($type, ['none', 'color', 'gradient', 'image'], true)) {
            $type = 'none';
        }

        $bgOut = ['type' => $type];

        if ($type === 'color') {
            $bgOut['color'] = $this->cleanCssVal($bgIn['color'] ?? '#ffffff');
        }

        if ($type === 'gradient') {
            $legacy = is_array($bgIn['gradient'] ?? null) ? $bgIn['gradient'] : [];

            $from = $this->cleanCssVal($bgIn['from'] ?? ($legacy['from'] ?? '#0d6efd'));
            $to   = $this->cleanCssVal($bgIn['to'] ?? ($legacy['to'] ?? '#6610f2'));
            $ang  = is_numeric($bgIn['angle'] ?? null)
                ? (int) $bgIn['angle']
                : (is_numeric($legacy['angle'] ?? null) ? (int) $legacy['angle'] : 135);

            $ang = max(0, min(360, $ang));

            $bgOut['from']  = $from;
            $bgOut['to']    = $to;
            $bgOut['angle'] = $ang;
            $bgOut['gradient'] = [
                'from'  => $from,
                'to'    => $to,
                'angle' => $ang,
            ];
        }

        if ($type === 'image') {
            $img = is_array($bgIn['image'] ?? null) ? $bgIn['image'] : [];

            $bgOut['image'] = [
                'src'        => $this->cleanUrl($img['src'] ?? ''),
                'size'       => $this->cleanCssVal($img['size'] ?? 'cover'),
                'position'   => $this->cleanCssVal($img['position'] ?? 'center center'),
                'repeat'     => $this->cleanCssVal($img['repeat'] ?? 'no-repeat'),
                'attachment' => $this->cleanCssVal($img['attachment'] ?? 'scroll'),
                'parallax'   => !empty($img['parallax']),
            ];

            $ov = is_array($img['overlay'] ?? null) ? $img['overlay'] : [];
            $op = is_numeric($ov['opacity'] ?? null) ? (float) $ov['opacity'] : 0.35;
            if ($op < 0) {
                $op = 0;
            }
            if ($op > 0.9) {
                $op = 0.9;
            }

            $bgOut['image']['overlay'] = [
                'enabled' => !empty($ov['enabled']),
                'color'   => $this->cleanCssVal($ov['color'] ?? '#000000'),
                'opacity' => $op,
            ];
        }

        return $bgOut;
    }
}
