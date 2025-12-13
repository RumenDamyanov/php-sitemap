<?php

/**
 * Unit tests for the LaravelSitemapAdapter class and its integration with Laravel contracts.
 */

require_once __DIR__ . '/../Stubs/LaravelStubs.php';

use Rumenx\Sitemap\Adapters\LaravelSitemapAdapter;
use Rumenx\Sitemap\Sitemap;

test('LaravelSitemapAdapter can be instantiated', function () {
    $cache = new class implements \Illuminate\Contracts\Cache\Repository {
    };
    $config = new class implements \Illuminate\Contracts\Config\Repository {
    };
    $file = new class extends \Illuminate\Filesystem\Filesystem {
    };
    $response = new class implements \Illuminate\Contracts\Routing\ResponseFactory {
    };
    $view = new class implements \Illuminate\Contracts\View\Factory {
    };
    $adapter = new LaravelSitemapAdapter([], $cache, $config, $file, $response, $view);
    expect($adapter)->toBeInstanceOf(LaravelSitemapAdapter::class);
});

test('LaravelSitemapAdapter holds a Sitemap instance', function () {
    $cache = new class implements \Illuminate\Contracts\Cache\Repository {
    };
    $config = new class implements \Illuminate\Contracts\Config\Repository {
    };
    $file = new class extends \Illuminate\Filesystem\Filesystem {
    };
    $response = new class implements \Illuminate\Contracts\Routing\ResponseFactory {
    };
    $view = new class implements \Illuminate\Contracts\View\Factory {
    };
    $adapter = new LaravelSitemapAdapter([], $cache, $config, $file, $response, $view);
    $sitemap = $adapter->getSitemap();
    expect($sitemap)->toBeInstanceOf(Sitemap::class);
});

test('LaravelSitemapAdapter renderResponse() returns response with correct content type', function () {
    $cache = new class implements \Illuminate\Contracts\Cache\Repository {
    };
    $config = new class implements \Illuminate\Contracts\Config\Repository {
    };
    $file = new class extends \Illuminate\Filesystem\Filesystem {
    };
    $response = new class implements \Illuminate\Contracts\Routing\ResponseFactory {
        public function make($content, $status = 200, array $headers = [])
        {
            return (object) [
                'content' => $content,
                'status' => $status,
                'headers' => $headers,
            ];
        }
    };
    $view = new class implements \Illuminate\Contracts\View\Factory {
    };

    $adapter = new LaravelSitemapAdapter([], $cache, $config, $file, $response, $view);
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $result = $adapter->renderResponse();

    expect($result->content)->toContain('<loc>https://example.com/</loc>');
    expect($result->headers['Content-Type'])->toContain('application/xml');
});

test('LaravelSitemapAdapter renderView() passes correct data to view', function () {
    $cache = new class implements \Illuminate\Contracts\Cache\Repository {
    };
    $config = new class implements \Illuminate\Contracts\Config\Repository {
    };
    $file = new class extends \Illuminate\Filesystem\Filesystem {
    };
    $response = new class implements \Illuminate\Contracts\Routing\ResponseFactory {
    };
    $view = new class implements \Illuminate\Contracts\View\Factory {
        public $lastViewData = [];
        public function make($view, $data = [], $mergeData = [])
        {
            $this->lastViewData = $data;
            return (object) ['data' => $data];
        }
    };

    $adapter = new LaravelSitemapAdapter([], $cache, $config, $file, $response, $view);
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $adapter->renderView('sitemap.xml');

    expect($view->lastViewData)->toHaveKey('items');
    expect($view->lastViewData)->toHaveKey('sitemaps');
    expect($view->lastViewData['items'])->toHaveCount(1);
});

test('LaravelSitemapAdapter store() saves file using filesystem', function () {
    $cache = new class implements \Illuminate\Contracts\Cache\Repository {
    };
    $config = new class implements \Illuminate\Contracts\Config\Repository {
    };
    $file = new class extends \Illuminate\Filesystem\Filesystem {
        public $lastPutPath = null;
        public $lastPutContent = null;
        public function put($path, $contents, $lock = false)
        {
            $this->lastPutPath = $path;
            $this->lastPutContent = $contents;
            return true;
        }
    };
    $response = new class implements \Illuminate\Contracts\Routing\ResponseFactory {
    };
    $view = new class implements \Illuminate\Contracts\View\Factory {
    };

    $adapter = new LaravelSitemapAdapter([], $cache, $config, $file, $response, $view);
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $result = $adapter->store('public/sitemap.xml');

    expect($result)->toBeTrue();
    expect($file->lastPutContent)->toContain('<loc>https://example.com/</loc>');
});

