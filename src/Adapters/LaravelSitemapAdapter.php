<?php

namespace Rumenx\Sitemap\Adapters;

use Rumenx\Sitemap\Sitemap;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactory;

/**
 * Laravel adapter for the php-sitemap package.
 *
 * Provides integration with Laravel's cache, config, filesystem, response, and view services.
 */
class LaravelSitemapAdapter
{
    /**
     * The underlying Sitemap instance.
     * @var Sitemap
     */
    protected Sitemap $sitemap;

    /**
     * Laravel cache repository instance.
     * @var CacheRepository
     */
    protected $cache;

    /**
     * Laravel config repository instance.
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * Laravel filesystem instance.
     * @var Filesystem
     */
    protected $file;

    /**
     * Laravel response factory instance.
     * @var ResponseFactory
     */
    protected $response;

    /**
     * Laravel view factory instance.
     * @var ViewFactory
     */
    protected $view;

    /**
     * Create a new LaravelSitemapAdapter instance.
     *
     * @param array<string, mixed> $config Sitemap configuration array.
     * @param CacheRepository $cache Laravel cache repository.
     * @param ConfigRepository $configRepository Laravel config repository.
     * @param Filesystem $file Laravel filesystem.
     * @param ResponseFactory $response Laravel response factory.
     * @param ViewFactory $view Laravel view factory.
     */
    public function __construct(array $config, CacheRepository $cache, ConfigRepository $configRepository, Filesystem $file, ResponseFactory $response, ViewFactory $view)
    {
        $this->sitemap = new Sitemap($config);
        $this->cache = $cache;
        $this->configRepository = $configRepository;
        $this->file = $file;
        $this->response = $response;
        $this->view = $view;
    }

    /**
     * Get the underlying Sitemap instance.
     *
     * @return Sitemap
     */
    public function getSitemap(): Sitemap
    {
        return $this->sitemap;
    }

    // Add Laravel-specific methods for rendering, storing, etc.
}
