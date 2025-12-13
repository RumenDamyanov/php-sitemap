# Automated Generation

Learn how to set up automated sitemap generation using the `rumenx/php-sitemap` package. This guide covers cron jobs, queues, webhooks, and monitoring for production-ready automated sitemap systems.

## Cron-Based Generation

### Basic Cron Setup

```php
<?php
// scripts/generate-sitemap.php

use Rumenx\Sitemap\Sitemap;

class CronSitemapGenerator
{
    private $config;
    private $outputDir;
    private $logFile;
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->outputDir = $config['output_dir'] ?? 'public/sitemaps';
        $this->logFile = $config['log_file'] ?? 'logs/sitemap-generation.log';
        
        // Ensure directories exist
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
        
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }
    
    public function generateAllSitemaps()
    {
        $this->log("Starting sitemap generation at " . date('Y-m-d H:i:s'));
        
        try {
            $results = [];
            
            // Generate main sitemap
            $results['main'] = $this->generateMainSitemap();
            
            // Generate product sitemap
            $results['products'] = $this->generateProductSitemap();
            
            // Generate blog sitemap
            $results['blog'] = $this->generateBlogSitemap();
            
            // Generate category sitemap
            $results['categories'] = $this->generateCategorySitemap();
            
            // Generate sitemap index
            $results['index'] = $this->generateSitemapIndex($results);
            
            // Update last generation timestamp
            $this->updateLastGeneration();
            
            $this->log("Successfully generated all sitemaps: " . json_encode($results));
            
            return $results;
            
        } catch (Exception $e) {
            $this->log("Error generating sitemaps: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function generateMainSitemap()
    {
        $sitemap = new Sitemap();
        
        // Static pages
        $staticPages = [
            '/' => ['priority' => '1.0', 'changefreq' => 'weekly'],
            '/about' => ['priority' => '0.8', 'changefreq' => 'monthly'],
            '/contact' => ['priority' => '0.7', 'changefreq' => 'monthly'],
            '/privacy' => ['priority' => '0.5', 'changefreq' => 'yearly'],
            '/terms' => ['priority' => '0.5', 'changefreq' => 'yearly']
        ];
        
        foreach ($staticPages as $url => $params) {
            $sitemap->add(
                $this->config['base_url'] . $url,
                date('c'),
                $params['priority'],
                $params['changefreq']
            );
        }
        
        $filename = $this->outputDir . '/sitemap-main.xml';
        file_put_contents($filename, $sitemap->renderXml());
        
        return [
            'filename' => 'sitemap-main.xml',
            'path' => $filename,
            'urls' => count($staticPages),
            'size' => filesize($filename)
        ];
    }
    
    private function generateProductSitemap()
    {
        $pdo = $this->getDatabaseConnection();
        
        $stmt = $pdo->query("
            SELECT slug, updated_at, stock_quantity
            FROM products 
            WHERE active = 1 
            ORDER BY updated_at DESC
        ");
        
        $sitemap = new Sitemap();
        $urlCount = 0;
        
        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $priority = $product['stock_quantity'] > 0 ? '0.8' : '0.6';
            
            $sitemap->add(
                $this->config['base_url'] . '/products/' . $product['slug'],
                date('c', strtotime($product['updated_at'])),
                $priority,
                'weekly'
            );
            
            $urlCount++;
        }
        
        $filename = $this->outputDir . '/sitemap-products.xml';
        file_put_contents($filename, $sitemap->renderXml());
        
        return [
            'filename' => 'sitemap-products.xml',
            'path' => $filename,
            'urls' => $urlCount,
            'size' => filesize($filename)
        ];
    }
    
    private function generateBlogSitemap()
    {
        $pdo = $this->getDatabaseConnection();
        
        $stmt = $pdo->query("
            SELECT slug, published_at, updated_at
            FROM posts 
            WHERE published = 1 AND published_at <= NOW()
            ORDER BY published_at DESC
        ");
        
        $sitemap = new Sitemap();
        $urlCount = 0;
        
        while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lastmod = $post['updated_at'] ?: $post['published_at'];
            
            $sitemap->add(
                $this->config['base_url'] . '/blog/' . $post['slug'],
                date('c', strtotime($lastmod)),
                '0.7',
                'monthly'
            );
            
            $urlCount++;
        }
        
        $filename = $this->outputDir . '/sitemap-blog.xml';
        file_put_contents($filename, $sitemap->renderXml());
        
        return [
            'filename' => 'sitemap-blog.xml',
            'path' => $filename,
            'urls' => $urlCount,
            'size' => filesize($filename)
        ];
    }
    
    private function generateCategorySitemap()
    {
        $pdo = $this->getDatabaseConnection();
        
        $stmt = $pdo->query("
            SELECT slug, updated_at
            FROM categories 
            WHERE active = 1 
            ORDER BY name
        ");
        
        $sitemap = new Sitemap();
        $urlCount = 0;
        
        while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sitemap->add(
                $this->config['base_url'] . '/categories/' . $category['slug'],
                date('c', strtotime($category['updated_at'])),
                '0.9',
                'weekly'
            );
            
            $urlCount++;
        }
        
        $filename = $this->outputDir . '/sitemap-categories.xml';
        file_put_contents($filename, $sitemap->renderXml());
        
        return [
            'filename' => 'sitemap-categories.xml',
            'path' => $filename,
            'urls' => $urlCount,
            'size' => filesize($filename)
        ];
    }
    
    private function generateSitemapIndex($sitemapResults)
    {
        $sitemapIndex = new Sitemap();
        
        foreach ($sitemapResults as $type => $result) {
            if (isset($result['filename'])) {
                $sitemapIndex->addSitemap(
                    $this->config['base_url'] . '/sitemaps/' . $result['filename'],
                    date('c')
                );
            }
        }
        
        $items = $sitemapIndex->getModel()->getSitemaps();
        $xml = view('sitemap.sitemapindex', compact('items'))->render();
        
        $filename = $this->outputDir . '/sitemap.xml';
        file_put_contents($filename, $xml);
        
        return [
            'filename' => 'sitemap.xml',
            'path' => $filename,
            'sitemaps' => count($sitemapResults),
            'size' => filesize($filename)
        ];
    }
    
    private function getDatabaseConnection()
    {
        static $pdo = null;
        
        if ($pdo === null) {
            $dsn = "mysql:host={$this->config['db']['host']};dbname={$this->config['db']['name']}";
            $pdo = new PDO($dsn, $this->config['db']['user'], $this->config['db']['pass']);
        }
        
        return $pdo;
    }
    
    private function updateLastGeneration()
    {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->outputDir . '/.last-generation', $timestamp);
    }
    
    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);
    }
}

// Configuration
$config = [
    'base_url' => 'https://example.com',
    'output_dir' => '/var/www/html/sitemaps',
    'log_file' => '/var/log/sitemap-generation.log',
    'db' => [
        'host' => 'localhost',
        'name' => 'website',
        'user' => 'dbuser',
        'pass' => 'dbpass'
    ]
];

// CLI execution
if (php_sapi_name() === 'cli') {
    $generator = new CronSitemapGenerator($config);
    
    try {
        $results = $generator->generateAllSitemaps();
        echo "Sitemap generation completed successfully\n";
        echo json_encode($results, JSON_PRETTY_PRINT) . "\n";
        exit(0);
    } catch (Exception $e) {
        echo "Sitemap generation failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
```

