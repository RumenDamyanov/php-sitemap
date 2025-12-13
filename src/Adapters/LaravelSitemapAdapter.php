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
     * @phpstan-var CacheRepository&\Illuminate\Cache\Repository
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
     * @phpstan-var ResponseFactory&\Illuminate\Routing\ResponseFactory
     */
    protected $response;

    /**
     * Laravel view factory instance.
     * @var ViewFactory
     * @phpstan-var ViewFactory&\Illuminate\View\Factory
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

    /**
     * Render sitemap as HTTP response with proper headers.
     *
     * @param string $format Output format (default: 'xml').
     * @return mixed Laravel HTTP Response
     */
    public function renderResponse(string $format = 'xml')
    {
        $content = $this->sitemap->render($format);

        $contentType = match ($format) {
            'xml' => 'application/xml',
            'html' => 'text/html',
            'txt' => 'text/plain',
            default => 'application/xml',
        };

        return $this->response->make($content, 200, [
            'Content-Type' => $contentType . '; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Render sitemap using Laravel view.
     *
     * @param string $viewName The view template name.
     * @param array<string, mixed> $additionalData Additional data to pass to view.
     * @return mixed Laravel View instance
     */
    public function renderView(string $viewName = 'sitemap.xml', array $additionalData = [])
    {
        $data = array_merge([
            'items' => $this->sitemap->getModel()->getItems(),
            'sitemaps' => $this->sitemap->getModel()->getSitemaps(),
        ], $additionalData);

        return $this->view->make($viewName, $data);
    }

    /**
     * Store sitemap to file using Laravel filesystem.
     *
     * @param string $path Path relative to storage directory (e.g., 'public/sitemap.xml').
     * @param string $format Output format (default: 'xml').
     * @return bool True on success, false on failure.
     */
    public function store(string $path = 'public/sitemap.xml', string $format = 'xml'): bool
    {
        $content = $this->sitemap->render($format);

        // Use Laravel's storage_path helper if available
        $fullPath = function_exists('storage_path') ? storage_path($path) : $path;

        return $this->file->put($fullPath, $content) !== false;
    }

    /**
     * Get cached sitemap or generate new one.
     *
     * @param string $cacheKey Cache key to use.
     * @param int $minutes Cache duration in minutes.
     * @param string $format Output format (default: 'xml').
     * @return string Sitemap content.
     */
    public function cached(string $cacheKey = 'sitemap', int $minutes = 60, string $format = 'xml'): string
    {
        $ttl = $minutes * 60; // Convert to seconds

        return $this->cache->remember($cacheKey, $ttl, function () use ($format) {
            return $this->sitemap->render($format);
        });
    }

    /**
     * Clear sitemap cache.
     *
     * @param string $cacheKey Cache key to clear.
     * @return bool True if cache was cleared.
     */
    public function clearCache(string $cacheKey = 'sitemap'): bool
    {
        return $this->cache->forget($cacheKey);
    }

    /**
     * Render cached sitemap as HTTP response.
     *
     * @param string $cacheKey Cache key to use.
     * @param int $minutes Cache duration in minutes.
     * @param string $format Output format (default: 'xml').
     * @return mixed Laravel HTTP Response
     */
    public function cachedResponse(string $cacheKey = 'sitemap', int $minutes = 60, string $format = 'xml')
    {
        $content = $this->cached($cacheKey, $minutes, $format);

        $contentType = match ($format) {
            'xml' => 'application/xml',
            'html' => 'text/html',
            'txt' => 'text/plain',
            default => 'application/xml',
        };

        return $this->response->make($content, 200, [
            'Content-Type' => $contentType . '; charset=utf-8',
            'Cache-Control' => 'public, max-age=' . ($minutes * 60),
        ]);
    }
}
