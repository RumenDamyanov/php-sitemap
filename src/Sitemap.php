<?php

namespace Rumenx\Sitemap;

use Rumenx\Sitemap\Config\SitemapConfig;
use Rumenx\Sitemap\Interfaces\SitemapInterface;
use Rumenx\Sitemap\Validation\SitemapValidator;

/**
 * Framework-agnostic Sitemap class for php-sitemap package.
 *
 * This class provides methods to create and manage sitemaps,
 * allowing you to add URLs, images, videos, and other metadata.
 * It supports rendering the sitemap as XML and can be used
 * in various PHP frameworks or standalone applications.
 */
class Sitemap implements SitemapInterface
{
    /**
     * The underlying Model instance that stores sitemap data.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Configuration instance.
     *
     * @var SitemapConfig|null
     */
    protected ?SitemapConfig $config = null;

    /**
     * Create a new Sitemap instance.
     *
     * @param array<string, mixed>|Model|SitemapConfig $configOrModel Optional configuration array, Model instance, or SitemapConfig.
     *                                   If array, a new Model will be created with it.
     *                                   If Model, it will be used directly.
     *                                   If SitemapConfig, it will be used for configuration.
     */
    public function __construct(array|Model|SitemapConfig $configOrModel = [])
    {
        if ($configOrModel instanceof Model) {
            $this->model = $configOrModel;
        } elseif ($configOrModel instanceof SitemapConfig) {
            $this->config = $configOrModel;
            $this->model = new Model($configOrModel->toArray());
        } else {
            $this->model = new Model($configOrModel);
        }
    }

    /**
     * Get the configuration instance.
     *
     * @return SitemapConfig|null
     */
    public function getConfig(): ?SitemapConfig
    {
        return $this->config;
    }

    /**
     * Set the configuration instance.
     *
     * @param SitemapConfig $config Configuration to use.
     * @return self
     */
    public function setConfig(SitemapConfig $config): self
    {
        $this->config = $config;
        return $this;
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
     * @return self Returns the Sitemap instance for method chaining.
     * @throws \InvalidArgumentException If strict mode is enabled and validation fails.
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
    ): self {
        // Validate if strict mode is enabled
        if ($this->config?->isStrictMode()) {
            SitemapValidator::validateItem($loc, $lastmod, $priority, $freq, $images);
        }

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
        return $this;
    }

    /**
     * Add one or more sitemap items using an array of parameters.
     *
     * If a multidimensional array is provided, each sub-array is added as an item.
     * Escapes values for XML safety if enabled in the model.
     *
     * @param array<string, mixed>|array<int, array<string, mixed>> $params Item parameters or list of items.
     *
     * @return self Returns the Sitemap instance for method chaining.
     * @throws \InvalidArgumentException If strict mode is enabled and validation fails.
     */
    public function addItem(array $params = []): self
    {
        // If multidimensional, recursively add each
        if (array_is_list($params) && isset($params[0]) && is_array($params[0])) {
            /** @var array<int, array<string, mixed>> $params */
            foreach ($params as $a) {
                $this->addItem($a);
            }
            return $this;
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

        // Validate if strict mode is enabled
        if ($this->config?->isStrictMode()) {
            SitemapValidator::validateItem(
                $params['loc'],
                $params['lastmod'],
                $params['priority'],
                $params['freq'],
                $params['images']
            );
        }
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
        return $this;
    }

    /**
     * Add a sitemap index entry (for sitemap index files).
     *
     * @param string      $loc     The URL of the sitemap file.
     * @param string|null $lastmod Last modification date (optional).
     *
     * @return self Returns the Sitemap instance for method chaining.
     * @throws \InvalidArgumentException If strict mode is enabled and validation fails.
     */
    public function addSitemap(string $loc, ?string $lastmod = null): self
    {
        // Validate if strict mode is enabled
        if ($this->config?->isStrictMode()) {
            SitemapValidator::validateUrl($loc);
            SitemapValidator::validateLastmod($lastmod);
        }

        $this->model->addSitemap([
            'loc'     => $loc,
            'lastmod' => $lastmod,
        ]);
        return $this;
    }

    /**
     * Reset the list of sitemaps (for sitemap index files).
     *
     * @param array<int, array<string, mixed>> $sitemaps Optional new list of sitemaps.
     *
     * @return self Returns the Sitemap instance for method chaining.
     */
    public function resetSitemaps(array $sitemaps = []): self
    {
        $this->model->resetSitemaps($sitemaps);
        return $this;
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

        $result = $xml->asXML();
        if ($result === false) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException('Failed to generate XML sitemap');
            // @codeCoverageIgnoreEnd
        }

        return $result;
    }

    /**
     * Render the sitemap in the specified format.
     *
     * @param string $format Output format (e.g., 'xml', 'html', 'txt').
     * @param string|null $style Optional style or template.
     * @return string
     * @throws \InvalidArgumentException If format is not supported.
     */
    public function render(string $format = 'xml', ?string $style = null): string
    {
        if ($format === 'xml') {
            return $this->renderXml();
        }

        // Use view files for other formats
        $viewFile = __DIR__ . '/views/' . $format . '.php';
        if (!file_exists($viewFile)) {
            throw new \InvalidArgumentException("Unsupported format: {$format}");
        }

        $items = $this->model->getItems();
        $sitemaps = $this->model->getSitemaps();
        $channel = ['title' => '', 'link' => ''];

        ob_start();
        include $viewFile;
        return ob_get_clean();
    }

    /**
     * Generate the sitemap content in the specified format.
     * This is an alias for render() method.
     *
     * @param string $format Output format (e.g., 'xml', 'html').
     * @param string|null $style Optional style or template.
     * @return string
     * @throws \InvalidArgumentException If format is not supported.
     */
    public function generate(string $format = 'xml', ?string $style = null): string
    {
        return $this->render($format, $style);
    }

    /**
     * Store the sitemap to a file in the specified format.
     *
     * @param string $format Output format (e.g., 'xml', 'html').
     * @param string $filename Name of the file to store.
     * @param string|null $path Optional path to store the file.
     * @param string|null $style Optional style or template.
     * @return bool True on success, false on failure.
     */
    public function store(string $format = 'xml', string $filename = 'sitemap', ?string $path = null, ?string $style = null): bool
    {
        $content = $this->render($format, $style);

        // Determine full path
        $directory = $path ?? getcwd();
        $fullPath = rtrim($directory, '/') . '/' . $filename;

        // Add extension if not present
        if (!str_ends_with($fullPath, '.' . $format)) {
            $fullPath .= '.' . $format;
        }

        // Ensure directory exists
        $dir = dirname($fullPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException("Failed to create directory: {$dir}");
            // @codeCoverageIgnoreEnd
        }

        // Write file
        $result = file_put_contents($fullPath, $content);

        return $result !== false;
    }
}
