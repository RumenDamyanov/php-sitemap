# Caching Strategies

Learn how to implement efficient caching strategies for sitemaps using the `rumenx/php-sitemap` package. This guide covers Redis, file-based caching, database caching, and performance optimization techniques.

## Redis Caching

### Basic Redis Implementation

```php
<?php
use Rumenx\Sitemap\Sitemap;
use Redis;

class RedisSitemapCache
{
    private $redis;
    private $defaultTTL = 3600; // 1 hour
    
    public function __construct($redisConfig = [])
    {
        $this->redis = new Redis();
        $host = $redisConfig['host'] ?? '127.0.0.1';
        $port = $redisConfig['port'] ?? 6379;
        $password = $redisConfig['password'] ?? null;
        
        $this->redis->connect($host, $port);
        
        if ($password) {
            $this->redis->auth($password);
        }
    }
    
    public function getCachedSitemap($key, $generator = null, $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTTL;
        $cacheKey = "sitemap:{$key}";
        
        // Try to get from cache
        $cached = $this->redis->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Generate new sitemap if generator provided
        if ($generator && is_callable($generator)) {
            $sitemap = $generator();
            
            // Cache the result
            $this->redis->setex($cacheKey, $ttl, $sitemap);
            
            return $sitemap;
        }
        
        return null;
    }
    
    public function setCachedSitemap($key, $content, $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTTL;
        $cacheKey = "sitemap:{$key}";
        
        return $this->redis->setex($cacheKey, $ttl, $content);
    }
    
    public function invalidateCache($pattern = null)
    {
        if ($pattern) {
            $keys = $this->redis->keys("sitemap:{$pattern}*");
        } else {
            $keys = $this->redis->keys('sitemap:*');
        }
        
        if ($keys) {
            return $this->redis->del($keys);
        }
        
        return 0;
    }
    
    public function getCacheInfo()
    {
        $keys = $this->redis->keys('sitemap:*');
        $info = [];
        
        foreach ($keys as $key) {
            $ttl = $this->redis->ttl($key);
            $size = strlen($this->redis->get($key));
            
            $info[str_replace('sitemap:', '', $key)] = [
                'ttl' => $ttl,
                'size' => $size,
                'expires_at' => $ttl > 0 ? date('Y-m-d H:i:s', time() + $ttl) : 'Never'
            ];
        }
        
        return $info;
    }
}

// Usage example
$cache = new RedisSitemapCache(['host' => 'localhost', 'port' => 6379]);

function generateProductSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);
    
    $stmt = $pdo->query("
        SELECT slug, name, updated_at 
        FROM products 
        WHERE active = 1 
        ORDER BY updated_at DESC 
        LIMIT 50000
    ");
    
    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sitemap->add(
            "https://example.com/products/{$product['slug']}",
            date('c', strtotime($product['updated_at'])),
            '0.8',
            'weekly'
        );
    }
    
    return $sitemap->renderXml();
}

// Get cached sitemap or generate new one
$productSitemap = $cache->getCachedSitemap('products', 'generateProductSitemap', 7200);

header('Content-Type: application/xml; charset=utf-8');
echo $productSitemap;
```

### Advanced Redis Caching with Tagging

