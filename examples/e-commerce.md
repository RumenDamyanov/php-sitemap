# E-commerce Sitemap Examples

Learn how to create comprehensive sitemaps for e-commerce websites using the `rumenx/php-sitemap` package. This guide covers products, categories, brands, reviews, and multi-language stores.

## Product Sitemaps

### Basic Product Sitemap

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);

// Get active products
$stmt = $pdo->query("
    SELECT 
        slug, 
        name, 
        updated_at,
        stock_quantity,
        CASE 
            WHEN featured = 1 THEN '0.9'
            WHEN stock_quantity > 10 THEN '0.8'
            WHEN stock_quantity > 0 THEN '0.7'
            ELSE '0.5'
        END as priority
    FROM products 
    WHERE active = 1 
    ORDER BY updated_at DESC
    LIMIT 50000
");

while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $changefreq = $product['stock_quantity'] > 0 ? 'daily' : 'weekly';
    
    $sitemap->add(
        "https://shop.example.com/products/{$product['slug']}",
        date('c', strtotime($product['updated_at'])),
        $product['priority'],
        $changefreq
    );
}

header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

### Product Sitemap with Images

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateProductSitemapWithImages()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);
    
    // Get products with images
    $stmt = $pdo->prepare("
        SELECT 
            p.slug, 
            p.name, 
            p.updated_at,
            p.description,
            GROUP_CONCAT(pi.image_url) as images
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id
        WHERE p.active = 1 
        GROUP BY p.id
        ORDER BY p.updated_at DESC
        LIMIT 10000
    ");
    
    $stmt->execute();
    
    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $images = [];
        
        if ($product['images']) {
            $imageUrls = explode(',', $product['images']);
            foreach ($imageUrls as $imageUrl) {
                $images[] = [
                    'url' => "https://shop.example.com/images/products/{$imageUrl}",
                    'title' => $product['name'],
                    'caption' => $product['description'] ? substr($product['description'], 0, 100) : null
                ];
            }
        }
        
        $sitemap->add(
            "https://shop.example.com/products/{$product['slug']}",
            date('c', strtotime($product['updated_at'])),
            '0.8',
            'weekly',
            [],
            $product['name'],
            $images
        );
    }
    
    return $sitemap->renderXml();
}

header('Content-Type: application/xml; charset=utf-8');
echo generateProductSitemapWithImages();
```

### Product Variations Sitemap

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateProductVariationsSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);
    
    // Get products with variations
    $stmt = $pdo->query("
        SELECT 
            p.slug as product_slug,
            p.name as product_name,
            p.updated_at,
            v.id as variation_id,
            v.sku,
            v.attributes,
            v.price
        FROM products p
        INNER JOIN product_variations v ON p.id = v.product_id
        WHERE p.active = 1 AND v.stock_quantity > 0
        ORDER BY p.updated_at DESC, v.price ASC
    ");
    
    while ($variation = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Add main product URL
        $sitemap->add(
            "https://shop.example.com/products/{$variation['product_slug']}",
            date('c', strtotime($variation['updated_at'])),
            '0.8',
            'weekly'
        );
        
        // Add variation-specific URL if using SKU-based URLs
        if ($variation['sku']) {
            $sitemap->add(
                "https://shop.example.com/products/{$variation['product_slug']}/sku/{$variation['sku']}",
                date('c', strtotime($variation['updated_at'])),
                '0.7',
                'weekly'
            );
        }
    }
    
    return $sitemap->renderXml();
}

header('Content-Type: application/xml; charset=utf-8');
echo generateProductVariationsSitemap();
```

## Category Sitemaps

### Product Categories

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);

