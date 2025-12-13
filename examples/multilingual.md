# Multilingual Sitemap Examples

Learn how to create comprehensive sitemaps for multilingual websites using the `rumenx/php-sitemap` package. This guide covers language alternates, hreflang implementation, and region-specific content.

## Basic Multilingual Sitemap

### Simple Language Alternatives

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=multilingual', $username, $password);

$languages = ['en', 'es', 'fr', 'de', 'it'];

// Get pages with translations
$stmt = $pdo->query("
    SELECT 
        p.id,
        p.slug as original_slug,
        pt.language,
        pt.slug as translated_slug,
        pt.title as translated_title,
        pt.updated_at
    FROM pages p
    INNER JOIN page_translations pt ON p.id = pt.page_id
    WHERE p.published = 1
    ORDER BY p.id, pt.language
");

$pages = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $pages[$row['id']][$row['language']] = $row;
}

foreach ($pages as $pageId => $translations) {
    foreach ($translations as $lang => $translation) {
        // Build alternate language URLs
        $alternates = [];
        foreach ($translations as $altLang => $altTranslation) {
            if ($altLang !== $lang) {
                $alternates[] = [
                    'lang' => $altLang,
                    'url' => "https://example.com/{$altLang}/{$altTranslation['translated_slug']}"
                ];
            }
        }
        
        $sitemap->add(
            "https://example.com/{$lang}/{$translation['translated_slug']}",
            date('c', strtotime($translation['updated_at'])),
            '0.8',
            'monthly',
            [],
            $translation['translated_title'],
            [],
            [],
            $alternates
        );
    }
}

header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

### Language-Specific Sitemaps

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateLanguageSpecificSitemap($language)
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=multilingual', $username, $password);
    
    // Get content for specific language
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            pt.slug,
            pt.title,
            pt.meta_description,
            pt.updated_at,
            p.page_type,
            p.priority_base
        FROM pages p
        INNER JOIN page_translations pt ON p.id = pt.page_id
        WHERE pt.language = :language 
        AND p.published = 1
        ORDER BY p.page_type = 'homepage' DESC, pt.updated_at DESC
    ");
    
    $stmt->execute(['language' => $language]);
    
    while ($page = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $priority = $page['page_type'] === 'homepage' ? '1.0' : ($page['priority_base'] ?: '0.8');
        
        $url = $page['page_type'] === 'homepage' 
            ? "https://example.com/{$language}/" 
            : "https://example.com/{$language}/{$page['slug']}";
        
        // Get alternates for this page
        $alternates = $this->getPageAlternates($page['id'], $language);
        
        $sitemap->add(
            $url,
            date('c', strtotime($page['updated_at'])),
            $priority,
            'monthly',
            [],
            $page['title'],
            [],
            [],
            $alternates
        );
    }
    
    return $sitemap->renderXml();
}