```php
<?php
use Rumenx\Sitemap\Sitemap;

class TaggedRedisSitemapCache extends RedisSitemapCache
{
    public function setCachedSitemapWithTags($key, $content, $tags = [], $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTTL;
        $cacheKey = "sitemap:{$key}";
        
        // Set the main cache entry
        $this->redis->setex($cacheKey, $ttl, $content);
        
        // Set tag associations
        foreach ($tags as $tag) {
            $tagKey = "sitemap_tag:{$tag}";
            $this->redis->sadd($tagKey, $cacheKey);
            $this->redis->expire($tagKey, $ttl + 300); // Tags expire slightly later
        }
        
        return true;
    }
    
    public function invalidateByCacheTag($tag)
    {
        $tagKey = "sitemap_tag:{$tag}";
        $keys = $this->redis->smembers($tagKey);
        
        if ($keys) {
            $this->redis->del($keys);
            $this->redis->del($tagKey);
            return count($keys);
        }
        
        return 0;
    }
    
    public function getCacheByTag($tag)
    {
        $tagKey = "sitemap_tag:{$tag}";
        $keys = $this->redis->smembers($tagKey);
        $results = [];
        
        foreach ($keys as $key) {
            $content = $this->redis->get($key);
            if ($content !== false) {
                $results[str_replace('sitemap:', '', $key)] = $content;
            }
        }
        
        return $results;
    }
}

// Usage with tags
$cache = new TaggedRedisSitemapCache();

function generateBlogSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=blog', $username, $password);
    
    $stmt = $pdo->query("
        SELECT slug, title, published_at, updated_at 
        FROM posts 
        WHERE published = 1 
        ORDER BY published_at DESC 
        LIMIT 10000
    ");
    
    while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lastmod = $post['updated_at'] ?: $post['published_at'];
        
        $sitemap->add(
            "https://blog.example.com/posts/{$post['slug']}",
            date('c', strtotime($lastmod)),
            '0.7',
            'monthly'
        );
    }
    
    return $sitemap->renderXml();
}

// Cache with tags for easy invalidation
$blogSitemap = $cache->getCachedSitemap('blog', 'generateBlogSitemap');
$cache->setCachedSitemapWithTags('blog', $blogSitemap, ['blog', 'posts', 'content'], 3600);

// Invalidate when blog content changes
// $cache->invalidateByCacheTag('blog');
```

## File-Based Caching

### Simple File Caching

```php
<?php
use Rumenx\Sitemap\Sitemap;

class FileSitemapCache
{
    private $cacheDir;
    private $defaultTTL = 3600;
    
    public function __construct($cacheDir = 'cache/sitemaps')
    {
        $this->cacheDir = rtrim($cacheDir, '/');
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function getCachedSitemap($key, $generator = null, $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTTL;
        $cacheFile = $this->getCacheFilePath($key);
        
        // Check if cache file exists and is not expired
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            return file_get_contents($cacheFile);
        }
        
        // Generate new sitemap if generator provided
        if ($generator && is_callable($generator)) {
            $sitemap = $generator();
            
            // Save to cache file
            file_put_contents($cacheFile, $sitemap, LOCK_EX);
            
            return $sitemap;
        }
        
        return null;
    }
    
    public function setCachedSitemap($key, $content)
    {
        $cacheFile = $this->getCacheFilePath($key);
        return file_put_contents($cacheFile, $content, LOCK_EX) !== false;
    }
    
    public function invalidateCache($pattern = null)
    {
        $files = glob($this->cacheDir . '/' . ($pattern ?: '*') . '.xml');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    public function getCacheInfo()
    {
        $files = glob($this->cacheDir . '/*.xml');
        $info = [];
        
        foreach ($files as $file) {
            $key = basename($file, '.xml');
            $mtime = filemtime($file);
            $size = filesize($file);
            
            $info[$key] = [
                'created_at' => date('Y-m-d H:i:s', $mtime),
                'size' => $size,
                'age_seconds' => time() - $mtime
            ];
        }
        
        return $info;
    }
    
    public function cleanExpiredCache($maxAge = 3600)
    {
        $files = glob($this->cacheDir . '/*.xml');
        $deleted = 0;
        
        foreach ($files as $file) {
            if ((time() - filemtime($file)) > $maxAge) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    private function getCacheFilePath($key)
    {
        return $this->cacheDir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $key) . '.xml';
    }
}

// Usage example
$cache = new FileSitemapCache('storage/cache/sitemaps');

function generateCategorySitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);
    
    $stmt = $pdo->query("
        SELECT slug, name, updated_at 
        FROM categories 
        WHERE active = 1 
        ORDER BY name
    ");
    
    while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sitemap->add(
            "https://example.com/categories/{$category['slug']}",
            date('c', strtotime($category['updated_at'])),
            '0.9',
            'weekly'
        );
    }
    
    return $sitemap->renderXml();
}

// Get cached sitemap with 2 hour TTL
$categorySitemap = $cache->getCachedSitemap('categories', 'generateCategorySitemap', 7200);

header('Content-Type: application/xml; charset=utf-8');
echo $categorySitemap;
```

