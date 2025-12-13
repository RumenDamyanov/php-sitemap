# Validation and Configuration

This guide covers the type-safe configuration and input validation features introduced in the latest version.

## Type-Safe Configuration with SitemapConfig

### Basic Configuration

```php
<?php
use Rumenx\Sitemap\Sitemap;
use Rumenx\Sitemap\Config\SitemapConfig;

// Create configuration
$config = new SitemapConfig(
    escaping: true,
    strictMode: false,
    useGzip: false,
    defaultFormat: 'xml'
);

// Create sitemap with configuration
$sitemap = new Sitemap($config);

// Add URLs as normal
$sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
```

### Configuration Options

```php
<?php
use Rumenx\Sitemap\Config\SitemapConfig;

$config = new SitemapConfig(
    escaping: true,              // Enable/disable URL escaping (default: true)
    useCache: false,             // Enable/disable caching (default: false)
    cachePath: '/tmp/cache',     // Custom cache path (default: null)
    useLimitSize: false,         // Enable sitemap size limits (default: false)
    maxSize: 10485760,           // Maximum size in bytes (default: 10MB)
    useGzip: false,              // Enable gzip compression (default: false)
    useStyles: true,             // Enable XSL stylesheets (default: true)
    domain: 'https://example.com', // Base domain (default: null)
    strictMode: false,           // Enable strict validation (default: false)
    defaultFormat: 'xml'         // Default format (default: 'xml')
);
```

### Create from Array

```php
<?php
use Rumenx\Sitemap\Sitemap;
use Rumenx\Sitemap\Config\SitemapConfig;

// Load from array (e.g., from config file)
$config = SitemapConfig::fromArray([
    'escaping' => true,
    'strict_mode' => true,
    'use_gzip' => true,
    'default_format' => 'xml'
]);

$sitemap = new Sitemap($config);
```

### Export to Array

```php
<?php
use Rumenx\Sitemap\Config\SitemapConfig;

$config = new SitemapConfig(
    escaping: false,
    strictMode: true
);

// Export configuration
$array = $config->toArray();
print_r($array);

// Output:
// Array
// (
//     [escaping] => 
//     [use_cache] => 
//     [cache_path] => 
//     [use_limit_size] => 
//     [max_size] => 10485760
//     [use_gzip] => 
//     [use_styles] => 1
//     [domain] => 
//     [strict_mode] => 1
//     [default_format] => xml
// )
```

### Fluent Configuration

```php
<?php
use Rumenx\Sitemap\Sitemap;
use Rumenx\Sitemap\Config\SitemapConfig;

// Chain configuration methods
$config = (new SitemapConfig())
    ->setEscaping(true)
    ->setStrictMode(true)
    ->setUseGzip(true)
    ->setDefaultFormat('xml')
    ->setDomain('https://example.com');

$sitemap = new Sitemap($config);
```

### Update Configuration

```php
<?php
use Rumenx\Sitemap\Sitemap;
use Rumenx\Sitemap\Config\SitemapConfig;

$sitemap = new Sitemap();

// Set configuration later
$config = new SitemapConfig(strictMode: true);
$sitemap->setConfig($config);

// Get current configuration
$currentConfig = $sitemap->getConfig();
```

## Input Validation

### Strict Mode Validation

When strict mode is enabled, all input is automatically validated:

```php
<?php
use Rumenx\Sitemap\Sitemap;
use Rumenx\Sitemap\Config\SitemapConfig;

// Enable strict mode
$config = new SitemapConfig(strictMode: true);
$sitemap = new Sitemap($config);

// Valid data - works fine
$sitemap->add('https://example.com', '2023-12-01', '0.8', 'daily');

// Invalid URL - throws InvalidArgumentException
try {
    $sitemap->add('not-a-valid-url', '2023-12-01', '0.8', 'daily');
} catch (\InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage(); // "Invalid URL format: not-a-valid-url"
}

// Invalid priority - throws InvalidArgumentException
try {
    $sitemap->add('https://example.com', '2023-12-01', '2.0', 'daily');
} catch (\InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage(); // "Priority must be between 0.0 and 1.0"
}

// Invalid frequency - throws InvalidArgumentException
try {
    $sitemap->add('https://example.com', '2023-12-01', '0.8', 'sometimes');
} catch (\InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage(); // "Invalid frequency: sometimes"
}
```

### Manual Validation

You can also use the validator directly:

