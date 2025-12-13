# Fluent Interface and Method Chaining

The package now supports method chaining for a more elegant and readable API.

## Basic Method Chaining

### Chaining add() Methods

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Chain multiple add() calls
$sitemap
    ->add('https://example.com/', date('c'), '1.0', 'daily')
    ->add('https://example.com/about', date('c'), '0.8', 'monthly')
    ->add('https://example.com/contact', date('c'), '0.6', 'yearly');

// Render and output
echo $sitemap->renderXml();
```

### Chaining addItem() Methods

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Chain addItem() calls
$sitemap
    ->addItem(['loc' => 'https://example.com/', 'priority' => '1.0'])
    ->addItem(['loc' => 'https://example.com/about', 'priority' => '0.8'])
    ->addItem(['loc' => 'https://example.com/contact', 'priority' => '0.6']);

echo $sitemap->renderXml();
```

### Mixed Method Chaining

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Mix different methods
$sitemap
    ->add('https://example.com/', date('c'), '1.0', 'daily')
    ->addItem(['loc' => 'https://example.com/about', 'priority' => '0.8'])
    ->add('https://example.com/contact', date('c'), '0.6', 'yearly')
    ->addSitemap('https://example.com/sitemap2.xml', date('c'));

echo $sitemap->renderXml();
```

## Practical Examples

### Building Sitemap from Database

```php
<?php
use Rumenx\Sitemap\Sitemap;

// Fetch data from database
$pages = [
    ['url' => 'https://example.com/', 'priority' => '1.0', 'freq' => 'daily'],
    ['url' => 'https://example.com/about', 'priority' => '0.8', 'freq' => 'monthly'],
    ['url' => 'https://example.com/services', 'priority' => '0.9', 'freq' => 'weekly'],
];

$sitemap = new Sitemap();

// Build sitemap with method chaining
foreach ($pages as $page) {
    $sitemap->add($page['url'], date('c'), $page['priority'], $page['freq']);
}

// Can continue chaining after loop
$sitemap
    ->add('https://example.com/contact', date('c'), '0.6', 'yearly')
    ->add('https://example.com/privacy', date('c'), '0.5', 'yearly');

echo $sitemap->renderXml();
```

### Conditional Building

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Start building
$sitemap
    ->add('https://example.com/', date('c'), '1.0', 'daily')
    ->add('https://example.com/about', date('c'), '0.8', 'monthly');

// Conditionally add more URLs
if ($includeProducts) {
    $sitemap->add('https://example.com/products', date('c'), '0.9', 'weekly');
}

if ($includeBlog) {
    $sitemap->add('https://example.com/blog', date('c'), '0.9', 'daily');
}

// Continue chaining
$sitemap->add('https://example.com/contact', date('c'), '0.6', 'yearly');

echo $sitemap->renderXml();
```

### Chain with Configuration

```php
<?php
use Rumenx\Sitemap\Sitemap;
use Rumenx\Sitemap\Config\SitemapConfig;

// Configure and build in one flow
$config = (new SitemapConfig())
    ->setEscaping(true)
    ->setStrictMode(true)
    ->setDefaultFormat('xml');

$sitemap = (new Sitemap($config))
    ->add('https://example.com/', date('c'), '1.0', 'daily')
    ->add('https://example.com/about', date('c'), '0.8', 'monthly')
    ->add('https://example.com/contact', date('c'), '0.6', 'yearly');

echo $sitemap->renderXml();
```

## Advanced Chaining Patterns

### Chaining with Store

```php
<?php
use Rumenx\Sitemap\Sitemap;

// Build and save in one chain
(new Sitemap())
    ->add('https://example.com/', date('c'), '1.0', 'daily')
    ->add('https://example.com/about', date('c'), '0.8', 'monthly')
    ->add('https://example.com/contact', date('c'), '0.6', 'yearly')
    ->store('xml', 'sitemap', './public');

echo "Sitemap saved!\n";
```

### Batch Operations with Chaining

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Add static pages
$sitemap
    ->add('https://example.com/', date('c'), '1.0', 'daily')
    ->add('https://example.com/about', date('c'), '0.8', 'monthly');

