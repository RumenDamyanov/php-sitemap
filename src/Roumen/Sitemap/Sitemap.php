<?php namespace Roumen\Sitemap;

/**
 * Sitemap class for laravel-sitemap package.
 *
 * @author Roumen Damianoff <roumen@dawebs.com>
 * @version 2.5.8
 * @link http://roumen.it/projects/laravel-sitemap
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;


class Sitemap
{

    /**
     * Model instance
     *
     * @var Model $model
     */
    public $model = null;


    /**
     * Using constructor we populate our model from configuration file
     *
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
     * Checks if content is cached
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
     * @param array $videos
     * @param array $googlenews
     *
     * @return void
     */
    public function add($loc, $lastmod = null, $priority = null, $freq = null, $images = [], $title = null, $translations = [], $videos = [], $googlenews = [])
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
                foreach ($translations as $k => $translation)
                {
                    foreach ($translation as $key => $value)
                    {
                        $translations[$k][$key] = htmlentities($value, ENT_XML1);
                    }
                }
            }

            if ($videos)
            {
                foreach ($videos as $k => $video)
                {
                    if ($video['title']) $videos[$k]['title'] = htmlentities($video['title'], ENT_XML1);
                    if ($video['description']) $videos[$k]['description'] = htmlentities($video['description'], ENT_XML1);
                }
            }

            if ($googlenews)
            {
                if (isset($googlenews['sitename'])) $googlenews['sitename'] = htmlentities($googlenews['sitename'], ENT_XML1);
            }

        }

        $googlenews['sitename'] = isset($googlenews['sitename']) ? $googlenews['sitename'] : '';
        $googlenews['language'] = isset($googlenews['language']) ? $googlenews['language'] : 'en';
        $googlenews['publication_date'] = isset($googlenews['publication_date']) ? $googlenews['publication_date'] : date('Y-m-d H:i:s');

        $this->model->setItems([
            'loc' => $loc,
            'lastmod' => $lastmod,
            'priority' => $priority,
            'freq' => $freq,
            'images' => $images,
            'title' => $title,
            'translations' => $translations,
            'videos' => $videos,
            'googlenews' => $googlenews
        ]);
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
        $this->model->setSitemaps([
            'loc' => $loc,
            'lastmod' => $lastmod,
        ]);
    }


    /**
     * Returns document with all sitemap items from $items array
     *
     * @param string $format (options: xml, html, txt, ror-rss, ror-rdf, google-news)
     *
     * @return View
     */
    public function render($format = 'xml')
    {
        $data = $this->generate($format);

        if ($format == 'html')
        {
            return $data['content'];
        }

        return Response::make($data['content'], 200, $data['headers']);
    }


    /**
     * Generates document with all sitemap items from $items array
     *
     * @param string $format (options: xml, html, txt, ror-rss, ror-rdf, sitemapindex, google-news)
     *
     * @return array
     */
    public function generate($format = 'xml')
    {
        // don't render (cache) more than 50000 elements in a single sitemap
        if (count($this->model->getItems()) > 50000)
        {
            // get only most recent 50000
            $this->model->limitSize();
        }

        // check if caching is enabled, there is a cached content and its duration isn't expired
        if ($this->isCached())
        {
            ($format == 'sitemapindex') ? $this->model->resetSitemaps(Cache::get($this->model->getCacheKey())) : $this->model->resetItems(Cache::get($this->model->getCacheKey()));
        }
        elseif ($this->model->getUseCache())
        {
           ($format == 'sitemapindex') ? Cache::put($this->model->getCacheKey(), $this->model->getSitemaps(), $this->model->getCacheDuration()) : Cache::put($this->model->getCacheKey(), $this->model->getItems(), $this->model->getCacheDuration());
        }

        if (!$this->model->getLink())
        {
            $this->model->setLink(Config::get('app.url'));
        }

        if (!$this->model->getTitle())
        {
            $this->model->setTitle('Sitemap for ' . $this->model->getLink());
        }

        $channel = [
            'title' => $this->model->getTitle(),
            'link' => $this->model->getLink(),
        ];

        switch ($format)
        {
            case 'ror-rss':
                return ['content' => View::make('sitemap::ror-rss', ['items' => $this->model->getItems(), 'channel' => $channel])->render(), 'headers' => ['Content-type' => 'text/rss+xml; charset=utf-8']];
            case 'ror-rdf':
                return ['content' => View::make('sitemap::ror-rdf', ['items' => $this->model->getItems(), 'channel' => $channel])->render(), 'headers' => ['Content-type' => 'text/rdf+xml; charset=utf-8']];
            case 'html':
                return ['content' => View::make('sitemap::html', ['items' => $this->model->getItems(), 'channel' => $channel])->render(), 'headers' => ['Content-type' => 'text/html']];
            case 'txt':
                return ['content' => View::make('sitemap::txt', ['items' => $this->model->getItems()])->render(), 'headers' => ['Content-type' => 'text/plain']];
            case 'sitemapindex':
                return ['content' => View::make('sitemap::sitemapindex', ['sitemaps' => $this->model->getSitemaps()])->render(), 'headers' => ['Content-type' => 'text/xml; charset=utf-8']];
            default:
                return ['content' => View::make('sitemap::'.$format, ['items' => $this->model->getItems()])->render(), 'headers' => ['Content-type' => 'text/xml; charset=utf-8']];
        }
    }


    /**
     * Generate sitemap and store it to a file
     *
     * @param string $format (options: xml, html, txt, ror-rss, ror-rdf, sitemapindex, google-news)
     * @param string $filename (without file extension, may be a path like 'sitemaps/sitemap1' but must exist)
     *
     * @return void
     */
    public function store($format = 'xml', $filename = 'sitemap')
    {
        // turn off caching for this method
        $this->model->setUseCache(false);

        // use correct file extension
        ($format == 'txt' || $format == 'html') ? $fe = $format : $fe = 'xml';

        // check if this sitemap have more than 50000 elements
        if (count($this->model->getItems()) > 50000)
        {
            // check if limiting size of items array is enabled
            if (!$this->model->getUseLimitSize())
            {
                // use sitemapindex and generate partial sitemaps
                foreach (array_chunk($this->model->getItems(), 50000) as $key => $item)
                {
                    $this->model->resetItems($item);
                    $this->store($format, $filename . '-' . $key);
                    $this->addSitemap(url($filename . '-' . $key . '.' . $fe));
                }

                $data = $this->generate('sitemapindex');
            }
            else
            {
                // reset items and use only most recent 50000 items
                $this->model->limitSize();
                $data = $this->generate($format);
            }
        }
        else
        {
            $data = $this->generate($format);
        }

        $file = public_path() . DIRECTORY_SEPARATOR . $filename . '.' . $fe;

        // must return something
        if (File::put($file, $data['content']))
        {
            return "Success! Your sitemap file is created.";
        }
        else
        {
            return "Error! Your sitemap file is NOT created.";
        }

        // clear memory
        if ($format == 'sitemapindex')
        {
            $this->model->resetSitemaps();
            $this->model->resetItems();
        }
        else
        {
            $this->model->resetItems();
        }
    }


}