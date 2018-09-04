<?php

namespace Laravelium\Sitemap\Test;

use Laravelium\Sitemap\Sitemap;
use Orchestra\Testbench\TestCase as TestCase;
use Laravelium\Sitemap\SitemapServiceProvider;

class SitemapTest extends TestCase
{
    protected $sitemap;
    protected $sp;
    protected $itemSeeder = [];

    protected function getPackageProviders($app)
    {
        return [SitemapServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return ['Sitemap' => Sitemap::class];
    }

    public function setUp()
    {
        parent::setUp();

        $config = [
            'sitemap.use_cache'       => false,
            'sitemap.cache_key'       => 'Laravel.Sitemap.',
            'sitemap.cache_duration'  => 3600,
            'sitemap.testing'         => true,
            'sitemap.styles_location' => '/styles/',
        ];

        config($config);

        $this->sitemap = $this->app->make(Sitemap::class);
    }

    public function testMisc()
    {
        // test object initialization
        $this->assertInstanceOf(Sitemap::class, $this->sitemap);

        // test custom methods
        $this->assertEquals([SitemapServiceProvider::class], $this->getPackageProviders($this->sitemap));
        $this->assertEquals(['Sitemap'=>Sitemap::class], $this->getPackageAliases($this->sitemap));

        // test SitemapServiceProvider (fixes coverage of the class!)
        $this->sp = new SitemapServiceProvider($this->sitemap);
        $this->assertEquals(['sitemap', Sitemap::class], $this->sp->provides());
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
                    ['url' => 'test.png'],
                    ['url' => '<&>'],
                    ];

        $imagesEscaped = [
                    ['url' => 'test.png'],
                    ['url' => '&lt;&amp;&gt;'],
                    ];

        $videos = [
                    [
                        'title'       => 'TestTitle',
                        'description' => 'TestDescription',
                        'content_loc' => 'https://damianoff.com/testVideo.flv',
                        'uploader'    => [
                                         'uploader' => 'Roumen',
                                         'info'     => 'https://damianoff.com',
                                         ],
                        'gallery_loc' => [
                                            'title'       => 'testGalleryTitle',
                                            'gallery_loc' => 'https://damianoff.com/testGallery',
                                        ],
                        'price' => [
                                            'currency' => 'EUR',
                                            'price'    => '100.00',
                                        ],
                        'restriction' => [
                                            'relationship' => 'allow',
                                            'restriction'  => 'IE GB US CA',
                                        ],
                        'player_loc' => [
                                            'player_loc'  => 'https://damianoff.com/testPlayer.flv',
                                            'allow_embed' => 'yes',
                                            'autoplay'    => 'ap=1',
                                        ],
                        'thumbnail_loc'         => 'https://damianoff.com/testVideo.png',
                        'duration'              => '600',
                        'expiration_date'       => '2015-12-30T23:59:00+02:00',
                        'rating'                => '5.00',
                        'view_count'            => '100',
                        'publication_date'      => '2015-05-30T23:59:00+02:00',
                        'family_friendly'       => 'yes',
                        'requires_subscription' => 'no',

                    ],
                    ['title'          => 'TestTitle2&',
                        'description' => 'TestDescription2&',
                        'content_loc' => 'https://damianoff.com/testVideo2.flv', ],
                    ];

        $googleNews = [
                        'sitename'        => 'Foo',
                        'language'        => 'en',
                        'publication_date'=> '2016-01-03',
                        'access'          => 'Subscription',
                        'keywords'        => 'googlenews, sitemap',
                        'genres'          => 'PressRelease, Blog',
                        'stock_tickers'   => 'NASDAQ:A, NASDAQ:B',
                    ];

        $alternates = [
                        [
                        'media'=> 'only screen and (max-width: 640px)',
                        'url'  => 'https://m.foobar.tld',
                        ],
                        [
                        'media'=> 'only screen and (max-width: 960px)',
                        'url'  => 'https://foobar.tld',
                        ],
                    ];

        // add new sitemap items
        $this->sitemap->add('TestLoc', '2014-02-29 00:00:00', 0.95, 'weekly', $images, 'TestTitle', $translations, $videos, $googleNews, $alternates);
        $this->sitemap->add(null, null, 0.85, 'daily');

        $items = $this->sitemap->model->getItems();

        // count items
        $this->assertCount(2, $items);

        // item attributes
        $this->assertEquals('TestLoc', $items[0]['loc']);
        $this->assertEquals('/', $items[1]['loc']);
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

        // limit items
        $this->sitemap->model->limitSize(1);

        $this->assertCount(1, $this->sitemap->model->getItems());

        // reset items
        $this->sitemap->model->resetItems();

        $this->assertCount(0, $this->sitemap->model->getItems());
    }

