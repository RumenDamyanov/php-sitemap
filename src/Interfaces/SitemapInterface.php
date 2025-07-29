<?php

namespace Rumenx\Sitemap\Interfaces;

/**
 * Interface for Sitemap implementations.
 *
 * Defines the contract for adding items, managing sitemaps, rendering, and storing sitemap data.
 */
interface SitemapInterface
{
    /**
     * Add a single sitemap item using individual parameters.
     *
     * @param string $loc The URL of the page.
     * @param string|null $lastmod Last modification date (optional).
     * @param string|null $priority Priority of this URL (optional).
     * @param string|null $freq Change frequency (optional).
     * @param array<int, array<string, mixed>> $images Images associated with the URL (optional).
     * @param string|null $title Title of the page (optional).
     * @param array<int, array<string, mixed>> $translations Alternate language versions (optional).
     * @param array<int, array<string, mixed>> $videos Videos associated with the URL (optional).
     * @param array<string, mixed> $googlenews Google News metadata (optional).
     * @param array<int, array<string, mixed>> $alternates Alternate URLs (optional).
     * @return void
     */
    public function add($loc, $lastmod = null, $priority = null, $freq = null, $images = [], $title = null, $translations = [], $videos = [], $googlenews = [], $alternates = []);

    /**
     * Add one or more sitemap items using an array of parameters.
     *
     * @param array<string, mixed> $params Item parameters or list of items.
     * @return void
     */
    public function addItem($params = []);

    /**
     * Add a sitemap index entry (for sitemap index files).
     *
     * @param string $loc The URL of the sitemap file.
     * @param string|null $lastmod Last modification date (optional).
     * @return void
     */
    public function addSitemap($loc, $lastmod = null);

    /**
     * Reset the list of sitemaps (for sitemap index files).
     *
     * @param array<int, array<string, mixed>> $sitemaps Optional new list of sitemaps.
     * @return void
     */
    public function resetSitemaps($sitemaps = []);

    /**
     * Render the sitemap in the specified format.
     *
     * @param string $format Output format (e.g., 'xml', 'html').
     * @param string|null $style Optional style or template.
     * @return string
     */
    public function render($format = 'xml', $style = null);

    /**
     * Generate the sitemap content in the specified format.
     *
     * @param string $format Output format (e.g., 'xml', 'html').
     * @param string|null $style Optional style or template.
     * @return string
     */
    public function generate($format = 'xml', $style = null);

    /**
     * Store the sitemap to a file in the specified format.
     *
     * @param string $format Output format (e.g., 'xml', 'html').
     * @param string $filename Name of the file to store.
     * @param string|null $path Optional path to store the file.
     * @param string|null $style Optional style or template.
     * @return bool True on success, false on failure.
     */
    public function store($format = 'xml', $filename = 'sitemap', $path = null, $style = null);
}