// Get categories with product count
$stmt = $pdo->query("
    SELECT 
        c.slug,
        c.name,
        c.updated_at,
        c.parent_id,
        COUNT(p.id) as product_count,
        CASE 
            WHEN c.parent_id IS NULL THEN '0.9'  -- Main categories
            WHEN COUNT(p.id) > 50 THEN '0.8'     -- Popular subcategories
            WHEN COUNT(p.id) > 10 THEN '0.7'     -- Medium subcategories
            ELSE '0.6'                           -- Small subcategories
        END as priority
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.active = 1
    WHERE c.active = 1
    GROUP BY c.id
    ORDER BY c.parent_id ASC, product_count DESC
");

while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $changefreq = $category['product_count'] > 50 ? 'daily' : 'weekly';
    
    $sitemap->add(
        "https://shop.example.com/categories/{$category['slug']}",
        date('c', strtotime($category['updated_at'])),
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
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);
    
    // Get category hierarchy
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
        SELECT * FROM category_tree ORDER BY level, name
    ");
    
    while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $priority = match($category['level']) {
            0 => '0.9',  // Root categories
            1 => '0.8',  // Level 1 subcategories  
            2 => '0.7',  // Level 2 subcategories
            default => '0.6'  // Deeper levels
        };
        
        $sitemap->add(
            "https://shop.example.com/categories/{$category['full_path']}",
            date('c'),
            $priority,
            'weekly'
        );
    }
    
    return $sitemap->renderXml();
}

header('Content-Type: application/xml; charset=utf-8');
echo generateNestedCategoriesSitemap();
```

## Brand Pages

### Brand Directory

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);

// Get brands with product count
$stmt = $pdo->query("
    SELECT 
        b.slug,
        b.name,
        b.updated_at,
        COUNT(p.id) as product_count,
        CASE 
            WHEN COUNT(p.id) > 100 THEN '0.9'  -- Major brands
            WHEN COUNT(p.id) > 25 THEN '0.8'   -- Popular brands
            WHEN COUNT(p.id) > 5 THEN '0.7'    -- Medium brands
            ELSE '0.6'                         -- Small brands
        END as priority
    FROM brands b
    LEFT JOIN products p ON b.id = p.brand_id AND p.active = 1
    WHERE b.active = 1
    GROUP BY b.id
    HAVING product_count > 0
    ORDER BY product_count DESC
");

while ($brand = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $changefreq = $brand['product_count'] > 50 ? 'daily' : 'weekly';
    
    $sitemap->add(
        "https://shop.example.com/brands/{$brand['slug']}",
        date('c', strtotime($brand['updated_at'])),
        $brand['priority'],
        $changefreq
    );
}

header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

## Multi-Language E-commerce

### Language-Specific Product Pages

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateMultiLanguageProductSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);
    
    $languages = ['en', 'es', 'fr', 'de'];
    
    // Get products with translations
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.slug,
            p.updated_at,
            pt.language,
            pt.translated_slug,
            pt.name as translated_name
        FROM products p
        INNER JOIN product_translations pt ON p.id = pt.product_id
        WHERE p.active = 1
        ORDER BY p.updated_at DESC, pt.language
    ");
    
    $products = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $products[$row['id']][$row['language']] = $row;
    }
    
    foreach ($products as $productId => $translations) {
        foreach ($translations as $lang => $translation) {
            // Build alternate language URLs
            $alternates = [];
            foreach ($translations as $altLang => $altTranslation) {
                if ($altLang !== $lang) {
                    $alternates[] = [
                        'lang' => $altLang,
                        'url' => "https://shop.example.com/{$altLang}/products/{$altTranslation['translated_slug']}"
                    ];
                }
            }
            
            $sitemap->add(
                "https://shop.example.com/{$lang}/products/{$translation['translated_slug']}",
                date('c', strtotime($translation['updated_at'])),
                '0.8',
                'weekly',
                [],
                $translation['translated_name'],
                [],
                [],
                $alternates
            );
        }
    }
    
    return $sitemap->renderXml();
}

header('Content-Type: application/xml; charset=utf-8');
echo generateMultiLanguageProductSitemap();
```

### Currency-Specific Pages

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateCurrencySpecificSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);
    
    $regions = [
        'us' => ['currency' => 'USD', 'lang' => 'en'],
        'eu' => ['currency' => 'EUR', 'lang' => 'en'],
        'uk' => ['currency' => 'GBP', 'lang' => 'en'],
        'ca' => ['currency' => 'CAD', 'lang' => 'en']
    ];
    
    $stmt = $pdo->query("
        SELECT slug, name, updated_at 
        FROM products 
        WHERE active = 1 AND international_shipping = 1
        ORDER BY updated_at DESC
        LIMIT 10000
    ");
    
    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        foreach ($regions as $region => $config) {
            $sitemap->add(
                "https://shop.example.com/{$region}/products/{$product['slug']}",
                date('c', strtotime($product['updated_at'])),
                '0.8',
                'weekly'
            );
        }
    }
    
    return $sitemap->renderXml();
}

