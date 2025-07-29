# **[php-sitemap](https://github.com/RumenDamyanov/php-sitemap) package**

[![CI](https://github.com/RumenDamyanov/php-sitemap/actions/workflows/ci.yml/badge.svg)](https://github.com/RumenDamyanov/php-sitemap/actions)
[![codecov](https://codecov.io/gh/RumenDamyanov/php-sitemap/branch/master/graph/badge.svg)](https://codecov.io/gh/RumenDamyanov/php-sitemap)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE.md)

**php-sitemap** is a modern, framework-agnostic PHP package for generating sitemaps in XML, TXT, HTML, and Google News formats. It works seamlessly with Laravel, Symfony, or any PHP project. Features include high test coverage, robust CI, extensible adapters, and support for images, videos, translations, alternates, and Google News.


---

## Features

- **Framework-agnostic**: Use in Laravel, Symfony, or any PHP project
- **Multiple formats**: XML, TXT, HTML, Google News, mobile
- **Rich content**: Supports images, videos, translations, alternates, Google News
- **Modern PHP**: Type-safe, extensible, and robust (PHP 8.2+)
- **High test coverage**: 100% code coverage, CI/CD ready
- **Easy integration**: Simple API, drop-in for controllers/routes
- **Extensible**: Adapters for Laravel, Symfony, and more
- **Quality tools**: PHPStan Level 6, PSR-12, comprehensive testing

---

## Quick Links

- 📖 [Installation](#installation)
- 🚀 [Usage Examples](#usage)
- 🧪 [Testing & Development](#testing--development)
- 🤝 [Contributing](CONTRIBUTING.md)
- 🔒 [Security Policy](SECURITY.md)
- 💝 [Support & Funding](FUNDING.md)
- 📄 [License](#license)

---

## Installation

```bash
composer require rumenx/php-sitemap
```

---

## Usage

### Laravel Example

**Controller method:**

```php
use Rumenx\Sitemap\Sitemap;

public function sitemap(Sitemap $sitemap)
{
    $sitemap->add('https://example.com/', now(), '1.0', 'daily');
    $sitemap->add('https://example.com/about', now(), '0.8', 'monthly', images: [
        ['url' => 'https://example.com/img/about.jpg', 'title' => 'About Us']
    ]);
    // Add more items as needed...
    
    // Render XML using a view template
    $items = $sitemap->getModel()->getItems();
    return response()->view('sitemap.xml', compact('items'), 200, ['Content-Type' => 'application/xml']);
}
```

**Route registration:**

```php
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap']);
```

**Advanced:**

```php
// Add with translations, videos, alternates, Google News
$sitemap->add(
    'https://example.com/news',
    now(),
    '0.7',
    'weekly',
    images: [['url' => 'https://example.com/img/news.jpg', 'title' => 'News Image']],
    title: 'News Article',
    translations: [['language' => 'fr', 'url' => 'https://example.com/fr/news']],
    videos: [['title' => 'News Video', 'description' => 'Video description']],
    googlenews: [
        'sitename' => 'Example News',
        'language' => 'en',
        'publication_date' => now(),
    ],
    alternates: [['media' => 'print', 'url' => 'https://example.com/news-print']]
);
```

---

### Symfony Example

**Controller:**

```php
use Rumenx\Sitemap\Sitemap;
use Symfony\Component\HttpFoundation\Response;

class SitemapController
{
    public function sitemap(): Response
    {
        $sitemap = new Sitemap();
        $sitemap->add('https://example.com/', (new \DateTime())->format(DATE_ATOM), '1.0', 'daily');
        $sitemap->add('https://example.com/contact', (new \DateTime())->format(DATE_ATOM), '0.5', 'monthly');
        // Add more items as needed...
        
        // Render XML
        $xml = $sitemap->renderXml();
        return new Response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
```

**Route registration:**

```yaml
# config/routes.yaml
sitemap:
    path: /sitemap.xml
    controller: App\Controller\SitemapController::sitemap
```

---

### Generic PHP Example

```php
require 'vendor/autoload.php';

use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
$sitemap->add('https://example.com/products', date('c'), '0.9', 'weekly', [
    ['url' => 'https://example.com/img/product.jpg', 'title' => 'Product Image']
]);

// Output XML
header('Content-Type: application/xml');
echo $sitemap->renderXml();
```

---

### Advanced Features

```php
// Add with all supported fields
$sitemap->add(
    'https://example.com/news',
    date('c'),
    '0.8',
    'daily',
    images: [['url' => 'https://example.com/img/news.jpg', 'title' => 'News Image']],
    title: 'News Article',
    translations: [['language' => 'fr', 'url' => 'https://example.com/fr/news']],
    videos: [['title' => 'News Video', 'description' => 'Video description']],
    googlenews: [
        'sitename' => 'Example News',
        'language' => 'en',
        'publication_date' => date('c'),
    ],
    alternates: [['media' => 'print', 'url' => 'https://example.com/news-print']]
);

// Generate XML using renderXml() method
$xml = $sitemap->renderXml();
file_put_contents('sitemap.xml', $xml);

// Or use view templates for more control (create your own views based on src/views/)
$items = $sitemap->getModel()->getItems();
// Pass $items to your view template
```

---

### add() vs addItem()

You can add sitemap entries using either the `add()` or `addItem()` methods:

**add() — Simple, type-safe, one-at-a-time:**

```php
// Recommended for most use cases
$sitemap->add(
    'https://example.com/',
    date('c'),
    '1.0',
    'daily',
    images: [['url' => 'https://example.com/img.jpg', 'title' => 'Image']],
    title: 'Homepage'
);
```

**addItem() — Advanced, array-based, supports batch:**

```php
// Add a single item with an array (all fields as keys)
$sitemap->addItem([
    'loc' => 'https://example.com/about',
    'lastmod' => date('c'),
    'priority' => '0.8',
    'freq' => 'monthly',
    'title' => 'About Us',
    'images' => [['url' => 'https://example.com/img/about.jpg', 'title' => 'About Us']],
]);

// Add multiple items at once (batch add)
$sitemap->addItem([
    [
        'loc' => 'https://example.com/page1',
        'title' => 'Page 1',
    ],
    [
        'loc' => 'https://example.com/page2',
        'title' => 'Page 2',
    ],
]);
```

- Use `add()` for simple, explicit, one-at-a-time additions (recommended for most users).
- Use `addItem()` for advanced, batch, or programmatic additions with arrays (e.g., when looping over database results).

---

## Rendering Options

The package provides multiple ways to generate sitemap output:

### 1. Built-in XML Renderer (Simple)

```php
$sitemap = new Sitemap();
$sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
$xml = $sitemap->renderXml(); // Returns XML string
```

### 2. View Templates (Flexible)

For more control, use the included view templates or create your own:

```php
$sitemap = new Sitemap();
$sitemap->add('https://example.com/', date('c'), '1.0', 'daily');

// Get the data for your view
$items = $sitemap->getModel()->getItems();

// Laravel: Use response()->view() or view()->render()
return response()->view('sitemap.xml', compact('items'), 200, ['Content-Type' => 'application/xml']);

// Symfony: Use Twig templates
return $this->render('sitemap.xml.twig', ['items' => $items], new Response('', 200, ['Content-Type' => 'application/xml']));

// Generic PHP: Include view templates
ob_start();
include 'vendor/rumenx/php-sitemap/src/views/xml.php';
$xml = ob_get_clean();
```

**Available view templates** in `src/views/`:

- `xml.php` - Standard XML sitemap
- `xml-mobile.php` - Mobile-specific sitemap
- `google-news.php` - Google News sitemap
- `sitemapindex.php` - Sitemap index
- `txt.php` - Plain text format
- `html.php` - HTML format

## Testing & Development

### Running Tests

```bash
# Run all tests
composer test

# Run tests with text coverage report
composer coverage

# Generate full HTML coverage report
composer coverage-html
```

### Code Quality

```bash
# Run static analysis (PHPStan Level 6)
composer analyze

# Check coding standards (PSR-12)
composer style

# Auto-fix coding standards
composer style-fix
```

### Manual Testing

```bash
# Run specific test file
./vendor/bin/pest tests/Unit/SitemapTest.php

# Run tests in watch mode
./vendor/bin/pest --watch
```

---

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details on:

- Development setup
- Coding standards
- Testing requirements
- Pull request process

---

## Security

If you discover a security vulnerability, please review our [Security Policy](SECURITY.md) for responsible disclosure guidelines.

---

## Support

If you find this package helpful, consider:

- ⭐ Starring the repository
- 💝 [Supporting development](FUNDING.md)
- 🐛 [Reporting issues](https://github.com/RumenDamyanov/php-sitemap/issues)
- 🤝 [Contributing improvements](CONTRIBUTING.md)

---

## License

[MIT License](LICENSE.md)