test('LaravelSitemapAdapter cached() uses cache repository', function () {
    $cache = new class implements \Illuminate\Contracts\Cache\Repository {
        public $rememberedKey = null;
        public $rememberedTtl = null;
        public function remember($key, $ttl, $callback)
        {
            $this->rememberedKey = $key;
            $this->rememberedTtl = $ttl;
            return $callback();
        }
    };
    $config = new class implements \Illuminate\Contracts\Config\Repository {
    };
    $file = new class extends \Illuminate\Filesystem\Filesystem {
    };
    $response = new class implements \Illuminate\Contracts\Routing\ResponseFactory {
    };
    $view = new class implements \Illuminate\Contracts\View\Factory {
    };

    $adapter = new LaravelSitemapAdapter([], $cache, $config, $file, $response, $view);
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $content = $adapter->cached('test-sitemap', 30);

    expect($cache->rememberedKey)->toBe('test-sitemap');
    expect($cache->rememberedTtl)->toBe(1800); // 30 minutes * 60
    expect($content)->toContain('<loc>https://example.com/</loc>');
});

test('LaravelSitemapAdapter clearCache() calls cache forget', function () {
    $cache = new class implements \Illuminate\Contracts\Cache\Repository {
        public $forgottenKey = null;
        public function forget($key)
        {
            $this->forgottenKey = $key;
            return true;
        }
    };
    $config = new class implements \Illuminate\Contracts\Config\Repository {
    };
    $file = new class extends \Illuminate\Filesystem\Filesystem {
    };
    $response = new class implements \Illuminate\Contracts\Routing\ResponseFactory {
    };
    $view = new class implements \Illuminate\Contracts\View\Factory {
    };

    $adapter = new LaravelSitemapAdapter([], $cache, $config, $file, $response, $view);
    $result = $adapter->clearCache('test-sitemap');

    expect($result)->toBeTrue();
    expect($cache->forgottenKey)->toBe('test-sitemap');
});

test('LaravelSitemapAdapter renderResponse() supports html format', function () {
    $cache = new class implements \Illuminate\Contracts\Cache\Repository {
    };
    $config = new class implements \Illuminate\Contracts\Config\Repository {
    };
    $file = new class extends \Illuminate\Filesystem\Filesystem {
    };
    $response = new class implements \Illuminate\Contracts\Routing\ResponseFactory {
        public function make($content, $status = 200, array $headers = [])
        {
            return (object) [
                'content' => $content,
                'status' => $status,
                'headers' => $headers,
            ];
        }
    };
    $view = new class implements \Illuminate\Contracts\View\Factory {
    };

    $adapter = new LaravelSitemapAdapter([], $cache, $config, $file, $response, $view);
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $result = $adapter->renderResponse('html');

    expect($result->headers['Content-Type'])->toContain('text/html');
});

test('LaravelSitemapAdapter renderResponse() supports txt format', function () {
    $cache = new class implements \Illuminate\Contracts\Cache\Repository {
    };
    $config = new class implements \Illuminate\Contracts\Config\Repository {
    };
    $file = new class extends \Illuminate\Filesystem\Filesystem {
    };
    $response = new class implements \Illuminate\Contracts\Routing\ResponseFactory {
        public function make($content, $status = 200, array $headers = [])
        {
            return (object) [
                'content' => $content,
                'status' => $status,
                'headers' => $headers,
            ];
        }
    };
    $view = new class implements \Illuminate\Contracts\View\Factory {
    };

    $adapter = new LaravelSitemapAdapter([], $cache, $config, $file, $response, $view);
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $result = $adapter->renderResponse('txt');

    expect($result->headers['Content-Type'])->toContain('text/plain');
});

