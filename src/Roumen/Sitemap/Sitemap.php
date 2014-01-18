<?php

namespace Roumen\Sitemap;

/**
 * Sitemap class for laravel-sitemap package.
 *
 * @author Roumen Damianoff <roumen@dawebs.com>
 * @version 2.3.6
 * @link http://roumen.it/projects/laravel-sitemap
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
use Config,
    Response,
    View,
    File,
    Cache;

class Sitemap
{

    /**
     * Model instance
     * @var Model $model 
     */
    protected $model = null;

    /**
     * Using constructor we populate our model from configuration file
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->model = new Model($config);
    }

    /**
     * Set cache options
     * 
     * @param string $key
     * @param integer $duration
     * @param boolean $useCache
     */
    public function setCache($key = null, $duration = null, $useCache = true)
    {
        $this->model->setUseCache($useCache);
        if ($key !== null) {
            $this->model->setCacheKey($key);
        }
        if ($duration !== null) {
            $this->model->setCacheDuration($duration);
        }
    }

    /**
     * Add new sitemap item to $items array
     *
     * @param string $loc
     * @param string $lastmod
     * @param string $priority
     * @param string $freq
     * @param array  $image
     * @param string $title
     *
     * @return void
     */
    public function add($loc, $lastmod = null, $priority = null, $freq = null, $image = array(), $title = null)
    {
        $this->model->setItems(
                array(
                    'loc' => $loc,
                    'lastmod' => $lastmod,
                    'priority' => $priority,
                    'freq' => $freq,
                    'image' => $image,
                    'title' => $title
                )
        );
    }

    /**
     * Returns document with all sitemap items from $items array
     *
     * @param string $format (options: xml, html, txt, ror-rss, ror-rdf)
     *
     * @return View
     */
    public function render($format = 'xml')
    {
        $data = $this->generate($format);
        return Response::make($data['content'], 200, $data['headers']);
    }

    /**
     * Generates document with all sitemap items from $items array
     *
     * @param string $format (options: xml, html, txt, ror-rss, ror-rdf)
     *
     * @return array
     */
    public function generate($format = 'xml')
    {
        if (empty($this->model->getLink())) {
            $this->model->setLink(Config::get('app.url'));
        }

        if (empty($this->model->getTitle())) {
            $this->model->setTitle(('Sitemap for ' . $this->model->getLink()));
        }

        $channel = array(
            'title' => $this->model->getTitle(),
            'link' => $this->model->getLink()
        );

        if ($this->model->getUseCache()) {
            if (Cache::has($this->model->getCacheKey())) {
                $this->model->setItems(Cache::get($this->model->getCacheKey()));
            } else {
                Cache::put($this->model->getCacheKey(), $this->model->getItems(), $this->model->getCacheDuration());
            }
        }

        switch ($format) {
            case 'ror-rss':
                return array('content' => View::make('sitemap::ror-rss', array('items' => $this->model->getItems(), 'channel' => $channel)), 'headers' => array('Content-type' => 'text/rss+xml; charset=utf-8'));
            case 'ror-rdf':
                return array('content' => View::make('sitemap::ror-rdf', array('items' => $this->model->getItems(), 'channel' => $channel)), 'headers' => array('Content-type' => 'text/rdf+xml; charset=utf-8'));
            case 'html':
                return array('content' => View::make('sitemap::html', array('items' => $this->model->getItems(), 'channel' => $channel)), 'headers' => array('Content-type' => 'text/html'));
            case 'txt':
                return array('content' => View::make('sitemap::txt', array('items' => $this->model->getItems(), 'channel' => $channel)), 'headers' => array('Content-type' => 'text/plain'));
            default:
                return array('content' => View::make('sitemap::xml', array('items' => $this->model->getItems(), 'channel' => $channel)), 'headers' => array('Content-type' => 'text/xml; charset=utf-8'));
        }
    }

    /**
     * Generate sitemap and store it to a file
     *
     * @param string $format (options: xml, html, txt, ror-rss, ror-rdf)
     * @param string $filename (without file extension, may be a path like 'sitemaps/sitemap1' but must exist)
     *
     * @return void
     */
    public function store($format = 'xml', $filename = 'sitemap')
    {
        $data = $this->generate($format);

        if ($format == 'ror-rss' || $format == 'ror-rdf') {
            $format = 'xml';
        }

        $file = public_path() . DIRECTORY_SEPARATOR . $filename . '.' . $format;

        File::put($file, $data['content']);
    }

}