function getPageAlternates($pageId, $currentLanguage)
{
    $pdo = new PDO('mysql:host=localhost;dbname=multilingual', $username, $password);
    $alternates = [];
    
    $stmt = $pdo->prepare("
        SELECT language, slug 
        FROM page_translations 
        WHERE page_id = :page_id AND language != :current_language
    ");
    
    $stmt->execute([
        'page_id' => $pageId,
        'current_language' => $currentLanguage
    ]);
    
    while ($alt = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $alternates[] = [
            'lang' => $alt['language'],
            'url' => "https://example.com/{$alt['language']}/{$alt['slug']}"
        ];
    }
    
    return $alternates;
}

// Usage
$language = $_GET['lang'] ?? 'en';
header('Content-Type: application/xml; charset=utf-8');
echo generateLanguageSpecificSitemap($language);
```

## Regional Sitemaps

### Country/Region Specific Content

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateRegionalSitemap($region)
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=multilingual', $username, $password);
    
    $regionConfig = [
        'us' => ['languages' => ['en'], 'currency' => 'USD', 'domain' => 'example.com'],
        'ca' => ['languages' => ['en', 'fr'], 'currency' => 'CAD', 'domain' => 'example.ca'],
        'uk' => ['languages' => ['en'], 'currency' => 'GBP', 'domain' => 'example.co.uk'],
        'eu' => ['languages' => ['en', 'de', 'fr', 'es', 'it'], 'currency' => 'EUR', 'domain' => 'example.eu'],
        'mx' => ['languages' => ['es'], 'currency' => 'MXN', 'domain' => 'example.mx']
    ];
    
    if (!isset($regionConfig[$region])) {
        throw new InvalidArgumentException("Unsupported region: {$region}");
    }
    
    $config = $regionConfig[$region];
    $baseUrl = "https://{$config['domain']}";
    
    // Get regional content
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            pt.language,
            pt.slug,
            pt.title,
            pt.updated_at,
            p.page_type,
            rc.price_local,
            rc.availability
        FROM pages p
        INNER JOIN page_translations pt ON p.id = pt.page_id
        LEFT JOIN regional_content rc ON p.id = rc.page_id AND rc.region = :region
        WHERE pt.language IN (" . str_repeat('?,', count($config['languages']) - 1) . "?)
        AND p.published = 1
        AND (rc.available_in_region = 1 OR rc.available_in_region IS NULL)
        ORDER BY pt.language, p.page_type = 'homepage' DESC
    ");
    
    $params = array_merge([$region], $config['languages']);
    $stmt->execute($params);
    
    while ($page = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $url = $page['page_type'] === 'homepage' 
            ? $baseUrl . '/' 
            : "{$baseUrl}/{$page['slug']}";
        
        // Get regional alternates
        $alternates = $this->getRegionalAlternates($page['id'], $region);
        
        $sitemap->add(
            $url,
            date('c', strtotime($page['updated_at'])),
            $page['page_type'] === 'homepage' ? '1.0' : '0.8',
            'monthly',
            [],
            $page['title'],
            [],
            [],
            $alternates
        );
    }
    
    return $sitemap->renderXml();
}

function getRegionalAlternates($pageId, $currentRegion)
{
    $pdo = new PDO('mysql:host=localhost;dbname=multilingual', $username, $password);
    $alternates = [];
    
    $regionDomains = [
        'us' => 'example.com',
        'ca' => 'example.ca', 
        'uk' => 'example.co.uk',
        'eu' => 'example.eu',
        'mx' => 'example.mx'
    ];
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT rc.region, pt.slug, pt.language
        FROM regional_content rc
        INNER JOIN page_translations pt ON rc.page_id = pt.page_id
        WHERE rc.page_id = :page_id 
        AND rc.region != :current_region
        AND rc.available_in_region = 1
    ");
    
    $stmt->execute([
        'page_id' => $pageId,
        'current_region' => $currentRegion
    ]);
    
    while ($alt = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($regionDomains[$alt['region']])) {
            $alternates[] = [
                'lang' => $alt['language'],
                'url' => "https://{$regionDomains[$alt['region']]}/{$alt['slug']}"
            ];
        }
    }
    
    return $alternates;
}

// Usage
$region = $_GET['region'] ?? 'us';
header('Content-Type: application/xml; charset=utf-8');
echo generateRegionalSitemap($region);
```

## Subdomain-Based Multilingual Sites

### Language Subdomains

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateSubdomainLanguageSitemap($language)
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=multilingual', $username, $password);
    
    $languageSubdomains = [
        'en' => 'www.example.com',
        'es' => 'es.example.com',
        'fr' => 'fr.example.com',
        'de' => 'de.example.com',
        'it' => 'it.example.com',
        'pt' => 'pt.example.com',
        'ja' => 'ja.example.com',
        'zh' => 'zh.example.com'
    ];
    
    if (!isset($languageSubdomains[$language])) {
        throw new InvalidArgumentException("Unsupported language: {$language}");
    }
    
    $baseUrl = "https://{$languageSubdomains[$language]}";
    
    // Get pages for this language
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            pt.slug,
            pt.title,
            pt.updated_at,
            p.page_type
        FROM pages p
        INNER JOIN page_translations pt ON p.id = pt.page_id
        WHERE pt.language = :language AND p.published = 1
        ORDER BY p.page_type = 'homepage' DESC, pt.updated_at DESC
    ");
    
    $stmt->execute(['language' => $language]);
    
    while ($page = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $url = $page['page_type'] === 'homepage' 
            ? $baseUrl . '/' 
            : "{$baseUrl}/{$page['slug']}";
        
        // Get subdomain alternates
        $alternates = [];
        foreach ($languageSubdomains as $altLang => $altDomain) {
            if ($altLang !== $language) {
                $altSlug = $this->getTranslatedSlug($page['id'], $altLang);
                if ($altSlug) {
                    $altUrl = $page['page_type'] === 'homepage' 
                        ? "https://{$altDomain}/" 
                        : "https://{$altDomain}/{$altSlug}";
                    
                    $alternates[] = [
                        'lang' => $altLang,
                        'url' => $altUrl
                    ];
                }
            }
        }
        
        $sitemap->add(
            $url,
            date('c', strtotime($page['updated_at'])),
            $page['page_type'] === 'homepage' ? '1.0' : '0.8',
            'monthly',
            [],
            $page['title'],
            [],
            [],
            $alternates
        );
    }
    
    return $sitemap->renderXml();
}

