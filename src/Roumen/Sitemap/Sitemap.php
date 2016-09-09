<?php namespace Roumen\Sitemap;

/**
 * Sitemap class for laravel-sitemap package.
 *
 * @author Roumen Damianoff <roumen@crimsson.com>
 * @version 2.6.5
 * @link http://roumen.it/projects/laravel-sitemap
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Artisan;


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
     * @param array $alternates
     *
     * @return void
     */
    public function add($loc, $lastmod = null, $priority = null, $freq = null, $images = [], $title = null, $translations = [], $videos = [], $googlenews = [], $alternates = [])
    {

        $params = [
            'loc'           =>  $loc,
            'lastmod'       =>  $lastmod,
            'priority'      =>  $priority,
            'freq'          =>  $freq,
            'images'        =>  $images,
            'title'         =>  $title,
            'translations'  =>  $translations,
            'videos'        =>  $videos,
            'googlenews'    =>  $googlenews,
            'alternates'    =>  $alternates,
        ];


        $this->addItem($params);
    }

     /**
     * Add new sitemap one or multiple items to $items array
     *
     * @param array $params
     *
     * @return void
     */
    public function addItem($params = [])
    {

        // if is multidimensional
        if (array_key_exists(1, $params))
        {
            foreach ($params as $a)
            {
                $this->addItem($a);
            }

            return;
        }

        // get params
        foreach ($params as $key => $value)
        {
            $$key = $value;
        }

        // set default values
        if (!isset($loc)) $loc = '/';
        if (!isset($lastmod)) $lastmod = null;
        if (!isset($priority)) $priority = null;
        if (!isset($freq)) $freq = null;
        if (!isset($title)) $title = null;
        if (!isset($images)) $images = [];
        if (!isset($translations)) $translations = [];
        if (!isset($alternates)) $alternates = [];
        if (!isset($videos)) $videos = [];
        if (!isset($googlenews)) $googlenews = [];

        // escaping
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

            if ($alternates)
            {
                foreach ($alternates as $k => $alternate)
                {
                    foreach ($alternate as $key => $value)
                    {
                        $alternates[$k][$key] = htmlentities($value, ENT_XML1);
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
            'googlenews' => $googlenews,
            'alternates' => $alternates
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
     * @param string $style (path to custom xls style like '/styles/xsl/xml-sitemap.xsl')
     *
     * @return View
     */
    public function render($format = 'xml', $style = null)
    {
        // limit size of sitemap
        if ($this->model->getMaxSize() > 0 && count($this->model->getItems()) > $this->model->getMaxSize())
        {
            $this->model->limitSize($this->model->getMaxSize());
        }
        else if ($format == 'google-news' && count($this->model->getItems()) > 1000)
        {
            $this->model->limitSize(1000);
        }
        else if ($format != 'google-news' && count($this->model->getItems()) > 50000)
        {
            $this->model->limitSize();
        }

        $data = $this->generate($format, $style);

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
     * @param string $style (path to custom xls style like '/styles/xsl/xml-sitemap.xsl')
     *
     * @return array
     */
    public function generate($format = 'xml', $style = null)
    {
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

        // check if styles are enabled
        if ($this->model->getUseStyles())
        {
            if ($style != null && file_exists(public_path($style)))
            {
                // use this style
            }
            else if ($this->model->getSloc() != null && file_exists(public_path($this->model->getSloc().$format.'.xsl')))
            {
                // use style from your custom location
                $style = $this->model->getSloc().$format.'.xsl';
            }
            else if (file_exists(public_path().'/vendor/sitemap/styles/'.$format.'.xsl'))
            {
                // use the default vendor style
                $style = asset('/vendor/sitemap/styles/'.$format.'.xsl');
            }
            else
            {
                // don't use style
                $style = null;
            }
        }
        else
        {
            // don't use style
            $style = null;
        }

        switch ($format)
        {
            case 'ror-rss':
                return ['content' => View::make('sitemap::ror-rss', ['items' => $this->model->getItems(), 'channel' => $channel, 'style' => $style])->render(), 'headers' => ['Content-type' => 'text/rss+xml; charset=utf-8']];
            case 'ror-rdf':
                return ['content' => View::make('sitemap::ror-rdf', ['items' => $this->model->getItems(), 'channel' => $channel, 'style' => $style])->render(), 'headers' => ['Content-type' => 'text/rdf+xml; charset=utf-8']];
            case 'html':
                return ['content' => View::make('sitemap::html', ['items' => $this->model->getItems(), 'channel' => $channel, 'style' => $style])->render(), 'headers' => ['Content-type' => 'text/html']];
            case 'txt':
                return ['content' => View::make('sitemap::txt', ['items' => $this->model->getItems(), 'style' => $style])->render(), 'headers' => ['Content-type' => 'text/plain']];
            case 'sitemapindex':
                return ['content' => View::make('sitemap::sitemapindex', ['sitemaps' => $this->model->getSitemaps(), 'style' => $style])->render(), 'headers' => ['Content-type' => 'text/xml; charset=utf-8']];
            default:
                return ['content' => View::make('sitemap::'.$format, ['items' => $this->model->getItems(), 'style' => $style])->render(), 'headers' => ['Content-type' => 'text/xml; charset=utf-8']];
        }
    }

    /**
     * Generate sitemap and store it to a file
     *
     * @param string $format (options: xml, html, txt, ror-rss, ror-rdf, sitemapindex, google-news)
     * @param string $filename (without file extension, may be a path like 'sitemaps/sitemap1' but must exist)
     * @param string $path (path to store sitemap like '/www/site/public')
     * @param string $style (path to custom xls style like '/styles/xsl/xml-sitemap.xsl')
     *
     * @return void
     */
    public function store($format = 'xml', $filename = 'sitemap', $path = null, $style = null)
    {
        // turn off caching for this method
        $this->model->setUseCache(false);

        // use correct file extension
        ($format == 'txt' || $format == 'html') ? $fe = $format : $fe = 'xml';

        // use custom size limit for sitemaps
        if ($this->model->getMaxSize() > 0 && count($this->model->getItems()) >= $this->model->getMaxSize())
        {
            if ($this->model->getUseLimitSize())
            {
                // limit size
                $this->model->limitSize($this->model->getMaxSize());
                $data = $this->generate($format, $style);
            }
            else
            {
                // use sitemapindex and generate partial sitemaps
                foreach (array_chunk($this->model->getItems(), $this->model->getMaxSize()) as $key => $item)
                {
                    // reset current items
                    $this->model->resetItems($item);

                    // generate new partial sitemap
                    $this->store($format, $filename . '-' . $key, $path, $style);

                    // add sitemap to sitemapindex
                    if ($path != null)
                    {
                        // if using custom path generate relative urls for sitemaps in the sitemapindex
                        $this->addSitemap($filename . '-' . $key . '.' . $fe);
                    }
                    else
                    {
                        // else generate full urls based on app's domain
                        $this->addSitemap(url($filename . '-' . $key . '.' . $fe));
                    }
                }

                $data = $this->generate('sitemapindex', $style);
            }

        }
        else if ( ($format != "google-news" && count($this->model->getItems()) > 50000) || ($format == "google-news" && count($this->model->getItems()) > 1000) )
        {
            ($format != "google-news") ? $max = 50000 : $max = 1000;

            // check if limiting size of items array is enabled
            if (!$this->model->getUseLimitSize())
            {
                // use sitemapindex and generate partial sitemaps
                foreach (array_chunk($this->model->getItems(), $max) as $key => $item)
                {
                    // reset current items
                    $this->model->resetItems($item);

                    // generate new partial sitemap
                    $this->store($format, $filename . '-' . $key, $path, $style);

                    // add sitemap to sitemapindex
                    if ($path != null)
                    {
                        // if using custom path generate relative urls for sitemaps in the sitemapindex
                        $this->addSitemap($filename . '-' . $key . '.' . $fe);
                    }
                    else
                    {
                        // else generate full urls based on app's domain
                        $this->addSitemap(url($filename . '-' . $key . '.' . $fe));
                    }
                }

                $data = $this->generate('sitemapindex', $style);
            }
            else
            {
                // reset items and use only most recent $max items
                $this->model->limitSize($max);
                $data = $this->generate($format, $style);
            }
        }
        else
        {
            $data = $this->generate($format, $style);
        }

        // if custom path
        if ($path==null)
        {
            $file = public_path() . DIRECTORY_SEPARATOR . $filename . '.' . $fe;
        }
        else
        {
            $file = $path . DIRECTORY_SEPARATOR . $filename . '.' . $fe;
        }

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