```bash
# Crontab entry - runs daily at 2 AM
0 2 * * * /usr/bin/php /path/to/scripts/generate-sitemap.php >> /var/log/cron.log 2>&1

# Crontab entry - runs every 6 hours
0 */6 * * * /usr/bin/php /path/to/scripts/generate-sitemap.php

# Crontab entry - runs hourly for high-frequency sites
0 * * * * /usr/bin/php /path/to/scripts/generate-sitemap.php
```

## Queue-Based Generation

### Laravel Queue Implementation

```php
<?php
// app/Jobs/GenerateSitemapJob.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Rumenx\Sitemap\Sitemap;
use App\Models\Product;
use App\Models\Post;
use App\Models\Category;

class GenerateSitemapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $timeout = 3600; // 1 hour timeout
    public $tries = 3;
    
    private $sitemapType;
    private $options;
    
    public function __construct($sitemapType = 'all', $options = [])
    {
        $this->sitemapType = $sitemapType;
        $this->options = $options;
    }
    
    public function handle()
    {
        try {
            switch ($this->sitemapType) {
                case 'products':
                    $this->generateProductSitemap();
                    break;
                case 'blog':
                    $this->generateBlogSitemap();
                    break;
                case 'categories':
                    $this->generateCategorySitemap();
                    break;
                case 'all':
                default:
                    $this->generateAllSitemaps();
                    break;
            }
            
            \Log::info("Sitemap generation completed", [
                'type' => $this->sitemapType,
                'options' => $this->options
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Sitemap generation failed", [
                'type' => $this->sitemapType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    private function generateAllSitemaps()
    {
        $sitemaps = [];
        
        // Generate individual sitemaps
        $sitemaps[] = $this->generateProductSitemap();
        $sitemaps[] = $this->generateBlogSitemap();
        $sitemaps[] = $this->generateCategorySitemap();
        
        // Generate sitemap index
        $this->generateSitemapIndex($sitemaps);
        
        // Notify search engines
        $this->notifySearchEngines();
    }
    
    private function generateProductSitemap()
    {
        $sitemap = new Sitemap();
        
        Product::active()
            ->with(['category'])
            ->chunk(1000, function ($products) use ($sitemap) {
                foreach ($products as $product) {
                    $sitemap->add(
                        route('product.show', $product->slug),
                        $product->updated_at->toISOString(),
                        $product->stock_quantity > 0 ? '0.8' : '0.6',
                        'weekly'
                    );
                }
            });
        
        $filename = 'sitemap-products.xml';
        $path = public_path("sitemaps/{$filename}");
        
        file_put_contents($path, $sitemap->renderXml());
        
        return [
            'filename' => $filename,
            'path' => $path,
            'url' => url("sitemaps/{$filename}"),
            'lastmod' => now()->toISOString()
        ];
    }
    
    private function generateBlogSitemap()
    {
        $sitemap = new Sitemap();
        
        Post::published()
            ->with(['author', 'category'])
            ->chunk(1000, function ($posts) use ($sitemap) {
                foreach ($posts as $post) {
                    $lastmod = $post->updated_at ?? $post->published_at;
                    
                    $sitemap->add(
                        route('blog.show', $post->slug),
                        $lastmod->toISOString(),
                        '0.7',
                        'monthly'
                    );
                }
            });
        
        $filename = 'sitemap-blog.xml';
        $path = public_path("sitemaps/{$filename}");
        
        file_put_contents($path, $sitemap->renderXml());
        
        return [
            'filename' => $filename,
            'path' => $path,
            'url' => url("sitemaps/{$filename}"),
            'lastmod' => now()->toISOString()
        ];
    }
    
    private function generateCategorySitemap()
    {
        $sitemap = new Sitemap();
        
        Category::active()
            ->chunk(1000, function ($categories) use ($sitemap) {
                foreach ($categories as $category) {
                    $sitemap->add(
                        route('category.show', $category->slug),
                        $category->updated_at->toISOString(),
                        '0.9',
                        'weekly'
                    );
                }
            });
        
        $filename = 'sitemap-categories.xml';
        $path = public_path("sitemaps/{$filename}");
        
        file_put_contents($path, $sitemap->renderXml());
        
        return [
            'filename' => $filename,
            'path' => $path,
            'url' => url("sitemaps/{$filename}"),
            'lastmod' => now()->toISOString()
        ];
    }
    
    private function generateSitemapIndex($sitemaps)
    {
        $sitemapIndex = new Sitemap();
        
        foreach ($sitemaps as $sitemap) {
            $sitemapIndex->addSitemap($sitemap['url'], $sitemap['lastmod']);
        }
        
        $items = $sitemapIndex->getModel()->getSitemaps();
        $xml = view('sitemap.sitemapindex', compact('items'))->render();
        
        file_put_contents(public_path('sitemaps/sitemap.xml'), $xml);
    }
    
    private function notifySearchEngines()
    {
        $sitemapUrl = url('sitemaps/sitemap.xml');
        
        $searchEngines = [
            'google' => "https://www.google.com/ping?sitemap={$sitemapUrl}",
            'bing' => "https://www.bing.com/ping?sitemap={$sitemapUrl}"
        ];
        
        foreach ($searchEngines as $engine => $pingUrl) {
            try {
                $response = file_get_contents($pingUrl);
                \Log::info("Notified {$engine} about sitemap update", [
                    'url' => $pingUrl,
                    'response' => $response
                ]);
            } catch (\Exception $e) {
                \Log::warning("Failed to notify {$engine}", [
                    'url' => $pingUrl,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    public function failed(\Throwable $exception)
    {
        \Log::error("Sitemap generation job failed", [
            'type' => $this->sitemapType,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Optionally send notification to administrators
        // \Notification::route('mail', 'admin@example.com')
        //     ->notify(new SitemapGenerationFailed($exception));
    }
}

// app/Console/Commands/GenerateSitemapCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\GenerateSitemapJob;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate {type=all : Type of sitemap to generate}';
    protected $description = 'Generate sitemaps for the website';
    
    public function handle()
    {
        $type = $this->argument('type');
        
        $this->info("Dispatching sitemap generation job for: {$type}");
        
        GenerateSitemapJob::dispatch($type);
        
        $this->info("Sitemap generation job dispatched successfully");
    }
}

// app/Console/Kernel.php - Add to schedule method

protected function schedule(Schedule $schedule)
{
    // Generate full sitemap daily at 2 AM
    $schedule->command('sitemap:generate all')
             ->dailyAt('02:00')
             ->onOneServer();
    
    // Generate products sitemap every 4 hours
    $schedule->command('sitemap:generate products')
             ->cron('0 */4 * * *')
             ->onOneServer();
    
    // Generate blog sitemap every 6 hours
    $schedule->command('sitemap:generate blog')
             ->cron('0 */6 * * *')
             ->onOneServer();
}

// Usage
// Manual dispatch
GenerateSitemapJob::dispatch('products');

// Delayed dispatch
GenerateSitemapJob::dispatch('all')->delay(now()->addMinutes(10));

// Priority queue
GenerateSitemapJob::dispatch('all')->onQueue('high');
```

