# Rendering Formats

Learn how to generate sitemaps in different output formats including XML, HTML, TXT, and other specialized formats using the `rumenx/php-sitemap` package.

## XML Sitemap (Default)

### Standard XML Sitemap

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

$sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
$sitemap->add('https://example.com/about', date('c'), '0.8', 'monthly');

// Generate XML (default format)
$xml = $sitemap->renderXml();

header('Content-Type: application/xml; charset=utf-8');
echo $xml;
```

### XML with Custom Styling

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Set custom XSL stylesheet
$sitemap->getModel()->setEscaping(true);

$sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
$sitemap->add('https://example.com/products', date('c'), '0.9', 'weekly');

// Get items and render with custom view
$items = $sitemap->getModel()->getItems();
$style = 'https://example.com/sitemap.xsl';

// Use custom XML template with styling
$xml = view('sitemap.xml', compact('items', 'style'))->render();

header('Content-Type: application/xml; charset=utf-8');
echo $xml;
```

## HTML Sitemap

### Human-Readable HTML Format

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Add pages with titles for better HTML display
$sitemap->add('https://example.com/', date('c'), '1.0', 'daily', [], 'Homepage');
$sitemap->add('https://example.com/about', date('c'), '0.8', 'monthly', [], 'About Us');
$sitemap->add('https://example.com/contact', date('c'), '0.6', 'yearly', [], 'Contact');
$sitemap->add('https://example.com/blog', date('c'), '0.9', 'daily', [], 'Blog');

// Generate HTML
$items = $sitemap->getModel()->getItems();
$html = view('sitemap.html', compact('items'))->render();

header('Content-Type: text/html; charset=utf-8');
echo $html;
```

### Styled HTML Sitemap with CSS

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateStyledHtmlSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
    
    // Add static pages
    $staticPages = [
        ['url' => 'https://example.com/', 'title' => 'Homepage', 'priority' => '1.0'],
        ['url' => 'https://example.com/about', 'title' => 'About Us', 'priority' => '0.8'],
        ['url' => 'https://example.com/services', 'title' => 'Our Services', 'priority' => '0.9'],
        ['url' => 'https://example.com/contact', 'title' => 'Contact Us', 'priority' => '0.6']
    ];
    
    foreach ($staticPages as $page) {
        $sitemap->add($page['url'], date('c'), $page['priority'], 'monthly', [], $page['title']);
    }
    
    // Add blog posts
    $stmt = $pdo->query("
        SELECT slug, title, updated_at, category 
        FROM posts 
        WHERE published = 1 
        ORDER BY category, updated_at DESC
        LIMIT 100
    ");
    
    while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sitemap->add(
            "https://example.com/blog/{$post['slug']}",
            date('c', strtotime($post['updated_at'])),
            '0.7',
            'monthly',
            [],
            $post['title']
        );
    }
    
    // Group items by type for better HTML organization
    $items = $sitemap->getModel()->getItems();
    $groupedItems = groupItemsByType($items);
    
    return view('sitemap.styled-html', compact('groupedItems'))->render();
}

function groupItemsByType($items)
{
    $groups = [
        'pages' => [],
        'blog' => [],
        'other' => []
    ];
    
    foreach ($items as $item) {
        if (strpos($item['loc'], '/blog/') !== false) {
            $groups['blog'][] = $item;
        } elseif (in_array($item['loc'], ['https://example.com/', 'https://example.com/about', 'https://example.com/contact'])) {
            $groups['pages'][] = $item;
        } else {
            $groups['other'][] = $item;
        }
    }
    
    return $groups;
}

header('Content-Type: text/html; charset=utf-8');
echo generateStyledHtmlSitemap();
```

## TXT Sitemap

### Plain Text URL List

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

$sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
$sitemap->add('https://example.com/about', date('c'), '0.8', 'monthly');
$sitemap->add('https://example.com/products', date('c'), '0.9', 'weekly');
$sitemap->add('https://example.com/contact', date('c'), '0.6', 'yearly');

// Generate TXT format
$items = $sitemap->getModel()->getItems();
$txt = view('sitemap.txt', compact('items'))->render();

