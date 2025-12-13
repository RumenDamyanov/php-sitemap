<?php

/**
 * Feature test: Ensure sitemap can be generated and rendered in XML format.
 */

test('sitemap can be generated and rendered in XML', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
    $xml = $sitemap->renderXml();
    expect($xml)->toContain('<?xml');
    expect($xml)->toContain('<urlset');
});