### Compressed File Caching

```php
<?php
class CompressedFileSitemapCache extends FileSitemapCache
{
    public function getCachedSitemap($key, $generator = null, $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTTL;
        $cacheFile = $this->getCacheFilePath($key) . '.gz';
        
        // Check if compressed cache file exists and is not expired
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            return gzfile_get_contents($cacheFile);
        }
        
        // Generate new sitemap if generator provided
        if ($generator && is_callable($generator)) {
            $sitemap = $generator();
            
            // Save compressed to cache file
            file_put_contents($cacheFile, gzencode($sitemap, 9), LOCK_EX);
            
            return $sitemap;
        }
        
        return null;
    }
    
    public function setCachedSitemap($key, $content)
    {
        $cacheFile = $this->getCacheFilePath($key) . '.gz';
        return file_put_contents($cacheFile, gzencode($content, 9), LOCK_EX) !== false;
    }
    
    private function gzfile_get_contents($filename)
    {
        $data = file_get_contents($filename);
        return gzdecode($data);
    }
    
    public function getCacheInfo()
    {
        $files = glob($this->cacheDir . '/*.xml.gz');
        $info = [];
        
        foreach ($files as $file) {
            $key = basename($file, '.xml.gz');
            $mtime = filemtime($file);
            $size = filesize($file);
            
            // Get uncompressed size
            $handle = gzopen($file, 'rb');
            $uncompressed = '';
            while (!gzeof($handle)) {
                $uncompressed .= gzread($handle, 8192);
            }
            gzclose($handle);
            $uncompressedSize = strlen($uncompressed);
            
            $info[$key] = [
                'created_at' => date('Y-m-d H:i:s', $mtime),
                'compressed_size' => $size,
                'uncompressed_size' => $uncompressedSize,
                'compression_ratio' => round(($size / $uncompressedSize) * 100, 2) . '%',
                'age_seconds' => time() - $mtime
            ];
        }
        
        return $info;
    }
}

// Usage
$cache = new CompressedFileSitemapCache('storage/cache/sitemaps');

// Large sitemaps benefit significantly from compression
$largeSitemap = $cache->getCachedSitemap('all-products', 'generateLargeProductSitemap', 3600);
```

## Database Caching

### MySQL-Based Caching