## Event-Driven Generation

### Model Event Listeners

```php
<?php
// app/Observers/ProductObserver.php

namespace App\Observers;

use App\Models\Product;
use App\Jobs\GenerateSitemapJob;

class ProductObserver
{
    public function created(Product $product)
    {
        if ($product->active) {
            $this->scheduleSitemapGeneration();
        }
    }
    
    public function updated(Product $product)
    {
        if ($product->wasChanged(['active', 'slug', 'updated_at'])) {
            $this->scheduleSitemapGeneration();
        }
    }
    
    public function deleted(Product $product)
    {
        $this->scheduleSitemapGeneration();
    }
    
    private function scheduleSitemapGeneration()
    {
        // Throttle sitemap generation to prevent too frequent updates
        $cacheKey = 'sitemap_generation_scheduled';
        
        if (!\Cache::has($cacheKey)) {
            // Schedule generation with 5-minute delay to batch multiple changes
            GenerateSitemapJob::dispatch('products')->delay(now()->addMinutes(5));
            
            // Prevent duplicate jobs for 10 minutes
            \Cache::put($cacheKey, true, now()->addMinutes(10));
        }
    }
}

// app/Providers/EventServiceProvider.php

protected $observers = [
    Product::class => [ProductObserver::class],
    Post::class => [PostObserver::class],
    Category::class => [CategoryObserver::class],
];

// app/Events/SitemapUpdateRequested.php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SitemapUpdateRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $sitemapType;
    public $reason;
    public $data;
    
    public function __construct($sitemapType, $reason, $data = [])
    {
        $this->sitemapType = $sitemapType;
        $this->reason = $reason;
        $this->data = $data;
    }
}

// app/Listeners/HandleSitemapUpdateRequest.php

namespace App\Listeners;

use App\Events\SitemapUpdateRequested;
use App\Jobs\GenerateSitemapJob;

class HandleSitemapUpdateRequest
{
    public function handle(SitemapUpdateRequested $event)
    {
        // Log the event
        \Log::info('Sitemap update requested', [
            'type' => $event->sitemapType,
            'reason' => $event->reason,
            'data' => $event->data
        ]);
        
        // Determine delay based on reason
        $delay = $this->getDelayForReason($event->reason);
        
        // Dispatch job with appropriate delay
        GenerateSitemapJob::dispatch($event->sitemapType)->delay($delay);
    }
    
    private function getDelayForReason($reason)
    {
        switch ($reason) {
            case 'urgent':
                return now()->addMinutes(1);
            case 'bulk_update':
                return now()->addMinutes(10);
            case 'scheduled':
                return now()->addMinutes(5);
            default:
                return now()->addMinutes(5);
        }
    }
}

// Usage in controllers or services
event(new SitemapUpdateRequested('products', 'bulk_update', [
    'updated_count' => 150
]));
```