test('LaravelSitemapAdapter cachedResponse() returns cached response', function () {
    $cache = new class implements \Illuminate\Contracts\Cache\Repository {
        public function remember($key, $ttl, $callback)
        {
            return $callback();
        }
    };
    $config = new class implements \Illuminate\Contracts\Config\Repository {
    };
    $file = new class extends \Illuminate\Filesystem\Filesystem {
    };
    $response = new class implements \Illuminate\Contracts\Routing\ResponseFactory {
        public function make($content, $status = 200, array $headers = [])
        {
            return (object) [
                'content' => $content,
                'status' => $status,
                'headers' => $headers,
            ];
        }
    };
    $view = new class implements \Illuminate\Contracts\View\Factory {
    };

    $adapter = new LaravelSitemapAdapter([], $cache, $config, $file, $response, $view);
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $result = $adapter->cachedResponse('sitemap-key', 60);

    expect($result->content)->toContain('<loc>https://example.com/</loc>');
    expect($result->headers['Content-Type'])->toContain('application/xml');
    expect($result->headers['Cache-Control'])->toContain('max-age=3600'); // 60 * 60
});

test('LaravelSitemapAdapter cachedResponse() supports different formats', function () {
    $cache = new class implements \Illuminate\Contracts\Cache\Repository {
        public function remember($key, $ttl, $callback)
        {
            return $callback();
        }
    };
    $config = new class implements \Illuminate\Contracts\Config\Repository {
    };
    $file = new class extends \Illuminate\Filesystem\Filesystem {
    };
    $response = new class implements \Illuminate\Contracts\Routing\ResponseFactory {
        public function make($content, $status = 200, array $headers = [])
        {
            return (object) [
                'content' => $content,
                'status' => $status,
                'headers' => $headers,
            ];
        }
    };
    $view = new class implements \Illuminate\Contracts\View\Factory {
    };

    $adapter = new LaravelSitemapAdapter([], $cache, $config, $file, $response, $view);
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $resultHtml = $adapter->cachedResponse('sitemap-html', 30, 'html');
    $resultTxt = $adapter->cachedResponse('sitemap-txt', 30, 'txt');

    expect($resultHtml->headers['Content-Type'])->toContain('text/html');
    expect($resultTxt->headers['Content-Type'])->toContain('text/plain');
});

test('LaravelSitemapAdapter renderResponse() uses default content type for unknown format', function () {
    $cache = new class implements \Illuminate\Contracts\Cache\Repository {
    };
    $config = new class implements \Illuminate\Contracts\Config\Repository {
    };
    $file = new class extends \Illuminate\Filesystem\Filesystem {
    };
    $response = new class implements \Illuminate\Contracts\Routing\ResponseFactory {
        public function make($content, $status = 200, array $headers = [])
        {
            return (object) [
                'content' => $content,
                'status' => $status,
                'headers' => $headers,
            ];
        }
    };
    $view = new class implements \Illuminate\Contracts\View\Factory {
    };

    $adapter = new LaravelSitemapAdapter([], $cache, $config, $file, $response, $view);
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    // Test with ror-rss format (exists but should use default content type logic)
    $result = $adapter->renderResponse('ror-rss');

    expect($result->headers['Content-Type'])->toContain('application/xml'); // default
});

test('LaravelSitemapAdapter cachedResponse() uses default content type for unknown format', function () {
    $cache = new class implements \Illuminate\Contracts\Cache\Repository {
        public function remember($key, $ttl, $callback)
        {
            return $callback();
        }
    };
    $config = new class implements \Illuminate\Contracts\Config\Repository {
    };
    $file = new class extends \Illuminate\Filesystem\Filesystem {
    };
    $response = new class implements \Illuminate\Contracts\Routing\ResponseFactory {
        public function make($content, $status = 200, array $headers = [])
        {
            return (object) [
                'content' => $content,
                'status' => $status,
                'headers' => $headers,
            ];
        }
    };
    $view = new class implements \Illuminate\Contracts\View\Factory {
    };

    $adapter = new LaravelSitemapAdapter([], $cache, $config, $file, $response, $view);
    $adapter->getSitemap()->add('https://example.com/', date('c'), '1.0', 'daily');

    $result = $adapter->cachedResponse('sitemap-ror', 30, 'ror-rss');

    expect($result->headers['Content-Type'])->toContain('application/xml'); // default
});
