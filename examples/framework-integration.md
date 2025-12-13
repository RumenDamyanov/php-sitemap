# Framework Integration

Learn how to integrate the `rumenx/php-sitemap` package into popular PHP frameworks including Laravel, Symfony, and others.

## Laravel Integration

### Basic Laravel Setup

#### Service Provider Registration

Add to `config/app.php` (if not using auto-discovery):

```php
'providers' => [
    // Other providers...
    Rumenx\Sitemap\Adapters\LaravelSitemapAdapter::class,
],
```

#### Route Definition

```php
// routes/web.php
use App\Http\Controllers\SitemapController;

Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])
    ->name('sitemap');

Route::get('/sitemap-{type}.xml', [SitemapController::class, 'sitemapByType'])
    ->where('type', 'posts|products|categories')
    ->name('sitemap.type');

Route::get('/sitemap-index.xml', [SitemapController::class, 'sitemapIndex'])
    ->name('sitemap.index');
```

#### Controller Implementation

```php
<?php
// app/Http/Controllers/SitemapController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Rumenx\Sitemap\Sitemap;
use App\Models\Post;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        return Cache::remember('sitemap.main', 3600, function () {
            $sitemap = new Sitemap();
            
            // Add static pages
            $sitemap->add(url('/'), now()->toISOString(), '1.0', 'daily');
            $sitemap->add(url('/about'), now()->toISOString(), '0.8', 'monthly');
            $sitemap->add(url('/contact'), now()->toISOString(), '0.6', 'yearly');
            
            // Add recent posts
            Post::published()
                ->latest('updated_at')
                ->limit(1000)
                ->chunk(100, function ($posts) use ($sitemap) {
                    foreach ($posts as $post) {
                        $sitemap->add(
                            url("/blog/{$post->slug}"),
                            $post->updated_at->toISOString(),
                            '0.7',
                            'monthly',
                            [], // images
                            $post->title
                        );
                    }
                });
            
            $xml = $sitemap->renderXml();
            
            return response($xml, 200, [
                'Content-Type' => 'application/xml; charset=utf-8'
            ]);
        });
    }
    
    public function sitemapByType(string $type): Response
    {
        return Cache::remember("sitemap.{$type}", 3600, function () use ($type) {
            $sitemap = new Sitemap();
            
            switch ($type) {
                case 'posts':
                    $this->addPosts($sitemap);
                    break;
                case 'products':
                    $this->addProducts($sitemap);
                    break;
                case 'categories':
                    $this->addCategories($sitemap);
                    break;
            }
            
            $xml = $sitemap->renderXml();
            
            return response($xml, 200, [
                'Content-Type' => 'application/xml; charset=utf-8'
            ]);
        });
    }
    
    public function sitemapIndex(): Response
    {
        return Cache::remember('sitemap.index', 3600, function () {
            $sitemap = new Sitemap();
            
            // Add main sitemap
            $sitemap->addSitemap(url('/sitemap.xml'), now()->toISOString());
            
            // Add content type sitemaps
            $types = ['posts', 'products', 'categories'];
            foreach ($types as $type) {
                $sitemap->addSitemap(
                    url("/sitemap-{$type}.xml"),
                    $this->getLastModified($type)
                );
            }
            
            // Render using the sitemapindex view
            $items = $sitemap->getModel()->getSitemaps();
            $xml = view('sitemap.sitemapindex', compact('items'))->render();
            
            return response($xml, 200, [
                'Content-Type' => 'application/xml; charset=utf-8'
            ]);
        });
    }
    
    private function addPosts(Sitemap $sitemap): void
    {
        Post::published()
            ->with('featuredImage')
            ->latest('updated_at')
            ->chunk(1000, function ($posts) use ($sitemap) {
                foreach ($posts as $post) {
                    $images = [];
                    
                    if ($post->featuredImage) {
                        $images[] = [
                            'url' => $post->featuredImage->url,
                            'title' => $post->featuredImage->alt_text ?? $post->title,
                            'caption' => $post->featuredImage->caption
                        ];
                    }
                    
                    $sitemap->add(
                        url("/blog/{$post->slug}"),
                        $post->updated_at->toISOString(),
                        '0.7',
                        'monthly',
                        $images,
                        $post->title
                    );
                }
            });
    }
    
    private function addProducts(Sitemap $sitemap): void
    {
        Product::active()
            ->with('images')
            ->latest('updated_at')
            ->chunk(1000, function ($products) use ($sitemap) {
                foreach ($products as $product) {
                    $images = $product->images->map(function ($image) use ($product) {
                        return [
                            'url' => $image->url,
                            'title' => $image->alt_text ?? $product->name,
                            'caption' => $image->caption
                        ];
                    })->toArray();
                    
                    $sitemap->add(
                        url("/products/{$product->slug}"),
                        $product->updated_at->toISOString(),
                        '0.8',
                        'weekly',
                        $images,
                        $product->name
                    );
                }
            });
    }
    
    private function addCategories(Sitemap $sitemap): void
    {
        Category::active()
            ->latest('updated_at')
            ->chunk(100, function ($categories) use ($sitemap) {
                foreach ($categories as $category) {
                    $sitemap->add(
                        url("/categories/{$category->slug}"),
                        $category->updated_at->toISOString(),
                        '0.6',
                        'monthly',
                        [],
                        $category->name
                    );
                }
            });
    }
    
    private function getLastModified(string $type): string
    {
        switch ($type) {
            case 'posts':
                $latest = Post::published()->latest('updated_at')->first();
                break;
            case 'products':
                $latest = Product::active()->latest('updated_at')->first();
                break;
            case 'categories':
                $latest = Category::active()->latest('updated_at')->first();
                break;
            default:
                return now()->toISOString();
        }
        
        return $latest ? $latest->updated_at->toISOString() : now()->toISOString();
    }
}
```