function getTranslatedSlug($pageId, $language)
{
    $pdo = new PDO('mysql:host=localhost;dbname=multilingual', $username, $password);
    
    $stmt = $pdo->prepare("
        SELECT slug 
        FROM page_translations 
        WHERE page_id = :page_id AND language = :language
    ");
    
    $stmt->execute(['page_id' => $pageId, 'language' => $language]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['slug'] : null;
}

// Usage
$language = $_GET['lang'] ?? 'en';
header('Content-Type: application/xml; charset=utf-8');
echo generateSubdomainLanguageSitemap($language);
```

## E-commerce Multilingual Sitemap

### Multilingual Product Catalog

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateMultilingualEcommerceSitemap($language, $region = null)
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);
    
    $baseUrl = "https://shop.example.com/{$language}";
    if ($region) {
        $baseUrl = "https://shop.example.com/{$region}/{$language}";
    }
    
    // Get products with translations
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.sku,
            pt.slug,
            pt.name,
            pt.description,
            p.updated_at,
            p.price,
            p.stock_quantity,
            c.slug as category_slug,
            ct.slug as translated_category_slug,
            pi.image_url as featured_image
        FROM products p
        INNER JOIN product_translations pt ON p.id = pt.product_id
        INNER JOIN categories c ON p.category_id = c.id
        INNER JOIN category_translations ct ON c.id = ct.category_id AND ct.language = :language
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_featured = 1
        WHERE pt.language = :language2 
        AND p.active = 1 
        AND p.stock_quantity > 0
        ORDER BY p.updated_at DESC
        LIMIT 50000
    ");
    
    $stmt->execute([
        'language' => $language,
        'language2' => $language
    ]);
    
    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $images = [];
        
        if ($product['featured_image']) {
            $images[] = [
                'url' => "https://shop.example.com/images/{$product['featured_image']}",
                'title' => $product['name'],
                'caption' => $product['description'] ? substr($product['description'], 0, 150) : null
            ];
        }
        
        // Get product alternates
        $alternates = $this->getProductAlternates($product['id'], $language, $region);
        
        $sitemap->add(
            "{$baseUrl}/products/{$product['translated_category_slug']}/{$product['slug']}",
            date('c', strtotime($product['updated_at'])),
            '0.8',
            'weekly',
            [],
            $product['name'],
            $images,
            [],
            $alternates
        );
    }
    
    // Add category pages
    $categoryStmt = $pdo->prepare("
        SELECT 
            c.id,
            ct.slug,
            ct.name,
            c.updated_at,
            COUNT(p.id) as product_count
        FROM categories c
        INNER JOIN category_translations ct ON c.id = ct.category_id
        LEFT JOIN products p ON c.id = p.category_id AND p.active = 1
        WHERE ct.language = :language AND c.active = 1
        GROUP BY c.id
        ORDER BY product_count DESC
    ");
    
    $categoryStmt->execute(['language' => $language]);
    
    while ($category = $categoryStmt->fetch(PDO::FETCH_ASSOC)) {
        $alternates = $this->getCategoryAlternates($category['id'], $language, $region);
        
        $sitemap->add(
            "{$baseUrl}/categories/{$category['slug']}",
            date('c', strtotime($category['updated_at'])),
            '0.9',
            'weekly',
            [],
            $category['name'],
            [],
            [],
            $alternates
        );
    }
    
    return $sitemap->renderXml();
}

function getProductAlternates($productId, $currentLanguage, $currentRegion = null)
{
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);
    $alternates = [];
    
    $stmt = $pdo->prepare("
        SELECT 
            pt.language,
            pt.slug,
            ct.slug as category_slug
        FROM product_translations pt
        INNER JOIN products p ON pt.product_id = p.id
        INNER JOIN category_translations ct ON p.category_id = ct.category_id AND ct.language = pt.language
        WHERE pt.product_id = :product_id 
        AND pt.language != :current_language
    ");
    
    $stmt->execute([
        'product_id' => $productId,
        'current_language' => $currentLanguage
    ]);
    
    while ($alt = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $baseUrl = "https://shop.example.com/{$alt['language']}";
        if ($currentRegion) {
            $baseUrl = "https://shop.example.com/{$currentRegion}/{$alt['language']}";
        }
        
        $alternates[] = [
            'lang' => $alt['language'],
            'url' => "{$baseUrl}/products/{$alt['category_slug']}/{$alt['slug']}"
        ];
    }
    
    return $alternates;
}

function getCategoryAlternates($categoryId, $currentLanguage, $currentRegion = null)
{
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce', $username, $password);
    $alternates = [];
    
    $stmt = $pdo->prepare("
        SELECT language, slug
        FROM category_translations
        WHERE category_id = :category_id 
        AND language != :current_language
    ");
    
    $stmt->execute([
        'category_id' => $categoryId,
        'current_language' => $currentLanguage
    ]);
    
    while ($alt = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $baseUrl = "https://shop.example.com/{$alt['language']}";
        if ($currentRegion) {
            $baseUrl = "https://shop.example.com/{$currentRegion}/{$alt['language']}";
        }
        
        $alternates[] = [
            'lang' => $alt['language'],
            'url' => "{$baseUrl}/categories/{$alt['slug']}"
        ];
    }
    
    return $alternates;
}

// Usage
$language = $_GET['lang'] ?? 'en';
$region = $_GET['region'] ?? null;

header('Content-Type: application/xml; charset=utf-8');
echo generateMultilingualEcommerceSitemap($language, $region);
```

## Blog Multilingual Sitemap

### Multilingual Blog Posts

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateMultilingualBlogSitemap($language)
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=blog', $username, $password);
    
    $baseUrl = "https://blog.example.com/{$language}";
    
    // Get blog posts with translations
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            pt.slug,
            pt.title,
            pt.excerpt,
            p.published_at,
            p.updated_at,
            p.featured_image,
            ct.slug as category_slug,
            ut.display_name as author_name
        FROM posts p
        INNER JOIN post_translations pt ON p.id = pt.post_id
        INNER JOIN category_translations ct ON p.category_id = ct.category_id AND ct.language = :language
        LEFT JOIN user_translations ut ON p.author_id = ut.user_id AND ut.language = :language2
        WHERE pt.language = :language3 
        AND p.published = 1 
        AND p.published_at <= NOW()
        ORDER BY p.published_at DESC
        LIMIT 50000
    ");
    
    $stmt->execute([
        'language' => $language,
        'language2' => $language,
        'language3' => $language
    ]);
    
    while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $images = [];
        
        if ($post['featured_image']) {
            $images[] = [
                'url' => "https://blog.example.com/images/{$post['featured_image']}",
                'title' => $post['title'],
                'caption' => $post['excerpt'] ? substr($post['excerpt'], 0, 150) : null
            ];
        }
        
        // Get post alternates
        $alternates = $this->getBlogPostAlternates($post['id'], $language);
        
        $lastmod = $post['updated_at'] ?: $post['published_at'];
        
        $sitemap->add(
            "{$baseUrl}/{$post['category_slug']}/{$post['slug']}",
            date('c', strtotime($lastmod)),
            '0.7',
            'monthly',
            [],
            $post['title'],
            $images,
            [],
            $alternates
        );
    }
    
    return $sitemap->renderXml();
}

