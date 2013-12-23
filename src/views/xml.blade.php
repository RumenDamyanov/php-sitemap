{{ '<?xml version="1.0" encoding="UTF-8"?>'."\n" }}
<urlset
      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
@foreach($items as $item)
    <url>
        <loc>{{ $item['loc'] }}</loc>
        @if($item['priority'] !== null)<priority>{{ $item['priority'] }}</priority>@endif
        @if($item['lastmod'] !== null)<lastmod>{{ date('Y-m-d\TH:i:sP', strtotime($item['lastmod'])) }}</lastmod>@endif
        @if($item['freq'] !== null)<changefreq>{{ $item['freq'] }}</changefreq>@endif
        @if(!empty($item['image']))
        <image:image>
          @foreach($item['image'] as $image)
            <image:loc>{{ $image['url'] }}</image:loc>
            <image:caption>{{ $image['caption'] }}</image:caption>
          @endforeach
        </image:image>
        @endif
    </url>
@endforeach
</urlset>