    public function testSitemapAddItem()
    {
        // add one item
        $this->sitemap->addItem([
            'title' => 'testTitle0',
        ]);

        // add multiple items
        $this->sitemap->addItem([
                    [
                        'loc'      => 'TestLoc2',
                        'lastmod'  => '2016-01-02 00:00:00',
                        'priority' => 0.85,
                        'freq'     => 'daily',
                    ],
                    [
                        'loc'      => 'TestLoc3',
                        'lastmod'  => '2016-01-03 00:00:00',
                        'priority' => 0.75,
                        'freq'     => 'daily',
                    ],
            ]);

        $items = $this->sitemap->model->getItems();

        // count items
        $this->assertCount(3, $items);

        // item attributes
        $this->assertEquals('/', $items[0]['loc']);
        $this->assertEquals('testTitle0', $items[0]['title']);
        $this->assertEquals('TestLoc2', $items[1]['loc']);
        $this->assertEquals('TestLoc3', $items[2]['loc']);
    }

    public function testSitemapSetCache()
    {
        $this->sitemap->setCache('TestKey', 69, true);

        $this->assertEquals('TestKey', $this->sitemap->model->getCacheKey());
        $this->assertEquals(69, $this->sitemap->model->getCacheDuration());
        $this->assertEquals(true, $this->sitemap->model->getUseCache());
    }

    public function testSitemapAddSitemap()
    {
        $this->assertEquals([], $this->sitemap->model->getSitemaps());

        $this->sitemap->addSitemap('https://test.local', '2018-06-11 14:35:00');

        $testSitemapsArray = [
            'loc'     => 'https://test.local',
            'lastmod' => '2018-06-11 14:35:00',
        ];

        $this->assertEquals($testSitemapsArray, $this->sitemap->model->getSitemaps()[0]);

        $this->sitemap->resetSitemaps();
        $this->assertEquals([], $this->sitemap->model->getSitemaps());
    }

    public function testIsCached()
    {
        $this->assertEquals(false, $this->sitemap->IsCached());

        $this->sitemap->setCache('testKey', 60, true);

        $this->sitemap->cache->put($this->sitemap->model->getCacheKey(), $this->sitemap->model->getItems(), $this->sitemap->model->getCacheDuration());

        $this->assertEquals(true, $this->sitemap->IsCached());
    }

    public function testRenderSitemap()
    {
        $sitemap = $this->sitemap->render();
        $this->assertEquals(200, $sitemap->status());
        $this->assertEquals('text/xml; charset=utf-8', $sitemap->headers->get('Content-Type'));

        $sitemap = $this->sitemap->render('xml', '/styles/xsl/xml-sitemap.xsl');
        $this->assertEquals('text/xml; charset=utf-8', $sitemap->headers->get('Content-Type'));

        $sitemap = $this->sitemap->render('sitemapindex');
        $this->assertEquals('text/xml; charset=utf-8', $sitemap->headers->get('Content-Type'));

        $sitemap = $this->sitemap->render('html');
        $this->assertEquals('text/html; charset=utf-8', $sitemap->headers->get('Content-Type'));

        $sitemap = $this->sitemap->render('txt');
        $this->assertEquals('text/plain; charset=utf-8', $sitemap->headers->get('Content-Type'));

        $sitemap = $this->sitemap->render('ror-rss');
        $this->assertEquals('text/rss+xml; charset=utf-8', $sitemap->headers->get('Content-Type'));

        $sitemap = $this->sitemap->render('ror-rdf');
        $this->assertEquals('text/rdf+xml; charset=utf-8', $sitemap->headers->get('Content-Type'));

        $this->seedItems();
        $this->sitemap->model->resetItems($this->itemSeeder);

        // xml > 5000
        $sitemap = $this->sitemap->render();
        $this->assertEquals(200, $sitemap->status());
        $this->assertEquals('text/xml; charset=utf-8', $sitemap->headers->get('Content-Type'));

        $this->sitemap->model->resetItems($this->itemSeeder);

        // google-news > 1000
        $sitemap = $this->sitemap->render('google-news');
        $this->assertEquals(200, $sitemap->status());
        $this->assertEquals('text/xml; charset=utf-8', $sitemap->headers->get('Content-Type'));

        $this->sitemap->model->resetItems($this->itemSeeder);

        // maxSize == 100
        $this->sitemap->model->setMaxSize(100);
        $this->assertEquals(100, $this->sitemap->model->getMaxSize());

        $this->sitemap->model->resetItems($this->itemSeeder);

        $sitemap = $this->sitemap->render();
        $this->assertEquals(200, $sitemap->status());
        $this->assertEquals('text/xml; charset=utf-8', $sitemap->headers->get('Content-Type'));
    }

