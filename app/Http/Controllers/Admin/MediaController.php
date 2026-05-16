<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function index(Request $r)
    {
        $q = (string) $r->query('q', '');
        $perPage = max(1, min(200, (int) $r->query('per_page', 24)));

        $items = Media::query()
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($qq) use ($q) {
                    $qq->where('original_name', 'like', "%{$q}%")
                        ->orWhere('title', 'like', "%{$q}%")
                        ->orWhere('alt', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        if ($r->wantsJson() || $r->boolean('ajax')) {
            return response()->json([
                'data' => $items->getCollection()->map(function (Media $m) {
                    return $this->transformMedia($m);
                })->values(),
                'meta' => [
                    'current_page' => $items->currentPage(),
                    'last_page'    => $items->lastPage(),
                    'per_page'     => $items->perPage(),
                    'total'        => $items->total(),
                ],
            ]);
        }

        return view('admin.media.index', [
            'items' => $items,
            'q'     => $q,
        ]);
    }

    public function store(Request $r, ImageUploadService $svc)
    {
        $r->validate([
            'file'  => ['required', 'file'],
            'title' => ['nullable', 'string', 'max:255'],
            'alt'   => ['nullable', 'string', 'max:255'],
        ]);

        $file = $r->file('file');
        $mime = $file->getMimeType() ?: $file->getClientMimeType();
        $isJson = $r->wantsJson() || $r->expectsJson() || $r->boolean('ajax');

        if (str_starts_with((string) $mime, 'image/')) {
            $r->validate([
                'file' => ['file', 'mimes:jpeg,jpg,png,webp', 'max:20480'],
            ]);

            $m = $svc->storeWithVariants($file, [
                'title' => $r->string('title')->toString(),
                'alt'   => $r->string('alt')->toString(),
            ]);

            if ($isJson) {
                return response()->json($this->transformMedia($m), 201);
            }

            return redirect()
                ->route('admin.media.index')
                ->with('ok', 'Immagine caricata e varianti create.');
        }

        if (str_starts_with((string) $mime, 'video/')) {
            $r->validate([
                'file' => ['file', 'mimetypes:video/mp4,video/webm,video/ogg', 'max:5120'],
            ]);

            $dir = 'media/videos/' . date('Y/m');
            $path = $file->store($dir, 'public');

            $m = Media::create([
                'disk'          => 'public',
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime'          => $mime,
                'size'          => $file->getSize(),
                'alt'           => $r->string('alt')->toString(),
                'title'         => $r->string('title')->toString(),
                'variants'      => [],
            ]);

            if ($isJson) {
                return response()->json($this->transformMedia($m), 201);
            }

            return redirect()
                ->route('admin.media.index')
                ->with('ok', 'Video caricato correttamente.');
        }

        if ($isJson) {
            return response()->json([
                'message' => 'Tipo file non supportato.',
            ], 422);
        }

        return back()->withErrors([
            'file' => 'Tipo file non supportato.',
        ]);
    }

    public function update(Request $r, Media $medium, ImageUploadService $svc)
    {
        $data = $r->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'alt'   => ['nullable', 'string', 'max:255'],
            'file'  => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:20480'],
        ]);

        $medium->fill([
            'title' => $data['title'] ?? $medium->title,
            'alt'   => $data['alt'] ?? $medium->alt,
        ])->save();

        if ($r->hasFile('file')) {
            $svc->replaceWithVariants($medium, $r->file('file'));
        }

        return back()->with('ok', 'Media aggiornato.');
    }

    public function destroy(Request $r, Media $medium, ImageUploadService $svc)
    {
        $svc->deleteWithVariants($medium);

        if ($r->wantsJson() || $r->expectsJson() || $r->boolean('ajax')) {
            return response()->json([
                'ok' => true,
                'message' => 'File e varianti eliminati.',
            ]);
        }

        return back()->with('ok', 'File e varianti eliminati.');
    }

    public function browse(Request $r)
    {
        $type = (string) $r->query('type', '');
        $imagesOnly = filter_var($r->query('images_only', $type ? '0' : '1'), FILTER_VALIDATE_BOOL);
        $q = (string) $r->query('q', '');
        $per = max(1, min(100, (int) $r->query('per_page', 24)));

        $qb = Media::query();

        if ($type === 'image' || $imagesOnly) {
            $qb->where('mime', 'like', 'image/%');
        } elseif ($type === 'video') {
            $qb->where('mime', 'like', 'video/%');
        }

        if ($q !== '') {
            $qb->where(function ($qq) use ($q) {
                $qq->where('original_name', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('alt', 'like', "%{$q}%");
            });
        }

        $paginator = $qb->orderByDesc('id')->paginate($per)->appends($r->query());

        $items = $paginator->getCollection()->map(function (Media $m) {
            return $this->transformMedia($m);
        })->values();

        return response()->json([
            'items' => $items,
            'pagination' => [
                'current_page'  => $paginator->currentPage(),
                'last_page'     => $paginator->lastPage(),
                'per_page'      => $paginator->perPage(),
                'total'         => $paginator->total(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ],
        ]);
    }

    public function picker(Request $r)
    {
        $q = (string) $r->query('q', '');
        $page = max(1, (int) $r->query('page', 1));
        $per = max(12, min(60, (int) $r->query('per', 24)));
        $mode = (string) $r->query('pb_mode', 'image');

        $qb = Media::query();

        if ($mode === 'image') {
            $qb->where('mime', 'like', 'image/%');
        } elseif ($mode === 'video') {
            $qb->where('mime', 'like', 'video/%');
        } elseif ($mode !== 'any') {
            $qb->where('mime', 'like', 'image/%');
        }

        if ($q !== '') {
            $qb->where(function ($w) use ($q) {
                $w->where('original_name', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('alt', 'like', "%{$q}%");
            });
        }

        $p = $qb->orderByDesc('id')->paginate($per, ['*'], 'page', $page);

        $items = collect($p->items())
            ->map(fn (Media $m) => $this->transformMedia($m))
            ->values();

        return response()->json([
            'items'     => $items,
            'page'      => $p->currentPage(),
            'last_page' => $p->lastPage(),
            'has_more'  => $p->hasMorePages(),
            'total'     => $p->total(),
        ]);
    }

    private function transformMedia(Media $m): array
    {
        $isImage = str_starts_with((string) $m->mime, 'image/');

        return [
            'id'            => $m->id,
            'url'           => $m->url,
            'src'           => $m->url,
            'full'          => $isImage ? ($m->variantUrl('full') ?? $m->url) : $m->url,
            'thumb'         => $isImage ? ($m->variantUrl('thumb') ?? $m->url) : null,
            'q25'           => $isImage ? ($m->variantUrl('25') ?? $m->url) : null,
            'q59'           => $isImage ? ($m->variantUrl('59') ?? $m->url) : null,
            'q75'           => $isImage ? ($m->variantUrl('75') ?? $m->url) : null,
            'variants'      => $isImage ? [
                'thumb' => $m->variantUrl('thumb'),
                '25'    => $m->variantUrl('25'),
                '59'    => $m->variantUrl('59'),
                '75'    => $m->variantUrl('75'),
                'full'  => $m->variantUrl('full') ?? $m->url,
            ] : [
                'full' => $m->url,
            ],
            'title'         => $m->title,
            'alt'           => $m->alt,
            'mime'          => $m->mime,
            'size'          => $m->size,
            'width'         => $m->width,
            'height'        => $m->height,
            'w'             => $m->width,
            'h'             => $m->height,
            'original_name' => $m->original_name,
            'created_at'    => optional($m->created_at)->toDateTimeString(),
        ];
    }
}
