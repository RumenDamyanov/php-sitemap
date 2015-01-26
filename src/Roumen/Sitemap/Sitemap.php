<?php

namespace Roumen\Sitemap;

/**
 * Sitemap class for laravel-sitemap package.
 *
 * @author Roumen Damianoff <roumen@dawebs.com>
 * @version 2.4.15
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
    public $model = null;


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
     * @param Carbon|Datetime|int $duration
     * @param boolean $useCache
     */
    public function setCache($key = null, $duration = null, $useCache = true)
    {
        $this->model->setUseCache($useCache);

        if ($key !== null)
        {
            $this->model->setCacheKey($key);
        }

        if ($duration !== null)
        {
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
     * @param array  $images
     * @param string $title
     * @param array $translations
     *
     * @return void
     */
    public function add($loc, $lastmod = null, $priority = null, $freq = null, $images = array(), $title = null, $translations = array())
    {

        if ($this->model->getEscaping())
        {
            $loc = htmlentities($loc, ENT_XML1);

            if ($title != null) htmlentities($title, ENT_XML1);

            if ($images)
            {
                foreach ($images as $k => $image)
                {
                    foreach ($image as $key => $value)
                    {
                        $images[$k][$key] = htmlentities($value, ENT_XML1);
                    }
                }
            }

            if ($translations)
            {
                foreach($translations as $translation)
                {
                    foreach ($translation as $key => $value)
                    {
                        $translation[$key] = htmlentities($value, ENT_XML1);
                    }
                }
            }

        }


        $this->model->setItems(
                array(
                    'loc' => $loc,
                    'lastmod' => $lastmod,
                    'priority' => $priority,
                    'freq' => $freq,
                    'images' => $images,
                    'title' => $title,
                    'translations' => $translations
                )
        );
    }


    /**
     * Add new sitemap to $sitemaps array
     *
     * @param string $loc
     * @param string $lastmod
     *
     * @return void
     */
    public function addSitemap($loc, $lastmod = null)
    {
        $this->model->setSitemaps(
                array(
                    'loc' => $loc,
                    'lastmod' => $lastmod
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

        if($format=='html')
        {
            return $data['content'];
        }

        return Response::make($data['content'], 200, $data['headers']);
    }


    /**
     * Generates document with all sitemap items from $items array
     *
     * @param string $format (options: xml, html, txt, ror-rss, ror-rdf, sitemapindex)
     *
     * @return array
     */
    public function generate($format = 'xml')
    {

        if ($this->isCached())
        {
            ($format == 'sitemapindex') ? $this->model->sitemaps = Cache::get($this->model->getCacheKey()) : $this->model->items = Cache::get($this->model->getCacheKey());
        } elseif ($this->model->getUseCache())
            {
               ($format == 'sitemapindex') ? Cache::put($this->model->getCacheKey(), $this->model->getSitemaps(), $this->model->getCacheDuration()) : Cache::put($this->model->getCacheKey(), $this->model->getItems(), $this->model->getCacheDuration());
            }

        if (!$this->model->getLink())
        {
            $this->model->setLink(Config::get('app.url'));
        }

        if (!$this->model->getTitle())
        {
            $this->model->setTitle(('Sitemap for ' . $this->model->getLink()));
        }

        $channel = array(
            'title' => $this->model->getTitle(),
            'link' => $this->model->getLink()
        );

        // check if this sitemap have more than 50000 elements
        if ( count($this->model->getItems()) > 50000 )
        {
            // option 1: reset items to 50000 elements
            $this->model->resetItems();

            // TODO option 2: split them to two partial sitemaps and add them to sitemapindex
        }

        switch ($format)
        {
            case 'ror-rss':
                return array('content' => View::make('sitemap::ror-rss', array('items' => $this->model->getItems(), 'channel' => $channel))->render(), 'headers' => array('Content-type' => 'text/rss+xml; charset=utf-8'));
            case 'ror-rdf':
                return array('content' => View::make('sitemap::ror-rdf', array('items' => $this->model->getItems(), 'channel' => $channel))->render(), 'headers' => array('Content-type' => 'text/rdf+xml; charset=utf-8'));
            case 'html':
                return array('content' => View::make('sitemap::html', array('items' => $this->model->getItems(), 'channel' => $channel))->render(), 'headers' => array('Content-type' => 'text/html'));
            case 'txt':
                return array('content' => View::make('sitemap::txt', array('items' => $this->model->getItems()))->render(), 'headers' => array('Content-type' => 'text/plain'));
            case 'sitemapindex':
                return array('content' => View::make('sitemap::sitemapindex', array('sitemaps' => $this->model->getSitemaps()))->render(), 'headers' => array('Content-type' => 'text/xml; charset=utf-8'));
            default:
                return array('content' => View::make('sitemap::xml', array('items' => $this->model->getItems()))->render(), 'headers' => array('Content-type' => 'text/xml; charset=utf-8'));
        }
    }


    /**
     * Generate sitemap and store it to a file
     *
     * @param string $format (options: xml, html, txt, ror-rss, ror-rdf, sitemapindex)
     * @param string $filename (without file extension, may be a path like 'sitemaps/sitemap1' but must exist)
     *
     * @return void
     */
    public function store($format = 'xml', $filename = 'sitemap')
    {
        $data = $this->generate($format);

        if ($format == 'ror-rss' || $format == 'ror-rdf' || $format == 'sitemapindex')
        {
            $format = 'xml';
        }

        $file = public_path() . DIRECTORY_SEPARATOR . $filename . '.' . $format;

        // must return something
        if (File::put($file, $data['content']))
        {
            return "Success! Your sitemap file is created.";
        } else
            {
                return "Error! Your sitemap file is NOT created.";
            }

        // clear
        ($format == 'sitemapindex') ? $this->model->sitemaps = array() : $this->model->items = array();
    }


    /**
     * Check if content is cached
     *
     * @return bool
     */
    public function isCached()
    {
        if ($this->model->getUseCache())
        {
            if (Cache::has($this->model->getCacheKey()))
            {
                return true;
            }
        }

        return false;
    }


}
