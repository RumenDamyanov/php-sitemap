# Blog & CMS Sitemap Examples

Learn how to create comprehensive sitemaps for blogs and content management systems using the `rumenx/php-sitemap` package. This guide covers posts, pages, categories, tags, authors, and archives.

## Blog Post Sitemaps

### Basic Blog Sitemap

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=blog', $username, $password);

// Get published blog posts
$stmt = $pdo->query("
    SELECT 
        slug, 
        title,
        published_at,
        updated_at,
        view_count,
        comment_count,
        CASE 
            WHEN featured = 1 THEN '0.9'
            WHEN view_count > 1000 THEN '0.8'
            WHEN view_count > 100 THEN '0.7'
            ELSE '0.6'
        END as priority
    FROM posts 
    WHERE published = 1 
    AND published_at <= NOW()
    ORDER BY published_at DESC
    LIMIT 50000
");

while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Use updated_at if available, otherwise published_at
    $lastmod = $post['updated_at'] ?: $post['published_at'];
    
    // More popular posts change more frequently
    $changefreq = $post['view_count'] > 500 ? 'weekly' : 'monthly';
    
    $sitemap->add(
        "https://blog.example.com/posts/{$post['slug']}",
        date('c', strtotime($lastmod)),
        $post['priority'],
        $changefreq
    );
}

header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

### Blog Posts with Images

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateBlogSitemapWithImages()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=blog', $username, $password);
    
    // Get posts with featured images and gallery images
    $stmt = $pdo->query("
        SELECT 
            p.slug,
            p.title,
            p.excerpt,
            p.published_at,
            p.updated_at,
            p.featured_image,
            GROUP_CONCAT(pi.image_url) as gallery_images
        FROM posts p
        LEFT JOIN post_images pi ON p.id = pi.post_id
        WHERE p.published = 1 
        AND p.published_at <= NOW()
        GROUP BY p.id
        ORDER BY p.published_at DESC
        LIMIT 10000
    ");
    
    while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $images = [];
        
        // Add featured image
        if ($post['featured_image']) {
            $images[] = [
                'url' => "https://blog.example.com/images/{$post['featured_image']}",
                'title' => $post['title'],
                'caption' => $post['excerpt'] ? substr($post['excerpt'], 0, 150) : null
            ];
        }
        
        // Add gallery images
        if ($post['gallery_images']) {
            $galleryUrls = explode(',', $post['gallery_images']);
            foreach (array_slice($galleryUrls, 0, 4) as $imageUrl) { // Max 5 images total
                $images[] = [
                    'url' => "https://blog.example.com/images/{$imageUrl}",
                    'title' => $post['title']
                ];
            }
        }
        
        $lastmod = $post['updated_at'] ?: $post['published_at'];
        
        $sitemap->add(
            "https://blog.example.com/posts/{$post['slug']}",
            date('c', strtotime($lastmod)),
            '0.7',
            'monthly',
            [],
            $post['title'],
            $images
        );
    }
    
    return $sitemap->renderXml();
}

header('Content-Type: application/xml; charset=utf-8');
echo generateBlogSitemapWithImages();
```

### Blog Posts by Category

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateCategoryBasedBlogSitemap($categorySlug = null)
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=blog', $username, $password);
    
    $whereClause = "WHERE p.published = 1 AND p.published_at <= NOW()";
    $params = [];
    
    if ($categorySlug) {
        $whereClause .= " AND c.slug = :category_slug";
        $params['category_slug'] = $categorySlug;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            p.slug,
            p.title,
            p.published_at,
            p.updated_at,
            p.view_count,
            c.slug as category_slug,
            c.name as category_name
        FROM posts p
        INNER JOIN categories c ON p.category_id = c.id
        {$whereClause}
        ORDER BY p.published_at DESC
        LIMIT 50000
    ");
    
    $stmt->execute($params);
    
    while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lastmod = $post['updated_at'] ?: $post['published_at'];
        
        $sitemap->add(
            "https://blog.example.com/{$post['category_slug']}/{$post['slug']}",
            date('c', strtotime($lastmod)),
            '0.7',
            'monthly'
        );
    }
    
    return $sitemap->renderXml();
}

// Usage: Generate sitemap for specific category or all posts
$category = $_GET['category'] ?? null;
header('Content-Type: application/xml; charset=utf-8');
echo generateCategoryBasedBlogSitemap($category);
```

## Page Sitemaps

