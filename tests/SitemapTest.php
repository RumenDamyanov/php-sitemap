<?php

class SitemapTest extends PHPUnit_Framework_TestCase
{
    protected $sitemap;


    public function setUp()
    {
        parent::setUp();

        // config
        $config = [
            'use_cache' => false,
            'cache_key' => 'Laravel.Sitemap.',
            'cache_duration' => 3600,
            'testing' => true
        ];

        $this->sitemap = new Roumen\Sitemap\Sitemap($config);
    }


    public function testSitemapAttributes()
    {
        $this->sitemap->model->setLink('TestLink');
        $this->sitemap->model->setTitle('TestTitle');
        $this->sitemap->model->setUseCache(true);
        $this->sitemap->model->setCacheKey('lv-sitemap');
        $this->sitemap->model->setCacheDuration(72000);
        $this->sitemap->model->setEscaping(false);
        $this->sitemap->model->setUseLimitSize(true);
        $this->sitemap->model->setMaxSize(10000);
        $this->sitemap->model->setUseStyles(false);
        $this->sitemap->model->setSloc('https://static.foobar.tld/xsl-styles/');

        $this->assertEquals('TestLink', $this->sitemap->model->getLink());
        $this->assertEquals('TestTitle', $this->sitemap->model->getTitle());
        $this->assertEquals(true, $this->sitemap->model->getUseCache());
        $this->assertEquals('lv-sitemap', $this->sitemap->model->getCacheKey());
        $this->assertEquals(72000, $this->sitemap->model->getCacheDuration());
        $this->assertEquals(false, $this->sitemap->model->getEscaping());
        $this->assertEquals(true, $this->sitemap->model->getUseLimitSize());
        $this->assertEquals(10000, $this->sitemap->model->getMaxSize());
        $this->assertEquals(true, $this->sitemap->model->testing);
        $this->assertEquals(false, $this->sitemap->model->getUseStyles());
        $this->assertEquals('https://static.foobar.tld/xsl-styles/', $this->sitemap->model->getSloc());
    }


    public function testSitemapAdd()
    {
        // dummy data
        $translations = [
                           ['language' => 'de', 'url' => '/pageDe'],
                           ['language' => 'bg', 'url' => '/pageBg?id=1&sid=2'],
                         ];

        $translationsEscaped = [
                           ['language' => 'de', 'url' => '/pageDe'],
                           ['language' => 'bg', 'url' => '/pageBg?id=1&amp;sid=2'],
                         ];

        $images = [
                    ["url" => "test.png"],
                    ["url" => "<&>"],
                  ];

        $imagesEscaped = [
                    ["url" => "test.png"],
                    ["url" => "&lt;&amp;&gt;"],
                  ];

        $videos = [
                    [
                        'title'=>"TestTitle",
                        'description'=>"TestDescription",
                        'content_loc' => 'https://roumen.it/testVideo.flv',
                        'uploader' => [
                                       'uploader' => 'Roumen',
                                       'info' => 'https://roumen.it'
                                       ],
                        'gallery_loc' => [
                                            'title' => 'testGalleryTitle',
                                            'gallery_loc' => 'https://roumen.it/testGallery'
                                        ],
                        'price' => [
                                            'currency' => 'EUR',
                                            'price' => '100.00'
                                        ],
                        'restriction' => [
                                            'relationship' => 'allow',
                                            'restriction' => 'IE GB US CA'
                                        ],
                        'player_loc' => [
                                            'player_loc' => 'https://roumen.it/testPlayer.flv',
                                            'allow_embed' => 'yes',
                                            'autoplay' => 'ap=1'
                                        ],
                        'thumbnail_loc' => 'https://roumen.it/testVideo.png',
                        'duration' => '600',
                        'expiration_date' => '2015-12-30T23:59:00+02:00',
                        'rating' => '5.00',
                        'view_count' => '100',
                        'publication_date' => '2015-05-30T23:59:00+02:00',
                        'family_friendly' => 'yes',
                        'requires_subscription' => 'no',



                    ],
                    [   'title'=>"TestTitle2&",
                        'description'=>"TestDescription2&",
                        'content_loc' => 'https://roumen.it/testVideo2.flv',]
                    ];

        $googleNews = [
                        'sitename'=>'Foo',
                        'language'=>'en',
                        'publication_date'=>'2016-01-03',
                        'access'=>'Subscription',
                        'keywords'=>'googlenews, sitemap',
                        'genres'=>'PressRelease, Blog',
                        'stock_tickers'=>'NASDAQ:A, NASDAQ:B'
                    ];

        $alternates = [
                        [
                        'media'=>'only screen and (max-width: 640px)',
                        'url'=>'https://m.foobar.tld'
                        ],
                        [
                        'media'=>'only screen and (max-width: 960px)',
                        'url'=>'https://foobar.tld'
                        ],
                    ];


        // add new sitemap items
        $this->sitemap->add('TestLoc', '2014-02-29 00:00:00', 0.95, 'weekly', $images, 'TestTitle', $translations, $videos, $googleNews, $alternates);
        $this->sitemap->add('TestLoc2', '2016-01-01 00:00:00', 0.85, 'daily');

        $items = $this->sitemap->model->getItems();

        // count items
        $this->assertCount(2, $items);

        // item attributes
        $this->assertEquals('TestLoc', $items[0]['loc']);
        $this->assertEquals('TestLoc2', $items[1]['loc']);
        $this->assertEquals('2014-02-29 00:00:00', $items[0]['lastmod']);
        $this->assertEquals('0.95', $items[0]['priority']);
        $this->assertEquals('weekly', $items[0]['freq']);
        $this->assertEquals('TestTitle', $items[0]['title']);

        // images
        $this->assertEquals($imagesEscaped, $items[0]['images']);

        // translations
        $this->assertEquals($translationsEscaped, $items[0]['translations']);

        // videos
        $this->assertEquals($videos[0]['content_loc'], $items[0]['videos'][0]['content_loc']);
        $this->assertEquals($videos[1]['content_loc'], $items[0]['videos'][1]['content_loc']);
        $this->assertEquals('TestTitle2&amp;', $items[0]['videos'][1]['title']);
        $this->assertEquals('TestDescription2&amp;', $items[0]['videos'][1]['description']);

        // googlenews
        $this->assertEquals($googleNews['sitename'], $items[0]['googlenews']['sitename']);
        $this->assertEquals($googleNews['publication_date'], $items[0]['googlenews']['publication_date']);

        // alternates
        $this->assertEquals($alternates[1]['url'], $items[0]['alternates'][1]['url']);

    }


    public function testSitemapAddItem()
    {
        // add one item
        $this->sitemap->addItem([
                        'loc' => 'TestLoc',
                        'lastmod' => '2016-01-01 00:00:00',
                        'priority' => 0.95,
                        'freq' => 'daily'
                    ]);

        // add multiple items
        $this->sitemap->addItem([
                    [
                        'loc' => 'TestLoc2',
                        'lastmod' => '2016-01-02 00:00:00',
                        'priority' => 0.85,
                        'freq' => 'daily'
                    ],
                    [
                        'loc' => 'TestLoc3',
                        'lastmod' => '2016-01-03 00:00:00',
                        'priority' => 0.75,
                        'freq' => 'daily'
                    ]
            ]);

        $items = $this->sitemap->model->getItems();

        // count items
        $this->assertCount(3, $items);

        // item attributes
        $this->assertEquals('TestLoc', $items[0]['loc']);
        $this->assertEquals('TestLoc2', $items[1]['loc']);
        $this->assertEquals('TestLoc3', $items[2]['loc']);
    }


}
