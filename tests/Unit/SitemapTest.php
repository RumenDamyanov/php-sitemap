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
        ['loc' => '/sitemap1.xml','lastmod' => null],
        ['loc' => '/sitemap2.xml','lastmod' => null]
    ]);
    $sitemap->resetSitemaps([
        ['loc' => '/reset.xml','lastmod' => null]
    ]);
    expect($sitemap->getModel()->getSitemaps())->toBe([
        ['loc' => '/reset.xml','lastmod' => null]
    ]);
});

test('Sitemap escaping works for special characters', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap(['escaping' => true]);
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

test('Sitemap implements SitemapInterface', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    expect($sitemap)->toBeInstanceOf(\Rumenx\Sitemap\Interfaces\SitemapInterface::class);
});

test('Sitemap render() method works with xml format', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
    $xml = $sitemap->render('xml');
    expect($xml)->toContain('<loc>https://example.com/</loc>');
    expect($xml)->toContain('<urlset');
});

test('Sitemap render() throws exception for unsupported format', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
    $sitemap->render('json');
})->throws(\InvalidArgumentException::class, 'Unsupported format');

test('Sitemap generate() is alias for render()', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
    $rendered = $sitemap->render('xml');
    $generated = $sitemap->generate('xml');
    expect($generated)->toBe($rendered);
});

test('Sitemap store() creates file successfully', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->add('https://example.com/', date('c'), '1.0', 'daily');

    $tempDir = sys_get_temp_dir() . '/php-sitemap-test-' . uniqid();
    mkdir($tempDir, 0755, true);

    $result = $sitemap->store('xml', 'test-sitemap', $tempDir);

    expect($result)->toBeTrue();
    expect(file_exists($tempDir . '/test-sitemap.xml'))->toBeTrue();

    $content = file_get_contents($tempDir . '/test-sitemap.xml');
    expect($content)->toContain('<loc>https://example.com/</loc>');

    // Cleanup
    unlink($tempDir . '/test-sitemap.xml');
    rmdir($tempDir);
});

test('Sitemap store() creates nested directories if needed', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->add('https://example.com/', date('c'), '1.0', 'daily');

    $tempDir = sys_get_temp_dir() . '/php-sitemap-test-' . uniqid() . '/nested/dir';

    $result = $sitemap->store('xml', 'sitemap', $tempDir);

    expect($result)->toBeTrue();
    expect(file_exists($tempDir . '/sitemap.xml'))->toBeTrue();

    // Cleanup
    unlink($tempDir . '/sitemap.xml');
    rmdir($tempDir);
    rmdir(dirname($tempDir));
    rmdir(dirname(dirname($tempDir)));
});

test('Model setEscaping() changes escaping mode', function () {
    $model = new \Rumenx\Sitemap\Model(['escaping' => true]);
    expect($model->getEscaping())->toBeTrue();

    $model->setEscaping(false);
    expect($model->getEscaping())->toBeFalse();

    $model->setEscaping(true);
    expect($model->getEscaping())->toBeTrue();
});

test('Sitemap add() returns self for method chaining', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $result = $sitemap->add('https://example.com/', date('c'), '1.0', 'daily');

    expect($result)->toBe($sitemap);
    expect($result)->toBeInstanceOf(\Rumenx\Sitemap\Sitemap::class);
});

test('Sitemap addItem() returns self for method chaining', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $result = $sitemap->addItem(['loc' => 'https://example.com/']);

    expect($result)->toBe($sitemap);
});

test('Sitemap addSitemap() returns self for method chaining', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $result = $sitemap->addSitemap('https://example.com/sitemap.xml', date('c'));

    expect($result)->toBe($sitemap);
});

test('Sitemap resetSitemaps() returns self for method chaining', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $result = $sitemap->resetSitemaps();

    expect($result)->toBe($sitemap);
});