```php
<?php
use Rumenx\Sitemap\Validation\SitemapValidator;

// Validate URL
try {
    SitemapValidator::validateUrl('https://example.com');
    echo "URL is valid\n";
} catch (\InvalidArgumentException $e) {
    echo "Invalid URL: " . $e->getMessage();
}

// Validate priority
try {
    SitemapValidator::validatePriority('0.8');
    echo "Priority is valid\n";
} catch (\InvalidArgumentException $e) {
    echo "Invalid priority: " . $e->getMessage();
}

// Validate frequency
try {
    SitemapValidator::validateFrequency('daily');
    echo "Frequency is valid\n";
} catch (\InvalidArgumentException $e) {
    echo "Invalid frequency: " . $e->getMessage();
}

// Validate date
try {
    SitemapValidator::validateLastmod('2023-12-01');
    echo "Date is valid\n";
} catch (\InvalidArgumentException $e) {
    echo "Invalid date: " . $e->getMessage();
}
```

### Validate Complete Item

```php
<?php
use Rumenx\Sitemap\Validation\SitemapValidator;

try {
    SitemapValidator::validateItem(
        'https://example.com',
        '2023-12-01',
        '0.8',
        'daily',
        [
            ['url' => 'https://example.com/image.jpg']
        ]
    );
    echo "Item is valid\n";
} catch (\InvalidArgumentException $e) {
    echo "Validation error: " . $e->getMessage();
}
```

### Validation Rules

**URL Validation:**
- Must not be empty
- Must be valid URL format
- Must use http or https scheme

**Priority Validation:**
- Must be between 0.0 and 1.0
- Null values are accepted

**Frequency Validation:**
- Must be one of: `always`, `hourly`, `daily`, `weekly`, `monthly`, `yearly`, `never`
- Null values are accepted

**Date Validation:**
- Must be valid ISO 8601 format
- Examples: `2023-12-01`, `2023-12-01T10:30:00+00:00`
- Null values are accepted

**Image Validation:**
- Must have a `url` field
- URL must be valid

## Practical Examples

### Production Configuration

```php
<?php
use Rumenx\Sitemap\Sitemap;
use Rumenx\Sitemap\Config\SitemapConfig;

// Production-ready configuration
$config = new SitemapConfig(
    escaping: true,           // Escape special characters
    strictMode: true,         // Validate all input
    useGzip: true,            // Enable compression
    useStyles: true,          // Include XSL styles
    defaultFormat: 'xml'      // Use XML format
);

$sitemap = new Sitemap($config);

// Validate and add URLs
try {
    $sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
    $sitemap->add('https://example.com/about', date('c'), '0.8', 'monthly');
} catch (\InvalidArgumentException $e) {
    // Log validation errors
    error_log("Sitemap validation error: " . $e->getMessage());
}
```

### Development Configuration

```php
<?php
use Rumenx\Sitemap\Sitemap;
use Rumenx\Sitemap\Config\SitemapConfig;

// Development configuration (relaxed validation)
$config = new SitemapConfig(
    escaping: false,          // Don't escape for debugging
    strictMode: false,        // Allow invalid data for testing
    useGzip: false,           // No compression for readability
    defaultFormat: 'xml'
);

$sitemap = new Sitemap($config);
```

### User Input Validation

```php
<?php
use Rumenx\Sitemap\Sitemap;
use Rumenx\Sitemap\Validation\SitemapValidator;
use Rumenx\Sitemap\Config\SitemapConfig;

// Validate user-provided data before adding to sitemap
$userUrls = $_POST['urls'] ?? [];

$config = new SitemapConfig(strictMode: true);
$sitemap = new Sitemap($config);

foreach ($userUrls as $url) {
    try {
        // Pre-validate
        SitemapValidator::validateUrl($url);
        
        // Add to sitemap
        $sitemap->add($url, date('c'), '0.5', 'monthly');
        
        echo "Added: $url\n";
    } catch (\InvalidArgumentException $e) {
        echo "Skipped invalid URL ($url): " . $e->getMessage() . "\n";
    }
}
```

## Next Steps

- Learn about [Fluent Interface](fluent-interface.md) for method chaining
- Explore [Framework Integration](framework-integration.md) for Laravel/Symfony
- Check [Advanced Features](rendering-formats.md) for different output formats

## Tips

1. **Enable strict mode in production** to catch data quality issues early
2. **Use manual validation** for user-provided data before adding to sitemaps
3. **Configure once, reuse** - create a configuration object and pass it to multiple sitemaps
4. **Export configuration** to save settings to files or databases
5. **Fluent interface** makes configuration code more readable and maintainable

