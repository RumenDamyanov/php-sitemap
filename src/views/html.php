<?php
$escapeHtml = static function (mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8', false);
};

$httpHref = static function (mixed $value) use ($escapeHtml): string {
    $decoded = html_entity_decode((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    if (preg_match('#\Ahttps?://#i', trim($decoded)) !== 1) {
        return '#';
    }

    return $escapeHtml($value);
};
?>
<!DOCTYPE html>
<html>
<head>
	<title><?= $escapeHtml($channel['title']) ?></title>
</head>
<body>
	<h1><a href="<?= $httpHref($channel['link']) ?>"><?= $escapeHtml($channel['title']) ?></a></h1>
	<ul>
		<?php foreach ($items as $item) : ?>
		<li>
			<a href="<?= $httpHref($item['loc']) ?>"><?= $escapeHtml((empty($item['title'])) ? $item['loc'] : $item['title']) ?></a>
			<small>(last updated: <?= date('Y-m-d\TH:i:sP', strtotime((string) $item['lastmod'])) ?>)</small>
		</li>
		<?php endforeach; ?>
	</ul>
</body>
</html>