## Webhook-Based Generation

### API Endpoint for External Triggers

```php
<?php
// routes/api.php

Route::middleware(['api', 'throttle:10,1'])->group(function () {
    Route::post('/sitemap/generate', [SitemapController::class, 'generate']);
    Route::get('/sitemap/status', [SitemapController::class, 'status']);
});

// app/Http/Controllers/SitemapController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\GenerateSitemapJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class SitemapController extends Controller
{
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|string|in:all,products,blog,categories',
            'priority' => 'sometimes|string|in:low,normal,high',
            'delay' => 'sometimes|integer|min:0|max:3600',
            'webhook_url' => 'sometimes|url'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 400);
        }
        
        $type = $request->input('type', 'all');
        $priority = $request->input('priority', 'normal');
        $delay = $request->input('delay', 0);
        $webhookUrl = $request->input('webhook_url');
        
        // Check rate limiting
        $rateLimitKey = 'sitemap_generation_rate_limit';
        if (Cache::has($rateLimitKey)) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'Sitemap generation was triggered recently'
            ], 429);
        }
        
        // Generate unique job ID
        $jobId = uniqid('sitemap_', true);
        
        // Dispatch job
        $job = GenerateSitemapJob::dispatch($type, [
            'job_id' => $jobId,
            'webhook_url' => $webhookUrl,
            'requested_by' => $request->ip(),
            'requested_at' => now()->toISOString()
        ]);
        
        if ($delay > 0) {
            $job->delay(now()->addSeconds($delay));
        }
        
        // Set priority queue
        $queueName = $priority === 'high' ? 'high' : 'default';
        $job->onQueue($queueName);
        
        // Set rate limit
        $rateLimitDuration = $priority === 'high' ? 60 : 300; // 1 or 5 minutes
        Cache::put($rateLimitKey, true, now()->addSeconds($rateLimitDuration));
        
        // Store job info
        Cache::put("sitemap_job_{$jobId}", [
            'type' => $type,
            'status' => 'queued',
            'created_at' => now()->toISOString(),
            'priority' => $priority,
            'delay' => $delay
        ], now()->addHours(24));
        
        return response()->json([
            'success' => true,
            'job_id' => $jobId,
            'type' => $type,
            'priority' => $priority,
            'delay' => $delay,
            'estimated_completion' => now()->addSeconds($delay + 120)->toISOString()
        ]);
    }
    
    public function status(Request $request)
    {
        $jobId = $request->input('job_id');
        
        if (!$jobId) {
            // Return general status
            return response()->json([
                'last_generation' => $this->getLastGenerationInfo(),
                'queue_status' => $this->getQueueStatus(),
                'recent_jobs' => $this->getRecentJobs()
            ]);
        }
        
        // Return specific job status
        $jobInfo = Cache::get("sitemap_job_{$jobId}");
        
        if (!$jobInfo) {
            return response()->json([
                'error' => 'Job not found',
                'job_id' => $jobId
            ], 404);
        }
        
        return response()->json([
            'job_id' => $jobId,
            'status' => $jobInfo['status'],
            'type' => $jobInfo['type'],
            'created_at' => $jobInfo['created_at'],
            'completed_at' => $jobInfo['completed_at'] ?? null,
            'error' => $jobInfo['error'] ?? null
        ]);
    }
    
    private function getLastGenerationInfo()
    {
        $lastGenFile = public_path('sitemaps/.last-generation');
        
        if (file_exists($lastGenFile)) {
            return [
                'timestamp' => file_get_contents($lastGenFile),
                'files' => $this->getSitemapFiles()
            ];
        }
        
        return null;
    }
    
    private function getSitemapFiles()
    {
        $sitemapDir = public_path('sitemaps');
        $files = [];
        
        if (is_dir($sitemapDir)) {
            foreach (glob($sitemapDir . '/*.xml') as $file) {
                $files[] = [
                    'name' => basename($file),
                    'size' => filesize($file),
                    'modified' => date('c', filemtime($file))
                ];
            }
        }
        
        return $files;
    }
    
    private function getQueueStatus()
    {
        try {
            // This would depend on your queue driver
            return [
                'pending' => \Queue::size('default'),
                'failed' => \Queue::size('failed')
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to get queue status'];
        }
    }
    
    private function getRecentJobs()
    {
        // Get recent jobs from cache
        $jobs = [];
        $pattern = 'sitemap_job_*';
        
        // This is a simplified implementation
        // In production, you might want to use a database or Redis
        
        return $jobs;
    }
}

// Webhook notification example
class SitemapWebhookNotifier
{
    public static function notify($webhookUrl, $data)
    {
        if (!$webhookUrl) {
            return;
        }
        
        try {
            $payload = json_encode([
                'event' => 'sitemap.generated',
                'data' => $data,
                'timestamp' => now()->toISOString()
            ]);
            
            $options = [
                'http' => [
                    'header' => [
                        'Content-Type: application/json',
                        'User-Agent: SitemapGenerator/1.0'
                    ],
                    'method' => 'POST',
                    'content' => $payload,
                    'timeout' => 30
                ]
            ];
            
            $context = stream_context_create($options);
            $response = file_get_contents($webhookUrl, false, $context);
            
            \Log::info('Webhook notification sent', [
                'url' => $webhookUrl,
                'response' => $response
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Webhook notification failed', [
                'url' => $webhookUrl,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

## Monitoring and Alerting

### Comprehensive Monitoring System

```php
<?php
// app/Services/SitemapMonitor.php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SitemapMonitor
{
    private $config;
    
    public function __construct()
    {
        $this->config = config('sitemap.monitoring', []);
    }
    
    public function checkSitemapHealth()
    {
        $checks = [
            'file_existence' => $this->checkFileExistence(),
            'file_sizes' => $this->checkFileSizes(),
            'generation_frequency' => $this->checkGenerationFrequency(),
            'xml_validity' => $this->checkXmlValidity(),
            'url_accessibility' => $this->checkUrlAccessibility(),
            'search_engine_status' => $this->checkSearchEngineStatus()
        ];
        
        $overallStatus = $this->determineOverallStatus($checks);
        
        $report = [
            'timestamp' => now()->toISOString(),
            'overall_status' => $overallStatus,
            'checks' => $checks,
            'recommendations' => $this->generateRecommendations($checks)
        ];
        
        // Store report
        Cache::put('sitemap_health_report', $report, now()->addHours(24));
        
        // Send alerts if needed
        if ($overallStatus !== 'healthy') {
            $this->sendAlert($report);
        }
        
        return $report;
    }
    
    private function checkFileExistence()
    {
        $requiredFiles = [
            'sitemap.xml',
            'sitemap-products.xml',
            'sitemap-blog.xml',
            'sitemap-categories.xml'
        ];
        
        $missingFiles = [];
        $existingFiles = [];
        
        foreach ($requiredFiles as $file) {
            $path = public_path("sitemaps/{$file}");
            
            if (file_exists($path)) {
                $existingFiles[] = [
                    'file' => $file,
                    'size' => filesize($path),
                    'modified' => date('c', filemtime($path))
                ];
            } else {
                $missingFiles[] = $file;
            }
        }
        
        return [
            'status' => empty($missingFiles) ? 'healthy' : 'warning',
            'existing_files' => $existingFiles,
            'missing_files' => $missingFiles,
            'message' => empty($missingFiles) 
                ? 'All required sitemap files exist'
                : 'Some sitemap files are missing: ' . implode(', ', $missingFiles)
        ];
    }
    
    private function checkFileSizes()
    {
        $sitemapDir = public_path('sitemaps');
        $issues = [];
        $fileInfo = [];
        
        foreach (glob($sitemapDir . '/*.xml') as $file) {
            $size = filesize($file);
            $filename = basename($file);
            
            $fileInfo[] = [
                'file' => $filename,
                'size' => $size,
                'size_formatted' => $this->formatBytes($size)
            ];
            
            // Check for suspiciously small files (less than 1KB)
            if ($size < 1024) {
                $issues[] = "{$filename} is suspiciously small ({$size} bytes)";
            }
            
            // Check for very large files (over 50MB)
            if ($size > 50 * 1024 * 1024) {
                $issues[] = "{$filename} is very large (" . $this->formatBytes($size) . ")";
            }
        }
        
        return [
            'status' => empty($issues) ? 'healthy' : 'warning',
            'file_info' => $fileInfo,
            'issues' => $issues,
            'message' => empty($issues) 
                ? 'All sitemap files have appropriate sizes'
                : 'Some files have size issues: ' . implode(', ', $issues)
        ];
    }
    
    private function checkGenerationFrequency()
    {
        $lastGenFile = public_path('sitemaps/.last-generation');
        
        if (!file_exists($lastGenFile)) {
            return [
                'status' => 'critical',
                'last_generation' => null,
                'hours_since' => null,
                'message' => 'No generation timestamp found'
            ];
        }
        
        $lastGeneration = file_get_contents($lastGenFile);
        $lastGenTime = strtotime($lastGeneration);
        $hoursSince = (time() - $lastGenTime) / 3600;
        
        $maxHours = $this->config['max_hours_between_generations'] ?? 48;
        
        $status = 'healthy';
        if ($hoursSince > $maxHours) {
            $status = 'critical';
        } elseif ($hoursSince > $maxHours * 0.8) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'last_generation' => $lastGeneration,
            'hours_since' => round($hoursSince, 1),
            'max_hours' => $maxHours,
            'message' => $status === 'healthy' 
                ? "Sitemap generated {$hoursSince} hours ago"
                : "Sitemap not generated for {$hoursSince} hours (max: {$maxHours})"
        ];
    }
    
    private function checkXmlValidity()
    {
        $sitemapFiles = glob(public_path('sitemaps/*.xml'));
        $validFiles = [];
        $invalidFiles = [];
        
        foreach ($sitemapFiles as $file) {
            $filename = basename($file);
            
            libxml_use_internal_errors(true);
            $xml = simplexml_load_file($file);
            
            if ($xml === false) {
                $errors = libxml_get_errors();
                $invalidFiles[] = [
                    'file' => $filename,
                    'errors' => array_map(function($error) {
                        return trim($error->message);
                    }, $errors)
                ];
            } else {
                $validFiles[] = $filename;
            }
            
            libxml_clear_errors();
        }
        
        return [
            'status' => empty($invalidFiles) ? 'healthy' : 'critical',
            'valid_files' => $validFiles,
            'invalid_files' => $invalidFiles,
            'message' => empty($invalidFiles)
                ? 'All XML files are valid'
                : 'Some XML files are invalid: ' . implode(', ', array_column($invalidFiles, 'file'))
        ];
    }
    
    private function checkUrlAccessibility()
    {
        $sitemapUrl = url('sitemaps/sitemap.xml');
        
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'SitemapMonitor/1.0'
                ]
            ]);
            
            $response = file_get_contents($sitemapUrl, false, $context);
            
            if ($response === false) {
                return [
                    'status' => 'critical',
                    'url' => $sitemapUrl,
                    'accessible' => false,
                    'message' => 'Sitemap URL is not accessible'
                ];
            }
            
            return [
                'status' => 'healthy',
                'url' => $sitemapUrl,
                'accessible' => true,
                'size' => strlen($response),
                'message' => 'Sitemap URL is accessible'
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'url' => $sitemapUrl,
                'accessible' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to check sitemap accessibility'
            ];
        }
    }
    
    private function checkSearchEngineStatus()
    {
        // This would check Google Search Console API, Bing Webmaster API, etc.
        // For now, we'll return a placeholder
        
        return [
            'status' => 'unknown',
            'google' => ['status' => 'unknown', 'last_submitted' => null],
            'bing' => ['status' => 'unknown', 'last_submitted' => null],
            'message' => 'Search engine status check not implemented'
        ];
    }
    
    private function determineOverallStatus($checks)
    {
        $statuses = array_column($checks, 'status');
        
        if (in_array('critical', $statuses)) {
            return 'critical';
        }
        
        if (in_array('warning', $statuses)) {
            return 'warning';
        }
        
        return 'healthy';
    }
    
    private function generateRecommendations($checks)
    {
        $recommendations = [];
        
        foreach ($checks as $checkName => $result) {
            if ($result['status'] === 'critical') {
                switch ($checkName) {
                    case 'file_existence':
                        $recommendations[] = 'Regenerate missing sitemap files immediately';
                        break;
                    case 'generation_frequency':
                        $recommendations[] = 'Check cron jobs and sitemap generation process';
                        break;
                    case 'xml_validity':
                        $recommendations[] = 'Fix XML validation errors in sitemap files';
                        break;
                    case 'url_accessibility':
                        $recommendations[] = 'Check web server configuration and file permissions';
                        break;
                }
            }
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'All checks passed - no action required';
        }
        
        return $recommendations;
    }
    
    private function sendAlert($report)
    {
        $alertConfig = $this->config['alerts'] ?? [];
        
        if (empty($alertConfig['enabled']) || empty($alertConfig['recipients'])) {
            return;
        }
        
        Log::warning('Sitemap health check alert', $report);
        
        // Send email alert
        if ($alertConfig['email'] ?? true) {
            try {
                Mail::to($alertConfig['recipients'])
                    ->send(new \App\Mail\SitemapHealthAlert($report));
            } catch (\Exception $e) {
                Log::error('Failed to send sitemap alert email: ' . $e->getMessage());
            }
        }
        
        // Send Slack notification
        if ($alertConfig['slack'] ?? false) {
            $this->sendSlackAlert($report);
        }
    }
    
    private function sendSlackAlert($report)
    {
        $webhookUrl = $this->config['slack_webhook_url'] ?? null;
        
        if (!$webhookUrl) {
            return;
        }
        
        $payload = [
            'text' => "Sitemap Health Alert: {$report['overall_status']}",
            'attachments' => [
                [
                    'color' => $report['overall_status'] === 'critical' ? 'danger' : 'warning',
                    'fields' => [
                        [
                            'title' => 'Status',
                            'value' => ucfirst($report['overall_status']),
                            'short' => true
                        ],
                        [
                            'title' => 'Timestamp',
                            'value' => $report['timestamp'],
                            'short' => true
                        ],
                        [
                            'title' => 'Recommendations',
                            'value' => implode("\n", $report['recommendations']),
                            'short' => false
                        ]
                    ]
                ]
            ]
        ];
        
        try {
            $context = stream_context_create([
                'http' => [
                    'header' => 'Content-Type: application/json',
                    'method' => 'POST',
                    'content' => json_encode($payload)
                ]
            ]);
            
            file_get_contents($webhookUrl, false, $context);
            
        } catch (\Exception $e) {
            Log::error('Failed to send Slack alert: ' . $e->getMessage());
        }
    }
    
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}

