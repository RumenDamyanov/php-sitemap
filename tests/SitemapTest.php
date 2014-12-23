<?php

class SitemapTest extends PHPUnit_Framework_TestCase
{
    protected $sitemap;

    public function setUp()
    {
        parent::setUp();

        // config
        $config = array(
                        'use_cache' => false,
                        'cache_key' => 'Laravel.Sitemap.',
                        'cache_duration' => 3600,
                    );

        $this->sitemap = new Roumen\Sitemap\Sitemap($config);
    }

    public function testSitemapAdd()
    {
    	$this->sitemap->add('TestLoc','2014-02-29 00:00:00', 0.95, 'weekly', array("test.png","test2.jpg"), 'TestTitle');

        $items = $this->sitemap->model->getItems();

        $this->assertCount(1, $items);

        $this->assertEquals('TestLoc', $items[0]['loc']);
        $this->assertEquals('2014-02-29 00:00:00', $items[0]['lastmod']);
        $this->assertEquals('0.95', $items[0]['priority']);
        $this->assertEquals('weekly', $items[0]['freq']);
        $this->assertEquals(array('test.png','test2.jpg'), $items[0]['images']);
        $this->assertEquals('TestTitle', $items[0]['title']);

    }

    public function testSitemapAttributes()
    {
        $this->sitemap->model->setLink('TestLink');
        $this->sitemap->model->setTitle('TestTitle');
        $this->sitemap->model->setUseCache(true);
        $this->sitemap->model->setCacheKey('lv-sitemap');
        $this->sitemap->model->setCacheDuration(72000);

        $this->assertEquals('TestLink', $this->sitemap->model->getLink());
        $this->assertEquals('TestTitle', $this->sitemap->model->getTitle());
        $this->assertEquals(true, $this->sitemap->model->getUseCache());
        $this->assertEquals('lv-sitemap', $this->sitemap->model->getCacheKey());
        $this->assertEquals(72000, $this->sitemap->model->getCacheDuration());
    }

    public function testSitemapRender()
    {
    	//
    }

    public function testSitemapStore()
    {
        //
    }

    public function testSitemapCache()
    {
        //
    }

}