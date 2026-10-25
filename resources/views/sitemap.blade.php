<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
    @php($locales = ['en', 'bn'])

    <!-- Static Pages -->
    @foreach($staticPages as $page)
        @foreach($locales as $locale)
            @php($path = $page['path'] === '' ? '/'.$locale : '/'.$locale.'/'.$page['path'])
    <url>
        <loc>{{ url($path) }}</loc>
            @foreach($locales as $alternateLocale)
                @php($alternatePath = $page['path'] === '' ? '/'.$alternateLocale : '/'.$alternateLocale.'/'.$page['path'])
        <xhtml:link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ url($alternatePath) }}"/>
            @endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ url($page['path'] === '' ? '/en' : '/en/'.$page['path']) }}"/>
        <changefreq>{{ $page['changefreq'] }}</changefreq>
        <priority>{{ $page['priority'] }}</priority>
    </url>
        @endforeach
    @endforeach

    <!-- Services -->
    @foreach($services as $service)
        @foreach(['en' => $service->slug_en, 'bn' => $service->slug_bn] as $locale => $slug)
            @if($slug)
    <url>
        <loc>{{ url('/'.$locale.'/services/'.$slug) }}</loc>
                @foreach(['en' => $service->slug_en, 'bn' => $service->slug_bn] as $alternateLocale => $alternateSlug)
                    @if($alternateSlug)
        <xhtml:link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ url('/'.$alternateLocale.'/services/'.$alternateSlug) }}"/>
                    @endif
                @endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ url('/en/services/'.$service->slug_en) }}"/>
                @if($service->updated_at)
        <lastmod>{{ $service->updated_at->utc()->toAtomString() }}</lastmod>
                @endif
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
            @endif
        @endforeach
    @endforeach

    <!-- Products -->
    @foreach($products as $product)
        @foreach(['en' => $product->slug_en, 'bn' => $product->slug_bn] as $locale => $slug)
            @if($slug)
    <url>
        <loc>{{ url('/'.$locale.'/products/'.$slug) }}</loc>
                @foreach(['en' => $product->slug_en, 'bn' => $product->slug_bn] as $alternateLocale => $alternateSlug)
                    @if($alternateSlug)
        <xhtml:link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ url('/'.$alternateLocale.'/products/'.$alternateSlug) }}"/>
                    @endif
                @endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ url('/en/products/'.$product->slug_en) }}"/>
                @if($product->updated_at)
        <lastmod>{{ $product->updated_at->utc()->toAtomString() }}</lastmod>
                @endif
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
            @endif
        @endforeach
    @endforeach

    <!-- Portfolios -->
    @foreach($portfolios as $portfolio)
        @foreach(['en' => $portfolio->slug_en, 'bn' => $portfolio->slug_bn] as $locale => $slug)
            @if($slug)
    <url>
        <loc>{{ url('/'.$locale.'/portfolio/'.$slug) }}</loc>
                @foreach(['en' => $portfolio->slug_en, 'bn' => $portfolio->slug_bn] as $alternateLocale => $alternateSlug)
                    @if($alternateSlug)
        <xhtml:link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ url('/'.$alternateLocale.'/portfolio/'.$alternateSlug) }}"/>
                    @endif
                @endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ url('/en/portfolio/'.$portfolio->slug_en) }}"/>
                @if($portfolio->updated_at)
        <lastmod>{{ $portfolio->updated_at->utc()->toAtomString() }}</lastmod>
                @endif
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
            @endif
        @endforeach
    @endforeach

    <!-- Posts -->
    @foreach($posts as $post)
        @foreach(['en' => $post->slug_en, 'bn' => $post->slug_bn] as $locale => $slug)
            @if($slug)
    <url>
        <loc>{{ url('/'.$locale.'/blog/'.$slug) }}</loc>
                @foreach(['en' => $post->slug_en, 'bn' => $post->slug_bn] as $alternateLocale => $alternateSlug)
                    @if($alternateSlug)
        <xhtml:link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ url('/'.$alternateLocale.'/blog/'.$alternateSlug) }}"/>
                    @endif
                @endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ url('/en/blog/'.$post->slug_en) }}"/>
                @if($post->updated_at)
        <lastmod>{{ $post->updated_at->utc()->toAtomString() }}</lastmod>
                @endif
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
            @endif
        @endforeach
    @endforeach
</urlset>