test('Sitemap supports fluent method chaining with add()', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();

    $sitemap
        ->add('https://example.com/', date('c'), '1.0', 'daily')
        ->add('https://example.com/about', date('c'), '0.8', 'monthly')
        ->add('https://example.com/contact', date('c'), '0.6', 'yearly');

    $items = $sitemap->getModel()->getItems();
    expect($items)->toHaveCount(3);
    expect($items[0]['loc'])->toBe('https://example.com/');
    expect($items[1]['loc'])->toBe('https://example.com/about');
    expect($items[2]['loc'])->toBe('https://example.com/contact');
});

test('Sitemap supports fluent method chaining with addItem()', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();

    $sitemap
        ->addItem(['loc' => '/page1', 'priority' => '1.0'])
        ->addItem(['loc' => '/page2', 'priority' => '0.8'])
        ->addItem(['loc' => '/page3', 'priority' => '0.6']);

    $items = $sitemap->getModel()->getItems();
    expect($items)->toHaveCount(3);
    expect($items[0]['loc'])->toBe('/page1');
});

test('Sitemap supports fluent method chaining with addSitemap()', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();

    $sitemap
        ->addSitemap('https://example.com/sitemap1.xml', date('c'))
        ->addSitemap('https://example.com/sitemap2.xml', date('c'))
        ->addSitemap('https://example.com/sitemap3.xml', date('c'));

    $sitemaps = $sitemap->getModel()->getSitemaps();
    expect($sitemaps)->toHaveCount(3);
});

test('Sitemap supports mixed fluent method chaining', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();

    $sitemap
        ->add('https://example.com/', date('c'), '1.0', 'daily')
        ->addItem(['loc' => '/page1'])
        ->addSitemap('https://example.com/sitemap1.xml')
        ->add('https://example.com/about', date('c'), '0.8', 'monthly')
        ->addItem(['loc' => '/page2']);

    $items = $sitemap->getModel()->getItems();
    $sitemaps = $sitemap->getModel()->getSitemaps();

    expect($items)->toHaveCount(4);
    expect($sitemaps)->toHaveCount(1);
});

test('Sitemap fluent chaining works with batch addItem()', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();

    $result = $sitemap
        ->addItem([
            ['loc' => '/page1'],
            ['loc' => '/page2'],
        ])
        ->add('https://example.com/', date('c'), '1.0', 'daily');

    expect($result)->toBe($sitemap);
    expect($sitemap->getModel()->getItems())->toHaveCount(3);
});

// Config Integration Tests
test('Sitemap can be instantiated with SitemapConfig', function () {
    $config = new \Rumenx\Sitemap\Config\SitemapConfig(
        escaping: false,
        strictMode: true
    );

    $sitemap = new \Rumenx\Sitemap\Sitemap($config);

    expect($sitemap->getConfig())->toBe($config);
    expect($sitemap->getConfig()->isEscaping())->toBeFalse();
    expect($sitemap->getConfig()->isStrictMode())->toBeTrue();
});

test('Sitemap config can be set after instantiation', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $config = new \Rumenx\Sitemap\Config\SitemapConfig(strictMode: true);

    $result = $sitemap->setConfig($config);

    expect($result)->toBe($sitemap);
    expect($sitemap->getConfig())->toBe($config);
});

test('Sitemap config can be retrieved', function () {
    $config = new \Rumenx\Sitemap\Config\SitemapConfig();
    $sitemap = new \Rumenx\Sitemap\Sitemap($config);

    expect($sitemap->getConfig())->toBe($config);
});

// Validation Tests
test('Sitemap validates URL in strict mode', function () {
    $config = new \Rumenx\Sitemap\Config\SitemapConfig(strictMode: true);
    $sitemap = new \Rumenx\Sitemap\Sitemap($config);

    expect(fn() => $sitemap->add('not-a-valid-url'))
        ->toThrow(\InvalidArgumentException::class, 'Invalid URL format');
});