```php
<?php
use Rumenx\Sitemap\Sitemap;

class DatabaseSitemapCache
{
    private $pdo;
    private $defaultTTL = 3600;
    private $table = 'sitemap_cache';
    
    public function __construct($dbConfig)
    {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
        
        $this->createCacheTable();
    }
    
    private function createCacheTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS {$this->table} (
                cache_key VARCHAR(255) PRIMARY KEY,
                content LONGTEXT NOT NULL,
                compressed TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                tags JSON NULL,
                size_bytes INT UNSIGNED DEFAULT 0,
                INDEX idx_expires_at (expires_at),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB
        ";
        
        $this->pdo->exec($sql);
    }
    
    public function getCachedSitemap($key, $generator = null, $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTTL;
        
        // Clean expired entries
        $this->cleanExpired();
        
        // Try to get from cache
        $stmt = $this->pdo->prepare("
            SELECT content, compressed 
            FROM {$this->table} 
            WHERE cache_key = :key 
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        
        $stmt->execute(['key' => $key]);
        $cached = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cached) {
            $content = $cached['content'];
            
            if ($cached['compressed']) {
                $content = gzdecode($content);
            }
            
            return $content;
        }
        
        // Generate new sitemap if generator provided
        if ($generator && is_callable($generator)) {
            $sitemap = $generator();
            
            // Store in cache
            $this->setCachedSitemap($key, $sitemap, [], $ttl);
            
            return $sitemap;
        }
        
        return null;
    }
    
    public function setCachedSitemap($key, $content, $tags = [], $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTTL;
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
        
        // Compress large content
        $shouldCompress = strlen($content) > 10240; // 10KB threshold
        $storedContent = $shouldCompress ? gzencode($content, 6) : $content;
        
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} 
            (cache_key, content, compressed, expires_at, tags, size_bytes) 
            VALUES (:key, :content, :compressed, :expires_at, :tags, :size)
            ON DUPLICATE KEY UPDATE
            content = VALUES(content),
            compressed = VALUES(compressed),
            expires_at = VALUES(expires_at),
            tags = VALUES(tags),
            size_bytes = VALUES(size_bytes),
            created_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([
            'key' => $key,
            'content' => $storedContent,
            'compressed' => $shouldCompress ? 1 : 0,
            'expires_at' => $expiresAt,
            'tags' => $tags ? json_encode($tags) : null,
            'size' => strlen($storedContent)
        ]);
    }
    
    public function invalidateCache($pattern = null)
    {
        if ($pattern) {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE cache_key LIKE :pattern");
            $stmt->execute(['pattern' => str_replace('*', '%', $pattern)]);
        } else {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table}");
            $stmt->execute();
        }
        
        return $stmt->rowCount();
    }
    
    public function invalidateByTag($tag)
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM {$this->table} 
            WHERE JSON_CONTAINS(tags, :tag)
        ");
        
        $stmt->execute(['tag' => json_encode($tag)]);
        
        return $stmt->rowCount();
    }
    
    public function getCacheInfo()
    {
        $stmt = $this->pdo->query("
            SELECT 
                cache_key,
                compressed,
                created_at,
                expires_at,
                size_bytes,
                tags,
                CASE 
                    WHEN expires_at IS NULL THEN 'Never'
                    WHEN expires_at > NOW() THEN CONCAT(TIMESTAMPDIFF(SECOND, NOW(), expires_at), ' seconds')
                    ELSE 'Expired'
                END as ttl_remaining
            FROM {$this->table}
            ORDER BY created_at DESC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function cleanExpired()
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM {$this->table} 
            WHERE expires_at IS NOT NULL AND expires_at <= NOW()
        ");
        
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    public function getCacheStats()
    {
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(*) as total_entries,
                SUM(size_bytes) as total_size_bytes,
                AVG(size_bytes) as avg_size_bytes,
                SUM(CASE WHEN compressed = 1 THEN 1 ELSE 0 END) as compressed_entries,
                SUM(CASE WHEN expires_at > NOW() THEN 1 ELSE 0 END) as active_entries,
                SUM(CASE WHEN expires_at <= NOW() THEN 1 ELSE 0 END) as expired_entries
            FROM {$this->table}
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Usage example
$config = [
    'host' => 'localhost',
    'name' => 'website',
    'user' => 'dbuser',
    'pass' => 'dbpass'
];

$cache = new DatabaseSitemapCache($config);

function generateFullSitemap()
{
    $sitemap = new Sitemap();
    // ... generate complete sitemap
    return $sitemap->renderXml();
}

// Cache with tags for easy invalidation
$fullSitemap = $cache->getCachedSitemap('full-site', 'generateFullSitemap', 7200);

header('Content-Type: application/xml; charset=utf-8');
echo $fullSitemap;
```

## Memcached Integration

### Memcached Caching Implementation

