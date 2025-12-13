<?php

/**
 * Feature tests for rendering and validating all view templates and their edge cases.
 */

test('all view templates are readable and contain expected tags', function () {
    $views = [
        'google-news.php' => 'news',
        'html.php' => '<html',
        'ror-rdf.php' => 'rdf',
        'ror-rss.php' => 'rss',
        'sitemapindex.php' => 'sitemapindex',
        'txt.php' => '',
        'xml-mobile.php' => 'urlset',
        'xml.php' => 'urlset',
    ];
    foreach ($views as $file => $expected) {
        $content = file_get_contents(__DIR__ . '/../../src/views/' . $file);
        expect($content)->not()->toBeEmpty();
        if ($expected) {
            expect($content)->toContain($expected);
        }
    }
});

test('google-news view renders expected XML with full data', function () {
    $style = 'style.xsl';
    $items = [[
        'loc' => 'https://example.com/news1',
        'lastmod' => '2024-06-08T12:00:00+00:00',
        'alternates' => [
            ['media' => 'print', 'url' => 'https://example.com/news1-print'],
        ],
        'title' => 'News Title',
        'googlenews' => [
            'sitename' => 'Example News',
            'language' => 'en',
            'publication_date' => '2024-06-08T12:00:00+00:00',
            'access' => 'Subscription',
            'keywords' => ['php', 'sitemap'],
            'genres' => ['PressRelease'],
            'stock_tickers' => ['EXMPL:US'],
        ],
    ]];
    ob_start();
    include __DIR__ . '/../../src/views/google-news.php';
    $output = ob_get_clean();
    expect($output)->toContain('<?xml version="1.0" encoding="UTF-8"?>');
    expect($output)->toContain('<?xml-stylesheet href="style.xsl" type="text/xsl"?>');
    expect($output)->toContain('<loc>https://example.com/news1</loc>');
    expect($output)->toContain('<news:name>Example News</news:name>');
    expect($output)->toContain('<news:language>en</news:language>');
    expect($output)->toContain('<news:publication_date>2024-06-08T12:00:00+00:00</news:publication_date>');
    expect($output)->toContain('<news:title>News Title</news:title>');
    expect($output)->toContain('<news:access>Subscription</news:access>');
    expect($output)->toContain('<news:keywords>php,sitemap</news:keywords>');
    expect($output)->toContain('<news:genres>PressRelease</news:genres>');
    expect($output)->toContain('<news:stock_tickers>EXMPL:US</news:stock_tickers>');
});

test('xml view renders expected XML with images, videos, translations', function () {
    $style = null;
    $items = [[
        'loc' => 'https://example.com/page',
        'lastmod' => '2024-06-08T12:00:00+00:00',
        'priority' => '0.8',
        'freq' => 'weekly',
        'title' => 'Page Title',
        'images' => [
            ['url' => 'https://example.com/image.jpg', 'title' => 'Image Title'],
        ],
        'translations' => [
            ['language' => 'fr', 'url' => 'https://example.com/fr/page'],
        ],
        'videos' => [
            ['title' => 'Video Title', 'description' => 'Video Desc', 'thumbnail_loc' => 'https://example.com/thumb.jpg'],
        ],
        'alternates' => [
            ['media' => 'screen', 'url' => 'https://example.com/mobile'],
        ],
        'googlenews' => [
            'sitename' => 'Example News',
            'language' => 'en',
            'publication_date' => '2024-06-08T12:00:00+00:00',
        ],
    ]];
    ob_start();
    include __DIR__ . '/../../src/views/xml.php';
    $output = ob_get_clean();
    expect($output)->toContain('<loc>https://example.com/page</loc>');
    expect($output)->toContain('<image:loc>https://example.com/image.jpg</image:loc>');
    expect($output)->toContain('<image:title>Image Title</image:title>');
    expect($output)->toContain('<xhtml:link rel="alternate" hreflang="fr" href="https://example.com/fr/page" />');
    expect($output)->toContain('<video:title><![CDATA[Video Title]]></video:title>');
    expect($output)->toContain('<video:description><![CDATA[Video Desc]]></video:description>');
    expect($output)->toContain('<video:thumbnail_loc>https://example.com/thumb.jpg</video:thumbnail_loc>');
    expect($output)->toContain('<xhtml:link rel="alternate" media="screen" href="https://example.com/mobile" />');
});

test('sitemapindex view renders expected XML with sitemaps', function () {
    $style = null;
    $sitemaps = [
        ['loc' => 'https://example.com/sitemap1.xml', 'lastmod' => '2024-06-08T12:00:00+00:00'],
        ['loc' => 'https://example.com/sitemap2.xml', 'lastmod' => null],
    ];
    ob_start();
    include __DIR__ . '/../../src/views/sitemapindex.php';
    $output = ob_get_clean();
    expect($output)->toContain('<loc>https://example.com/sitemap1.xml</loc>');
    expect($output)->toContain('<lastmod>2024-06-08T12:00:00+00:00</lastmod>');
    expect($output)->toContain('<loc>https://example.com/sitemap2.xml</loc>');
});

