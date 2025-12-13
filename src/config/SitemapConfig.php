<?php

namespace Rumenx\Sitemap\Config;

/**
 * Type-safe configuration class for Sitemap.
 *
 * Provides a structured way to manage sitemap configuration with
 * validation and sensible defaults.
 */
class SitemapConfig
{
    /**
     * Create a new configuration instance.
     *
     * @param bool $escaping Enable/disable URL escaping (default: true)
     * @param bool $useCache Enable/disable caching (default: false)
     * @param string|null $cachePath Custom cache path (default: null)
     * @param bool $useLimitSize Enable sitemap size limits (default: false)
     * @param int $maxSize Maximum sitemap size in bytes (default: 10MB)
     * @param bool $useGzip Enable gzip compression (default: false)
     * @param bool $useStyles Enable XSL stylesheets (default: true)
     * @param string|null $domain Base domain for URLs (default: null)
     * @param bool $strictMode Enable strict validation (default: false)
     * @param string $defaultFormat Default rendering format (default: 'xml')
     */
    public function __construct(
        private bool $escaping = true,
        private bool $useCache = false,
        private ?string $cachePath = null,
        private bool $useLimitSize = false,
        private int $maxSize = 10485760, // 10MB
        private bool $useGzip = false,
        private bool $useStyles = true,
        private ?string $domain = null,
        private bool $strictMode = false,
        private string $defaultFormat = 'xml'
    ) {
        $this->validate();
    }

    /**
     * Validate configuration values.
     *
     * @throws \InvalidArgumentException If any value is invalid.
     */
    private function validate(): void
    {
        if ($this->maxSize <= 0) {
            throw new \InvalidArgumentException('maxSize must be greater than 0');
        }

        if (!in_array($this->defaultFormat, ['xml', 'txt', 'html', 'rss', 'rdf', 'google-news'], true)) {
            throw new \InvalidArgumentException("Invalid default format: {$this->defaultFormat}");
        }

        if ($this->domain !== null && !filter_var($this->domain, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid domain: {$this->domain}");
        }
    }

    /**
     * Create configuration from array.
     *
     * @param array<string, mixed> $config Configuration array.
     * @return self
     */
    public static function fromArray(array $config): self
    {
        return new self(
            escaping: $config['escaping'] ?? true,
            useCache: $config['use_cache'] ?? false,
            cachePath: $config['cache_path'] ?? null,
            useLimitSize: $config['use_limit_size'] ?? false,
            maxSize: $config['max_size'] ?? 10485760,
            useGzip: $config['use_gzip'] ?? false,
            useStyles: $config['use_styles'] ?? true,
            domain: $config['domain'] ?? null,
            strictMode: $config['strict_mode'] ?? false,
            defaultFormat: $config['default_format'] ?? 'xml'
        );
    }

    /**
     * Export configuration to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'escaping' => $this->escaping,
            'use_cache' => $this->useCache,
            'cache_path' => $this->cachePath,
            'use_limit_size' => $this->useLimitSize,
            'max_size' => $this->maxSize,
            'use_gzip' => $this->useGzip,
            'use_styles' => $this->useStyles,
            'domain' => $this->domain,
            'strict_mode' => $this->strictMode,
            'default_format' => $this->defaultFormat,
        ];
    }

    /**
     * Get escaping setting.
     */
    public function isEscaping(): bool
    {
        return $this->escaping;
    }

    /**
     * Set escaping.
     */
    public function setEscaping(bool $escaping): self
    {
        $this->escaping = $escaping;
        return $this;
    }

    /**
     * Check if caching is enabled.
     */
    public function isCacheEnabled(): bool
    {
        return $this->useCache;
    }

    /**
     * Enable/disable cache.
     */
    public function setUseCache(bool $useCache): self
    {
        $this->useCache = $useCache;
        return $this;
    }

    /**
     * Get cache path.
     */
    public function getCachePath(): ?string
    {
        return $this->cachePath;
    }

    /**
     * Set cache path.
     */
    public function setCachePath(?string $cachePath): self
    {
        $this->cachePath = $cachePath;
        return $this;
    }

    /**
     * Check if size limiting is enabled.
     */
    public function isLimitSizeEnabled(): bool
    {
        return $this->useLimitSize;
    }

    /**
     * Enable/disable size limiting.
     */
    public function setUseLimitSize(bool $useLimitSize): self
    {
        $this->useLimitSize = $useLimitSize;
        return $this;
    }

    /**
     * Get maximum size in bytes.
     */
    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    /**
     * Set maximum size in bytes.
     */
    public function setMaxSize(int $maxSize): self
    {
        if ($maxSize <= 0) {
            throw new \InvalidArgumentException('maxSize must be greater than 0');
        }
        $this->maxSize = $maxSize;
        return $this;
    }

    /**
     * Check if gzip compression is enabled.
     */
    public function isGzipEnabled(): bool
    {
        return $this->useGzip;
    }

    /**
     * Enable/disable gzip compression.
     */
    public function setUseGzip(bool $useGzip): self
    {
        $this->useGzip = $useGzip;
        return $this;
    }

    /**
     * Check if styles are enabled.
     */
    public function areStylesEnabled(): bool
    {
        return $this->useStyles;
    }

    /**
     * Enable/disable styles.
     */
    public function setUseStyles(bool $useStyles): self
    {
        $this->useStyles = $useStyles;
        return $this;
    }

    /**
     * Get base domain.
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Set base domain.
     */
    public function setDomain(?string $domain): self
    {
        if ($domain !== null && !filter_var($domain, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid domain: {$domain}");
        }
        $this->domain = $domain;
        return $this;
    }

    /**
     * Check if strict mode is enabled.
     */
    public function isStrictMode(): bool
    {
        return $this->strictMode;
    }

    /**
     * Enable/disable strict mode.
     */
    public function setStrictMode(bool $strictMode): self
    {
        $this->strictMode = $strictMode;
        return $this;
    }

    /**
     * Get default format.
     */
    public function getDefaultFormat(): string
    {
        return $this->defaultFormat;
    }

    /**
     * Set default format.
     */
    public function setDefaultFormat(string $defaultFormat): self
    {
        if (!in_array($defaultFormat, ['xml', 'txt', 'html', 'rss', 'rdf', 'google-news'], true)) {
            throw new \InvalidArgumentException("Invalid default format: {$defaultFormat}");
        }
        $this->defaultFormat = $defaultFormat;
        return $this;
    }
}
