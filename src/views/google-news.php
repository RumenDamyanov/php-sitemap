<?= '<'.'?'.'xml version="1.0" encoding="UTF-8"?>'."\n" ?>
<?php if ($style != null) echo '<'.'?'.'xml-stylesheet href="'.$style.'" type="text/xsl"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
<?php foreach($items as $item) : ?>
  <url>
    <loc><?= $item['loc'] ?></loc>
    <news:news>
      <news:publication>
        <news:name><?= $item['googlenews']['sitename'] ?></news:name>
        <news:language><?= $item['googlenews']['language']  ?></news:language>
      </news:publication>
      <news:publication_date><?= date('Y-m-d\TH:i:sP', strtotime($item['googlenews']['publication_date'])) ?></news:publication_date>
      <news:title><?= $item['title'] ?></news:title>
  <?php
  if (isset($item['googlenews']['access'])) {
    echo "\t\t" . '<news:access>' . $item['googlenews']['access'] . '</news:access>' . "\n";
  }

  if (isset($item['googlenews']['keywords'])) {
    echo "\t\t" . '<news:keywords>' . implode(',', $item['googlenews']['keywords']) . '</news:keywords>' . "\n";
  }

  if (isset($item['googlenews']['genres'])) {
    echo "\t\t" . '<news:genres>' . implode(',', $item['googlenews']['genres']) . '</news:genres>' . "\n";
  }

  if (isset($item['googlenews']['stock_tickers'])) {
    echo "\t\t" . '<news:stock_tickers>' . implode(',', $item['googlenews']['stock_tickers']) . '</news:stock_tickers>' . "\n";
  }
  ?>
    </news:news>
  </url>
<?php endforeach; ?>
</urlset>
