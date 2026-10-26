<?php

namespace Rumenx\Sitemap;

/**
 * Canonical sitemap output formats and config aliases.
 *
 * Render/store only accept packaged view names so `$format` cannot be used
 * to include files outside `src/views/`.
 */
final class SitemapFormat
{
    public const XML = 'xml';
    public const HTML = 'html';
    public const TXT = 'txt';
    public const GOOGLE_NEWS = 'google-news';
    public const XML_MOBILE = 'xml-mobile';
    public const ROR_RSS = 'ror-rss';
    public const ROR_RDF = 'ror-rdf';
    public const SITEMAP_INDEX = 'sitemapindex';

    /**
     * Formats that map to a packaged view (or built-in XML renderer).
     *
     * @var list<string>
     */
    public const ALL = [
        self::XML,
        self::HTML,
        self::TXT,
        self::GOOGLE_NEWS,
        self::XML_MOBILE,
        self::ROR_RSS,
        self::ROR_RDF,
        self::SITEMAP_INDEX,
    ];

    /**
     * Historical SitemapConfig aliases (not view filenames).
     *
     * @var array<string, string>
     */
    private const CONFIG_ALIASES = [
        'rss' => self::ROR_RSS,
        'rdf' => self::ROR_RDF,
    ];

    /**
     * Whether $format can be used with render()/store().
     */
    public static function isRenderable(string $format): bool
    {
        return in_array($format, self::ALL, true);
    }

    /**
     * Whether $format is accepted as SitemapConfig::$defaultFormat.
     */
    public static function isConfigurable(string $format): bool
    {
        return self::isRenderable($format) || isset(self::CONFIG_ALIASES[$format]);
    }
}