### Laravel Cache Invalidation

#### Event-Based Cache Clearing

```php
<?php
// app/Observers/SitemapCacheObserver.php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;

class SitemapCacheObserver
{
    public function created($model): void
    {
        $this->clearSitemapCache($model);
    }
    
    public function updated($model): void
    {
        $this->clearSitemapCache($model);
    }
    
    public function deleted($model): void
    {
        $this->clearSitemapCache($model);
    }
    
    private function clearSitemapCache($model): void
    {
        $modelClass = get_class($model);
        
        // Clear specific sitemap cache based on model
        if (str_contains($modelClass, 'Post')) {
            Cache::forget('sitemap.posts');
        } elseif (str_contains($modelClass, 'Product')) {
            Cache::forget('sitemap.products');
        } elseif (str_contains($modelClass, 'Category')) {
            Cache::forget('sitemap.categories');
        }
        
        // Clear main sitemap caches
        Cache::forget('sitemap.main');
        Cache::forget('sitemap.index');
    }
}
```

Register the observer in `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Post;
use App\Models\Product;
use App\Models\Category;
use App\Observers\SitemapCacheObserver;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Post::observe(SitemapCacheObserver::class);
        Product::observe(SitemapCacheObserver::class);
        Category::observe(SitemapCacheObserver::class);
    }
}
```

### Laravel Command for Sitemap Generation

```php
<?php
// app/Console/Commands/GenerateSitemap.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Rumenx\Sitemap\Sitemap;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate {--type=all : Type of sitemap to generate}';
    protected $description = 'Generate sitemap files';
    
    public function handle(): int
    {
        $type = $this->option('type');
        
        switch ($type) {
            case 'all':
                $this->generateAllSitemaps();
                break;
            case 'posts':
                $this->generatePostsSitemap();
                break;
            case 'products':
                $this->generateProductsSitemap();
                break;
            default:
                $this->error("Unknown sitemap type: {$type}");
                return 1;
        }
        
        $this->info('Sitemap generation completed!');
        return 0;
    }
    
    private function generateAllSitemaps(): void
    {
        $this->info('Generating all sitemaps...');
        
        $this->generatePostsSitemap();
        $this->generateProductsSitemap();
        $this->generateSitemapIndex();
    }
    
    private function generatePostsSitemap(): void
    {
        $this->info('Generating posts sitemap...');
        
        $sitemap = new Sitemap();
        
        Post::published()
            ->latest('updated_at')
            ->chunk(1000, function ($posts) use ($sitemap) {
                foreach ($posts as $post) {
                    $sitemap->add(
                        url("/blog/{$post->slug}"),
                        $post->updated_at->toISOString(),
                        '0.7',
                        'monthly'
                    );
                }
            });
        
        $xml = $sitemap->renderXml();
        Storage::disk('public')->put('sitemap-posts.xml', $xml);
        
        $this->info('Posts sitemap generated: sitemap-posts.xml');
    }
    
    private function generateProductsSitemap(): void
    {
        $this->info('Generating products sitemap...');
        
        $sitemap = new Sitemap();
        
        Product::active()
            ->latest('updated_at')
            ->chunk(1000, function ($products) use ($sitemap) {
                foreach ($products as $product) {
                    $sitemap->add(
                        url("/products/{$product->slug}"),
                        $product->updated_at->toISOString(),
                        '0.8',
                        'weekly'
                    );
                }
            });
        
        $xml = $sitemap->renderXml();
        Storage::disk('public')->put('sitemap-products.xml', $xml);
        
        $this->info('Products sitemap generated: sitemap-products.xml');
    }
    
    private function generateSitemapIndex(): void
    {
        $this->info('Generating sitemap index...');
        
        $sitemap = new Sitemap();
        
        $sitemap->addSitemap(url('/storage/sitemap-posts.xml'), now()->toISOString());
        $sitemap->addSitemap(url('/storage/sitemap-products.xml'), now()->toISOString());
        
        $items = $sitemap->getModel()->getSitemaps();
        $xml = view('sitemap.sitemapindex', compact('items'))->render();
        
        Storage::disk('public')->put('sitemap.xml', $xml);
        
        $this->info('Sitemap index generated: sitemap.xml');
    }
}
```