header('Content-Type: text/plain; charset=utf-8');
echo $txt;
```

### TXT with Comments

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateCommentedTxtSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
    
    // Add URLs with titles for comments
    $sitemap->add('https://example.com/', date('c'), '1.0', 'daily', [], 'Homepage');
    
    $stmt = $pdo->query("
        SELECT slug, title, updated_at 
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
            'monthly',
            [],
            $post['title']
        );
    }
    
    $items = $sitemap->getModel()->getItems();
    
    // Generate TXT with comments
    $output = "# Website Sitemap (Text Format)\n";
    $output .= "# Generated on: " . date('Y-m-d H:i:s') . "\n";
    $output .= "# Total URLs: " . count($items) . "\n\n";
    
    foreach ($items as $item) {
        if (!empty($item['title'])) {
            $output .= "# {$item['title']}\n";
        }
        $output .= $item['loc'] . "\n\n";
    }
    
    return $output;
}

header('Content-Type: text/plain; charset=utf-8');
echo generateCommentedTxtSitemap();
```

## Google News Format

### News-Specific XML

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Add news articles (last 48 hours only)
$googleNews = [
    'sitename' => 'Example News',
    'language' => 'en',
    'genres' => 'PressRelease',
    'publication_date' => date('c', strtotime('-2 hours')),
    'title' => 'Breaking News: Major Event Occurred',
    'keywords' => 'breaking, news, major, event'
];

$sitemap->add(
    'https://example.com/news/major-event',
    date('c', strtotime('-2 hours')),
    '1.0',
    'always',
    [],
    'Breaking News: Major Event Occurred',
    [],
    [],
    [],
    $googleNews
);

// Generate Google News XML
$items = $sitemap->getModel()->getItems();
$xml = view('sitemap.google-news', compact('items'))->render();

header('Content-Type: application/xml; charset=utf-8');
echo $xml;
```

## Mobile Sitemap

### Mobile-Specific URLs

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Add mobile URLs
$sitemap->add('https://m.example.com/', date('c'), '1.0', 'daily');
$sitemap->add('https://m.example.com/products', date('c'), '0.9', 'weekly');

// Or add with mobile alternates
$alternates = [
    [
        'media' => 'only screen and (max-width: 640px)',
        'url' => 'https://m.example.com/products'
    ]
];

$sitemap->add(
    'https://example.com/products',
    date('c'),
    '0.9',
    'weekly',
    [],
    'Products',
    [],
    [],
    $alternates
);

// Generate mobile XML
$items = $sitemap->getModel()->getItems();
$style = 'https://example.com/mobile-sitemap.xsl';
$xml = view('sitemap.xml-mobile', compact('items', 'style'))->render();

header('Content-Type: application/xml; charset=utf-8');
echo $xml;
```

## ROR (Resources of a Resource) Formats

### ROR-RSS Format

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

$sitemap->add('https://example.com/', date('c'), '1.0', 'daily', [], 'Homepage');
$sitemap->add('https://example.com/about', date('c'), '0.8', 'monthly', [], 'About Us');

// Generate ROR-RSS
$items = $sitemap->getModel()->getItems();
$title = 'Example Website';
$link = 'https://example.com';
$description = 'Sitemap for Example Website';

$xml = view('sitemap.ror-rss', compact('items', 'title', 'link', 'description'))->render();

header('Content-Type: application/xml; charset=utf-8');
echo $xml;
```

### ROR-RDF Format

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

$sitemap->add('https://example.com/', date('c'), '1.0', 'daily', [], 'Homepage');
$sitemap->add('https://example.com/products', date('c'), '0.9', 'weekly', [], 'Products');

// Generate ROR-RDF
$items = $sitemap->getModel()->getItems();
$title = 'Example Website';
$link = 'https://example.com';

$xml = view('sitemap.ror-rdf', compact('items', 'title', 'link'))->render();

header('Content-Type: application/xml; charset=utf-8');
echo $xml;
```

## Sitemap Index Format

### Multiple Sitemaps Index

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemapIndex = new Sitemap();

// Add individual sitemaps to index
$sitemapIndex->addSitemap('https://example.com/sitemap-posts.xml', date('c'));
$sitemapIndex->addSitemap('https://example.com/sitemap-products.xml', date('c'));
$sitemapIndex->addSitemap('https://example.com/sitemap-categories.xml', date('c'));