// Add batch of items
$sitemap->addItem([
    ['loc' => 'https://example.com/blog/post-1', 'priority' => '0.7'],
    ['loc' => 'https://example.com/blog/post-2', 'priority' => '0.7'],
    ['loc' => 'https://example.com/blog/post-3', 'priority' => '0.7'],
]);

// Continue adding
$sitemap->add('https://example.com/contact', date('c'), '0.6', 'yearly');

echo $sitemap->renderXml();
```

### Laravel Integration with Chaining

```php
<?php
use Rumenx\Sitemap\Sitemap;
use App\Models\Post;
use App\Models\Product;

public function sitemap()
{
    $sitemap = new Sitemap();
    
    // Add static pages
    $sitemap
        ->add('https://example.com/', now(), '1.0', 'daily')
        ->add('https://example.com/about', now(), '0.8', 'monthly');
    
    // Add blog posts
    Post::published()->each(function ($post) use ($sitemap) {
        $sitemap->add(
            route('blog.show', $post),
            $post->updated_at->format(DATE_ATOM),
            '0.7',
            'weekly'
        );
    });
    
    // Add products
    Product::active()->each(function ($product) use ($sitemap) {
        $sitemap->add(
            route('products.show', $product),
            $product->updated_at->format(DATE_ATOM),
            '0.9',
            'daily'
        );
    });
    
    // Add final pages
    $sitemap
        ->add('https://example.com/contact', now(), '0.6', 'yearly')
        ->add('https://example.com/privacy', now(), '0.5', 'yearly');
    
    return response($sitemap->renderXml(), 200, [
        'Content-Type' => 'application/xml'
    ]);
}
```

### Sitemap Index with Chaining

```php
<?php
use Rumenx\Sitemap\Sitemap;

// Create sitemap index
$sitemapIndex = (new Sitemap())
    ->addSitemap('https://example.com/sitemap-pages.xml', date('c'))
    ->addSitemap('https://example.com/sitemap-posts.xml', date('c'))
    ->addSitemap('https://example.com/sitemap-products.xml', date('c'))
    ->resetSitemaps([
        ['loc' => 'https://example.com/sitemap-pages.xml', 'lastmod' => date('c')],
        ['loc' => 'https://example.com/sitemap-posts.xml', 'lastmod' => date('c')],
    ]);

// Generate sitemap index view
$sitemaps = $sitemapIndex->getModel()->getSitemaps();
```

## Chainable Methods

All these methods return `$this` and can be chained:

- `add()` - Add a single URL
- `addItem()` - Add items using arrays
- `addSitemap()` - Add sitemap index entry
- `resetSitemaps()` - Reset sitemap index entries
- `setConfig()` - Set configuration

## Benefits of Method Chaining

1. **Readability** - Code reads more naturally from top to bottom
2. **Conciseness** - Less repetition of variable names
3. **Fluent API** - More expressive and intuitive
4. **Less Typing** - Fewer lines of code
5. **Modern Style** - Follows contemporary PHP patterns

## Comparison

### Without Chaining (Old Style)

```php
<?php
$sitemap = new Sitemap();
$sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
$sitemap->add('https://example.com/about', date('c'), '0.8', 'monthly');
$sitemap->add('https://example.com/contact', date('c'), '0.6', 'yearly');
echo $sitemap->renderXml();
```

### With Chaining (New Style)

```php
<?php
echo (new Sitemap())
    ->add('https://example.com/', date('c'), '1.0', 'daily')
    ->add('https://example.com/about', date('c'), '0.8', 'monthly')
    ->add('https://example.com/contact', date('c'), '0.6', 'yearly')
    ->renderXml();
```

## Next Steps

- Explore [Validation and Configuration](validation-and-configuration.md) for type-safe config
- Check [Framework Integration](framework-integration.md) for Laravel/Symfony examples
- See [Dynamic Sitemaps](dynamic-sitemaps.md) for database-driven content

## Tips

1. **Chain liberally** - Makes code more readable and maintainable
2. **Mix and match** - Combine `add()`, `addItem()`, and other methods
3. **Break lines** - Use multi-line chaining for better readability
4. **Store at the end** - Chain `store()` as the final method when saving to file
5. **Return early** - Chain directly in return statements for cleaner controllers

