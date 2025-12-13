<?php

namespace Rumenx\Sitemap\Validation;

/**
 * Validator class for sitemap data.
 *
 * Validates URLs, priorities, frequencies, and other sitemap parameters
 * according to sitemap protocol specifications.
 */
class SitemapValidator
{
    /**
     * Valid change frequency values according to sitemap protocol.
     *
     * @var array<int, string>
     */
    private const VALID_FREQUENCIES = [
        'always',
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'yearly',
        'never',
    ];

    /**
     * Validate a URL.
     *
     * @param string $url The URL to validate.
     * @return bool True if valid.
     * @throws \InvalidArgumentException If URL is invalid.
     */
    public static function validateUrl(string $url): bool
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('URL cannot be empty');
        }

        // Check if it's a valid URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid URL format: {$url}");
        }

        // Check for http or https scheme
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException("URL must use http or https scheme: {$url}");
        }

        return true;
    }

    /**
     * Validate priority value.
     *
     * @param string|null $priority The priority to validate (0.0 to 1.0).
     * @return bool True if valid.
     * @throws \InvalidArgumentException If priority is invalid.
     */
    public static function validatePriority(?string $priority): bool
    {
        if ($priority === null) {
            return true;
        }

        $value = (float) $priority;

        if ($value < 0.0 || $value > 1.0) {
            throw new \InvalidArgumentException("Priority must be between 0.0 and 1.0, got: {$priority}");
        }

        return true;
    }

    /**
     * Validate change frequency value.
     *
     * @param string|null $freq The frequency to validate.
     * @return bool True if valid.
     * @throws \InvalidArgumentException If frequency is invalid.
     */
    public static function validateFrequency(?string $freq): bool
    {
        if ($freq === null) {
            return true;
        }

        if (!in_array($freq, self::VALID_FREQUENCIES, true)) {
            throw new \InvalidArgumentException(
                "Invalid frequency: {$freq}. Valid values are: " . implode(', ', self::VALID_FREQUENCIES)
            );
        }

        return true;
    }

    /**
     * Validate last modification date.
     *
     * @param string|null $lastmod The date to validate (ISO 8601 format).
     * @return bool True if valid.
     * @throws \InvalidArgumentException If date format is invalid.
     */
    public static function validateLastmod(?string $lastmod): bool
    {
        if ($lastmod === null) {
            return true;
        }

        // Try to parse the date
        $timestamp = strtotime($lastmod);
        if ($timestamp === false) {
            throw new \InvalidArgumentException("Invalid date format: {$lastmod}. Use ISO 8601 format (e.g., " . date('c') . ")");
        }

        return true;
    }

    /**
     * Validate image data.
     *
     * @param array<string, mixed> $image The image data to validate.
     * @return bool True if valid.
     * @throws \InvalidArgumentException If image data is invalid.
     */
    public static function validateImage(array $image): bool
    {
        if (empty($image['url'])) {
            throw new \InvalidArgumentException('Image must have a URL');
        }

        self::validateUrl($image['url']);

        return true;
    }

    /**
     * Validate all parameters for a sitemap item.
     *
     * @param string $loc The URL location.
     * @param string|null $lastmod Last modification date.
     * @param string|null $priority Priority value.
     * @param string|null $freq Change frequency.
     * @param array<int, array<string, mixed>> $images Images array.
     * @return bool True if all validations pass.
     * @throws \InvalidArgumentException If any validation fails.
     */
    public static function validateItem(
        string $loc,
        ?string $lastmod = null,
        ?string $priority = null,
        ?string $freq = null,
        array $images = []
    ): bool {
        self::validateUrl($loc);
        self::validateLastmod($lastmod);
        self::validatePriority($priority);
        self::validateFrequency($freq);

        foreach ($images as $image) {
            self::validateImage($image);
        }

        return true;
    }
}
