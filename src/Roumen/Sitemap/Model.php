<?php

namespace Roumen\Sitemap;

use Carbon\Carbon as Carbon;

class Model
{

    public $items = array();
    private $title = null;
    private $link = null;
    private $useCache = false;
    private $cacheKey = "Laravel.Sitemap.";
    private $cacheDuration = 3600;

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
        return Carbon::now()->addMinutes($this->cacheDuration);
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
        $this->cacheDuration = intval($cacheDuration);
    }

}