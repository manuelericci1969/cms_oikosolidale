{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    {{-- Home --}}
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>

    {{-- Pagine dal Page Builder --}}
    @foreach($pages as $page)
        <url>
            <loc>{{ url($page->slug) }}</loc>
            @if($page->updated_at)
                <lastmod>{{ $page->updated_at->toAtomString() }}</lastmod>
            @endif
            <changefreq>monthly</changefreq>
            <priority>0.7</priority>
        </url>
    @endforeach

    {{-- Pagine legali --}}
    <url>
        <loc>{{ route('policy.privacy') }}</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc>{{ route('policy.cookie') }}</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    {{--@if(Route::has('policy.terms'))
        <url>
            <loc>{{ route('policy.terms') }}</loc>
            <changefreq>yearly</changefreq>
            <priority>0.3</priority>
        </url>
    @endif--}}
</urlset>