// Generate sitemap index XML
$items = $sitemapIndex->getModel()->getSitemaps();
$xml = view('sitemap.sitemapindex', compact('items'))->render();

header('Content-Type: application/xml; charset=utf-8');
echo $xml;
```

## JSON Format (Custom)

### JSON API Response

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateJsonSitemap()
{
    $sitemap = new Sitemap();
    
    $sitemap->add('https://example.com/', date('c'), '1.0', 'daily', [], 'Homepage');
    $sitemap->add('https://example.com/about', date('c'), '0.8', 'monthly', [], 'About Us');
    
    $items = $sitemap->getModel()->getItems();
    
    // Convert to JSON format
    $jsonData = [
        'sitemap' => [
            'generated' => date('c'),
            'total_urls' => count($items),
            'urls' => []
        ]
    ];
    
    foreach ($items as $item) {
        $jsonData['sitemap']['urls'][] = [
            'loc' => $item['loc'],
            'lastmod' => $item['lastmod'] ?? null,
            'priority' => $item['priority'] ?? null,
            'changefreq' => $item['freq'] ?? null,
            'title' => $item['title'] ?? null
        ];
    }
    
    return json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

header('Content-Type: application/json; charset=utf-8');
echo generateJsonSitemap();
```

## CSV Format (Custom)

### Spreadsheet-Compatible Format

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateCsvSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
    
    // Add URLs from database
    $stmt = $pdo->query("
        SELECT slug, title, updated_at, category 
        FROM posts 
        WHERE published = 1 
        ORDER BY category, updated_at DESC
    ");
    
    while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sitemap->add(
            "https://example.com/blog/{$post['slug']}",
            date('c', strtotime($post['updated_at'])),
            '0.7',
            'monthly',
            [],
            $post['title']
        );
    }
    
    $items = $sitemap->getModel()->getItems();
    
    // Generate CSV
    $csv = "URL,Title,Last Modified,Priority,Change Frequency\n";
    
    foreach ($items as $item) {
        $csv .= sprintf(
            '"%s","%s","%s","%s","%s"' . "\n",
            $item['loc'],
            str_replace('"', '""', $item['title'] ?? ''),
            $item['lastmod'] ?? '',
            $item['priority'] ?? '',
            $item['freq'] ?? ''
        );
    }
    
    return $csv;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sitemap.csv"');
echo generateCsvSitemap();
```

## Multi-Format Generator

### Flexible Format Selection

```php
<?php
use Rumenx\Sitemap\Sitemap;

class MultiFormatSitemapGenerator
{
    private $sitemap;
    
    public function __construct()
    {
        $this->sitemap = new Sitemap();
        $this->populateSitemap();
    }
    
    private function populateSitemap()
    {
        // Add sample data
        $this->sitemap->add('https://example.com/', date('c'), '1.0', 'daily', [], 'Homepage');
        $this->sitemap->add('https://example.com/about', date('c'), '0.8', 'monthly', [], 'About Us');
        $this->sitemap->add('https://example.com/products', date('c'), '0.9', 'weekly', [], 'Products');
    }
    
    public function generate($format = 'xml')
    {
        switch (strtolower($format)) {
            case 'xml':
                return $this->generateXml();
            case 'html':
                return $this->generateHtml();
            case 'txt':
                return $this->generateTxt();
            case 'json':
                return $this->generateJson();
            case 'csv':
                return $this->generateCsv();
            case 'google-news':
                return $this->generateGoogleNews();
            case 'ror-rss':
                return $this->generateRorRss();
            case 'ror-rdf':
                return $this->generateRorRdf();
            default:
                throw new InvalidArgumentException("Unsupported format: {$format}");
        }
    }
    
    public function getContentType($format)
    {
        $contentTypes = [
            'xml' => 'application/xml; charset=utf-8',
            'html' => 'text/html; charset=utf-8',
            'txt' => 'text/plain; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'csv' => 'text/csv; charset=utf-8',
            'google-news' => 'application/xml; charset=utf-8',
            'ror-rss' => 'application/xml; charset=utf-8',
            'ror-rdf' => 'application/xml; charset=utf-8'
        ];
        
        return $contentTypes[strtolower($format)] ?? 'text/plain; charset=utf-8';
    }
    
