# Dynamic Sitemaps

Generate sitemaps dynamically from database content with caching strategies. This modernizes the examples from the old wiki for the current framework-agnostic package.

## Basic Dynamic Sitemap

### Simple Database-Driven Sitemap

```php
<?php
use Rumenx\Sitemap\Sitemap;
use PDO;

// Database connection (adjust for your setup)
$pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);

// Create sitemap
$sitemap = new Sitemap();

// Add static pages
$sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
$sitemap->add('https://example.com/about', date('c'), '0.8', 'monthly');

// Fetch posts from database
$stmt = $pdo->query("
    SELECT slug, updated_at, priority, frequency 
    FROM posts 
    WHERE published = 1 
    ORDER BY updated_at DESC
");

while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sitemap->add(
        "https://example.com/blog/{$post['slug']}",
        date('c', strtotime($post['updated_at'])),
        $post['priority'] ?? '0.7',
        $post['frequency'] ?? 'monthly'
    );
}

// Output XML
header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

## With File-Based Caching

### Implementing Basic Caching

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateCachedSitemap($cacheFile = 'sitemap_cache.xml', $cacheMinutes = 60)
{
    // Check if cache is valid
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < ($cacheMinutes * 60)) {
        // Return cached version
        header('Content-Type: application/xml; charset=utf-8');
        readfile($cacheFile);
        return;
    }

    // Generate new sitemap
    $sitemap = new Sitemap();
    
    // Add static content
    $sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
    
    // Add dynamic content from database
    $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
    
    $stmt = $pdo->query("
        SELECT slug, updated_at 
        FROM posts 
        WHERE published = 1 
        ORDER BY updated_at DESC 
        LIMIT 1000
    ");
    
    while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sitemap->add(
            "https://example.com/blog/{$post['slug']}",
            date('c', strtotime($post['updated_at'])),
            '0.7',
            'monthly'
        );
    }
    
    // Generate XML
    $xml = $sitemap->renderXml();
    
    // Save to cache
    file_put_contents($cacheFile, $xml);
    
    // Output
    header('Content-Type: application/xml; charset=utf-8');
    echo $xml;
}

// Use the function
generateCachedSitemap('cache/sitemap.xml', 60); // Cache for 60 minutes
```

## Advanced Caching with Redis

### Redis-Based Caching

```php
<?php
use Rumenx\Sitemap\Sitemap;
use Redis;

class SitemapCache
{
    private $redis;
    private $cacheKey = 'sitemap:main';
    private $cacheTime = 3600; // 1 hour

    public function __construct()
    {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }

    public function getCachedSitemap()
    {
        return $this->redis->get($this->cacheKey);
    }

    public function setCachedSitemap($xml)
    {
        $this->redis->setex($this->cacheKey, $this->cacheTime, $xml);
    }

    public function isCached()
    {
        return $this->redis->exists($this->cacheKey);
    }

    public function generateSitemap()
    {
        // Check cache first
        if ($this->isCached()) {
            $cached = $this->getCachedSitemap();
            if ($cached) {
                header('Content-Type: application/xml; charset=utf-8');
                echo $cached;
                return;
            }
        }

        // Generate new sitemap
        $sitemap = new Sitemap();
        
        // Add content (your database logic here)
        $this->addStaticPages($sitemap);
        $this->addBlogPosts($sitemap);
        $this->addProducts($sitemap);
        
        // Generate XML
        $xml = $sitemap->renderXml();
        
        // Cache it
        $this->setCachedSitemap($xml);
        
        // Output
        header('Content-Type: application/xml; charset=utf-8');
        echo $xml;
    }

    private function addStaticPages($sitemap)
    {
        $sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
        $sitemap->add('https://example.com/about', date('c'), '0.8', 'monthly');
        $sitemap->add('https://example.com/contact', date('c'), '0.6', 'yearly');
    }

    private function addBlogPosts($sitemap)
    {
        $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
        
        $stmt = $pdo->query("
            SELECT slug, updated_at, priority 
            FROM blog_posts 
            WHERE status = 'published' 
            ORDER BY updated_at DESC
        ");
        
        while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sitemap->add(
                "https://example.com/blog/{$post['slug']}",
                date('c', strtotime($post['updated_at'])),
                $post['priority'] ?? '0.7',
                'monthly'
            );
        }
    }

    private function addProducts($sitemap)
    {
        $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
        
        $stmt = $pdo->query("
            SELECT slug, updated_at 
            FROM products 
            WHERE active = 1 
            ORDER BY updated_at DESC
        ");
        
        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sitemap->add(
                "https://example.com/products/{$product['slug']}",
                date('c', strtotime($product['updated_at'])),
                '0.8',
                'weekly'
            );
        }
    }
}

// Usage
$sitemapCache = new SitemapCache();
$sitemapCache->generateSitemap();
```

## Dynamic Sitemap with Images

### Adding Images from Database

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);

