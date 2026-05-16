{{-- resources/views/sitemap/xml.blade.php --}}

@php
    // $pages arriva dalla route /sitemap.xml
    // usiamo l'ultima updated_at come lastmod home, o la data di oggi se mancante
    /** @var \Illuminate\Support\Collection|\App\Models\Page[] $pages */
    $homeLastmod = optional($pages->max('updated_at'))->format('Y-m-d') ?? now()->format('Y-m-d');
@endphp

@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    {{-- Home --}}
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ $homeLastmod }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>

    {{-- Pagine dal Page Builder --}}
    @foreach($pages as $page)
        <url>
            <loc>{{ url($page->slug) }}</loc>

            @if($page->updated_at)
                <lastmod>{{ $page->updated_at->format('Y-m-d') }}</lastmod>
            @endif

            <changefreq>monthly</changefreq>
            <priority>0.7</priority>
        </url>
    @endforeach

    {{-- Pagine legali --}}
    @if(Route::has('policy.privacy'))
        <url>
            <loc>{{ route('policy.privacy') }}</loc>
            <changefreq>yearly</changefreq>
            <priority>0.3</priority>
        </url>
    @endif

    @if(Route::has('policy.cookie'))
        <url>
            <loc>{{ route('policy.cookie') }}</loc>
            <changefreq>yearly</changefreq>
            <priority>0.3</priority>
        </url>
    @endif

    @if(Route::has('policy.terms'))
        <url>
            <loc>{{ route('policy.terms') }}</loc>
            <changefreq>yearly</changefreq>
            <priority>0.3</priority>
        </url>
    @endif

</urlset>
