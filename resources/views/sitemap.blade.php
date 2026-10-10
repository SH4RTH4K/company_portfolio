<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
    <!-- Static Pages -->
    <url>
        <loc>{{ url('/en') }}</loc>
        <xhtml:link rel="alternate" hreflang="bn" href="{{ url('/bn') }}"/>
        <xhtml:link rel="alternate" hreflang="en" href="{{ url('/en') }}"/>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ url('/en/contact') }}</loc>
        <xhtml:link rel="alternate" hreflang="bn" href="{{ url('/bn/contact') }}"/>
        <xhtml:link rel="alternate" hreflang="en" href="{{ url('/en/contact') }}"/>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- Services -->
    @foreach($services as $service)
    <url>
        <loc>{{ url('/en/services/' . $service->slug_en) }}</loc>
        @if($service->slug_bn)
        <xhtml:link rel="alternate" hreflang="bn" href="{{ url('/bn/services/' . $service->slug_bn) }}"/>
        @endif
        <xhtml:link rel="alternate" hreflang="en" href="{{ url('/en/services/' . $service->slug_en) }}"/>
        <lastmod>{{ $service->updated_at->tz('UTC')->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    @endforeach

    <!-- Products -->
    @foreach($products as $product)
    <url>
        <loc>{{ url('/en/products/' . $product->slug_en) }}</loc>
        @if($product->slug_bn)
        <xhtml:link rel="alternate" hreflang="bn" href="{{ url('/bn/products/' . $product->slug_bn) }}"/>
        @endif
        <xhtml:link rel="alternate" hreflang="en" href="{{ url('/en/products/' . $product->slug_en) }}"/>
        <lastmod>{{ $product->updated_at->tz('UTC')->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach

    <!-- Posts -->
    @foreach($posts as $post)
    <url>
        <loc>{{ url('/en/blog/' . $post->slug_en) }}</loc>
        @if($post->slug_bn)
        <xhtml:link rel="alternate" hreflang="bn" href="{{ url('/bn/blog/' . $post->slug_bn) }}"/>
        @endif
        <xhtml:link rel="alternate" hreflang="en" href="{{ url('/en/blog/' . $post->slug_en) }}"/>
        <lastmod>{{ $post->updated_at->tz('UTC')->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach
</urlset>