```php
<?php
use Rumenx\Sitemap\Sitemap;

class MemcachedSitemapCache
{
    private $memcached;
    private $defaultTTL = 3600;
    private $keyPrefix = 'sitemap:';
    
    public function __construct($servers = [['127.0.0.1', 11211]])
    {
        $this->memcached = new Memcached();
        
        // Add servers
        foreach ($servers as $server) {
            $this->memcached->addServer($server[0], $server[1]);
        }
        
        // Set options for better performance
        $this->memcached->setOptions([
            Memcached::OPT_COMPRESSION => true,
            Memcached::OPT_SERIALIZER => Memcached::SERIALIZER_IGBINARY,
            Memcached::OPT_BINARY_PROTOCOL => true,
            Memcached::OPT_NO_BLOCK => true,
            Memcached::OPT_TCP_NODELAY => true
        ]);
    }
    
    public function getCachedSitemap($key, $generator = null, $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTTL;
        $cacheKey = $this->keyPrefix . $key;
        
        // Try to get from cache
        $cached = $this->memcached->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Generate new sitemap if generator provided
        if ($generator && is_callable($generator)) {
            $sitemap = $generator();
            
            // Cache the result
            $this->memcached->set($cacheKey, $sitemap, $ttl);
            
            return $sitemap;
        }
        
        return null;
    }
    
    public function setCachedSitemap($key, $content, $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTTL;
        $cacheKey = $this->keyPrefix . $key;
        
        return $this->memcached->set($cacheKey, $content, $ttl);
    }
    
    public function invalidateCache($keys = null)
    {
        if (is_array($keys)) {
            $cacheKeys = array_map(function($key) {
                return $this->keyPrefix . $key;
            }, $keys);
            
            return $this->memcached->deleteMulti($cacheKeys);
        } elseif ($keys) {
            return $this->memcached->delete($this->keyPrefix . $keys);
        } else {
            // Flush all cache (use with caution)
            return $this->memcached->flush();
        }
    }
    
    public function setCachedSitemapMulti($items, $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTTL;
        $cacheItems = [];
        
        foreach ($items as $key => $content) {
            $cacheItems[$this->keyPrefix . $key] = $content;
        }
        
        return $this->memcached->setMulti($cacheItems, $ttl);
    }
    
    public function getCachedSitemapMulti($keys)
    {
        $cacheKeys = array_map(function($key) {
            return $this->keyPrefix . $key;
        }, $keys);
        
        $results = $this->memcached->getMulti($cacheKeys);
        
        // Remove prefix from keys
        $cleanResults = [];
        foreach ($results as $key => $value) {
            $cleanKey = str_replace($this->keyPrefix, '', $key);
            $cleanResults[$cleanKey] = $value;
        }
        
        return $cleanResults;
    }
    
    public function getCacheStats()
    {
        return $this->memcached->getStats();
    }
}

// Usage example
$cache = new MemcachedSitemapCache([
    ['127.0.0.1', 11211],
    ['127.0.0.1', 11212] // Multiple servers for redundancy
]);

// Cache multiple sitemaps at once
$sitemapGenerators = [
    'products' => 'generateProductSitemap',
    'categories' => 'generateCategorySitemap',
    'blog' => 'generateBlogSitemap'
];

$sitemaps = [];
foreach ($sitemapGenerators as $key => $generator) {
    $sitemaps[$key] = $generator();
}

// Set all at once for better performance
$cache->setCachedSitemapMulti($sitemaps, 3600);

// Get multiple sitemaps at once
$cachedSitemaps = $cache->getCachedSitemapMulti(['products', 'categories', 'blog']);
```

## Cache Invalidation Strategies

### Event-Driven Cache Invalidation

