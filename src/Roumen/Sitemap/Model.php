<?php

namespace Roumen\Sitemap;

class Model
{

    protected $items = array();
    private $title = null;
    private $link = null;

    /**
     * Enable or disable cache
     * @var boolean
     */
    private $useCache = false;

    /**
     * Unique cache key
     * @var string
     */
    private $cacheKey = "Laravel.Sitemap.";

    /**
     * Cache duration, can be int or timestamp
     * @var Carbon|Datetime|int
     */
    private $cacheDuration = 3600;

    /**
     * Populating model variables from configuation file
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->useCache = isset($config['use_cache']) ? $config['use_cache'] : $this->useCache;
        $this->cacheKey = isset($config['cache_key']) ? $config['cache_key'] : $this->cacheKey;
        $this->cacheDuration = isset($config['cache_duration']) ? $config['cache_duration'] : $this->cacheDuration;
    }

    public function getItems()
    {
        return $this->items;
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

    public function setItems($items)
    {
        $this->items[] = $items;
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
