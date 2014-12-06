{{ '<'.'?'.'xml version="1.0" encoding="UTF-8"?>' }}
<urlset
  xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xmlns:xhtml="http://www.w3.org/1999/xhtml"
  xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
@foreach($items as $item)
  <url>
    <loc>{{ $item['loc'] }}</loc>
<?php

if (!empty($item['translation'])) {
  foreach ($item['translation'] as $translation) {
    echo "\t\t" . '<xhtml:link rel="alternate" hreflang="' . $translation['language'] . '" href="' . htmlentities($translation['url'], ENT_XML1) . '" />' . "\n";
  }
}

if ($item['priority'] !== null) {
  echo "\t\t" . '<priority>' . $item['priority'] . '</priority>' . "\n";
}

if ($item['lastmod'] !== null) {
  echo "\t\t" . '<lastmod>' . date('Y-m-d\TH:i:sP', strtotime($item['lastmod'])) . '</lastmod>' . "\n";
}

if ($item['freq'] !== null) {
  echo "\t\t" . '<changefreq>' . $item['freq'] . '</changefreq>' . "\n";
}

if (!empty($item['image'])) {
  foreach($item['image'] as $image) {
    echo "\t\t" . '<image:image>' . "\n";
    echo "\t\t\t" . '<image:loc>' . htmlentities($image['url'], ENT_XML1) . '</image:loc>' . "\n";
    if (isset($image['title'])) echo "\t\t\t" . '<image:title>' . htmlentities($image['title'], ENT_XML1) . '</image:title>' . "\n";
    echo "\t\t\t" . '<image:caption>' . htmlentities($image['caption'], ENT_XML1) . '</image:caption>' . "\n";
    if (isset($image['geo_location'])) echo "\t\t\t" . '<image:geo_location>' . htmlentities($image['geo_location'], ENT_XML1) . '</image:geo_location>' . "\n";
    echo "\t\t" . '</image:image>' . "\n";
  }
}

?>
  </url>
@endforeach
</urlset>
