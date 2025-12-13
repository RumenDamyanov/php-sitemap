# Sitemap Index

Learn how to manage multiple sitemaps using sitemap index files. This approach is essential for large websites with thousands of URLs.

## Why Use Sitemap Index?

- **URL Limits**: Each sitemap can contain max 50,000 URLs
- **File Size**: Max 50MB uncompressed per sitemap  
- **Organization**: Separate content types (posts, products, categories)
- **Performance**: Parallel generation and caching of individual sitemaps

## Basic Sitemap Index

### Simple Multi-Sitemap Setup

```php
<?php
use Rumenx\Sitemap\Sitemap;

// Create main sitemap index
$sitemapIndex = new Sitemap();

// Add references to individual sitemaps
$sitemapIndex->addSitemap('https://example.com/sitemap-posts.xml');
$sitemapIndex->addSitemap('https://example.com/sitemap-products.xml'); 
$sitemapIndex->addSitemap('https://example.com/sitemap-categories.xml');

// Generate sitemap index XML
$items = $sitemapIndex->getModel()->getSitemaps();
$xml = view('sitemap.sitemapindex', compact('items'))->render();

header('Content-Type: application/xml; charset=utf-8');
echo $xml;
```

### Individual Sitemap Files

**sitemap-posts.xml** (Route: `/sitemap-posts.xml`)

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);

// Get blog posts
$stmt = $pdo->query("
    SELECT slug, updated_at, priority 
    FROM posts 
    WHERE published = 1 
    ORDER BY updated_at DESC
    LIMIT 50000
");

while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sitemap->add(
        "https://example.com/blog/{$post['slug']}",
        date('c', strtotime($post['updated_at'])),
        $post['priority'] ?? '0.7',
        'monthly'
    );
}

header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

**sitemap-products.xml** (Route: `/sitemap-products.xml`)

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);