header('Content-Type: application/xml; charset=utf-8');
echo generateCurrencySpecificSitemap();
```

## Review and Rating Pages

### Product Reviews

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);

// Get products with significant reviews
$stmt = $pdo->query("
    SELECT 
        p.slug,
        p.name,
        MAX(r.created_at) as last_review_date,
        COUNT(r.id) as review_count,
        AVG(r.rating) as avg_rating
    FROM products p
    INNER JOIN reviews r ON p.id = r.product_id
    WHERE p.active = 1 AND r.approved = 1
    GROUP BY p.id
    HAVING review_count >= 5
    ORDER BY review_count DESC, avg_rating DESC
");

while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Main product page
    $sitemap->add(
        "https://shop.example.com/products/{$product['slug']}",
        date('c', strtotime($product['last_review_date'])),
        '0.8',
        'weekly'
    );
    
    // Reviews page for products with many reviews
    if ($product['review_count'] > 20) {
        $sitemap->add(
            "https://shop.example.com/products/{$product['slug']}/reviews",
            date('c', strtotime($product['last_review_date'])),
            '0.6',
            'weekly'
        );
    }
}

header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

## Shopping Features

### Wishlist and Compare Pages

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Add shopping feature pages
$shoppingPages = [
    'wishlist' => ['priority' => '0.7', 'changefreq' => 'daily'],
    'compare' => ['priority' => '0.6', 'changefreq' => 'weekly'], 
    'cart' => ['priority' => '0.9', 'changefreq' => 'always'],
    'checkout' => ['priority' => '0.9', 'changefreq' => 'monthly'],
    'account' => ['priority' => '0.8', 'changefreq' => 'monthly'],
    'orders' => ['priority' => '0.7', 'changefreq' => 'weekly']
];

foreach ($shoppingPages as $page => $config) {
    $sitemap->add(
        "https://shop.example.com/{$page}",
        date('c'),
        $config['priority'],
        $config['changefreq']
    );
}

header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

### Sale and Promotion Pages

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);

// Get active promotions
$stmt = $pdo->query("
    SELECT 
        slug,
        name,
        start_date,
        end_date,
        updated_at
    FROM promotions 
    WHERE active = 1 
    AND start_date <= NOW() 
    AND (end_date IS NULL OR end_date >= NOW())
    ORDER BY updated_at DESC
");

while ($promotion = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sitemap->add(
        "https://shop.example.com/sales/{$promotion['slug']}",
        date('c', strtotime($promotion['updated_at'])),
        '0.9',
        'daily'
    );
}

// Add general sale pages
$salePages = [
    'sale' => '0.9',
    'clearance' => '0.8', 
    'new-arrivals' => '0.9',
    'bestsellers' => '0.8',
    'featured' => '0.8'
];

foreach ($salePages as $page => $priority) {
    $sitemap->add(
        "https://shop.example.com/{$page}",
        date('c'),
        $priority,
        'daily'
    );
}

header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

## Search and Filter Pages

### Search Result Pages

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateSearchPagesSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);
    
    // Get popular search terms
    $stmt = $pdo->query("
        SELECT 
            search_term,
            COUNT(*) as search_count,
            MAX(searched_at) as last_searched
        FROM search_logs 
        WHERE searched_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND results_count > 0
        GROUP BY search_term
        HAVING search_count >= 10
        ORDER BY search_count DESC
        LIMIT 1000
    ");
    
    while ($search = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $encodedTerm = urlencode($search['search_term']);
        
        $sitemap->add(
            "https://shop.example.com/search?q={$encodedTerm}",
            date('c', strtotime($search['last_searched'])),
            '0.6',
            'weekly'
        );
    }
    
    return $sitemap->renderXml();
}

header('Content-Type: application/xml; charset=utf-8');
echo generateSearchPagesSitemap();
```

