# Basic Usage Examples

This guide shows fundamental sitemap generation patterns using the `rumenx/php-sitemap` package.

## Simple Sitemap Generation

### Minimal Example

```php
<?php
require 'vendor/autoload.php';

use Rumenx\Sitemap\Sitemap;

// Create sitemap instance
$sitemap = new Sitemap();

// Add basic URL
$sitemap->add('https://example.com/', date('c'), '1.0', 'daily');

// Generate XML output
$xml = $sitemap->renderXml();
echo $xml;
```

### Complete Basic Example

```php
<?php
require 'vendor/autoload.php';

use Rumenx\Sitemap\Sitemap;

// Create new sitemap
$sitemap = new Sitemap();

// Add homepage
$sitemap->add(
    'https://example.com/',
    date('c'),
    '1.0',
    'daily'
);

// Add about page
$sitemap->add(
    'https://example.com/about',
    date('c', strtotime('-1 week')),
    '0.8',
    'monthly'
);

// Add contact page
$sitemap->add(
    'https://example.com/contact',
    date('c', strtotime('-1 month')),
    '0.6',
    'yearly'
);

// Output XML with proper headers
header('Content-Type: application/xml; charset=utf-8');
echo $sitemap->renderXml();
```

## Adding Multiple URLs

### Using add() Method (Recommended)

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Static pages
$pages = [
    ['url' => 'https://example.com/', 'priority' => '1.0', 'freq' => 'daily'],
    ['url' => 'https://example.com/about', 'priority' => '0.8', 'freq' => 'monthly'],
    ['url' => 'https://example.com/services', 'priority' => '0.9', 'freq' => 'weekly'],
    ['url' => 'https://example.com/contact', 'priority' => '0.6', 'freq' => 'yearly'],
];

foreach ($pages as $page) {
    $sitemap->add(
        $page['url'],
        date('c'),
        $page['priority'],
        $page['freq']
    );
}

echo $sitemap->renderXml();
```

### Using addItem() Method (Array-based)

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Add single item with array
$sitemap->addItem([
    'loc' => 'https://example.com/blog',
    'lastmod' => date('c'),
    'priority' => '0.9',
    'freq' => 'weekly'
]);

// Add multiple items at once (batch)
$sitemap->addItem([
    [
        'loc' => 'https://example.com/blog/post-1',
        'lastmod' => date('c', strtotime('-1 day')),
        'priority' => '0.7',
        'freq' => 'monthly'
    ],
    [
        'loc' => 'https://example.com/blog/post-2',
        'lastmod' => date('c', strtotime('-2 days')),
        'priority' => '0.7',
        'freq' => 'monthly'
    ]
]);

echo $sitemap->renderXml();
```

## Working with Dates

### Different Date Formats

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Current date
$sitemap->add('https://example.com/today', date('c'), '1.0', 'daily');

// Specific date
$sitemap->add('https://example.com/page1', '2025-01-15T10:30:00+00:00', '0.8', 'monthly');

// Using DateTime object
$date = new DateTime('2025-01-10');
$sitemap->add('https://example.com/page2', $date->format(DATE_ATOM), '0.7', 'weekly');

// Using timestamp
$timestamp = strtotime('-1 week');
$sitemap->add('https://example.com/page3', date('c', $timestamp), '0.6', 'monthly');

echo $sitemap->renderXml();
```

## Priority and Frequency Guidelines

### Best Practices

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Homepage - Highest priority, updated frequently
$sitemap->add('https://example.com/', date('c'), '1.0', 'daily');

// Main sections - High priority
$sitemap->add('https://example.com/products', date('c'), '0.9', 'weekly');
$sitemap->add('https://example.com/blog', date('c'), '0.9', 'daily');

// Important pages - Medium-high priority
$sitemap->add('https://example.com/about', date('c'), '0.8', 'monthly');
$sitemap->add('https://example.com/services', date('c'), '0.8', 'monthly');

// Content pages - Medium priority
$sitemap->add('https://example.com/blog/article-1', date('c'), '0.7', 'monthly');
$sitemap->add('https://example.com/products/product-1', date('c'), '0.7', 'weekly');

// Static pages - Lower priority
$sitemap->add('https://example.com/contact', date('c'), '0.6', 'yearly');
$sitemap->add('https://example.com/privacy', date('c'), '0.5', 'yearly');
$sitemap->add('https://example.com/terms', date('c'), '0.5', 'yearly');

echo $sitemap->renderXml();
```

## Error Handling

### Basic Error Handling

```php
<?php
use Rumenx\Sitemap\Sitemap;

try {
    $sitemap = new Sitemap();
    
    // Add URLs
    $sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
    $sitemap->add('https://example.com/about', date('c'), '0.8', 'monthly');
    
    // Generate XML
    $xml = $sitemap->renderXml();
    
    // Set headers and output
    header('Content-Type: application/xml; charset=utf-8');
    echo $xml;
    
} catch (Exception $e) {
    // Log error
    error_log('Sitemap generation failed: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo 'Sitemap generation failed';
}
```

## Saving to File

### Generate and Save Sitemap

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Add URLs
$sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
$sitemap->add('https://example.com/about', date('c'), '0.8', 'monthly');

// Generate XML
$xml = $sitemap->renderXml();

// Save to file
$filename = 'sitemap.xml';
$result = file_put_contents($filename, $xml);

if ($result !== false) {
    echo "Sitemap saved to {$filename} ({$result} bytes)\n";
} else {
    echo "Failed to save sitemap\n";
}
```

### Save to Public Directory

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Add your URLs here
$sitemap->add('https://example.com/', date('c'), '1.0', 'daily');

// Generate XML
$xml = $sitemap->renderXml();

// Save to public directory
$publicPath = $_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml';
$result = file_put_contents($publicPath, $xml);

if ($result !== false) {
    echo "Sitemap available at: https://example.com/sitemap.xml\n";
} else {
    echo "Failed to save sitemap to public directory\n";
}
```

## Next Steps

Once you're comfortable with basic usage:

- Learn about [Framework Integration](framework-integration.md) for Laravel/Symfony
- Explore [Dynamic Sitemaps](dynamic-sitemaps.md) for database-driven content  
- Check [Rich Content](rich-content.md) for images, videos, and translations
- See [Rendering Formats](rendering-formats.md) for HTML, TXT, and other outputs

## Tips

1. **URL Validation**: Always use absolute URLs starting with `https://`
2. **Date Format**: Use ISO 8601 format (DATE_ATOM) for consistent dates
3. **Priority Range**: Keep priorities between 0.0 and 1.0
4. **Frequency Guidelines**: Use realistic update frequencies
5. **Testing**: Test your sitemap with Google Search Console