test('xml-mobile view renders expected XML for mobile', function () {
    $style = null;
    $items = [
        ['loc' => 'https://example.com/mobile'],
        ['loc' => 'https://example.com/other-mobile'],
    ];
    ob_start();
    include __DIR__ . '/../../src/views/xml-mobile.php';
    $output = ob_get_clean();
    expect($output)->toContain('<loc>https://example.com/mobile</loc>');
    expect($output)->toContain('<loc>https://example.com/other-mobile</loc>');
    expect($output)->toContain('<mobile:mobile/>');
});

test('txt view renders plain text URLs', function () {
    $items = [
        ['loc' => 'https://example.com/1'],
        ['loc' => 'https://example.com/2'],
    ];
    ob_start();
    include __DIR__ . '/../../src/views/txt.php';
    $output = ob_get_clean();
    expect($output)->toContain('https://example.com/1');
    expect($output)->toContain('https://example.com/2');
});

test('html view renders expected HTML with items', function () {
    $items = [
        ['loc' => 'https://example.com/1', 'title' => 'Title 1', 'lastmod' => '2024-06-08T12:00:00+00:00'],
        ['loc' => 'https://example.com/2', 'title' => '', 'lastmod' => '2024-06-08T12:00:00+00:00'],
    ];
    $channel = ['title' => 'My Sitemap', 'link' => 'https://example.com'];
    ob_start();
    include __DIR__ . '/../../src/views/html.php';
    $output = ob_get_clean();
    expect($output)->toContain('<title>My Sitemap</title>');
    expect($output)->toContain('<a href="https://example.com/1">Title 1</a>');
    expect($output)->toContain('<a href="https://example.com/2">https://example.com/2</a>');
    expect($output)->toContain('last updated: 2024-06-08T12:00:00+00:00');
});

test('ror-rss view renders expected RSS XML', function () {
    $items = [
        [
            'loc' => 'https://example.com/1',
            'title' => 'Title 1',
            'lastmod' => '2024-06-08T12:00:00+00:00',
            'freq' => 'daily',
            'priority' => '1.0',
        ],
    ];
    $channel = ['title' => 'ROR Channel', 'link' => 'https://example.com'];
    ob_start();
    include __DIR__ . '/../../src/views/ror-rss.php';
    $output = ob_get_clean();
    expect($output)->toContain('<title>ROR Channel</title>');
    expect($output)->toContain('<link>https://example.com</link>');
    expect($output)->toContain('<item>');
    expect($output)->toContain('<link>https://example.com/1</link>');
    expect($output)->toContain('<title>Title 1</title>');
    expect($output)->toContain('<ror:updated>2024-06-08T12:00:00+00:00</ror:updated>');
    expect($output)->toContain('<ror:updatePeriod>daily</ror:updatePeriod>');
    expect($output)->toContain('<ror:sortOrder>1.0</ror:sortOrder>');
    expect($output)->toContain('<ror:resourceOf>sitemap</ror:resourceOf>');
});

test('ror-rdf view renders expected RDF XML', function () {
    $items = [
        [
            'loc' => 'https://example.com/1',
            'title' => 'Title 1',
            'lastmod' => '2024-06-08T12:00:00+00:00',
            'freq' => 'daily',
            'priority' => '1.0',
        ],
    ];
    $channel = ['title' => 'ROR Channel', 'link' => 'https://example.com'];
    ob_start();
    include __DIR__ . '/../../src/views/ror-rdf.php';
    $output = ob_get_clean();
    expect($output)->toContain('<title>ROR Channel</title>');
    expect($output)->toContain('<url>https://example.com</url>');
    expect($output)->toContain('<type>sitemap</type>');
    expect($output)->toContain('<Resource>');
    expect($output)->toContain('<url>https://example.com/1</url>');
    expect($output)->toContain('<title>Title 1</title>');
    expect($output)->toContain('<updated>2024-06-08T12:00:00+00:00</updated>');
    expect($output)->toContain('<updatePeriod>daily</updatePeriod>');
    expect($output)->toContain('<sortOrder>1.0</sortOrder>');
    expect($output)->toContain('<resourceOf rdf:resource="sitemap"/>');
});