function getBlogPostAlternates($postId, $currentLanguage)
{
    $pdo = new PDO('mysql:host=localhost;dbname=blog', $username, $password);
    $alternates = [];
    
    $stmt = $pdo->prepare("
        SELECT 
            pt.language,
            pt.slug,
            ct.slug as category_slug
        FROM post_translations pt
        INNER JOIN posts p ON pt.post_id = p.id
        INNER JOIN category_translations ct ON p.category_id = ct.category_id AND ct.language = pt.language
        WHERE pt.post_id = :post_id 
        AND pt.language != :current_language
    ");
    
    $stmt->execute([
        'post_id' => $postId,
        'current_language' => $currentLanguage
    ]);
    
    while ($alt = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $alternates[] = [
            'lang' => $alt['language'],
            'url' => "https://blog.example.com/{$alt['language']}/{$alt['category_slug']}/{$alt['slug']}"
        ];
    }
    
    return $alternates;
}

// Usage
$language = $_GET['lang'] ?? 'en';
header('Content-Type: application/xml; charset=utf-8');
echo generateMultilingualBlogSitemap($language);
```

## Advanced Multilingual Features

### Right-to-Left (RTL) Language Support

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateRTLLanguageSitemap($language)
{
    $rtlLanguages = ['ar', 'he', 'fa', 'ur'];
    $isRTL = in_array($language, $rtlLanguages);
    
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=multilingual', $username, $password);
    
    $baseUrl = "https://example.com/{$language}";
    
    // Get pages with RTL considerations
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            pt.slug,
            pt.title,
            pt.updated_at,
            p.page_type,
            pt.text_direction
        FROM pages p
        INNER JOIN page_translations pt ON p.id = pt.page_id
        WHERE pt.language = :language AND p.published = 1
        ORDER BY p.page_type = 'homepage' DESC, pt.updated_at DESC
    ");
    
    $stmt->execute(['language' => $language]);
    
    while ($page = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $url = $page['page_type'] === 'homepage' 
            ? $baseUrl . '/' 
            : "{$baseUrl}/{$page['slug']}";
        
        // Get alternates including RTL/LTR considerations
        $alternates = $this->getDirectionalAlternates($page['id'], $language, $isRTL);
        
        $sitemap->add(
            $url,
            date('c', strtotime($page['updated_at'])),
            $page['page_type'] === 'homepage' ? '1.0' : '0.8',
            'monthly',
            [],
            $page['title'],
            [],
            [],
            $alternates
        );
    }
    
    return $sitemap->renderXml();
}

function getDirectionalAlternates($pageId, $currentLanguage, $isCurrentRTL)
{
    $pdo = new PDO('mysql:host=localhost;dbname=multilingual', $username, $password);
    $rtlLanguages = ['ar', 'he', 'fa', 'ur'];
    $alternates = [];
    
    $stmt = $pdo->prepare("
        SELECT language, slug 
        FROM page_translations 
        WHERE page_id = :page_id AND language != :current_language
    ");
    
    $stmt->execute([
        'page_id' => $pageId,
        'current_language' => $currentLanguage
    ]);
    
    while ($alt = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $alternates[] = [
            'lang' => $alt['language'],
            'url' => "https://example.com/{$alt['language']}/{$alt['slug']}"
        ];
    }
    
    return $alternates;
}

// Usage
$language = $_GET['lang'] ?? 'en';
header('Content-Type: application/xml; charset=utf-8');
echo generateRTLLanguageSitemap($language);
```

### Auto-Detecting User Language

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateAdaptiveSitemap()
{
    // Detect user's preferred language from headers
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en';
    $supportedLanguages = ['en', 'es', 'fr', 'de', 'it', 'pt', 'ja', 'zh'];
    
    // Parse accept-language header
    $userLanguages = [];
    $langParts = explode(',', $acceptLanguage);
    
    foreach ($langParts as $part) {
        $langData = explode(';', trim($part));
        $lang = substr($langData[0], 0, 2); // Get language code
        $quality = 1.0;
        
        if (isset($langData[1]) && strpos($langData[1], 'q=') === 0) {
            $quality = floatval(substr($langData[1], 2));
        }
        
        if (in_array($lang, $supportedLanguages)) {
            $userLanguages[$lang] = $quality;
        }
    }
    
    // Sort by quality and get best match
    arsort($userLanguages);
    $detectedLanguage = array_key_first($userLanguages) ?: 'en';
    
    // Generate sitemap for detected language
    return generateLanguageSpecificSitemap($detectedLanguage);
}

// Usage
header('Content-Type: application/xml; charset=utf-8');
echo generateAdaptiveSitemap();
```

## Complete Multilingual Sitemap Generator

### Comprehensive Multilingual System

```php
<?php
use Rumenx\Sitemap\Sitemap;

class MultilingualSitemapGenerator
{
    private $pdo;
    private $baseUrl;
    private $supportedLanguages;
    private $defaultLanguage;
    
    public function __construct($dbConfig, $baseUrl, $languages = [], $defaultLang = 'en')
    {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->supportedLanguages = $languages ?: ['en', 'es', 'fr', 'de'];
        $this->defaultLanguage = $defaultLang;
    }
    
    public function generateLanguageSitemap($language)
    {
        if (!in_array($language, $this->supportedLanguages)) {
            throw new InvalidArgumentException("Unsupported language: {$language}");
        }
        
        $sitemap = new Sitemap();
        
        // Add pages
        $this->addPagesToSitemap($sitemap, $language);
        
        // Add posts if blog functionality exists
        $this->addPostsToSitemap($sitemap, $language);
        
        // Add products if e-commerce functionality exists
        $this->addProductsToSitemap($sitemap, $language);
        
        return $sitemap->renderXml();
    }
    
    public function generateMasterSitemapIndex()
    {
        $sitemapIndex = new Sitemap();
        
        foreach ($this->supportedLanguages as $language) {
            $sitemapIndex->addSitemap(
                "{$this->baseUrl}/sitemap-{$language}.xml",
                date('c')
            );
        }
        
        $items = $sitemapIndex->getModel()->getSitemaps();
        return view('sitemap.sitemapindex', compact('items'))->render();
    }
    
    private function addPagesToSitemap($sitemap, $language)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.id,
                pt.slug,
                pt.title,
                pt.updated_at,
                p.page_type
            FROM pages p
            INNER JOIN page_translations pt ON p.id = pt.page_id
            WHERE pt.language = :language AND p.published = 1
            ORDER BY p.page_type = 'homepage' DESC, pt.updated_at DESC
        ");
        
        $stmt->execute(['language' => $language]);
        
        while ($page = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $url = $this->buildPageUrl($page, $language);
            $alternates = $this->getPageAlternates($page['id'], $language);
            
            $sitemap->add(
                $url,
                date('c', strtotime($page['updated_at'])),
                $page['page_type'] === 'homepage' ? '1.0' : '0.8',
                'monthly',
                [],
                $page['title'],
                [],
                [],
                $alternates
            );
        }
    }
    
    private function addPostsToSitemap($sitemap, $language)
    {
        // Check if posts table exists
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'posts'");
        if ($stmt->rowCount() === 0) return;
        
        $stmt = $this->pdo->prepare("
            SELECT 
                p.id,
                pt.slug,
                pt.title,
                p.published_at,
                p.updated_at
            FROM posts p
            INNER JOIN post_translations pt ON p.id = pt.post_id
            WHERE pt.language = :language 
            AND p.published = 1 
            AND p.published_at <= NOW()
            ORDER BY p.published_at DESC
            LIMIT 10000
        ");
        
        $stmt->execute(['language' => $language]);
        
        while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $url = $this->buildPostUrl($post, $language);
            $alternates = $this->getPostAlternates($post['id'], $language);
            
            $lastmod = $post['updated_at'] ?: $post['published_at'];
            
            $sitemap->add(
                $url,
                date('c', strtotime($lastmod)),
                '0.7',
                'monthly',
                [],
                $post['title'],
                [],
                [],
                $alternates
            );
        }
    }
    
    private function addProductsToSitemap($sitemap, $language)
    {
        // Check if products table exists
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'products'");
        if ($stmt->rowCount() === 0) return;
        
        $stmt = $this->pdo->prepare("
            SELECT 
                p.id,
                pt.slug,
                pt.name,
                p.updated_at
            FROM products p
            INNER JOIN product_translations pt ON p.id = pt.product_id
            WHERE pt.language = :language 
            AND p.active = 1
            ORDER BY p.updated_at DESC
            LIMIT 50000
        ");
        
        $stmt->execute(['language' => $language]);
        
        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $url = $this->buildProductUrl($product, $language);
            $alternates = $this->getProductAlternates($product['id'], $language);
            
            $sitemap->add(
                $url,
                date('c', strtotime($product['updated_at'])),
                '0.8',
                'weekly',
                [],
                $product['name'],
                [],
                [],
                $alternates
            );
        }
    }
    
    private function buildPageUrl($page, $language)
    {
        $langPrefix = $language === $this->defaultLanguage ? '' : "/{$language}";
        
        return $page['page_type'] === 'homepage' 
            ? $this->baseUrl . $langPrefix . '/' 
            : $this->baseUrl . $langPrefix . '/' . $page['slug'];
    }
    
    private function buildPostUrl($post, $language)
    {
        $langPrefix = $language === $this->defaultLanguage ? '' : "/{$language}";
        return $this->baseUrl . $langPrefix . '/posts/' . $post['slug'];
    }
    
    private function buildProductUrl($product, $language)
    {
        $langPrefix = $language === $this->defaultLanguage ? '' : "/{$language}";
        return $this->baseUrl . $langPrefix . '/products/' . $product['slug'];
    }
    
    private function getPageAlternates($pageId, $currentLanguage)
    {
        return $this->getAlternates('page_translations', 'page_id', $pageId, $currentLanguage, 'pages');
    }
    
    private function getPostAlternates($postId, $currentLanguage)
    {
        return $this->getAlternates('post_translations', 'post_id', $postId, $currentLanguage, 'posts');
    }
    
    private function getProductAlternates($productId, $currentLanguage)
    {
        return $this->getAlternates('product_translations', 'product_id', $productId, $currentLanguage, 'products');
    }
    
    private function getAlternates($table, $idField, $id, $currentLanguage, $urlType)
    {
        $alternates = [];
        
        $stmt = $this->pdo->prepare("
            SELECT language, slug 
            FROM {$table} 
            WHERE {$idField} = :id AND language != :current_language
        ");
        
        $stmt->execute([
            'id' => $id,
            'current_language' => $currentLanguage
        ]);
        
        while ($alt = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $langPrefix = $alt['language'] === $this->defaultLanguage ? '' : "/{$alt['language']}";
            $urlPath = $urlType === 'pages' ? $alt['slug'] : "{$urlType}/{$alt['slug']}";
            
            $alternates[] = [
                'lang' => $alt['language'],
                'url' => $this->baseUrl . $langPrefix . '/' . $urlPath
            ];
        }
        
        return $alternates;
    }
}

// Usage
$config = [
    'host' => 'localhost',
    'name' => 'multilingual_site',
    'user' => 'dbuser',
    'pass' => 'dbpass'
];

$languages = ['en', 'es', 'fr', 'de', 'it', 'pt'];
$generator = new MultilingualSitemapGenerator($config, 'https://example.com', $languages);

$language = $_GET['lang'] ?? null;

header('Content-Type: application/xml; charset=utf-8');

if ($language) {
    echo $generator->generateLanguageSitemap($language);
} else {
    echo $generator->generateMasterSitemapIndex();
}
```

## Performance Considerations

### Optimizing Multilingual Sitemaps

```php
<?php
// Caching multilingual sitemaps by language
function getCachedMultilingualSitemap($language, $ttl = 3600)
{
    $cacheKey = "sitemap_lang_{$language}";
    $cached = cache_get($cacheKey);
    
    if ($cached) {
        return $cached;
    }
    
    $generator = new MultilingualSitemapGenerator($config, $baseUrl, $languages);
    $sitemap = $generator->generateLanguageSitemap($language);
    
    cache_set($cacheKey, $sitemap, $ttl);
    
    return $sitemap;
}

// Batch generation for all languages
function generateAllLanguageSitemaps()
{
    $languages = ['en', 'es', 'fr', 'de', 'it'];
    $generator = new MultilingualSitemapGenerator($config, $baseUrl, $languages);
    
    foreach ($languages as $language) {
        $sitemap = $generator->generateLanguageSitemap($language);
        file_put_contents("sitemap-{$language}.xml", $sitemap);
        echo "Generated sitemap for {$language}\n";
    }
    
    // Generate master index
    $index = $generator->generateMasterSitemapIndex();
    file_put_contents('sitemap.xml', $index);
    echo "Generated master sitemap index\n";
}
```

## Next Steps

- Learn about [Caching Strategies](caching-strategies.md) for multilingual optimization
- Explore [Memory Optimization](memory-optimization.md) for large multilingual sites
- Check [Automated Generation](automated-generation.md) for scheduled multilingual updates
- See [Rendering Formats](rendering-formats.md) for different output formats
