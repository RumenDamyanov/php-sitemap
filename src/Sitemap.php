<?php

namespace Rumenx\Sitemap;

/**
 * Framework-agnostic Sitemap class for php-sitemap package.
 *
 * This class provides methods to create and manage sitemaps,
 * allowing you to add URLs, images, videos, and other metadata.
 * It supports rendering the sitemap as XML and can be used
 * in various PHP frameworks or standalone applications.
 */
class Sitemap
{
    /**
     * The underlying Model instance that stores sitemap data.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Create a new Sitemap instance.
     *
     * @param array<string, mixed>|Model $configOrModel Optional configuration array or Model instance.
     *                                   If array, a new Model will be created with it.
     *                                   If Model, it will be used directly.
     */
    public function __construct(array|Model $configOrModel = [])
    {
        if ($configOrModel instanceof Model) {
            $this->model = $configOrModel;
        } else {
            $this->model = new Model($configOrModel);
        }
    }

    /**
     * Get the underlying Model instance.
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Add a single sitemap item using individual parameters.
     *
     * @param string      $loc          The URL of the page.
     * @param string|null $lastmod      Last modification date (optional).
     * @param string|null $priority     Priority of this URL (optional).
     * @param string|null $freq         Change frequency (optional).
     * @param array<int, array<string, mixed>>       $images       Images associated with the URL (optional).
     * @param string|null $title        Title of the page (optional).
     * @param array<int, array<string, mixed>>       $translations Alternate language versions (optional).
     * @param array<int, array<string, mixed>>       $videos       Videos associated with the URL (optional).
     * @param array<string, mixed>       $googlenews   Google News metadata (optional).
     * @param array<int, array<string, mixed>>       $alternates   Alternate URLs (optional).
     *
     * @return void
     */
    public function add(
        string $loc,
        ?string $lastmod = null,
        ?string $priority = null,
        ?string $freq = null,
        array $images = [],
        ?string $title = null,
        array $translations = [],
        array $videos = [],
        array $googlenews = [],
        array $alternates = []
    ): void {
        $params = [
            'loc'           => $loc,
            'lastmod'       => $lastmod,
            'priority'      => $priority,
            'freq'          => $freq,
            'images'        => $images,
            'title'         => $title,
            'translations'  => $translations,
            'videos'        => $videos,
            'googlenews'    => $googlenews,
            'alternates'    => $alternates,
        ];
        $this->addItem($params);
    }

    /**
     * Add one or more sitemap items using an array of parameters.
     *
     * If a multidimensional array is provided, each sub-array is added as an item.
     * Escapes values for XML safety if enabled in the model.
     *
     * @param array<string, mixed>|array<int, array<string, mixed>> $params Item parameters or list of items.
     *
     * @return void
     */
    public function addItem(array $params = []): void
    {
        // If multidimensional, recursively add each
        if (array_is_list($params) && isset($params[0]) && is_array($params[0])) {
            /** @var array<int, array<string, mixed>> $params */
            foreach ($params as $a) {
                $this->addItem($a);
            }
            return;
        }
        // Set defaults
        $defaults = [
            'loc' => '/',
            'lastmod' => null,
            'priority' => null,
            'freq' => null,
            'title' => null,
            'images' => [],
            'translations' => [],
            'alternates' => [],
            'videos' => [],
            'googlenews' => [],
        ];
        $params = array_merge($defaults, $params);
        // Escaping
        if ($this->model->getEscaping()) {
            $params['loc'] = htmlentities($params['loc'], ENT_XML1);
            if ($params['title'] !== null) {
                $params['title'] = htmlentities($params['title'], ENT_XML1);
            }
            foreach (['images', 'translations', 'alternates'] as $arrKey) {
                if (!empty($params[$arrKey])) {
                    foreach ($params[$arrKey] as $k => $arr) {
                        foreach ($arr as $key => $value) {
                            $params[$arrKey][$k][$key] = htmlentities($value, ENT_XML1);
                        }
                    }
                }
            }
            if (!empty($params['videos'])) {
                foreach ($params['videos'] as $k => $video) {
                    if (!empty($video['title'])) {
                        $params['videos'][$k]['title'] = htmlentities($video['title'], ENT_XML1);
                    }
                    if (!empty($video['description'])) {
                        $params['videos'][$k]['description'] = htmlentities($video['description'], ENT_XML1);
                    }
                }
            }
            if (!empty($params['googlenews']) && isset($params['googlenews']['sitename'])) {
                $params['googlenews']['sitename'] = htmlentities($params['googlenews']['sitename'], ENT_XML1);
            }
        }
        $params['googlenews']['sitename'] = $params['googlenews']['sitename'] ?? '';
        $params['googlenews']['language'] = $params['googlenews']['language'] ?? 'en';
        $params['googlenews']['publication_date'] = $params['googlenews']['publication_date'] ?? date('Y-m-d H:i:s');
        // Append item
        $this->model->addItem($params);
    }

    /**
     * Add a sitemap index entry (for sitemap index files).
     *
     * @param string      $loc     The URL of the sitemap file.
     * @param string|null $lastmod Last modification date (optional).
     *
     * @return void
     */
    public function addSitemap(string $loc, ?string $lastmod = null): void
    {
        $this->model->addSitemap([
            'loc'     => $loc,
            'lastmod' => $lastmod,
        ]);
    }

    /**
     * Reset the list of sitemaps (for sitemap index files).
     *
     * @param array<int, array<string, mixed>> $sitemaps Optional new list of sitemaps.
     *
     * @return void
     */
    public function resetSitemaps(array $sitemaps = []): void
    {
        $this->model->resetSitemaps($sitemaps);
    }

    /**
     * Render the sitemap as XML using SimpleXMLElement.
     *
     * @return string XML string representing the sitemap.
     */
    public function renderXml(): string
    {
        $items = $this->model->getItems();
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
        foreach ($items as $item) {
            $url = $xml->addChild('url');
            $url->addChild('loc', $item['loc'] ?? '/');
            if (!empty($item['lastmod'])) {
                $url->addChild('lastmod', $item['lastmod']);
            }
            if (!empty($item['priority'])) {
                $url->addChild('priority', $item['priority']);
            }
            if (!empty($item['freq'])) {
                $url->addChild('changefreq', $item['freq']);
            }
            if (!empty($item['title'])) {
                $url->addChild('title', $item['title']);
            }
        }
        return $xml->asXML();
    }
}