test('Sitemap validates priority in strict mode', function () {
    $config = new \Rumenx\Sitemap\Config\SitemapConfig(strictMode: true);
    $sitemap = new \Rumenx\Sitemap\Sitemap($config);

    expect(fn() => $sitemap->add('https://example.com', null, '2.0'))
        ->toThrow(\InvalidArgumentException::class, 'Priority must be between 0.0 and 1.0');
});

test('Sitemap validates frequency in strict mode', function () {
    $config = new \Rumenx\Sitemap\Config\SitemapConfig(strictMode: true);
    $sitemap = new \Rumenx\Sitemap\Sitemap($config);

    expect(fn() => $sitemap->add('https://example.com', null, null, 'sometimes'))
        ->toThrow(\InvalidArgumentException::class, 'Invalid frequency');
});

test('Sitemap validates lastmod in strict mode', function () {
    $config = new \Rumenx\Sitemap\Config\SitemapConfig(strictMode: true);
    $sitemap = new \Rumenx\Sitemap\Sitemap($config);

    expect(fn() => $sitemap->add('https://example.com', 'not-a-date'))
        ->toThrow(\InvalidArgumentException::class, 'Invalid date format');
});

test('Sitemap validates images in strict mode', function () {
    $config = new \Rumenx\Sitemap\Config\SitemapConfig(strictMode: true);
    $sitemap = new \Rumenx\Sitemap\Sitemap($config);

    expect(fn() => $sitemap->add(
        'https://example.com',
        null,
        null,
        null,
        [['url' => 'not-a-valid-url']]
    ))->toThrow(\InvalidArgumentException::class, 'Invalid URL format');
});

test('Sitemap does not validate when strict mode is off', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap(); // strict mode is off by default

    // These should not throw exceptions
    $sitemap->add('not-a-url'); // Invalid URL
    $sitemap->add('/', null, '5.0'); // Invalid priority
    $sitemap->add('/', null, null, 'sometimes'); // Invalid frequency

    expect($sitemap->getModel()->getItems())->toHaveCount(3);
});

test('Sitemap addItem validates in strict mode', function () {
    $config = new \Rumenx\Sitemap\Config\SitemapConfig(strictMode: true);
    $sitemap = new \Rumenx\Sitemap\Sitemap($config);

    expect(fn() => $sitemap->addItem([
        'loc' => 'not-a-valid-url',
        'priority' => '0.5',
    ]))->toThrow(\InvalidArgumentException::class, 'Invalid URL format');
});

test('Sitemap addSitemap validates URL in strict mode', function () {
    $config = new \Rumenx\Sitemap\Config\SitemapConfig(strictMode: true);
    $sitemap = new \Rumenx\Sitemap\Sitemap($config);

    expect(fn() => $sitemap->addSitemap('not-a-valid-url'))
        ->toThrow(\InvalidArgumentException::class, 'Invalid URL format');
});

test('Sitemap addSitemap validates lastmod in strict mode', function () {
    $config = new \Rumenx\Sitemap\Config\SitemapConfig(strictMode: true);
    $sitemap = new \Rumenx\Sitemap\Sitemap($config);

    expect(fn() => $sitemap->addSitemap('https://example.com/sitemap.xml', 'not-a-date'))
        ->toThrow(\InvalidArgumentException::class, 'Invalid date format');
});

test('Sitemap accepts valid data in strict mode', function () {
    $config = new \Rumenx\Sitemap\Config\SitemapConfig(strictMode: true);
    $sitemap = new \Rumenx\Sitemap\Sitemap($config);

    $sitemap->add(
        'https://example.com',
        '2023-12-01',
        '0.8',
        'daily',
        [['url' => 'https://example.com/image.jpg']]
    );

    expect($sitemap->getModel()->getItems())->toHaveCount(1);
    expect($sitemap->getModel()->getItems()[0]['loc'])->toBe('https://example.com');
});