## Symfony Integration

### Service Configuration

```yaml
# config/services.yaml
services:
    Rumenx\Sitemap\Sitemap:
        public: true
    
    App\Service\SitemapService:
        arguments:
            $sitemap: '@Rumenx\Sitemap\Sitemap'
            $entityManager: '@doctrine.orm.entity_manager'
```

### Symfony Controller Implementation

```php
<?php
// src/Controller/SitemapController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use App\Service\SitemapService;

class SitemapController extends AbstractController
{
    private SitemapService $sitemapService;
    private FilesystemAdapter $cache;
    
    public function __construct(SitemapService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
        $this->cache = new FilesystemAdapter();
    }
    
    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function sitemap(): Response
    {
        $xml = $this->cache->get('sitemap_main', function (ItemInterface $item) {
            $item->expiresAfter(3600); // 1 hour
            
            return $this->sitemapService->generateMainSitemap();
        });
        
        return new Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8'
        ]);
    }
    
    #[Route('/sitemap-{type}.xml', name: 'sitemap_type', methods: ['GET'])]
    public function sitemapByType(string $type): Response
    {
        $xml = $this->cache->get("sitemap_{$type}", function (ItemInterface $item) use ($type) {
            $item->expiresAfter(3600);
            
            return $this->sitemapService->generateSitemapByType($type);
        });
        
        return new Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8'
        ]);
    }
    
    #[Route('/sitemap-index.xml', name: 'sitemap_index', methods: ['GET'])]
    public function sitemapIndex(): Response
    {
        $xml = $this->cache->get('sitemap_index', function (ItemInterface $item) {
            $item->expiresAfter(3600);
            
            return $this->sitemapService->generateSitemapIndex();
        });
        
        return new Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8'
        ]);
    }
}
```

### Service Implementation