// Console command for monitoring
// php artisan sitemap:monitor

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SitemapMonitor;

class SitemapMonitorCommand extends Command
{
    protected $signature = 'sitemap:monitor';
    protected $description = 'Check sitemap health and send alerts if needed';
    
    public function handle(SitemapMonitor $monitor)
    {
        $this->info('Running sitemap health check...');
        
        $report = $monitor->checkSitemapHealth();
        
        $this->info("Overall status: {$report['overall_status']}");
        
        foreach ($report['checks'] as $checkName => $result) {
            $status = $result['status'];
            $message = $result['message'];
            
            $color = $status === 'healthy' ? 'green' : ($status === 'warning' ? 'yellow' : 'red');
            $this->line("<fg={$color}>[{$status}]</fg> {$checkName}: {$message}");
        }
        
        if (!empty($report['recommendations'])) {
            $this->info("\nRecommendations:");
            foreach ($report['recommendations'] as $recommendation) {
                $this->line("- {$recommendation}");
            }
        }
        
        return $report['overall_status'] === 'healthy' ? 0 : 1;
    }
}
```

## Configuration Management

### Environment-Specific Configuration

```php
<?php
// config/sitemap.php

return [
    'output_directory' => env('SITEMAP_OUTPUT_DIR', public_path('sitemaps')),
    'base_url' => env('APP_URL', 'https://example.com'),
    'max_urls_per_sitemap' => env('SITEMAP_MAX_URLS', 50000),
    
    'generation' => [
        'enabled' => env('SITEMAP_GENERATION_ENABLED', true),
        'queue' => env('SITEMAP_QUEUE', 'default'),
        'timeout' => env('SITEMAP_TIMEOUT', 3600),
        'memory_limit' => env('SITEMAP_MEMORY_LIMIT', '256M'),
    ],
    
    'schedules' => [
        'full_generation' => env('SITEMAP_FULL_SCHEDULE', '0 2 * * *'), // Daily at 2 AM
        'products' => env('SITEMAP_PRODUCTS_SCHEDULE', '0 */4 * * *'),   // Every 4 hours
        'blog' => env('SITEMAP_BLOG_SCHEDULE', '0 */6 * * *'),           // Every 6 hours
        'categories' => env('SITEMAP_CATEGORIES_SCHEDULE', '0 */12 * * *'), // Every 12 hours
    ],
    
    'monitoring' => [
        'enabled' => env('SITEMAP_MONITORING_ENABLED', true),
        'max_hours_between_generations' => env('SITEMAP_MAX_HOURS', 48),
        'alerts' => [
            'enabled' => env('SITEMAP_ALERTS_ENABLED', true),
            'email' => env('SITEMAP_EMAIL_ALERTS', true),
            'slack' => env('SITEMAP_SLACK_ALERTS', false),
            'recipients' => explode(',', env('SITEMAP_ALERT_RECIPIENTS', 'admin@example.com')),
        ],
        'slack_webhook_url' => env('SITEMAP_SLACK_WEBHOOK'),
    ],
    
    'search_engines' => [
        'notify_on_generation' => env('SITEMAP_NOTIFY_SEARCH_ENGINES', true),
        'google' => [
            'enabled' => env('SITEMAP_NOTIFY_GOOGLE', true),
        ],
        'bing' => [
            'enabled' => env('SITEMAP_NOTIFY_BING', true),
        ],
    ],
    
    'caching' => [
        'enabled' => env('SITEMAP_CACHING_ENABLED', true),
        'ttl' => env('SITEMAP_CACHE_TTL', 3600), // 1 hour
        'key_prefix' => env('SITEMAP_CACHE_PREFIX', 'sitemap'),
    ],
    
    'rate_limiting' => [
        'enabled' => env('SITEMAP_RATE_LIMITING_ENABLED', true),
        'max_generations_per_hour' => env('SITEMAP_MAX_GENERATIONS_PER_HOUR', 6),
        'cooldown_minutes' => env('SITEMAP_COOLDOWN_MINUTES', 10),
    ],
];