### Filter Combination Pages

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateFilterPagesSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);
    
    // Get popular filter combinations
    $stmt = $pdo->query("
        SELECT DISTINCT
            c.slug as category_slug,
            b.slug as brand_slug,
            CONCAT(c.slug, '/', b.slug) as filter_path,
            COUNT(p.id) as product_count
        FROM categories c
        INNER JOIN products p ON c.id = p.category_id
        INNER JOIN brands b ON p.brand_id = b.id
        WHERE c.active = 1 AND b.active = 1 AND p.active = 1
        GROUP BY c.id, b.id
        HAVING product_count >= 5
        ORDER BY product_count DESC
        LIMIT 1000
    ");
    
    while ($filter = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $priority = $filter['product_count'] > 50 ? '0.8' : '0.6';
        
        $sitemap->add(
            "https://shop.example.com/categories/{$filter['filter_path']}",
            date('c'),
            $priority,
            'weekly'
        );
    }
    
    return $sitemap->renderXml();
}

header('Content-Type: application/xml; charset=utf-8');
echo generateFilterPagesSitemap();
```

## Complete E-commerce Sitemap Generator

### All-in-One E-commerce Sitemap

```php
<?php
use Rumenx\Sitemap\Sitemap;

class EcommerceSitemapGenerator
{
    private $pdo;
    private $baseUrl;
    