test('sitemapindex view renders with and without style and with/without lastmod', function () {
    // With style and lastmod
    $style = 'style.xsl';
    $sitemaps = [
        ['loc' => 'https://example.com/sitemap1.xml', 'lastmod' => '2024-06-08T12:00:00+00:00'],
        ['loc' => 'https://example.com/sitemap2.xml', 'lastmod' => null],
    ];
    ob_start();
    include __DIR__ . '/../../src/views/sitemapindex.php';
    $output = ob_get_clean();
    expect($output)->toContain('<?xml-stylesheet href="style.xsl" type="text/xsl"?>');
    expect($output)->toContain('<loc>https://example.com/sitemap1.xml</loc>');
    expect($output)->toContain('<lastmod>2024-06-08T12:00:00+00:00</lastmod>');
    expect($output)->toContain('<loc>https://example.com/sitemap2.xml</loc>');
    // Without style
    $style = null;
    ob_start();
    include __DIR__ . '/../../src/views/sitemapindex.php';
    $output = ob_get_clean();
    expect($output)->not()->toContain('<?xml-stylesheet');
});

test('xml view covers all branches (no images, no videos, no translations, no alternates, no googlenews)', function () {
    $style = null;
    $items = [[
        'loc' => 'https://example.com/page',
        'lastmod' => null,
        'priority' => null,
        'freq' => null,
        'title' => null,
        'images' => [],
        'translations' => [],
        'videos' => [],
        'alternates' => [],
        'googlenews' => [],
    ]];
    ob_start();
    include __DIR__ . '/../../src/views/xml.php';
    $output = ob_get_clean();
    expect($output)->toContain('<loc>https://example.com/page</loc>');
    // Should not contain image, video, translation, alternate, googlenews tags
    expect($output)->not()->toContain('<image:');
    expect($output)->not()->toContain('<video:');
    expect($output)->not()->toContain('<xhtml:link');
    expect($output)->not()->toContain('<news:');
});

test('xml-mobile view covers empty items', function () {
    $style = null;
    $items = [];
    ob_start();
    include __DIR__ . '/../../src/views/xml-mobile.php';
    $output = ob_get_clean();
    expect($output)->toContain('<urlset');
    // Should not contain <url> if items is empty
    expect($output)->not()->toContain('<url>');
});

test('xml view covers edge cases with various content types', function () {
    $style = null;
    $items = [
        // Only images
        [
            'loc' => 'https://example.com/img',
            'images' => [
                ['url' => 'https://example.com/image1.jpg'],
                ['url' => 'https://example.com/image2.jpg', 'title' => null],
            ],
        ],
        // Only videos
        [
            'loc' => 'https://example.com/vid',
            'videos' => [
                ['title' => 'V1', 'description' => null],
                ['title' => null, 'description' => 'Desc'],
            ],
        ],
        // Only translations
        [
            'loc' => 'https://example.com/tr',
            'translations' => [
                ['language' => 'es', 'url' => 'https://example.com/es/tr'],
            ],
        ],
        // Only alternates
        [
            'loc' => 'https://example.com/alt',
            'alternates' => [
                ['media' => 'print', 'url' => 'https://example.com/alt-print'],
            ],
        ],
        // Only googlenews
        [
            'loc' => 'https://example.com/news',
            'googlenews' => [
                'sitename' => 'NewsSite',
                'language' => 'bg',
                'publication_date' => '2025-06-08T12:00:00+00:00',
            ],
        ],
        // Special chars
        [
            'loc' => '<tag>&',
            'title' => 'Title & <Test>',
        ],
    ];
    ob_start();
    include __DIR__ . '/../../src/views/xml.php';
    $output = ob_get_clean();
    expect($output)->toContain('<image:loc>https://example.com/image1.jpg</image:loc>');
    expect($output)->toContain('<image:loc>https://example.com/image2.jpg</image:loc>');
    expect($output)->toContain('<video:title><![CDATA[V1]]></video:title>');
    expect($output)->toContain('<video:description><![CDATA[Desc]]></video:description>');
    expect($output)->toContain('<xhtml:link rel="alternate" hreflang="es" href="https://example.com/es/tr" />');
    expect($output)->toContain('<xhtml:link rel="alternate" media="print" href="https://example.com/alt-print" />');
    // Remove news expectations for xml.php, as it does not output <news:...> tags
    // expect($output)->toContain('<news:name>NewsSite</news:name>');
    // expect($output)->toContain('<news:language>bg</news:language>');
    // expect($output)->toContain('<news:publication_date>2025-06-08T12:00:00+00:00</news:publication_date>');
    expect($output)->toContain('<loc>&lt;tag&gt;&amp;</loc>');
});

test('xml-mobile view covers missing loc and special chars', function () {
    $style = null;
    $items = [
        [],
        ['loc' => '<tag>&'],
    ];
    ob_start();
    include __DIR__ . '/../../src/views/xml-mobile.php';
    $output = ob_get_clean();
    // Should not error on missing loc, but not output <loc> for it
    expect($output)->toContain('<loc>&lt;tag&gt;&amp;</loc>');
});

