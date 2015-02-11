<?php namespace Roumen\Sitemap;

class Model
{

    /**
     * @var array
     */
    public $items = [];

    /**
     * @var array
     */
    public $sitemaps = [];

    /**
     * @var null
     */
    private $title = null;

    /**
     * @var null
     */
    private $link = null;

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
    private $cacheKey = "Laravel.Sitemap.";

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
     * Populating model variables from configuation file
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->useCache = isset($config['use_cache']) ? $config['use_cache'] : $this->useCache;
        $this->cacheKey = isset($config['cache_key']) ? $config['cache_key'] : $this->cacheKey;
        $this->cacheDuration = isset($config['cache_duration']) ? $config['cache_duration'] : $this->cacheDuration;
        $this->escaping = isset($config['escaping']) ? $config['escaping'] : $this->escaping;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getSitemaps()
    {
        return $this->sitemaps;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function getUseCache()
    {
        return $this->useCache;
    }

    public function getCacheKey()
    {
        return $this->cacheKey;
    }

    public function getCacheDuration()
    {
        return $this->cacheDuration;
    }

    public function getEscaping()
    {
        return $this->escaping;
    }

    public function setEscaping($b)
    {
        $this->escaping = $b;
    }

    public function setItems($items)
    {
        $this->items[] = $items;
    }

    public function resetItems()
    {
        $this->items[] = array_slice($this->items[], 0, 50000);
    }

    public function setSitemaps($sitemaps)
    {
        $this->sitemaps[] = $sitemaps;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setLink($link)
    {
        $this->link = $link;
    }

    public function setUseCache($useCache)
    {
        $this->useCache = $useCache;
    }

    public function setCacheKey($cacheKey)
    {
        $this->cacheKey = $cacheKey;
    }

    public function setCacheDuration($cacheDuration)
    {
        $this->cacheDuration = $cacheDuration;
    }

}