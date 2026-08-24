<?= '<'.'?'.'xml version="1.0" encoding="UTF-8"?>'."\n" ?>
<?php if (null != $style) {
    echo '<'.'?'.'xml-stylesheet href="'.$style.'" type="text/xsl"?>'."\n";
}

$escapeXml = static function (mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8', false);
};

$escapeList = static function (mixed $value) use ($escapeXml): string {
    $items = is_array($value) ? $value : [$value];

    return implode(',', array_map($escapeXml, $items));
};
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
<?php foreach ($items as $item) : ?>
	<url>
	<loc><?= $escapeXml($item['loc'] ?? '') ?></loc>
	<?php
    if ($item['lastmod'] !== null) {
        echo '<lastmod>'.date('Y-m-d\TH:i:sP', strtotime($item['lastmod'])).'</lastmod>'."\n";
    }
    ?>
	<?php
    if (! empty($item['alternates'])) {
        foreach ($item['alternates'] as $alternate) {
            echo '<xhtml:link rel="alternate" media="'.$escapeXml($alternate['media'] ?? '').'" href="'.$escapeXml($alternate['url'] ?? '').'" />'."\n";
        }
    }
    ?>
	<news:news>
		<news:publication>
		<news:name><?= $escapeXml($item['googlenews']['sitename'] ?? '') ?></news:name>
		<news:language><?= $escapeXml($item['googlenews']['language'] ?? '')  ?></news:language>
		</news:publication>
		<news:publication_date><?= date('Y-m-d\TH:i:sP', strtotime($item['googlenews']['publication_date'])) ?></news:publication_date>
		<news:title><?= $escapeXml($item['title'] ?? '') ?></news:title>
	<?php
    if (isset($item['googlenews']['access'])) {
        echo "\t\t".'<news:access>'.$escapeXml($item['googlenews']['access']).'</news:access>'."\n";
    }

    if (isset($item['googlenews']['keywords'])) {
        echo "\t\t".'<news:keywords>'.$escapeList($item['googlenews']['keywords']).'</news:keywords>'."\n";
    }

    if (isset($item['googlenews']['genres'])) {
        echo "\t\t".'<news:genres>'.$escapeList($item['googlenews']['genres']).'</news:genres>'."\n";
    }

    if (isset($item['googlenews']['stock_tickers'])) {
        echo "\t\t".'<news:stock_tickers>'.$escapeList($item['googlenews']['stock_tickers']).'</news:stock_tickers>'."\n";
    }
    ?>
	</news:news>
	</url>
<?php endforeach; ?>
</urlset>
