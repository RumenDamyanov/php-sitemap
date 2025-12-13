<?php

namespace Rumenx\Sitemap\Adapters;

use Rumenx\Sitemap\Sitemap;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * Create HTTP response with sitemap content.
     *
     * @param string $format Output format (default: 'xml').
     * @return Response Symfony HTTP Response
     */
    public function createResponse(string $format = 'xml'): Response
    {
        $content = $this->sitemap->render($format);

        $contentType = match ($format) {
            'xml' => 'application/xml',
            'html' => 'text/html',
            'txt' => 'text/plain',
            default => 'application/xml',
        };

        return new Response($content, 200, [
            'Content-Type' => $contentType . '; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Store sitemap to file.
     *
     * @param string $path Full path where to store the sitemap.
     * @param string $format Output format (default: 'xml').
     * @return bool True on success, false on failure.
     */
    public function store(string $path, string $format = 'xml'): bool
    {
        $content = $this->sitemap->render($format);

        // Ensure directory exists
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException("Failed to create directory: {$directory}");
            // @codeCoverageIgnoreEnd
        }

        // Add extension if not present
        if (!str_ends_with($path, '.' . $format)) {
            $path .= '.' . $format;
        }

        return file_put_contents($path, $content) !== false;
    }

    /**
     * Get sitemap as downloadable response.
     *
     * @param string $filename Filename for download (default: 'sitemap.xml').
     * @param string $format Output format (default: 'xml').
     * @return Response Symfony HTTP Response with download headers
     */
    public function download(string $filename = 'sitemap.xml', string $format = 'xml'): Response
    {
        $content = $this->sitemap->render($format);

        // Add extension if not present
        if (!str_ends_with($filename, '.' . $format)) {
            $filename .= '.' . $format;
        }

        $contentType = match ($format) {
            'xml' => 'application/xml',
            'html' => 'text/html',
            'txt' => 'text/plain',
            default => 'application/xml',
        };

        return new Response($content, 200, [
            'Content-Type' => $contentType . '; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Create a gzipped sitemap response.
     *
     * @param string $format Output format (default: 'xml').
     * @return Response Symfony HTTP Response with gzipped content
     */
    public function createGzippedResponse(string $format = 'xml'): Response
    {
        $content = $this->sitemap->render($format);
        $gzippedContent = gzencode($content, 9);

        if ($gzippedContent === false) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException('Failed to gzip sitemap content');
            // @codeCoverageIgnoreEnd
        }

        return new Response($gzippedContent, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Content-Encoding' => 'gzip',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
