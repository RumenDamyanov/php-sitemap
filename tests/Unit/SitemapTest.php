<?php
/**
 * Unit tests for the Sitemap class, including item addition and XML rendering.
 */

test('Sitemap class can be instantiated', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    expect($sitemap)->toBeInstanceOf(\Rumenx\Sitemap\Sitemap::class);
});

test('Sitemap can add a single item', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->add('https://example.com/', '2024-01-01', '1.0', 'daily');
    $items = $sitemap->getModel()->getItems();
    expect($items[0]['loc'])->toBe('https://example.com/');
});

test('Sitemap can add multiple items at once', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->addItem([
        [ 'loc' => '/foo', 'title' => 'Foo' ],
        [ 'loc' => '/bar', 'title' => 'Bar' ]
    ]);
    $items = $sitemap->getModel()->getItems();
    expect($items[0]['loc'])->toBe('/foo');
    expect($items[1]['loc'])->toBe('/bar');
});

test('Sitemap can add and reset sitemaps', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->addSitemap('/sitemap1.xml');
    $sitemap->addSitemap('/sitemap2.xml');
    expect($sitemap->getModel()->getSitemaps())->toBe([
        ['loc'=>'/sitemap1.xml','lastmod'=>null],
        ['loc'=>'/sitemap2.xml','lastmod'=>null]
    ]);
    $sitemap->resetSitemaps([
        ['loc'=>'/reset.xml','lastmod'=>null]
    ]);
    expect($sitemap->getModel()->getSitemaps())->toBe([
        ['loc'=>'/reset.xml','lastmod'=>null]
    ]);
});

test('Sitemap escaping works for special characters', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap(['escaping'=>true]);
    $sitemap->add('<tag>', null, null, null, [], 'Title & More');
    $item = $sitemap->getModel()->getItems()[0];
    expect($item['loc'])->toBe('&lt;tag&gt;');
    expect($item['title'])->toBe('Title &amp; More');
});

test('Sitemap addItem handles empty and null values', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->addItem([]);
    $item = $sitemap->getModel()->getItems()[0];
    expect($item['loc'])->toBe('/');
});

test('Sitemap can be constructed with a Model instance', function () {
    $model = new \Rumenx\Sitemap\Model();
    $sitemap = new \Rumenx\Sitemap\Sitemap($model);
    expect($sitemap->getModel())->toBe($model);
});

test('Sitemap escapes nested array values in images, translations, and alternates', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap(['escaping' => true]);
    $sitemap->addItem([
        'images' => [
            ['url' => '<img&>', 'title' => 'T<>&']
        ],
        'translations' => [
            ['language' => 'en', 'url' => '<en&>']
        ],
        'alternates' => [
            ['media' => 'print', 'url' => '<print&>']
        ],
    ]);
    $item = $sitemap->getModel()->getItems()[0];
    expect($item['images'][0]['url'])->toBe(htmlentities('<img&>', ENT_XML1));
    expect($item['images'][0]['title'])->toBe(htmlentities('T<>&', ENT_XML1));
    expect($item['translations'][0]['url'])->toBe(htmlentities('<en&>', ENT_XML1));
    expect($item['alternates'][0]['url'])->toBe(htmlentities('<print&>', ENT_XML1));
});

test('Sitemap escapes video title and description when escaping is enabled', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap(['escaping' => true]);
    $sitemap->addItem([
        'videos' => [
            ['title' => 'V<>&', 'description' => 'D<>&'],
            ['title' => '', 'description' => ''], // Should not trigger escaping
        ],
    ]);
    $item = $sitemap->getModel()->getItems()[0];
    expect($item['videos'][0]['title'])->toBe(htmlentities('V<>&', ENT_XML1));
    expect($item['videos'][0]['description'])->toBe(htmlentities('D<>&', ENT_XML1));
    expect($item['videos'][1]['title'])->toBe('');
    expect($item['videos'][1]['description'])->toBe('');
});

test('Sitemap escapes googlenews sitename when escaping is enabled', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap(['escaping' => true]);
    $sitemap->addItem([
        'googlenews' => [
            'sitename' => 'Site<>&',
            'language' => 'en',
            'publication_date' => '2025-06-08T12:00:00+00:00',
        ],
    ]);
    $item = $sitemap->getModel()->getItems()[0];
    expect($item['googlenews']['sitename'])->toBe(htmlentities('Site<>&', ENT_XML1));
});

test('Sitemap renderXml includes title when present', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->addItem([
        'loc' => 'https://example.com',
        'title' => 'My Title',
    ]);
    $xml = $sitemap->renderXml();
    expect($xml)->toContain('<title>My Title</title>');
});