    private function generateXml()
    {
        return $this->sitemap->renderXml();
    }
    
    private function generateHtml()
    {
        $items = $this->sitemap->getModel()->getItems();
        return view('sitemap.html', compact('items'))->render();
    }
    
    private function generateTxt()
    {
        $items = $this->sitemap->getModel()->getItems();
        return view('sitemap.txt', compact('items'))->render();
    }
    
    private function generateJson()
    {
        $items = $this->sitemap->getModel()->getItems();
        
        $jsonData = [
            'sitemap' => [
                'generated' => date('c'),
                'urls' => $items
            ]
        ];
        
        return json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    private function generateCsv()
    {
        $items = $this->sitemap->getModel()->getItems();
        
        $csv = "URL,Title,Last Modified,Priority,Change Frequency\n";
        
        foreach ($items as $item) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s"' . "\n",
                $item['loc'],
                str_replace('"', '""', $item['title'] ?? ''),
                $item['lastmod'] ?? '',
                $item['priority'] ?? '',
                $item['freq'] ?? ''
            );
        }
        
        return $csv;
    }
    
    private function generateGoogleNews()
    {
        $items = $this->sitemap->getModel()->getItems();
        return view('sitemap.google-news', compact('items'))->render();
    }
    
    private function generateRorRss()
    {
        $items = $this->sitemap->getModel()->getItems();
        $title = 'Example Website';
        $link = 'https://example.com';
        $description = 'Sitemap for Example Website';
        
        return view('sitemap.ror-rss', compact('items', 'title', 'link', 'description'))->render();
    }
    
    private function generateRorRdf()
    {
        $items = $this->sitemap->getModel()->getItems();
        $title = 'Example Website';
        $link = 'https://example.com';
        
        return view('sitemap.ror-rdf', compact('items', 'title', 'link'))->render();
    }
}

// Usage example
$format = $_GET['format'] ?? 'xml';
$generator = new MultiFormatSitemapGenerator();

header('Content-Type: ' . $generator->getContentType($format));

try {
    echo $generator->generate($format);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo "Error: " . $e->getMessage();
}
```

## Format-Specific Routing

### Framework Router Example

```php
<?php
// Example routing for different formats

use Rumenx\Sitemap\Sitemap;

// Route: /sitemap.{format}
function handleSitemapRequest($format = 'xml')
{
    $generator = new MultiFormatSitemapGenerator();
    
    // Set appropriate content type
    header('Content-Type: ' . $generator->getContentType($format));
    
    // Add caching headers
    header('Cache-Control: public, max-age=3600');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
    
    // Generate and output sitemap
    try {
        echo $generator->generate($format);
    } catch (Exception $e) {
        http_response_code(500);
        echo "Error generating sitemap: " . $e->getMessage();
    }
}

// Example URLs:
// /sitemap.xml
// /sitemap.html
// /sitemap.txt
// /sitemap.json
// /sitemap.csv
```

## Best Practices for Different Formats

### Format-Specific Optimization

1. **XML Format**
   - Use proper XML encoding
   - Include XSL stylesheets for browser viewing
   - Validate against sitemap schema
   - Compress large XML files

2. **HTML Format**
   - Include proper meta tags and CSS
   - Organize URLs by categories
   - Add navigation and search functionality
   - Make it mobile-responsive

3. **TXT Format**
   - Keep it simple and clean
   - One URL per line
   - Consider adding comments for context
   - Useful for bulk processing

4. **JSON Format**
   - Include metadata and statistics
   - Use consistent field naming
   - Add API versioning
   - Perfect for AJAX requests

5. **Google News Format**
   - Only include recent articles (48 hours)
   - Use proper news-specific fields
   - Follow Google News guidelines
   - Update frequently

## Next Steps

- Learn about [E-commerce Examples](e-commerce.md) for product-specific formats
- Explore [Caching Strategies](caching-strategies.md) for format-specific caching
- Check [Framework Integration](framework-integration.md) for routing patterns
- See [Memory Optimization](memory-optimization.md) for large format generation