```php
<?php
class SitemapCacheManager
{
    private $caches = [];
    private $eventListeners = [];
    
    public function __construct()
    {
        // Initialize multiple cache backends
        $this->caches['redis'] = new RedisSitemapCache();
        $this->caches['file'] = new FileSitemapCache();
        $this->caches['database'] = new DatabaseSitemapCache($dbConfig);
    }
    
    public function addCache($name, $cacheInstance)
    {
        $this->caches[$name] = $cacheInstance;
    }
    
    public function addEventListener($event, $callback)
    {
        if (!isset($this->eventListeners[$event])) {
            $this->eventListeners[$event] = [];
        }
        
        $this->eventListeners[$event][] = $callback;
    }
    
    public function triggerEvent($event, $data = [])
    {
        if (isset($this->eventListeners[$event])) {
            foreach ($this->eventListeners[$event] as $callback) {
                call_user_func($callback, $data);
            }
        }
    }
    
    public function invalidateOnProductUpdate($productId)
    {
        $this->triggerEvent('product.updated', ['product_id' => $productId]);
        
        // Invalidate related caches
        foreach ($this->caches as $cache) {
            if (method_exists($cache, 'invalidateByTag')) {
                $cache->invalidateByTag('products');
            } else {
                $cache->invalidateCache('products*');
            }
        }
    }
    
    public function invalidateOnContentUpdate($contentType, $contentId)
    {
        $this->triggerEvent('content.updated', [
            'type' => $contentType,
            'id' => $contentId
        ]);
        
        $cachePatterns = [
            'post' => ['blog*', 'posts*', 'categories*'],
            'category' => ['categories*', 'blog*'],
            'page' => ['pages*', 'site*']
        ];
        
        if (isset($cachePatterns[$contentType])) {
            foreach ($this->caches as $cache) {
                foreach ($cachePatterns[$contentType] as $pattern) {
                    $cache->invalidateCache($pattern);
                }
            }
        }
    }
    
    public function getCachedSitemap($key, $generator = null, $preferredCache = 'redis')
    {
        if (!isset($this->caches[$preferredCache])) {
            $preferredCache = array_key_first($this->caches);
        }
        
        // Try preferred cache first
        $sitemap = $this->caches[$preferredCache]->getCachedSitemap($key, $generator);
        
        if ($sitemap) {
            return $sitemap;
        }
        
        // Try other caches
        foreach ($this->caches as $name => $cache) {
            if ($name !== $preferredCache) {
                $sitemap = $cache->getCachedSitemap($key);
                if ($sitemap) {
                    // Backfill preferred cache
                    $this->caches[$preferredCache]->setCachedSitemap($key, $sitemap);
                    return $sitemap;
                }
            }
        }
        
        return null;
    }
}

// Setup event listeners
$cacheManager = new SitemapCacheManager();

$cacheManager->addEventListener('product.updated', function($data) {
    error_log("Product {$data['product_id']} updated, invalidating product caches");
});

$cacheManager->addEventListener('content.updated', function($data) {
    error_log("Content {$data['type']} {$data['id']} updated, invalidating related caches");
});

// Usage in application
function updateProduct($productId, $data)
{
    global $cacheManager;
    
    // Update product in database
    // ... database update logic
    
    // Invalidate related caches
    $cacheManager->invalidateOnProductUpdate($productId);
}

function publishBlogPost($postId)
{
    global $cacheManager;
    
    // Publish post logic
    // ... database update logic
    
    // Invalidate blog-related caches
    $cacheManager->invalidateOnContentUpdate('post', $postId);
}
```

## Cache Warming Strategies

### Proactive Cache Warming

```php
<?php
class SitemapCacheWarmer
{
    private $cache;
    private $generators;
    
    public function __construct($cache)
    {
        $this->cache = $cache;
        $this->generators = [];
    }
    
    public function addGenerator($key, $generator, $ttl = 3600, $priority = 1)
    {
        $this->generators[$key] = [
            'generator' => $generator,
            'ttl' => $ttl,
            'priority' => $priority
        ];
    }
    
    public function warmCache($keys = null)
    {
        $toWarm = $keys ?: array_keys($this->generators);
        
        // Sort by priority (higher priority first)
        usort($toWarm, function($a, $b) {
            return $this->generators[$b]['priority'] <=> $this->generators[$a]['priority'];
        });
        
        $results = [];
        
        foreach ($toWarm as $key) {
            if (!isset($this->generators[$key])) {
                continue;
            }
            
            $config = $this->generators[$key];
            $startTime = microtime(true);
            
            try {
                $sitemap = $config['generator']();
                $this->cache->setCachedSitemap($key, $sitemap, $config['ttl']);
                
                $results[$key] = [
                    'status' => 'success',
                    'time' => microtime(true) - $startTime,
                    'size' => strlen($sitemap)
                ];
                
                echo "Warmed cache for '{$key}' in " . round($results[$key]['time'], 3) . "s\n";
                
            } catch (Exception $e) {
                $results[$key] = [
                    'status' => 'error',
                    'time' => microtime(true) - $startTime,
                    'error' => $e->getMessage()
                ];
                
                echo "Failed to warm cache for '{$key}': " . $e->getMessage() . "\n";
            }
        }
        
        return $results;
    }
    
    public function warmCacheAsync($keys = null)
    {
        $toWarm = $keys ?: array_keys($this->generators);
        $processes = [];
        
        foreach ($toWarm as $key) {
            if (!isset($this->generators[$key])) {
                continue;
            }
            
            // Create background process for each cache warming
            $cmd = "php -f warm_cache_worker.php -- --key=" . escapeshellarg($key);
            $process = proc_open($cmd, [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ], $pipes);
            
            if (is_resource($process)) {
                $processes[$key] = [
                    'process' => $process,
                    'pipes' => $pipes,
                    'start_time' => microtime(true)
                ];
            }
        }
        
        // Wait for all processes to complete
        $results = [];
        foreach ($processes as $key => $processData) {
            $output = stream_get_contents($processData['pipes'][1]);
            $error = stream_get_contents($processData['pipes'][2]);
            
            fclose($processData['pipes'][1]);
            fclose($processData['pipes'][2]);
            
            $exitCode = proc_close($processData['process']);
            
            $results[$key] = [
                'status' => $exitCode === 0 ? 'success' : 'error',
                'time' => microtime(true) - $processData['start_time'],
                'output' => $output,
                'error' => $error
            ];
        }
        
        return $results;
    }
    
    public function scheduleWarmUp($cronExpression, $keys = null)
    {
        // Add to cron job
        $command = "php -f " . __FILE__ . " -- --warm";
        if ($keys) {
            $command .= " --keys=" . implode(',', $keys);
        }
        
        // Example cron entry: 0 */2 * * * (every 2 hours)
        $cronEntry = "{$cronExpression} {$command}";
        
        return $cronEntry;
    }
}

// Setup cache warming
$cache = new RedisSitemapCache();
$warmer = new SitemapCacheWarmer($cache);

// Add generators with priorities
$warmer->addGenerator('homepage', 'generateHomepageSitemap', 1800, 10); // High priority
$warmer->addGenerator('products', 'generateProductSitemap', 3600, 8);
$warmer->addGenerator('blog', 'generateBlogSitemap', 3600, 6);
$warmer->addGenerator('categories', 'generateCategorySitemap', 7200, 4);

// Warm cache synchronously
$results = $warmer->warmCache();

// Or warm cache asynchronously for better performance
// $results = $warmer->warmCacheAsync();

// CLI usage for cron jobs
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['warm', 'keys:']);
    
    if (isset($options['warm'])) {
        $keys = isset($options['keys']) ? explode(',', $options['keys']) : null;
        $warmer->warmCache($keys);
    }
}
```

