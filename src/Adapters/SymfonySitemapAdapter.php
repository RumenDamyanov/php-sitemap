<?php

namespace Rumenx\Sitemap\Adapters;

use Rumenx\Sitemap\Sitemap;

/**
 * Symfony adapter for the php-sitemap package.
 *
 * Provides integration points for Symfony-based applications.
 */
class SymfonySitemapAdapter
{
    /**
     * The underlying Sitemap instance.
     * @var Sitemap
     */
    protected Sitemap $sitemap;

    /**
     * Create a new SymfonySitemapAdapter instance.
     *
     * @param array<string, mixed> $config Optional sitemap configuration array.
     */
    public function __construct(array $config = [])
    {
        $this->sitemap = new Sitemap($config);
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

    // Add Symfony-specific methods for rendering, storing, etc.
}