    public function testStoreSitemap()
    {
        $sitemap = $this->sitemap->store();
        $this->assertEquals(null, $sitemap);

        $sitemap = $this->sitemap->store('sitemapindex');
        $this->assertEquals(null, $sitemap);

        $sitemap = $this->sitemap->store('google-news');
        $this->assertEquals(null, $sitemap);

        $this->seedItems();
        $this->sitemap->model->resetItems($this->itemSeeder);

        $this->assertCount(50001, $this->sitemap->model->getItems(), 'gsitemap', '/www/', 'css/style.css');

        $this->sitemap->model->setUseLimitSize(false);

        $sitemap = $this->sitemap->store('google-news');
        $this->assertEquals(null, $sitemap);

        $this->sitemap->model->resetItems($this->itemSeeder);

        $this->assertCount(50001, $this->sitemap->model->getItems());

        $this->sitemap->model->setUseLimitSize(false);

        $sitemap = $this->sitemap->store('xml', 'sitemap', null, 'tests/style.css');
        $this->assertEquals(null, $sitemap);

        $this->sitemap->model->resetItems($this->itemSeeder);

        $sitemap = $this->sitemap->store('xml', 'sitemap', 'tests', 'tests/style.css');
        $this->assertEquals(null, $sitemap);

        $this->sitemap->model->setUseLimitSize(true);
        $this->sitemap->model->setUseGzip(true);

        $this->sitemap->model->resetItems($this->itemSeeder);

        $sitemap = $this->sitemap->store('xml', 'sitemap', null, 'tests/style.css');
        $this->assertEquals(null, $sitemap);

        $this->sitemap->model->resetItems($this->itemSeeder);

        $sitemap = $this->sitemap->store('xml', 'sitemap', 'tests', 'tests/style.css');
        $this->assertEquals(null, $sitemap);

        $this->sitemap->model->setMaxSize(400);

        $this->sitemap->model->setUseLimitSize(true);
        $this->sitemap->model->setUseGzip(false);

        $this->sitemap->model->resetItems($this->itemSeeder);

        $sitemap = $this->sitemap->store('xml', 'sitemap', null, 'tests/style.css');
        $this->assertEquals(null, $sitemap);

        $this->sitemap->model->resetItems($this->itemSeeder);

        $sitemap = $this->sitemap->store('xml', 'sitemap', 'tests', 'tests/style.css');
        $this->assertEquals(null, $sitemap);

        $this->sitemap->model->setUseLimitSize(false);

        $this->sitemap->model->resetItems($this->itemSeeder);

        $sitemap = $this->sitemap->store('xml', 'sitemap', null, 'tests/style.css');
        $this->assertEquals(null, $sitemap);

        $this->sitemap->model->resetItems($this->itemSeeder);

        $sitemap = $this->sitemap->store('xml', 'sitemap', 'tests', 'tests/style.css');
        $this->assertEquals(null, $sitemap);
    }

    public function testGenerateSitemap()
    {
        $this->sitemap->model->setUseStyles(true);
        $this->sitemap->model->setUseCache(true);
        $this->sitemap->model->setSloc('/styles/');

        $this->app->bind('path.public', function () {
            return realpath(__DIR__.'/../src/public');
        });

        $sitemap = $this->sitemap->generate('xml', 'xml.xsl');
        $this->assertEquals('text/xml; charset=utf-8', $sitemap['headers']['Content-type']);

        $sitemap = $this->sitemap->generate('sitemapindex', null);
        $this->assertEquals('text/xml; charset=utf-8', $sitemap['headers']['Content-type']);

        $this->sitemap->model->setUseStyles(false);
        $this->sitemap->model->setSloc(null);

        $sitemap = $this->sitemap->generate('xml', 'xml.xsl');
        $this->assertEquals('text/xml; charset=utf-8', $sitemap['headers']['Content-type']);

        $this->sitemap->setCache('testKey', 60, true);

        $this->sitemap->cache->put($this->sitemap->model->getCacheKey(), $this->sitemap->model->getItems(), $this->sitemap->model->getCacheDuration());

        $sitemap = $this->sitemap->generate('xml', '/styles/xsl/xml-sitemap.xsl');
        $this->assertEquals('text/xml; charset=utf-8', $sitemap['headers']['Content-type']);

        $sitemap = $this->sitemap->generate('sitemapindex', null);
        $this->assertEquals('text/xml; charset=utf-8', $sitemap['headers']['Content-type']);
    }

    private function seedItems($n = 50001)
    {
        $this->itemSeeder = [];

        for ($i = 0; $i < $n; $i++) {
            $this->itemSeeder[] = [
                    'loc'        => 'TestLoc'.$i,
                    'lastmod'    => '2018-06-11 20:00:00',
                    'priority'   => 0.95,
                    'freq'       => 'daily',
                    'googlenews' => [
                        'sitename'        => 'Foo',
                        'language'        => 'en',
                        'publication_date'=> '2018-08-25',
                    ],
                    'title' => 'TestTitle',
                ];
        }
    }
}