## Performance Monitoring

### Cache Performance Metrics

```php
<?php
class SitemapCacheMetrics
{
    private $cache;
    private $metricsCache;
    
    public function __construct($cache, $metricsCache = null)
    {
        $this->cache = $cache;
        $this->metricsCache = $metricsCache ?: $cache;
    }
    
    public function recordCacheHit($key, $responseTime)
    {
        $metric = [
            'type' => 'hit',
            'key' => $key,
            'response_time' => $responseTime,
            'timestamp' => time()
        ];
        
        $this->recordMetric($metric);
    }
    
    public function recordCacheMiss($key, $generationTime)
    {
        $metric = [
            'type' => 'miss',
            'key' => $key,
            'generation_time' => $generationTime,
            'timestamp' => time()
        ];
        
        $this->recordMetric($metric);
    }
    
    private function recordMetric($metric)
    {
        $metricsKey = "metrics:" . date('Y-m-d-H');
        
        if (method_exists($this->metricsCache, 'redis')) {
            // Use Redis for metrics storage
            $this->metricsCache->redis->lpush($metricsKey, json_encode($metric));
            $this->metricsCache->redis->expire($metricsKey, 86400 * 7); // Keep for 7 days
        } else {
            // Fallback to file storage
            $metricsFile = "metrics/" . date('Y-m-d-H') . ".log";
            file_put_contents($metricsFile, json_encode($metric) . "\n", FILE_APPEND | LOCK_EX);
        }
    }
    
    public function getMetricsSummary($hours = 24)
    {
        $summary = [
            'total_requests' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'hit_rate' => 0,
            'avg_response_time' => 0,
            'avg_generation_time' => 0,
            'top_keys' => []
        ];
        
        $responseTimes = [];
        $generationTimes = [];
        $keyStats = [];
        
        for ($i = 0; $i < $hours; $i++) {
            $timestamp = time() - ($i * 3600);
            $metricsKey = "metrics:" . date('Y-m-d-H', $timestamp);
            
            if (method_exists($this->metricsCache, 'redis')) {
                $metrics = $this->metricsCache->redis->lrange($metricsKey, 0, -1);
                
                foreach ($metrics as $metricJson) {
                    $metric = json_decode($metricJson, true);
                    $this->processMetric($metric, $summary, $responseTimes, $generationTimes, $keyStats);
                }
            }
        }
        
        // Calculate averages
        if (count($responseTimes) > 0) {
            $summary['avg_response_time'] = array_sum($responseTimes) / count($responseTimes);
        }
        
        if (count($generationTimes) > 0) {
            $summary['avg_generation_time'] = array_sum($generationTimes) / count($generationTimes);
        }
        
        if ($summary['total_requests'] > 0) {
            $summary['hit_rate'] = ($summary['cache_hits'] / $summary['total_requests']) * 100;
        }
        
        // Sort keys by frequency
        arsort($keyStats);
        $summary['top_keys'] = array_slice($keyStats, 0, 10, true);
        
        return $summary;
    }
    
    private function processMetric($metric, &$summary, &$responseTimes, &$generationTimes, &$keyStats)
    {
        $summary['total_requests']++;
        
        if (!isset($keyStats[$metric['key']])) {
            $keyStats[$metric['key']] = 0;
        }
        $keyStats[$metric['key']]++;
        
        if ($metric['type'] === 'hit') {
            $summary['cache_hits']++;
            $responseTimes[] = $metric['response_time'];
        } else {
            $summary['cache_misses']++;
            $generationTimes[] = $metric['generation_time'];
        }
    }
    
    public function getCacheHealth()
    {
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'recommendations' => []
        ];
        
        $metrics = $this->getMetricsSummary(1); // Last hour
        
        // Check hit rate
        if ($metrics['hit_rate'] < 50) {
            $health['status'] = 'warning';
            $health['issues'][] = "Low cache hit rate: {$metrics['hit_rate']}%";
            $health['recommendations'][] = "Consider increasing cache TTL or warming cache more frequently";
        }
        
        // Check response times
        if ($metrics['avg_response_time'] > 1.0) {
            $health['status'] = 'warning';
            $health['issues'][] = "High average response time: {$metrics['avg_response_time']}s";
            $health['recommendations'][] = "Consider optimizing cache storage or using faster cache backend";
        }
        
        // Check generation times
        if ($metrics['avg_generation_time'] > 5.0) {
            $health['status'] = 'warning';
            $health['issues'][] = "High sitemap generation time: {$metrics['avg_generation_time']}s";
            $health['recommendations'][] = "Consider optimizing database queries or using pagination";
        }
        
        return $health;
    }
}

// Usage
$cache = new RedisSitemapCache();
$metrics = new SitemapCacheMetrics($cache);

// Wrap cache calls with metrics
function getCachedSitemapWithMetrics($key, $generator = null)
{
    global $cache, $metrics;
    
    $startTime = microtime(true);
    $sitemap = $cache->getCachedSitemap($key);
    
    if ($sitemap) {
        $responseTime = microtime(true) - $startTime;
        $metrics->recordCacheHit($key, $responseTime);
        return $sitemap;
    }
    
    if ($generator && is_callable($generator)) {
        $generationStart = microtime(true);
        $sitemap = $generator();
        $generationTime = microtime(true) - $generationStart;
        
        $cache->setCachedSitemap($key, $sitemap);
        $metrics->recordCacheMiss($key, $generationTime);
        
        return $sitemap;
    }
    
    return null;
}

// Get performance summary
$summary = $metrics->getMetricsSummary(24);
echo "Cache hit rate: {$summary['hit_rate']}%\n";
echo "Average response time: {$summary['avg_response_time']}s\n";

// Check cache health
$health = $metrics->getCacheHealth();
if ($health['status'] !== 'healthy') {
    echo "Cache health issues:\n";
    foreach ($health['issues'] as $issue) {
        echo "- {$issue}\n";
    }
}
```

## Next Steps

- Learn about [Memory Optimization](memory-optimization.md) for handling large sitemaps
- Explore [Automated Generation](automated-generation.md) for scheduled cache updates
- Check [Performance Monitoring](performance-monitoring.md) for cache optimization
- See [Load Balancing](load-balancing.md) for distributed caching strategies