```php
<?php
// src/Service/SitemapService.php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Rumenx\Sitemap\Sitemap;
use App\Entity\Post;
use App\Entity\Product;
use App\Entity\Category;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapService
{
    private Sitemap $sitemap;
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;
    
    public function __construct(
        Sitemap $sitemap,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->sitemap = $sitemap;
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
    }
    
    public function generateMainSitemap(): string
    {
        $sitemap = new Sitemap();
        
        // Add static routes
        $sitemap->add(
            $this->urlGenerator->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL),
            (new \DateTime())->format(\DateTime::ATOM),
            '1.0',
            'daily'
        );
        
        $sitemap->add(
            $this->urlGenerator->generate('about', [], UrlGeneratorInterface::ABSOLUTE_URL),
            (new \DateTime())->format(\DateTime::ATOM),
            '0.8',
            'monthly'
        );
        
        // Add recent posts
        $posts = $this->entityManager
            ->getRepository(Post::class)
            ->findBy(['published' => true], ['updatedAt' => 'DESC'], 1000);
        
        foreach ($posts as $post) {
            $sitemap->add(
                $this->urlGenerator->generate('post_show', ['slug' => $post->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                $post->getUpdatedAt()->format(\DateTime::ATOM),
                '0.7',
                'monthly',
                [], // images
                $post->getTitle()
            );
        }
        
        return $sitemap->renderXml();
    }
    
    public function generateSitemapByType(string $type): string
    {
        $sitemap = new Sitemap();
        
        switch ($type) {
            case 'posts':
                $this->addPosts($sitemap);
                break;
            case 'products':
                $this->addProducts($sitemap);
                break;
            case 'categories':
                $this->addCategories($sitemap);
                break;
            default:
                throw new \InvalidArgumentException("Unknown sitemap type: {$type}");
        }
        
        return $sitemap->renderXml();
    }
    
    public function generateSitemapIndex(): string
    {
        $sitemap = new Sitemap();
        
        $sitemap->addSitemap(
            $this->urlGenerator->generate('sitemap', [], UrlGeneratorInterface::ABSOLUTE_URL),
            (new \DateTime())->format(\DateTime::ATOM)
        );
        
        $types = ['posts', 'products', 'categories'];
        foreach ($types as $type) {
            $sitemap->addSitemap(
                $this->urlGenerator->generate('sitemap_type', ['type' => $type], UrlGeneratorInterface::ABSOLUTE_URL),
                $this->getLastModified($type)
            );
        }
        
        // Use Twig to render the index
        $items = $sitemap->getModel()->getSitemaps();
        
        // You would need to create a Twig template for this
        return $this->renderSitemapIndex($items);
    }
    
    private function addPosts(Sitemap $sitemap): void
    {
        $posts = $this->entityManager
            ->getRepository(Post::class)
            ->findBy(['published' => true], ['updatedAt' => 'DESC']);
        
        foreach ($posts as $post) {
            $images = [];
            
            if ($post->getFeaturedImage()) {
                $images[] = [
                    'url' => $post->getFeaturedImage()->getUrl(),
                    'title' => $post->getFeaturedImage()->getAltText() ?? $post->getTitle(),
                    'caption' => $post->getFeaturedImage()->getCaption()
                ];
            }
            
            $sitemap->add(
                $this->urlGenerator->generate('post_show', ['slug' => $post->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                $post->getUpdatedAt()->format(\DateTime::ATOM),
                '0.7',
                'monthly',
                $images,
                $post->getTitle()
            );
        }
    }
    
    private function addProducts(Sitemap $sitemap): void
    {
        $products = $this->entityManager
            ->getRepository(Product::class)
            ->findBy(['active' => true], ['updatedAt' => 'DESC']);
        
        foreach ($products as $product) {
            $images = [];
            
            foreach ($product->getImages() as $image) {
                $images[] = [
                    'url' => $image->getUrl(),
                    'title' => $image->getAltText() ?? $product->getName(),
                    'caption' => $image->getCaption()
                ];
            }
            
            $sitemap->add(
                $this->urlGenerator->generate('product_show', ['slug' => $product->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                $product->getUpdatedAt()->format(\DateTime::ATOM),
                '0.8',
                'weekly',
                $images,
                $product->getName()
            );
        }
    }
    
    private function addCategories(Sitemap $sitemap): void
    {
        $categories = $this->entityManager
            ->getRepository(Category::class)
            ->findBy(['active' => true], ['updatedAt' => 'DESC']);
        
        foreach ($categories as $category) {
            $sitemap->add(
                $this->urlGenerator->generate('category_show', ['slug' => $category->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                $category->getUpdatedAt()->format(\DateTime::ATOM),
                '0.6',
                'monthly',
                [],
                $category->getName()
            );
        }
    }
    
    private function getLastModified(string $type): string
    {
        switch ($type) {
            case 'posts':
                $repository = $this->entityManager->getRepository(Post::class);
                $latest = $repository->findOneBy(['published' => true], ['updatedAt' => 'DESC']);
                break;
            case 'products':
                $repository = $this->entityManager->getRepository(Product::class);
                $latest = $repository->findOneBy(['active' => true], ['updatedAt' => 'DESC']);
                break;
            case 'categories':
                $repository = $this->entityManager->getRepository(Category::class);
                $latest = $repository->findOneBy(['active' => true], ['updatedAt' => 'DESC']);
                break;
            default:
                return (new \DateTime())->format(\DateTime::ATOM);
        }
        
        return $latest ? $latest->getUpdatedAt()->format(\DateTime::ATOM) : (new \DateTime())->format(\DateTime::ATOM);
    }
    
    private function renderSitemapIndex(array $items): string
    {
        // Simple XML generation for sitemap index
        // In a real application, you'd use Twig templates
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($items as $item) {
            $xml .= '  <sitemap>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($item['loc']) . '</loc>' . "\n";
            if (isset($item['lastmod'])) {
                $xml .= '    <lastmod>' . htmlspecialchars($item['lastmod']) . '</lastmod>' . "\n";
            }
            $xml .= '  </sitemap>' . "\n";
        }
        
        $xml .= '</sitemapindex>';
        
        return $xml;
    }
}
```