// .env.example

# Sitemap Configuration
SITEMAP_OUTPUT_DIR=/var/www/html/sitemaps
SITEMAP_MAX_URLS=50000
SITEMAP_GENERATION_ENABLED=true
SITEMAP_QUEUE=high
SITEMAP_TIMEOUT=3600
SITEMAP_MEMORY_LIMIT=512M

# Sitemap Schedules (cron format)
SITEMAP_FULL_SCHEDULE="0 2 * * *"
SITEMAP_PRODUCTS_SCHEDULE="0 */4 * * *"
SITEMAP_BLOG_SCHEDULE="0 */6 * * *"
SITEMAP_CATEGORIES_SCHEDULE="0 */12 * * *"

# Monitoring and Alerts
SITEMAP_MONITORING_ENABLED=true
SITEMAP_MAX_HOURS=48
SITEMAP_ALERTS_ENABLED=true
SITEMAP_EMAIL_ALERTS=true
SITEMAP_SLACK_ALERTS=false
SITEMAP_ALERT_RECIPIENTS="admin@example.com,seo@example.com"
SITEMAP_SLACK_WEBHOOK=https://hooks.slack.com/services/xxx

# Search Engine Notifications
SITEMAP_NOTIFY_SEARCH_ENGINES=true
SITEMAP_NOTIFY_GOOGLE=true
SITEMAP_NOTIFY_BING=true

# Caching
SITEMAP_CACHING_ENABLED=true
SITEMAP_CACHE_TTL=3600
SITEMAP_CACHE_PREFIX=sitemap

# Rate Limiting
SITEMAP_RATE_LIMITING_ENABLED=true
SITEMAP_MAX_GENERATIONS_PER_HOUR=6
SITEMAP_COOLDOWN_MINUTES=10
```

## Next Steps

- Explore [Memory Optimization](memory-optimization.md) for large-scale generation
- Learn about [Caching Strategies](caching-strategies.md) for performance
- Check [Large Scale Sitemaps](large-scale-sitemaps.md) for enterprise solutions
- See [Performance Monitoring](performance-monitoring.md) for production insights
