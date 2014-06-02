{{ '<'.'?'.'xml version="1.0" encoding="UTF-8"?>'."\n" }}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($sitemaps as $sitemap)
	<sitemap>
		<loc>{{ $sitemap['loc'] }}</loc>
		@if($sitemap['lastmod'] !== null)<lastmod>{{ date('Y-m-d\TH:i:sP', strtotime($sitemap['lastmod'])) }}</lastmod>@endif
	</sitemap>
@endforeach
</sitemapindex>