    public function __construct($dbConfig, $baseUrl)
    {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    public function generateProductSitemap()
    {
        $sitemap = new Sitemap();
        
        $stmt = $this->pdo->query("
            SELECT 
                p.slug,
                p.name,
                p.updated_at,
                p.stock_quantity,
                COALESCE(AVG(r.rating), 0) as avg_rating,
                COUNT(r.id) as review_count,
                GROUP_CONCAT(DISTINCT pi.image_url) as images
            FROM products p
            LEFT JOIN reviews r ON p.id = r.product_id AND r.approved = 1
            LEFT JOIN product_images pi ON p.id = pi.product_id
            WHERE p.active = 1
            GROUP BY p.id
            ORDER BY p.updated_at DESC
            LIMIT 50000
        ");
        
        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Calculate priority based on multiple factors
            $priority = $this->calculateProductPriority(
                $product['stock_quantity'],
                $product['avg_rating'],
                $product['review_count']
            );
            
            $images = [];
            if ($product['images']) {
                $imageUrls = explode(',', $product['images']);
                foreach (array_slice($imageUrls, 0, 5) as $imageUrl) { // Max 5 images
                    $images[] = [
                        'url' => "{$this->baseUrl}/images/products/{$imageUrl}",
                        'title' => $product['name']
                    ];
                }
            }
            
            $sitemap->add(
                "{$this->baseUrl}/products/{$product['slug']}",
                date('c', strtotime($product['updated_at'])),
                $priority,
                $product['stock_quantity'] > 0 ? 'daily' : 'weekly',
                [],
                $product['name'],
                $images
            );
        }
        
        return $sitemap->renderXml();
    }
    
    public function generateCategorySitemap()
    {
        $sitemap = new Sitemap();
        
        $stmt = $this->pdo->query("
            SELECT 
                c.slug,
                c.name,
                c.updated_at,
                COUNT(p.id) as product_count,
                c.parent_id
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.active = 1
            WHERE c.active = 1
            GROUP BY c.id
            ORDER BY c.parent_id ASC, product_count DESC
        ");
        
        while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $priority = $this->calculateCategoryPriority(
                $category['product_count'],
                $category['parent_id']
            );
            
            $sitemap->add(
                "{$this->baseUrl}/categories/{$category['slug']}",
                date('c', strtotime($category['updated_at'])),
                $priority,
                $category['product_count'] > 50 ? 'daily' : 'weekly'
            );
        }
        
        return $sitemap->renderXml();
    }
    
    public function generateBrandSitemap()
    {
        $sitemap = new Sitemap();
        
        $stmt = $this->pdo->query("
            SELECT 
                b.slug,
                b.name,
                b.updated_at,
                COUNT(p.id) as product_count
            FROM brands b
            LEFT JOIN products p ON b.id = p.brand_id AND p.active = 1
            WHERE b.active = 1
            GROUP BY b.id
            HAVING product_count > 0
            ORDER BY product_count DESC
        ");
        
        while ($brand = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $priority = min(0.9, 0.5 + ($brand['product_count'] / 200));
            
            $sitemap->add(
                "{$this->baseUrl}/brands/{$brand['slug']}",
                date('c', strtotime($brand['updated_at'])),
                number_format($priority, 1),
                $brand['product_count'] > 50 ? 'daily' : 'weekly'
            );
        }
        
        return $sitemap->renderXml();
    }
    
    public function generateSitemapIndex()
    {
        $sitemapIndex = new Sitemap();
        
        $sitemaps = [
            'sitemap-products.xml' => date('c'),
            'sitemap-categories.xml' => date('c'),
            'sitemap-brands.xml' => date('c'),
            'sitemap-pages.xml' => date('c')
        ];
        
        foreach ($sitemaps as $sitemap => $lastmod) {
            $sitemapIndex->addSitemap("{$this->baseUrl}/{$sitemap}", $lastmod);
        }
        
        $items = $sitemapIndex->getModel()->getSitemaps();
        return view('sitemap.sitemapindex', compact('items'))->render();
    }
    
    private function calculateProductPriority($stock, $rating, $reviewCount)
    {
        $priority = 0.5; // Base priority
        
        // Stock bonus
        if ($stock > 20) $priority += 0.2;
        elseif ($stock > 0) $priority += 0.1;
        
        // Rating bonus
        if ($rating >= 4.5) $priority += 0.2;
        elseif ($rating >= 4.0) $priority += 0.1;
        
        // Review count bonus
        if ($reviewCount > 50) $priority += 0.1;
        elseif ($reviewCount > 10) $priority += 0.05;
        
        return number_format(min(1.0, $priority), 1);
    }
    
    private function calculateCategoryPriority($productCount, $parentId)
    {
        $priority = 0.5; // Base priority
        
        // Main category bonus
        if ($parentId === null) $priority += 0.3;
        
        // Product count bonus
        if ($productCount > 100) $priority += 0.2;
        elseif ($productCount > 25) $priority += 0.1;
        elseif ($productCount > 5) $priority += 0.05;
        
        return number_format(min(1.0, $priority), 1);
    }
}

// Usage
$config = [
    'host' => 'localhost',
    'name' => 'ecommerce',
    'user' => 'dbuser',
    'pass' => 'dbpass'
];

$generator = new EcommerceSitemapGenerator($config, 'https://shop.example.com');

// Generate specific sitemap based on request
$type = $_GET['type'] ?? 'index';

header('Content-Type: application/xml; charset=utf-8');

switch ($type) {
    case 'products':
        echo $generator->generateProductSitemap();
        break;
    case 'categories':
        echo $generator->generateCategorySitemap();
        break;
    case 'brands':
        echo $generator->generateBrandSitemap();
        break;
    case 'index':
    default:
        echo $generator->generateSitemapIndex();
        break;
}
```

## Performance Optimization for Large Stores

### Batch Processing for Million+ Products

```php
<?php
use Rumenx\Sitemap\Sitemap;

class LargeStoreSitemapGenerator
{
    private $pdo;
    private $baseUrl;
    private $batchSize = 10000;
    
    public function generateProductSitemapsBatch()
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM products WHERE active = 1");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $sitemapIndex = new Sitemap();
        $sitemapFiles = [];
        
        for ($offset = 0; $offset < $total; $offset += 50000) {
            $filename = "sitemap-products-" . ($offset / 50000 + 1) . ".xml";
            $this->generateProductBatch($offset, 50000, $filename);
            
            $sitemapFiles[] = $filename;
            $sitemapIndex->addSitemap("{$this->baseUrl}/{$filename}", date('c'));
        }
        
        // Generate index
        $items = $sitemapIndex->getModel()->getSitemaps();
        $indexXml = view('sitemap.sitemapindex', compact('items'))->render();
        file_put_contents('sitemap.xml', $indexXml);
        
        return $sitemapFiles;
    }
    
    private function generateProductBatch($offset, $limit, $filename)
    {
        $sitemap = new Sitemap();
        
        $stmt = $this->pdo->prepare("
            SELECT slug, name, updated_at, stock_quantity
            FROM products 
            WHERE active = 1 
            ORDER BY id 
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sitemap->add(
                "{$this->baseUrl}/products/{$product['slug']}",
                date('c', strtotime($product['updated_at'])),
                '0.8',
                'weekly'
            );
        }
        
        file_put_contents($filename, $sitemap->renderXml());
    }
}
```

## Next Steps

- Learn about [Multi-language Examples](multilingual.md) for international stores
- Explore [Caching Strategies](caching-strategies.md) for e-commerce optimization
- Check [Memory Optimization](memory-optimization.md) for large product catalogs
- See [Automated Generation](automated-generation.md) for scheduled sitemap updates
