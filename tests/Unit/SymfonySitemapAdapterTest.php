<?php

/**
 * Unit tests for the SymfonySitemapAdapter class and its integration points.
 */

require_once __DIR__ . '/../Stubs/SymfonyStubs.php';

use Rumenx\Sitemap\Adapters\SymfonySitemapAdapter;
use Rumenx\Sitemap\Sitemap;
use Symfony\Component\HttpFoundation\Response;

test('SymfonySitemapAdapter can be instantiated', function () {
    $adapter = new SymfonySitemapAdapter([]);
    expect($adapter)->toBeInstanceOf(SymfonySitemapAdapter::class);
});

test('SymfonySitemapAdapter holds a Sitemap instance', function () {
    $adapter = new SymfonySitemapAdapter([]);
    $sitemap = $adapter->getSitemap();
    expect($sitemap)->toBeInstanceOf(Sitemap::class);
});

test('SymfonySitemapAdapter createResponse() returns Response with correct content', function () {
    $adapter = new SymfonySitemapAdapter();
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $response = $adapter->createResponse();

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getContent())->toContain('<loc>https://example.com/</loc>');
    expect($response->headers->get('Content-Type'))->toContain('application/xml');
    expect($response->getStatusCode())->toBe(200);
});

test('SymfonySitemapAdapter createResponse() supports different formats', function () {
    $adapter = new SymfonySitemapAdapter();
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $xmlResponse = $adapter->createResponse('xml');
    expect($xmlResponse->headers->get('Content-Type'))->toContain('application/xml');
});

test('SymfonySitemapAdapter store() saves file to disk', function () {
    $adapter = new SymfonySitemapAdapter();
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $tempDir = sys_get_temp_dir() . '/php-sitemap-symfony-test-' . uniqid();
    mkdir($tempDir, 0755, true);
    $filePath = $tempDir . '/sitemap';

    $result = $adapter->store($filePath);

    expect($result)->toBeTrue();
    expect(file_exists($filePath . '.xml'))->toBeTrue();

    $content = file_get_contents($filePath . '.xml');
    expect($content)->toContain('<loc>https://example.com/</loc>');

    // Cleanup
    unlink($filePath . '.xml');
    rmdir($tempDir);
});

test('SymfonySitemapAdapter store() creates nested directories', function () {
    $adapter = new SymfonySitemapAdapter();
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $tempDir = sys_get_temp_dir() . '/php-sitemap-symfony-nested-' . uniqid() . '/sub/dir';
    $filePath = $tempDir . '/sitemap';

    $result = $adapter->store($filePath);

    expect($result)->toBeTrue();
    expect(file_exists($filePath . '.xml'))->toBeTrue();

    // Cleanup
    unlink($filePath . '.xml');
    rmdir($tempDir);
    rmdir(dirname($tempDir));
    rmdir(dirname(dirname($tempDir)));
});

test('SymfonySitemapAdapter download() returns Response with download headers', function () {
    $adapter = new SymfonySitemapAdapter();
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $response = $adapter->download('my-sitemap.xml');

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getContent())->toContain('<loc>https://example.com/</loc>');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->headers->get('Content-Disposition'))->toContain('my-sitemap.xml');
});

test('SymfonySitemapAdapter download() adds extension if missing', function () {
    $adapter = new SymfonySitemapAdapter();
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $response = $adapter->download('sitemap');

    expect($response->headers->get('Content-Disposition'))->toContain('sitemap.xml');
});

test('SymfonySitemapAdapter createGzippedResponse() returns gzipped content', function () {
    $adapter = new SymfonySitemapAdapter();
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $response = $adapter->createGzippedResponse();

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->headers->get('Content-Encoding'))->toBe('gzip');

    // Verify content is actually gzipped
    $content = $response->getContent();
    $decompressed = gzdecode($content);
    expect($decompressed)->toContain('<loc>https://example.com/</loc>');
});

test('SymfonySitemapAdapter createResponse() supports html format', function () {
    $adapter = new SymfonySitemapAdapter();
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $response = $adapter->createResponse('html');

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->headers->get('Content-Type'))->toContain('text/html');
});

test('SymfonySitemapAdapter createResponse() supports txt format', function () {
    $adapter = new SymfonySitemapAdapter();
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $response = $adapter->createResponse('txt');

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->headers->get('Content-Type'))->toContain('text/plain');
});

test('SymfonySitemapAdapter download() supports html format', function () {
    $adapter = new SymfonySitemapAdapter();
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $response = $adapter->download('sitemap.html', 'html');

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->headers->get('Content-Type'))->toContain('text/html');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment; filename="sitemap.html"');
});

test('SymfonySitemapAdapter download() supports txt format', function () {
    $adapter = new SymfonySitemapAdapter();
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $response = $adapter->download('sitemap.txt', 'txt');

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->headers->get('Content-Type'))->toContain('text/plain');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment; filename="sitemap.txt"');
});

test('SymfonySitemapAdapter createResponse() uses default content type for unknown format', function () {
    $adapter = new SymfonySitemapAdapter();
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $response = $adapter->createResponse('ror-rss');

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->headers->get('Content-Type'))->toContain('application/xml'); // default
});

test('SymfonySitemapAdapter download() uses default content type for unknown format', function () {
    $adapter = new SymfonySitemapAdapter();
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $response = $adapter->download('sitemap.rss', 'ror-rss');

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->headers->get('Content-Type'))->toContain('application/xml'); // default
});