test('xml-mobile view includes xml-stylesheet when style is set', function () {
    $style = 'style.xsl';
    $items = [
        ['loc' => 'https://example.com/mobile'],
    ];
    ob_start();
    include __DIR__ . '/../../src/views/xml-mobile.php';
    $output = ob_get_clean();
    expect($output)->toContain('<?xml-stylesheet href="style.xsl" type="text/xsl"?>');
});

test('xml view includes xml-stylesheet when style is set', function () {
    $style = 'style.xsl';
    $items = [
        [
            'loc' => 'https://example.com/page',
            'priority' => null,
            'lastmod' => null,
            'freq' => null,
            'title' => null,
        ],
    ];
    ob_start();
    include __DIR__ . '/../../src/views/xml.php';
    $output = ob_get_clean();
    expect($output)->toContain('<?xml-stylesheet href="style.xsl" type="text/xsl"?>');
});

test('xml view covers image caption, geo_location, and license attributes', function () {
    $style = null;
    $items = [[
        'loc' => 'https://example.com/page',
        'priority' => null,
        'lastmod' => null,
        'freq' => null,
        'title' => null,
        'images' => [[
            'url' => 'https://example.com/image.jpg',
            'caption' => 'A caption',
            'geo_location' => 'Sofia, Bulgaria',
            'license' => 'https://example.com/license',
        ]],
    ]];
    ob_start();
    include __DIR__ . '/../../src/views/xml.php';
    $output = ob_get_clean();
    expect($output)->toContain('<image:caption>A caption</image:caption>');
    expect($output)->toContain('<image:geo_location>Sofia, Bulgaria</image:geo_location>');
    expect($output)->toContain('<image:license>https://example.com/license</image:license>');
});

test('xml view covers all video attributes', function () {
    $style = null;
    $items = [[
        'loc' => 'https://example.com/page',
        'priority' => null,
        'lastmod' => null,
        'freq' => null,
        'title' => null,
        'videos' => [[
            'thumbnail_loc' => 'https://example.com/thumb.jpg',
            'title' => 'Video Title',
            'description' => 'Video Desc',
            'content_loc' => 'https://example.com/video.mp4',
            'duration' => '120',
            'expiration_date' => '2025-06-08T12:00:00+00:00',
            'rating' => '4.5',
            'view_count' => '1000',
            'publication_date' => '2025-06-08T12:00:00+00:00',
            'family_friendly' => 'yes',
            'requires_subscription' => 'no',
            'live' => 'no',
            'player_loc' => [
                'allow_embed' => 'yes',
                'autoplay' => 'ap=1',
                'player_loc' => 'https://example.com/player.swf',
            ],
            'restriction' => [
                'relationship' => 'allow',
                'restriction' => 'US GB',
            ],
            'gallery_loc' => [
                'title' => 'Gallery Title',
                'gallery_loc' => 'https://example.com/gallery',
            ],
            'price' => [
                'currency' => 'USD',
                'price' => '1.99',
            ],
            'uploader' => [
                'info' => 'https://example.com/uploader',
                'uploader' => 'Uploader Name',
            ],
        ]],
    ]];
    ob_start();
    include __DIR__ . '/../../src/views/xml.php';
    $output = ob_get_clean();
    expect($output)->toContain('<video:thumbnail_loc>https://example.com/thumb.jpg</video:thumbnail_loc>');
    expect($output)->toContain('<video:title><![CDATA[Video Title]]></video:title>');
    expect($output)->toContain('<video:description><![CDATA[Video Desc]]></video:description>');
    expect($output)->toContain('<video:content_loc>https://example.com/video.mp4</video:content_loc>');
    expect($output)->toContain('<video:duration>120</video:duration>');
    expect($output)->toContain('<video:expiration_date>2025-06-08T12:00:00+00:00</video:expiration_date>');
    expect($output)->toContain('<video:rating>4.5</video:rating>');
    expect($output)->toContain('<video:view_count>1000</video:view_count>');
    expect($output)->toContain('<video:publication_date>2025-06-08T12:00:00+00:00</video:publication_date>');
    expect($output)->toContain('<video:family_friendly>yes</video:family_friendly>');
    expect($output)->toContain('<video:requires_subscription>no</video:requires_subscription>');
    expect($output)->toContain('<video:live>no</video:live>');
    expect($output)->toContain('<video:player_loc allow_embed="yes" autoplay="ap=1">https://example.com/player.swf</video:player_loc>');
    expect($output)->toContain('<video:restriction relationship="allow">US GB</video:restriction>');
    expect($output)->toContain('<video:gallery_loc title="Gallery Title">https://example.com/gallery</video:gallery_loc>');
    expect($output)->toContain('<video:price currency="USD">1.99</video:price>');
    expect($output)->toContain('<video:uploader info="https://example.com/uploader">Uploader Name</video:uploader>');
});