### Symfony Console Command

```php
<?php
// src/Command/GenerateSitemapCommand.php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\SitemapService;

#[AsCommand(
    name: 'sitemap:generate',
    description: 'Generate sitemap files'
)]
class GenerateSitemapCommand extends Command
{
    private SitemapService $sitemapService;
    
    public function __construct(SitemapService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Sitemap type to generate', 'all');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getOption('type');
        
        $output->writeln("Generating sitemap(s): {$type}");
        
        switch ($type) {
            case 'all':
                $this->generateAll($output);
                break;
            default:
                $xml = $this->sitemapService->generateSitemapByType($type);
                file_put_contents("public/sitemap-{$type}.xml", $xml);
                $output->writeln("Generated sitemap-{$type}.xml");
                break;
        }
        
        $output->writeln('Sitemap generation completed!');
        
        return Command::SUCCESS;
    }
    
    private function generateAll(OutputInterface $output): void
    {
        $types = ['posts', 'products', 'categories'];
        
        foreach ($types as $type) {
            $xml = $this->sitemapService->generateSitemapByType($type);
            file_put_contents("public/sitemap-{$type}.xml", $xml);
            $output->writeln("Generated sitemap-{$type}.xml");
        }
        
        $indexXml = $this->sitemapService->generateSitemapIndex();
        file_put_contents('public/sitemap.xml', $indexXml);
        $output->writeln('Generated sitemap.xml (index)');
    }
}
```

## Standalone PHP Integration

### Simple Router Integration

```php
<?php
// public/index.php (simple router example)

require 'vendor/autoload.php';

use Rumenx\Sitemap\Sitemap;

$requestUri = $_SERVER['REQUEST_URI'];

switch ($requestUri) {
    case '/sitemap.xml':
        generateMainSitemap();
        break;
    case '/sitemap-posts.xml':
        generatePostsSitemap();
        break;
    case '/sitemap-products.xml':
        generateProductsSitemap();
        break;
    default:
        http_response_code(404);
        echo 'Not Found';
}

function generateMainSitemap()
{
    $sitemap = new Sitemap();
    
    // Add static pages
    $sitemap->add('https://example.com/', date('c'), '1.0', 'daily');
    $sitemap->add('https://example.com/about', date('c'), '0.8', 'monthly');
    
    // Add dynamic content from database
    $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
    
    $stmt = $pdo->query("
        SELECT slug, updated_at, title 
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
            [], // images
            $post['title']
        );
    }
    
    header('Content-Type: application/xml; charset=utf-8');
    echo $sitemap->renderXml();
}

function generatePostsSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
    
    $stmt = $pdo->query("
        SELECT slug, updated_at, title 
        FROM posts 
        WHERE published = 1 
        ORDER BY updated_at DESC
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
    
    header('Content-Type: application/xml; charset=utf-8');
    echo $sitemap->renderXml();
}

function generateProductsSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
    
    $stmt = $pdo->query("
        SELECT slug, updated_at, name 
        FROM products 
        WHERE active = 1 
        ORDER BY updated_at DESC
    ");
    
    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sitemap->add(
            "https://example.com/products/{$product['slug']}",
            date('c', strtotime($product['updated_at'])),
            '0.8',
            'weekly',
            [],
            $product['name']
        );
    }
    
    header('Content-Type: application/xml; charset=utf-8');
    echo $sitemap->renderXml();
}
```

## Best Practices

### Framework-Agnostic Tips

1. **Caching Strategy**
   - Use framework-specific caching (Redis, Memcached, file cache)
   - Implement cache invalidation on content updates
   - Set appropriate cache TTL based on content update frequency

2. **Performance Optimization**
   - Use database chunking for large datasets
   - Implement lazy loading for related data
   - Consider background job processing for large sitemaps

3. **URL Generation**
   - Use framework URL helpers for consistency
   - Ensure all URLs are absolute
   - Handle URL encoding properly

4. **Error Handling**
   - Implement proper exception handling
   - Log sitemap generation errors
   - Provide fallback sitemaps when needed

5. **Testing**
   - Test sitemap generation with large datasets
   - Validate XML output
   - Test cache invalidation scenarios

## Next Steps

- Explore [Rich Content](rich-content.md) for images, videos, and translations
- Check [Caching Strategies](caching-strategies.md) for optimization
- See [Automated Generation](automated-generation.md) for scheduling
- Learn about [E-commerce Examples](e-commerce.md) for product catalogs