// Get posts with images
$stmt = $pdo->query("
    SELECT p.slug, p.updated_at, p.title,
           GROUP_CONCAT(
               CONCAT(i.url, '|', i.title, '|', i.caption, '|', i.geo_location) 
               SEPARATOR ';'
           ) as images
    FROM posts p
    LEFT JOIN post_images i ON p.id = i.post_id
    WHERE p.published = 1
    GROUP BY p.id
    ORDER BY p.updated_at DESC
");

while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $images = [];
    
    if ($post['images']) {
        $imageData = explode(';', $post['images']);
        foreach ($imageData as $imgString) {
            $imgParts = explode('|', $imgString);
            if (count($imgParts) >= 2) {
                $image = ['url' => $imgParts[0], 'title' => $imgParts[1]];
                if (!empty($imgParts[2])) $image['caption'] = $imgParts[2];
                if (!empty($imgParts[3])) $image['geo_location'] = $imgParts[3];
                $images[] = $image;
            }
        }
    }
    
    $sitemap->add(
        "https://example.com/blog/{$post['slug']}",
        date('c', strtotime($post['updated_at'])),
        '0.7',
        'monthly',
        $images,  // images parameter
        $post['title']  // title parameter
    );
}

header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

## Multi-Language Dynamic Sitemaps

### Adding Translations from Database

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);

// Get posts with translations
$stmt = $pdo->query("
    SELECT p.slug, p.updated_at, p.title, p.lang,
           t.lang as trans_lang, t.slug as trans_slug
    FROM posts p
    LEFT JOIN post_translations t ON p.translation_group_id = t.translation_group_id AND t.lang != p.lang
    WHERE p.published = 1 AND p.lang = 'en'
    ORDER BY p.updated_at DESC
");

$postData = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $slug = $row['slug'];
    if (!isset($postData[$slug])) {
        $postData[$slug] = [
            'slug' => $slug,
            'updated_at' => $row['updated_at'],
            'title' => $row['title'],
            'translations' => []
        ];
    }
    
    if ($row['trans_lang'] && $row['trans_slug']) {
        $postData[$slug]['translations'][] = [
            'language' => $row['trans_lang'],
            'url' => "https://example.com/{$row['trans_lang']}/blog/{$row['trans_slug']}"
        ];
    }
}

foreach ($postData as $post) {
    $sitemap->add(
        "https://example.com/blog/{$post['slug']}",
        date('c', strtotime($post['updated_at'])),
        '0.7',
        'monthly',
        [], // images
        $post['title'], // title
        $post['translations'] // translations
    );
}

header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

## Cache Invalidation Strategies

### Event-Based Cache Clearing

```php
<?php
use Rumenx\Sitemap\Sitemap;

class SitemapManager
{
    private $cacheFile = 'cache/sitemap.xml';
    
    public function invalidateCache()
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }
    
    public function generateSitemap()
    {
        // Check cache
        if (file_exists($this->cacheFile) && (time() - filemtime($this->cacheFile)) < 3600) {
            header('Content-Type: application/xml; charset=utf-8');
            readfile($this->cacheFile);
            return;
        }
        
        // Generate new sitemap
        $sitemap = new Sitemap();
        
        // Your sitemap generation logic here
        $this->populateSitemap($sitemap);
        
        $xml = $sitemap->renderXml();
        file_put_contents($this->cacheFile, $xml);
        
        header('Content-Type: application/xml; charset=utf-8');
        echo $xml;
    }
    
    private function populateSitemap($sitemap)
    {
        // Add your content here
        $sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
        
        // Add database content
        // ... your database logic
    }
    
    // Call this when content is updated
    public function onContentUpdate()
    {
        $this->invalidateCache();
    }
}

// Usage in your CMS/application
$sitemapManager = new SitemapManager();

// When serving sitemap
$sitemapManager->generateSitemap();

// When content is updated (in your admin/CMS)
// $sitemapManager->onContentUpdate();
```

## Performance Considerations

### Optimized Database Queries

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateOptimizedSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
    
    // Use efficient queries with proper indexes
    $stmt = $pdo->prepare("
        SELECT slug, updated_at, priority 
        FROM posts 
        WHERE published = 1 
        AND updated_at > :since
        ORDER BY updated_at DESC 
        LIMIT 10000
    ");
    
    // Only include recently updated content for frequent regeneration
    $since = date('Y-m-d H:i:s', strtotime('-30 days'));
    $stmt->execute(['since' => $since]);
    
    while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sitemap->add(
            "https://example.com/blog/{$post['slug']}",
            date('c', strtotime($post['updated_at'])),
            $post['priority'] ?? '0.7',
            'monthly'
        );
    }
    
    return $sitemap->renderXml();
}
```

## Next Steps

- Learn about [Sitemap Index](sitemap-index.md) for handling multiple sitemaps
- Explore [Large Scale Sitemaps](large-scale-sitemaps.md) for millions of URLs
- Check [Caching Strategies](caching-strategies.md) for advanced optimization
- See [Framework Integration](framework-integration.md) for Laravel/Symfony patterns
