<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($staticUrls as $url)
    <url>
        <loc>{{ $url }}</loc>
        <changefreq>daily</changefreq>
    </url>
@endforeach
@foreach ($cars as $car)
    <url>
        <loc>{{ route('cars.show', $car) }}</loc>
        <lastmod>{{ $car->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
    </url>
@endforeach
</urlset>
