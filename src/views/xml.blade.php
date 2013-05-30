{{ '<?xml version="1.0" encoding="UTF-8"?>'."\n" }}
<urlset
      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
@foreach($items as $item)
    <url>
        <loc>{{ $item['loc'] }}</loc>
        <priority>{{ $item['priority'] }}</priority>
        <lastmod>{{ date('Y-m-d\TH:i:sP', strtotime($item['lastmod'])) }}</lastmod>
        <changefreq>{{ $item['freq'] }}</changefreq>
    </url>
@endforeach
</urlset>