// Get products
$stmt = $pdo->query("
    SELECT slug, updated_at 
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

header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

## Advanced Sitemap Index with Timestamps

### Including Last Modified Dates

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateSitemapIndex()
{
    $sitemapIndex = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
    
    // Get last modification dates for each content type
    $contentTypes = [
        'posts' => 'https://example.com/sitemap-posts.xml',
        'products' => 'https://example.com/sitemap-products.xml',
        'categories' => 'https://example.com/sitemap-categories.xml'
    ];
    
    foreach ($contentTypes as $table => $url) {
        // Get last modification time for this content type
        $stmt = $pdo->query("SELECT MAX(updated_at) as last_mod FROM {$table}");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $lastMod = $result['last_mod'] ? date('c', strtotime($result['last_mod'])) : date('c');
        
        // Add sitemap with last modified date
        $sitemapIndex->addSitemap($url, $lastMod);
    }
    
    // Generate index XML
    $items = $sitemapIndex->getModel()->getSitemaps();
    $xml = view('sitemap.sitemapindex', compact('items'))->render();
    
    header('Content-Type: application/xml; charset=utf-8');
    echo $xml;
}

generateSitemapIndex();
```

## File-Based Sitemap Index

### Generate and Store Individual Sitemaps

```php
<?php
use Rumenx\Sitemap\Sitemap;

class SitemapIndexManager
{
    private $baseUrl = 'https://example.com';
    private $storagePath = 'public/sitemaps/';
    
    public function generateAll()
    {
        // Ensure storage directory exists
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
        
        // Generate individual sitemaps
        $sitemapFiles = [
            'posts' => $this->generatePostsSitemap(),
            'products' => $this->generateProductsSitemap(),
            'categories' => $this->generateCategoriesSitemap()
        ];
        
        // Generate sitemap index
        $this->generateSitemapIndex($sitemapFiles);
        
        return $sitemapFiles;
    }
    
    private function generatePostsSitemap()
    {
        $sitemap = new Sitemap();
        $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
        
        $stmt = $pdo->query("
            SELECT slug, updated_at 
            FROM posts 
            WHERE published = 1 
            ORDER BY updated_at DESC 
            LIMIT 50000
        ");
        
        while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sitemap->add(
                "{$this->baseUrl}/blog/{$post['slug']}",
                date('c', strtotime($post['updated_at'])),
                '0.7',
                'monthly'
            );
        }
        
        // Save to file
        $filename = 'sitemap-posts.xml';
        $xml = $sitemap->renderXml();
        file_put_contents($this->storagePath . $filename, $xml);
        
        return [
            'file' => $filename,
            'url' => "{$this->baseUrl}/sitemaps/{$filename}",
            'lastmod' => date('c')
        ];
    }
    
    private function generateProductsSitemap()
    {
        $sitemap = new Sitemap();
        $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
        
        $stmt = $pdo->query("
            SELECT slug, updated_at 
            FROM products 
            WHERE active = 1 
            ORDER BY updated_at DESC 
            LIMIT 50000
        ");
        
        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sitemap->add(
                "{$this->baseUrl}/products/{$product['slug']}",
                date('c', strtotime($product['updated_at'])),
                '0.8',
                'weekly'
            );
        }
        
        // Save to file
        $filename = 'sitemap-products.xml';
        $xml = $sitemap->renderXml();
        file_put_contents($this->storagePath . $filename, $xml);
        
        return [
            'file' => $filename,
            'url' => "{$this->baseUrl}/sitemaps/{$filename}",
            'lastmod' => date('c')
        ];
    }
    
    private function generateCategoriesSitemap()
    {
        $sitemap = new Sitemap();
        $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
        
        $stmt = $pdo->query("
            SELECT slug, updated_at 
            FROM categories 
            WHERE active = 1 
            ORDER BY updated_at DESC
        ");
        
        while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sitemap->add(
                "{$this->baseUrl}/categories/{$category['slug']}",
                date('c', strtotime($category['updated_at'])),
                '0.6',
                'monthly'
            );
        }
        
        // Save to file
        $filename = 'sitemap-categories.xml';
        $xml = $sitemap->renderXml();
        file_put_contents($this->storagePath . $filename, $xml);
        
        return [
            'file' => $filename,
            'url' => "{$this->baseUrl}/sitemaps/{$filename}",
            'lastmod' => date('c')
        ];
    }
    
    private function generateSitemapIndex($sitemapFiles)
    {
        $sitemapIndex = new Sitemap();
        
        foreach ($sitemapFiles as $fileData) {
            $sitemapIndex->addSitemap($fileData['url'], $fileData['lastmod']);
        }
        
        // Generate index XML
        $items = $sitemapIndex->getModel()->getSitemaps();
        $xml = view('sitemap.sitemapindex', compact('items'))->render();
        
        // Save index file
        file_put_contents($this->storagePath . 'sitemap.xml', $xml);
        
        return $xml;
    }
}

// Usage
$manager = new SitemapIndexManager();
$files = $manager->generateAll();

echo "Generated sitemaps:\n";
foreach ($files as $type => $data) {
    echo "- {$data['file']} ({$data['url']})\n";
}
echo "- sitemap.xml (index)\n";
```

## Cached Sitemap Index

### Redis-Based Caching for Performance

```php
<?php
use Rumenx\Sitemap\Sitemap;
use Redis;

class CachedSitemapIndex
{
    private $redis;
    private $cacheTime = 3600; // 1 hour
    
    public function __construct()
    {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }
    
    public function getSitemapIndex()
    {
        $cacheKey = 'sitemap:index';
        
        // Check cache
        $cached = $this->redis->get($cacheKey);
        if ($cached) {
            header('Content-Type: application/xml; charset=utf-8');
            echo $cached;
            return;
        }
        
        // Generate new index
        $xml = $this->generateSitemapIndex();
        
        // Cache it
        $this->redis->setex($cacheKey, $this->cacheTime, $xml);
        
        // Output
        header('Content-Type: application/xml; charset=utf-8');
        echo $xml;
    }
    
    public function getIndividualSitemap($type)
    {
        $cacheKey = "sitemap:{$type}";
        
        // Check cache
        $cached = $this->redis->get($cacheKey);
        if ($cached) {
            header('Content-Type: application/xml; charset=utf-8');
            echo $cached;
            return;
        }
        
        // Generate new sitemap
        $xml = $this->generateIndividualSitemap($type);
        
        // Cache it
        $this->redis->setex($cacheKey, $this->cacheTime, $xml);
        
        // Output
        header('Content-Type: application/xml; charset=utf-8');
        echo $xml;
    }
    
    private function generateSitemapIndex()
    {
        $sitemapIndex = new Sitemap();
        
        $sitemaps = [
            'https://example.com/sitemap-posts.xml',
            'https://example.com/sitemap-products.xml',
            'https://example.com/sitemap-categories.xml'
        ];
        
        foreach ($sitemaps as $url) {
            $sitemapIndex->addSitemap($url, date('c'));
        }
        
        $items = $sitemapIndex->getModel()->getSitemaps();
        return view('sitemap.sitemapindex', compact('items'))->render();
    }
    
    private function generateIndividualSitemap($type)
    {
        $sitemap = new Sitemap();
        
        switch ($type) {
            case 'posts':
                return $this->addPosts($sitemap);
            case 'products':
                return $this->addProducts($sitemap);
            case 'categories':
                return $this->addCategories($sitemap);
            default:
                throw new InvalidArgumentException("Unknown sitemap type: {$type}");
        }
    }
    
    private function addPosts($sitemap)
    {
        $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
        
        $stmt = $pdo->query("
            SELECT slug, updated_at 
            FROM posts 
            WHERE published = 1 
            ORDER BY updated_at DESC 
            LIMIT 50000
        ");
        
        while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sitemap->add(
                "https://example.com/blog/{$post['slug']}",
                date('c', strtotime($post['updated_at'])),
                '0.7',
                'monthly'
            );
        }
        
        return $sitemap->renderXml();
    }
    
    // Similar methods for addProducts() and addCategories()...
    
    public function invalidateCache($type = null)
    {
        if ($type) {
            $this->redis->del("sitemap:{$type}");
        } else {
            // Clear all sitemap caches
            $keys = $this->redis->keys('sitemap:*');
            if ($keys) {
                $this->redis->del($keys);
            }
        }
    }
}

// Usage
$sitemapCache = new CachedSitemapIndex();

// For sitemap index
$sitemapCache->getSitemapIndex();

// For individual sitemaps
// $sitemapCache->getIndividualSitemap('posts');
// $sitemapCache->getIndividualSitemap('products');
// $sitemapCache->getIndividualSitemap('categories');

// Invalidate cache when content is updated
// $sitemapCache->invalidateCache('posts');
```

## Automated Sitemap Index Generation

### Command-Line Script for Cron Jobs

```php
#!/usr/bin/env php
<?php
/**
 * Generate sitemap index and individual sitemaps
 * Usage: php generate-sitemaps.php
 */

require 'vendor/autoload.php';

use Rumenx\Sitemap\Sitemap;

class SitemapGenerator
{
    private $baseUrl;
    private $outputDir;
    private $pdo;
    
    public function __construct($baseUrl, $outputDir, $dbConfig)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->outputDir = rtrim($outputDir, '/') . '/';
        
        // Create output directory if it doesn't exist
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
        
        // Database connection
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    }
    
    public function generateAll()
    {
        echo "Starting sitemap generation...\n";
        
        $sitemaps = [];
        
        // Generate individual sitemaps
        $sitemaps[] = $this->generatePostsSitemap();
        $sitemaps[] = $this->generateProductsSitemap();
        $sitemaps[] = $this->generateCategoriesSitemap();
        
        // Generate sitemap index
        $this->generateIndex($sitemaps);
        
        echo "Sitemap generation completed!\n";
    }
    
    private function generatePostsSitemap()
    {
        echo "Generating posts sitemap...\n";
        
        $sitemap = new Sitemap();
        
        $stmt = $this->pdo->query("
            SELECT slug, updated_at 
            FROM posts 
            WHERE published = 1 
            ORDER BY updated_at DESC 
            LIMIT 50000
        ");
        
        $count = 0;
        while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sitemap->add(
                "{$this->baseUrl}/blog/{$post['slug']}",
                date('c', strtotime($post['updated_at'])),
                '0.7',
                'monthly'
            );
            $count++;
        }
        
        $filename = 'sitemap-posts.xml';
        $xml = $sitemap->renderXml();
        file_put_contents($this->outputDir . $filename, $xml);
        
        echo "Generated {$filename} with {$count} posts\n";
        
        return [
            'loc' => "{$this->baseUrl}/{$filename}",
            'lastmod' => date('c')
        ];
    }
    
    private function generateProductsSitemap()
    {
        echo "Generating products sitemap...\n";
        
        $sitemap = new Sitemap();
        
        $stmt = $this->pdo->query("
            SELECT slug, updated_at 
            FROM products 
            WHERE active = 1 
            ORDER BY updated_at DESC 
            LIMIT 50000
        ");
        
        $count = 0;
        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sitemap->add(
                "{$this->baseUrl}/products/{$product['slug']}",
                date('c', strtotime($product['updated_at'])),
                '0.8',
                'weekly'
            );
            $count++;
        }
        
        $filename = 'sitemap-products.xml';
        $xml = $sitemap->renderXml();
        file_put_contents($this->outputDir . $filename, $xml);
        
        echo "Generated {$filename} with {$count} products\n";
        
        return [
            'loc' => "{$this->baseUrl}/{$filename}",
            'lastmod' => date('c')
        ];
    }
    
    private function generateCategoriesSitemap()
    {
        echo "Generating categories sitemap...\n";
        
        $sitemap = new Sitemap();
        
        $stmt = $this->pdo->query("
            SELECT slug, updated_at 
            FROM categories 
            WHERE active = 1 
            ORDER BY updated_at DESC
        ");
        
        $count = 0;
        while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sitemap->add(
                "{$this->baseUrl}/categories/{$category['slug']}",
                date('c', strtotime($category['updated_at'])),
                '0.6',
                'monthly'
            );
            $count++;
        }
        
        $filename = 'sitemap-categories.xml';
        $xml = $sitemap->renderXml();
        file_put_contents($this->outputDir . $filename, $xml);
        
        echo "Generated {$filename} with {$count} categories\n";
        
        return [
            'loc' => "{$this->baseUrl}/{$filename}",
            'lastmod' => date('c')
        ];
    }
    
    private function generateIndex($sitemaps)
    {
        echo "Generating sitemap index...\n";
        
        $sitemapIndex = new Sitemap();
        
        foreach ($sitemaps as $sitemap) {
            $sitemapIndex->addSitemap($sitemap['loc'], $sitemap['lastmod']);
        }
        
        $items = $sitemapIndex->getModel()->getSitemaps();
        $xml = view('sitemap.sitemapindex', compact('items'))->render();
        
        file_put_contents($this->outputDir . 'sitemap.xml', $xml);
        
        echo "Generated sitemap.xml index\n";
    }
}

// Configuration
$config = [
    'base_url' => 'https://example.com',
    'output_dir' => '/var/www/html/public',
    'database' => [
        'host' => 'localhost',
        'name' => 'yourdb',
        'user' => 'dbuser',
        'pass' => 'dbpass'
    ]
];

// Generate sitemaps
$generator = new SitemapGenerator(
    $config['base_url'],
    $config['output_dir'],
    $config['database']
);

$generator->generateAll();
```

### Cron Job Setup

Add to your crontab:

```bash
# Generate sitemaps every hour
0 * * * * /usr/bin/php /path/to/your/generate-sitemaps.php

# Or generate daily at 2 AM
0 2 * * * /usr/bin/php /path/to/your/generate-sitemaps.php
```

## Next Steps

- Learn about [Large Scale Sitemaps](large-scale-sitemaps.md) for millions of URLs
- Explore [Caching Strategies](caching-strategies.md) for optimal performance
- Check [Framework Integration](framework-integration.md) for Laravel/Symfony routing
- See [Memory Optimization](memory-optimization.md) for efficient processing