### Static Pages

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=cms', $username, $password);

// Get static pages
$stmt = $pdo->query("
    SELECT 
        slug,
        title,
        updated_at,
        page_type,
        CASE 
            WHEN page_type = 'homepage' THEN '1.0'
            WHEN page_type = 'about' THEN '0.8'
            WHEN page_type = 'contact' THEN '0.7'
            WHEN page_type = 'landing' THEN '0.9'
            ELSE '0.6'
        END as priority,
        CASE 
            WHEN page_type = 'homepage' THEN 'daily'
            WHEN page_type = 'landing' THEN 'weekly'
            ELSE 'monthly'
        END as changefreq
    FROM pages 
    WHERE published = 1 
    AND status = 'active'
    ORDER BY priority DESC, updated_at DESC
");

while ($page = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $url = $page['page_type'] === 'homepage' 
        ? 'https://example.com/' 
        : "https://example.com/{$page['slug']}";
    
    $sitemap->add(
        $url,
        date('c', strtotime($page['updated_at'])),
        $page['priority'],
        $page['changefreq']
    );
}

header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

### Hierarchical Pages

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateHierarchicalPagesSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=cms', $username, $password);
    
    // Get page hierarchy using recursive CTE
    $stmt = $pdo->query("
        WITH RECURSIVE page_tree AS (
            SELECT id, slug, title, parent_id, updated_at, 0 as level, slug as full_path
            FROM pages 
            WHERE parent_id IS NULL AND published = 1
            
            UNION ALL
            
            SELECT p.id, p.slug, p.title, p.parent_id, p.updated_at, pt.level + 1,
                   CONCAT(pt.full_path, '/', p.slug) as full_path
            FROM pages p
            INNER JOIN page_tree pt ON p.parent_id = pt.id
            WHERE p.published = 1
        )
        SELECT * FROM page_tree ORDER BY level, title
    ");
    
    while ($page = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $priority = match($page['level']) {
            0 => '0.9',  // Top-level pages
            1 => '0.8',  // Second-level pages
            2 => '0.7',  // Third-level pages
            default => '0.6'  // Deeper pages
        };
        
        $sitemap->add(
            "https://example.com/pages/{$page['full_path']}",
            date('c', strtotime($page['updated_at'])),
            $priority,
            'monthly'
        );
    }
    
    return $sitemap->renderXml();
}

header('Content-Type: application/xml; charset=utf-8');
echo generateHierarchicalPagesSitemap();
```

## Category Sitemaps

### Blog Categories

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=blog', $username, $password);

// Get categories with post counts
$stmt = $pdo->query("
    SELECT 
        c.slug,
        c.name,
        c.description,
        c.updated_at,
        COUNT(p.id) as post_count,
        MAX(p.published_at) as last_post_date,
        CASE 
            WHEN COUNT(p.id) > 50 THEN '0.9'
            WHEN COUNT(p.id) > 10 THEN '0.8'
            WHEN COUNT(p.id) > 1 THEN '0.7'
            ELSE '0.6'
        END as priority
    FROM categories c
    LEFT JOIN posts p ON c.id = p.category_id AND p.published = 1
    WHERE c.active = 1
    GROUP BY c.id
    ORDER BY post_count DESC
");

while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $lastmod = $category['last_post_date'] ?: $category['updated_at'];
    $changefreq = $category['post_count'] > 20 ? 'daily' : 'weekly';
    
    $sitemap->add(
        "https://blog.example.com/categories/{$category['slug']}",
        date('c', strtotime($lastmod)),
        $category['priority'],
        $changefreq
    );
}

header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

### Nested Categories

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateNestedCategoriesSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=blog', $username, $password);
    
    // Get category hierarchy with post counts
    $stmt = $pdo->query("
        WITH RECURSIVE category_tree AS (
            SELECT id, slug, name, parent_id, 0 as level, slug as full_path
            FROM categories 
            WHERE parent_id IS NULL AND active = 1
            
            UNION ALL
            
            SELECT c.id, c.slug, c.name, c.parent_id, ct.level + 1,
                   CONCAT(ct.full_path, '/', c.slug) as full_path
            FROM categories c
            INNER JOIN category_tree ct ON c.parent_id = ct.id
            WHERE c.active = 1
        )
        SELECT 
            ct.*,
            COUNT(p.id) as post_count,
            MAX(p.published_at) as last_post_date
        FROM category_tree ct
        LEFT JOIN posts p ON ct.id = p.category_id AND p.published = 1
        GROUP BY ct.id
        ORDER BY ct.level, ct.name
    ");
    
    while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $priority = match($category['level']) {
            0 => '0.9',  // Root categories
            1 => '0.8',  // Level 1 subcategories
            2 => '0.7',  // Level 2 subcategories
            default => '0.6'  // Deeper levels
        };
        
        // Boost priority for categories with many posts
        if ($category['post_count'] > 50) {
            $priority = min(1.0, floatval($priority) + 0.1);
        }
        
        $lastmod = $category['last_post_date'] ?: date('c');
        
        $sitemap->add(
            "https://blog.example.com/categories/{$category['full_path']}",
            $lastmod,
            number_format($priority, 1),
            $category['post_count'] > 10 ? 'weekly' : 'monthly'
        );
    }
    
    return $sitemap->renderXml();
}

header('Content-Type: application/xml; charset=utf-8');
echo generateNestedCategoriesSitemap();
```

## Tag Sitemaps

### Blog Tags

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=blog', $username, $password);

// Get tags with post counts (only include tags with multiple posts)
$stmt = $pdo->query("
    SELECT 
        t.slug,
        t.name,
        COUNT(pt.post_id) as post_count,
        MAX(p.published_at) as last_post_date,
        CASE 
            WHEN COUNT(pt.post_id) > 20 THEN '0.8'
            WHEN COUNT(pt.post_id) > 5 THEN '0.7'
            ELSE '0.6'
        END as priority
    FROM tags t
    INNER JOIN post_tags pt ON t.id = pt.tag_id
    INNER JOIN posts p ON pt.post_id = p.id
    WHERE p.published = 1 AND p.published_at <= NOW()
    GROUP BY t.id
    HAVING post_count >= 3  -- Only include tags with 3+ posts
    ORDER BY post_count DESC
    LIMIT 1000  -- Limit to top 1000 tags
");

while ($tag = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $changefreq = $tag['post_count'] > 10 ? 'weekly' : 'monthly';
    
    $sitemap->add(
        "https://blog.example.com/tags/{$tag['slug']}",
        date('c', strtotime($tag['last_post_date'])),
        $tag['priority'],
        $changefreq
    );
}

header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

## Author Pages

### Author Profiles

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=blog', $username, $password);

// Get authors with published posts
$stmt = $pdo->query("
    SELECT 
        u.username,
        u.display_name,
        u.updated_at,
        COUNT(p.id) as post_count,
        MAX(p.published_at) as last_post_date,
        AVG(p.view_count) as avg_post_views,
        CASE 
            WHEN COUNT(p.id) > 50 THEN '0.8'
            WHEN COUNT(p.id) > 10 THEN '0.7'
            WHEN COUNT(p.id) > 1 THEN '0.6'
            ELSE '0.5'
        END as priority
    FROM users u
    INNER JOIN posts p ON u.id = p.author_id
    WHERE p.published = 1 AND u.active = 1
    GROUP BY u.id
    HAVING post_count > 0
    ORDER BY post_count DESC
");

while ($author = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $lastmod = $author['last_post_date'] ?: $author['updated_at'];
    $changefreq = $author['post_count'] > 20 ? 'weekly' : 'monthly';
    
    $sitemap->add(
        "https://blog.example.com/authors/{$author['username']}",
        date('c', strtotime($lastmod)),
        $author['priority'],
        $changefreq
    );
}

header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

## Archive Pages

### Date-Based Archives

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateArchiveSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=blog', $username, $password);
    
    // Get monthly archives with post counts
    $stmt = $pdo->query("
        SELECT 
            YEAR(published_at) as year,
            MONTH(published_at) as month,
            COUNT(*) as post_count,
            MAX(published_at) as last_post_date
        FROM posts 
        WHERE published = 1 
        AND published_at <= NOW()
        GROUP BY YEAR(published_at), MONTH(published_at)
        HAVING post_count > 0
        ORDER BY year DESC, month DESC
    ");
    
    while ($archive = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $priority = $archive['post_count'] > 10 ? '0.7' : '0.6';
        
        // Recent months get higher priority
        $monthsAgo = (date('Y') - $archive['year']) * 12 + (date('n') - $archive['month']);
        if ($monthsAgo <= 12) {
            $priority = min(0.8, floatval($priority) + 0.1);
        }
        
        $sitemap->add(
            "https://blog.example.com/archives/{$archive['year']}/{$archive['month']}",
            date('c', strtotime($archive['last_post_date'])),
            $priority,
            $monthsAgo <= 3 ? 'weekly' : 'monthly'
        );
    }
    
    // Add yearly archives
    $stmt = $pdo->query("
        SELECT 
            YEAR(published_at) as year,
            COUNT(*) as post_count,
            MAX(published_at) as last_post_date
        FROM posts 
        WHERE published = 1 
        AND published_at <= NOW()
        GROUP BY YEAR(published_at)
        HAVING post_count > 0
        ORDER BY year DESC
    ");
    
    while ($archive = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $priority = $archive['post_count'] > 50 ? '0.8' : '0.7';
        
        $sitemap->add(
            "https://blog.example.com/archives/{$archive['year']}",
            date('c', strtotime($archive['last_post_date'])),
            $priority,
            $archive['year'] == date('Y') ? 'weekly' : 'monthly'
        );
    }
    
    return $sitemap->renderXml();
}

header('Content-Type: application/xml; charset=utf-8');
echo generateArchiveSitemap();
```

## Multi-Site CMS

### Multiple Blogs/Sites

```php
<?php
use Rumenx\Sitemap\Sitemap;

class MultiSiteCMSSitemapGenerator
{
    private $pdo;
    
    public function __construct($dbConfig)
    {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    }
    
    public function generateSiteSpecificSitemap($siteId)
    {
        $sitemap = new Sitemap();
        
        // Get site information
        $siteStmt = $this->pdo->prepare("
            SELECT domain, name, default_language 
            FROM sites 
            WHERE id = :site_id AND active = 1
        ");
        $siteStmt->execute(['site_id' => $siteId]);
        $site = $siteStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$site) {
            throw new Exception("Site not found or inactive");
        }
        
        $baseUrl = "https://{$site['domain']}";
        
        // Get posts for this site
        $stmt = $this->pdo->prepare("
            SELECT 
                p.slug,
                p.title,
                p.published_at,
                p.updated_at,
                c.slug as category_slug
            FROM posts p
            INNER JOIN categories c ON p.category_id = c.id
            WHERE p.site_id = :site_id 
            AND p.published = 1 
            AND p.published_at <= NOW()
            ORDER BY p.published_at DESC
            LIMIT 50000
        ");
        
        $stmt->execute(['site_id' => $siteId]);
        
        while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lastmod = $post['updated_at'] ?: $post['published_at'];
            
            $sitemap->add(
                "{$baseUrl}/{$post['category_slug']}/{$post['slug']}",
                date('c', strtotime($lastmod)),
                '0.7',
                'monthly'
            );
        }
        
        // Get pages for this site
        $pageStmt = $this->pdo->prepare("
            SELECT slug, title, updated_at, page_type
            FROM pages 
            WHERE site_id = :site_id AND published = 1
            ORDER BY updated_at DESC
        ");
        
        $pageStmt->execute(['site_id' => $siteId]);
        
        while ($page = $pageStmt->fetch(PDO::FETCH_ASSOC)) {
            $priority = $page['page_type'] === 'homepage' ? '1.0' : '0.8';
            
            $url = $page['page_type'] === 'homepage' 
                ? $baseUrl . '/' 
                : "{$baseUrl}/{$page['slug']}";
            
            $sitemap->add(
                $url,
                date('c', strtotime($page['updated_at'])),
                $priority,
                'monthly'
            );
        }
        
        return $sitemap->renderXml();
    }
    
    public function generateAllSitesSitemapIndex()
    {
        $sitemapIndex = new Sitemap();
        
        // Get all active sites
        $stmt = $this->pdo->query("
            SELECT id, domain, name, updated_at
            FROM sites 
            WHERE active = 1
            ORDER BY name
        ");
        
        while ($site = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sitemapIndex->addSitemap(
                "https://{$site['domain']}/sitemap.xml",
                date('c', strtotime($site['updated_at']))
            );
        }
        
        $items = $sitemapIndex->getModel()->getSitemaps();
        return view('sitemap.sitemapindex', compact('items'))->render();
    }
}

// Usage
$config = [
    'host' => 'localhost',
    'name' => 'multi_cms',
    'user' => 'dbuser',
    'pass' => 'dbpass'
];

$generator = new MultiSiteCMSSitemapGenerator($config);

$siteId = $_GET['site'] ?? null;

header('Content-Type: application/xml; charset=utf-8');

if ($siteId) {
    echo $generator->generateSiteSpecificSitemap($siteId);
} else {
    echo $generator->generateAllSitesSitemapIndex();
}
```

## RSS Feed Integration

### Convert RSS to Sitemap

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateSitemapFromRSS($rssFeedUrl)
{
    $sitemap = new Sitemap();
    
    // Load RSS feed
    $rss = simplexml_load_file($rssFeedUrl);
    
    if (!$rss) {
        throw new Exception("Could not load RSS feed");
    }
    
    foreach ($rss->channel->item as $item) {
        $pubDate = (string)$item->pubDate;
        $lastmod = $pubDate ? date('c', strtotime($pubDate)) : date('c');
        
        $sitemap->add(
            (string)$item->link,
            $lastmod,
            '0.7',
            'weekly'
        );
    }
    
    return $sitemap->renderXml();
}

// Usage
$rssUrl = $_GET['rss'] ?? 'https://blog.example.com/feed.xml';

header('Content-Type: application/xml; charset=utf-8');
echo generateSitemapFromRSS($rssUrl);
```

## Complete Blog/CMS Sitemap Generator

### All-in-One Blog Sitemap

```php
<?php
use Rumenx\Sitemap\Sitemap;

class BlogCMSSitemapGenerator
{
    private $pdo;
    private $baseUrl;
    
    public function __construct($dbConfig, $baseUrl)
    {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    public function generatePostsSitemap()
    {
        $sitemap = new Sitemap();
        
        $stmt = $this->pdo->query("
            SELECT 
                p.slug,
                p.title,
                p.excerpt,
                p.published_at,
                p.updated_at,
                p.featured_image,
                p.view_count,
                p.comment_count,
                c.slug as category_slug,
                u.username as author_username
            FROM posts p
            INNER JOIN categories c ON p.category_id = c.id
            INNER JOIN users u ON p.author_id = u.id
            WHERE p.published = 1 
            AND p.published_at <= NOW()
            ORDER BY p.published_at DESC
            LIMIT 50000
        ");
        
        while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $images = [];
            
            if ($post['featured_image']) {
                $images[] = [
                    'url' => "{$this->baseUrl}/images/{$post['featured_image']}",
                    'title' => $post['title'],
                    'caption' => $post['excerpt'] ? substr($post['excerpt'], 0, 150) : null
                ];
            }
            
            $priority = $this->calculatePostPriority(
                $post['view_count'],
                $post['comment_count']
            );
            
            $lastmod = $post['updated_at'] ?: $post['published_at'];
            
            $sitemap->add(
                "{$this->baseUrl}/posts/{$post['slug']}",
                date('c', strtotime($lastmod)),
                $priority,
                $post['view_count'] > 1000 ? 'weekly' : 'monthly',
                [],
                $post['title'],
                $images
            );
        }
        
        return $sitemap->renderXml();
    }
    
    public function generatePagesSitemap()
    {
        $sitemap = new Sitemap();
        
        $stmt = $this->pdo->query("
            SELECT slug, title, updated_at, page_type
            FROM pages 
            WHERE published = 1
            ORDER BY page_type = 'homepage' DESC, updated_at DESC
        ");
        
        while ($page = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $priority = match($page['page_type']) {
                'homepage' => '1.0',
                'about' => '0.8',
                'contact' => '0.7',
                'landing' => '0.9',
                default => '0.6'
            };
            
            $url = $page['page_type'] === 'homepage' 
                ? $this->baseUrl . '/' 
                : "{$this->baseUrl}/{$page['slug']}";
            
            $sitemap->add(
                $url,
                date('c', strtotime($page['updated_at'])),
                $priority,
                $page['page_type'] === 'homepage' ? 'daily' : 'monthly'
            );
        }
        
        return $sitemap->renderXml();
    }
    
    public function generateCategoriesSitemap()
    {
        $sitemap = new Sitemap();
        
        $stmt = $this->pdo->query("
            SELECT 
                c.slug,
                c.name,
                COUNT(p.id) as post_count,
                MAX(p.published_at) as last_post_date
            FROM categories c
            LEFT JOIN posts p ON c.id = p.category_id AND p.published = 1
            WHERE c.active = 1
            GROUP BY c.id
            ORDER BY post_count DESC
        ");
        
        while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $priority = $this->calculateCategoryPriority($category['post_count']);
            $lastmod = $category['last_post_date'] ?: date('c');
            
            $sitemap->add(
                "{$this->baseUrl}/categories/{$category['slug']}",
                $lastmod,
                $priority,
                $category['post_count'] > 20 ? 'weekly' : 'monthly'
            );
        }
        
        return $sitemap->renderXml();
    }
    
    public function generateSitemapIndex()
    {
        $sitemapIndex = new Sitemap();
        
        $sitemaps = [
            'sitemap-posts.xml' => date('c'),
            'sitemap-pages.xml' => date('c'),
            'sitemap-categories.xml' => date('c'),
            'sitemap-authors.xml' => date('c'),
            'sitemap-archives.xml' => date('c')
        ];
        
        foreach ($sitemaps as $sitemap => $lastmod) {
            $sitemapIndex->addSitemap("{$this->baseUrl}/{$sitemap}", $lastmod);
        }
        
        $items = $sitemapIndex->getModel()->getSitemaps();
        return view('sitemap.sitemapindex', compact('items'))->render();
    }
    
    private function calculatePostPriority($viewCount, $commentCount)
    {
        $priority = 0.5; // Base priority
        
        // View count bonus
        if ($viewCount > 5000) $priority += 0.3;
        elseif ($viewCount > 1000) $priority += 0.2;
        elseif ($viewCount > 100) $priority += 0.1;
        
        // Comment count bonus
        if ($commentCount > 20) $priority += 0.1;
        elseif ($commentCount > 5) $priority += 0.05;
        
        return number_format(min(1.0, $priority), 1);
    }
    
    private function calculateCategoryPriority($postCount)
    {
        $priority = 0.5; // Base priority
        
        if ($postCount > 50) $priority += 0.3;
        elseif ($postCount > 10) $priority += 0.2;
        elseif ($postCount > 1) $priority += 0.1;
        
        return number_format(min(1.0, $priority), 1);
    }
}

// Usage
$config = [
    'host' => 'localhost',
    'name' => 'blog',
    'user' => 'dbuser',
    'pass' => 'dbpass'
];

$generator = new BlogCMSSitemapGenerator($config, 'https://blog.example.com');

$type = $_GET['type'] ?? 'index';

header('Content-Type: application/xml; charset=utf-8');

switch ($type) {
    case 'posts':
        echo $generator->generatePostsSitemap();
        break;
    case 'pages':
        echo $generator->generatePagesSitemap();
        break;
    case 'categories':
        echo $generator->generateCategoriesSitemap();
        break;
    case 'index':
    default:
        echo $generator->generateSitemapIndex();
        break;
}
```

## Performance Tips for Large Blogs

### Pagination for Large Post Collections

```php
<?php
// Generate paginated sitemaps for blogs with 100k+ posts
function generatePaginatedPostSitemaps()
{
    $postsPerSitemap = 50000;
    $pdo = new PDO('mysql:host=localhost;dbname=blog', $username, $password);
    
    // Get total post count
    $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE published = 1");
    $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $sitemapIndex = new Sitemap();
    
    for ($offset = 0; $offset < $total; $offset += $postsPerSitemap) {
        $page = ($offset / $postsPerSitemap) + 1;
        $filename = "sitemap-posts-{$page}.xml";
        
        $sitemap = new Sitemap();
        
        $stmt = $pdo->prepare("
            SELECT slug, title, published_at, updated_at
            FROM posts 
            WHERE published = 1 
            ORDER BY published_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':limit', $postsPerSitemap, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lastmod = $post['updated_at'] ?: $post['published_at'];
            
            $sitemap->add(
                "https://blog.example.com/posts/{$post['slug']}",
                date('c', strtotime($lastmod)),
                '0.7',
                'monthly'
            );
        }
        
        // Save individual sitemap file
        file_put_contents($filename, $sitemap->renderXml());
        
        // Add to index
        $sitemapIndex->addSitemap("https://blog.example.com/{$filename}", date('c'));
    }
    
    // Generate sitemap index
    $items = $sitemapIndex->getModel()->getSitemaps();
    $indexXml = view('sitemap.sitemapindex', compact('items'))->render();
    file_put_contents('sitemap.xml', $indexXml);
}
```

## Next Steps

- Learn about [Multi-language Examples](multilingual.md) for international blogs
- Explore [Caching Strategies](caching-strategies.md) for blog optimization
- Check [Memory Optimization](memory-optimization.md) for large content sites
- See [Automated Generation](automated-generation.md) for scheduled content updates
