<?php namespace Roumen\Sitemap;

/**
 * Model class for laravel-sitemap package.
 *
 * @author Roumen Damianoff <roumen@crimsson.com>
 * @version 2.6.5
 * @link http://roumen.it/projects/laravel-sitemap
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

use Illuminate\Support\Facades\Cache;

class Model
{
    /**
     * @var boolean
     */
    public $testing = false;

    /**
     * @var array
     */
    private $items = [];

    /**
     * @var array
     */
    private $sitemaps = [];

    /**
     * @var string
     */
    private $title = null;

    /**
     * @var string
     */
    private $link = null;

    /**
     * Enable or disable xsl styles
     *
     * @var boolean
     */
    private $useStyles = true;

    /**
     * Set custom location for xsl styles (must end with slash)
     *
     * @var string
     */
    private $sloc = null;

    /**
     * Enable or disable cache
     *
     * @var boolean
     */
    private $useCache = false;

    /**
     * Unique cache key
     *
     * @var string
     */
    private $cacheKey = "laravel-sitemap.";

    /**
     * Cache duration, can be int or timestamp
     *
     * @var Carbon|Datetime|int
     */
    private $cacheDuration = 3600;

    /**
     * Escaping html entities
     *
     * @var boolean
     */
    private $escaping = true;

    /**
     * Use limitSize() for big sitemaps
     *
     * @var boolean
     */
    private $useLimitSize = false;

    /**
     * Custom max size for limitSize()
     *
     * @var boolean
     */
    private $maxSize = null;


    /**
     * Populating model variables from configuation file
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->useCache = isset($config['use_cache']) ? $config['use_cache'] : $this->useCache;
        $this->cacheKey = isset($config['cache_key']) ? $config['cache_key'] : $this->cacheKey;
        $this->cacheDuration = isset($config['cache_duration']) ? $config['cache_duration'] : $this->cacheDuration;
        $this->escaping = isset($config['escaping']) ? $config['escaping'] : $this->escaping;
        $this->useLimitSize = isset($config['use_limit_size']) ? $config['use_limit_size'] : $this->useLimitSize;
        $this->useStyles = isset($config['use_styles']) ? $config['use_styles'] : $this->useStyles;
        $this->sloc = isset($config['styles_location']) ? $config['styles_location'] : $this->sloc;
        $this->maxSize = isset($config['max_size']) ? $config['max_size'] : $this->maxSize;
        $this->testing = isset($config['testing']) ? $config['testing'] : $this->testing;
    }


    /**
     * Returns $items array
     *
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Returns $sitemaps array
     *
     * @return array
     */
    public function getSitemaps()
    {
        return $this->sitemaps;
    }


    /**
     * Returns $title value
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }


    /**
     * Returns $link value
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }


    /**
     * Returns $useStyles value
     *
     * @return boolean
     */
    public function getUseStyles()
    {
        return $this->useStyles;
    }


    /**
     * Returns $sloc value
     *
     * @return string
     */
    public function getSloc()
    {
        return $this->sloc;
    }


    /**
     * Returns $useCache value
     *
     * @return boolean
     */
    public function getUseCache()
    {
        return $this->useCache;
    }


    /**
     * Returns $CacheKey value
     *
     * @return string
     */
    public function getCacheKey()
    {
        return $this->cacheKey;
    }


    /**
     * Returns $CacheDuration value
     *
     * @return string
     */
    public function getCacheDuration()
    {
        return $this->cacheDuration;
    }


    /**
     * Returns $escaping value
     *
     * @return boolean
     */
    public function getEscaping()
    {
        return $this->escaping;
    }


    /**
     * Returns $useLimitSize value
     *
     * @return boolean
     */
    public function getUseLimitSize()
    {
        return $this->useLimitSize;
    }


    /**
     * Returns $maxSize value
     *
     * @param integer $maxSize
     */
    public function getMaxSize()
    {
        return $this->maxSize;
    }


    /**
     * Sets $escaping value
     *
     * @param boolean $escaping
     */
    public function setEscaping($b)
    {
        $this->escaping = $b;
    }


    /**
     * Adds item to $items array
     *
     * @param array $item
     */
    public function setItems($items)
    {
        $this->items[] = $items;
    }


    /**
     * Adds sitemap to $sitemaps array
     *
     * @param array $sitemap
     */
    public function setSitemaps($sitemap)
    {
        $this->sitemaps[] = $sitemap;
    }


    /**
     * Sets $title value
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }


    /**
     * Sets $link value
     *
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }


    /**
     * Sets $useStyles value
     *
     * @param boolean $useStyles
     */
    public function setUseStyles($useStyles)
    {
        $this->useStyles = $useStyles;
    }


    /**
     * Sets $sloc value
     *
     * @param string $sloc
     */
    public function setSloc($sloc)
    {
        $this->sloc = $sloc;
    }


    /**
     * Sets $useLimitSize value
     *
     * @param boolean $useLimitSize
     */
    public function setUseLimitSize($useLimitSize)
    {
        $this->useLimitSize = $useLimitSize;
    }


    /**
     * Sets $maxSize value
     *
     * @param integer $maxSize
     */
    public function setMaxSize($maxSize)
    {
        $this->maxSize = $maxSize;
    }


    /**
     * Limit size of $items array to 50000 elements (1000 for google-news)
     *
     */
    public function limitSize($max=50000)
    {
        $this->items = array_slice($this->items, 0, $max);
    }


    /**
     * Reset $items array
     *
     * @param array $items
     */
    public function resetItems($items=[])
    {
        $this->items = $items;
    }


    /**
     * Reset $sitemaps array
     *
     * @param array $sitemaps
     */
    public function resetSitemaps($sitemaps=[])
    {
        $this->sitemaps = $sitemaps;
    }


    /**
     * Set use cache value
     *
     * @param boolean $useCache
     */
    public function setUseCache($useCache)
    {
        $this->useCache = $useCache;
    }


    /**
     * Set cache key value
     *
     * @param string $cacheKey
     */
    public function setCacheKey($cacheKey)
    {
        $this->cacheKey = $cacheKey;
    }


    /**
     * Set cache duration value
     *
     * @param Carbon|Datetime|int $cacheDuration
     */
    public function setCacheDuration($cacheDuration)
    {
        $this->cacheDuration = $cacheDuration;
    }


}