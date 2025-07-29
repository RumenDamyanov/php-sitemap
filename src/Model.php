<?php

namespace Rumenx\Sitemap;

/**
 * Model class for php-sitemap package.
 *
 * This class is responsible for managing sitemap items and sitemaps.
 * It allows adding items, retrieving them, and managing sitemaps.
 */
class Model
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $items = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $sitemaps = [];

    private bool $escaping = true;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        if (isset($config['escaping'])) {
            $this->escaping = (bool)$config['escaping'];
        }
    }

    public function getEscaping(): bool
    {
        return $this->escaping;
    }

    /**
     * Add a sitemap item to the internal items array.
     *
     * @param array<string, mixed> $item The sitemap item to add.
     * @return void
     */
    public function addItem(array $item): void
    {
        $this->items[] = $item;
    }

    /**
     * Get all sitemap items.
     *
     * @return array<int, array<string, mixed>> The array of sitemap items.
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Add a sitemap index entry to the internal sitemaps array.
     *
     * @param array<string, mixed> $sitemap The sitemap index entry to add.
     * @return void
     */
    public function addSitemap(array $sitemap): void
    {
        $this->sitemaps[] = $sitemap;
    }

    /**
     * Get all sitemap index entries.
     *
     * @return array<int, array<string, mixed>> The array of sitemap index entries.
     */
    public function getSitemaps(): array
    {
        return $this->sitemaps;
    }

    /**
     * Reset the list of sitemap index entries.
     *
     * @param array<int, array<string, mixed>> $sitemaps Optional new list of sitemaps.
     * @return void
     */
    public function resetSitemaps(array $sitemaps = []): void
    {
        $this->sitemaps = $sitemaps;
    }
}
