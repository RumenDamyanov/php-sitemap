# Large Scale Sitemaps

Handle millions of URLs efficiently with optimized memory usage, chunking strategies, and automated generation. This guide shows how to scale sitemap generation for massive websites.

## Challenges with Large Sitemaps

- **Memory Limits**: PHP memory exhaustion with millions of URLs
- **URL Limits**: 50,000 URLs max per sitemap file
- **File Size**: 50MB max per sitemap (uncompressed)
- **Generation Time**: Long execution times
- **Server Resources**: CPU and I/O intensive operations

## Chunked Sitemap Generation

### Memory-Efficient Chunking Strategy

```php
<?php
use Rumenx\Sitemap\Sitemap;

class LargeScaleSitemapGenerator
{
    private $baseUrl;
    private $outputDir;
    private $pdo;
    private $chunkSize = 50000; // Max URLs per sitemap
    
    public function __construct($baseUrl, $outputDir, $dbConfig)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->outputDir = rtrim($outputDir, '/') . '/';
        $this->pdo = new PDO(
            "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}",
            $dbConfig['user'],
            $dbConfig['pass'],
            [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false] // Unbuffered for memory efficiency
        );
        
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    public function generateLargeProductSitemaps()
    {
        echo "Starting large-scale product sitemap generation...\n";
        
        // Get total count
        $countStmt = $this->pdo->query("SELECT COUNT(*) as total FROM products WHERE active = 1");
        $totalProducts = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo "Total products: {$totalProducts}\n";
        
        $sitemapCounter = 0;
        $urlCounter = 0;
        $sitemapIndex = new Sitemap();
        $currentSitemap = new Sitemap();
        
        // Process products in chunks to avoid memory issues
        $limit = 1000; // Process 1000 at a time
        $offset = 0;
        
        while ($offset < $totalProducts) {
            echo "Processing products {$offset} to " . ($offset + $limit) . "\n";
            
            $stmt = $this->pdo->prepare("
                SELECT slug, updated_at 
                FROM products 
                WHERE active = 1 
                ORDER BY id 
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($urlCounter >= $this->chunkSize) {
                    // Save current sitemap and start new one
                    $filename = "sitemap-products-{$sitemapCounter}.xml";
                    $this->saveSitemap($currentSitemap, $filename);
                    
                    // Add to index
                    $sitemapIndex->addSitemap(
                        "{$this->baseUrl}/{$filename}",
                        date('c')
                    );
                    
                    echo "Generated {$filename} with {$urlCounter} URLs\n";
                    
                    // Reset for next sitemap
                    $currentSitemap = new Sitemap();
                    $urlCounter = 0;
                    $sitemapCounter++;
                }
                
                $currentSitemap->add(
                    "{$this->baseUrl}/products/{$product['slug']}",
                    date('c', strtotime($product['updated_at'])),
                    '0.8',
                    'weekly'
                );
                
                $urlCounter++;
            }
            
            $offset += $limit;
            
            // Free memory
            $stmt = null;
            
            // Optional: garbage collection
            if ($offset % 10000 === 0) {
                gc_collect_cycles();
            }
        }
        
        // Handle remaining URLs
        if ($urlCounter > 0) {
            $filename = "sitemap-products-{$sitemapCounter}.xml";
            $this->saveSitemap($currentSitemap, $filename);
            
            $sitemapIndex->addSitemap(
                "{$this->baseUrl}/{$filename}",
                date('c')
            );
            
            echo "Generated {$filename} with {$urlCounter} URLs\n";
        }
        
        // Generate sitemap index
        $this->generateSitemapIndex($sitemapIndex, 'sitemap-products-index.xml');
        
        echo "Generated sitemap index for {$totalProducts} products in " . ($sitemapCounter + 1) . " files\n";
    }
    
    private function saveSitemap($sitemap, $filename)
    {
        $xml = $sitemap->renderXml();
        file_put_contents($this->outputDir . $filename, $xml);
        
        // Clear memory
        $sitemap = null;
        $xml = null;
    }
    
    private function generateSitemapIndex($sitemapIndex, $filename)
    {
        $items = $sitemapIndex->getModel()->getSitemaps();
        $xml = view('sitemap.sitemapindex', compact('items'))->render();
        file_put_contents($this->outputDir . $filename, $xml);
    }
}

// Usage
$config = [
    'base_url' => 'https://example.com',
    'output_dir' => '/var/www/html/public/sitemaps/',
    'database' => [
        'host' => 'localhost',
        'name' => 'yourdb',
        'user' => 'dbuser',
        'pass' => 'dbpass'
    ]
];

$generator = new LargeScaleSitemapGenerator(
    $config['base_url'],
    $config['output_dir'],
    $config['database']
);

$generator->generateLargeProductSitemaps();
```

## Multi-Table Large Scale Generation

### Handling Multiple Content Types

```php
<?php
use Rumenx\Sitemap\Sitemap;

class MultiTableSitemapGenerator
{
    private $baseUrl;
    private $outputDir;
    private $pdo;
    private $chunkSize = 45000; // Leave room for other URLs
    
    public function __construct($baseUrl, $outputDir, $dbConfig)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->outputDir = rtrim($outputDir, '/') . '/';
        $this->pdo = new PDO(
            "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}",
            $dbConfig['user'],
            $dbConfig['pass'],
            [
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
        
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    public function generateAllSitemaps()
    {
        $masterIndex = new Sitemap();
        
        // Generate sitemaps for each content type
        $contentTypes = [
            'posts' => $this->generateContentSitemaps('posts', 'blog'),
            'products' => $this->generateContentSitemaps('products', 'products'),
            'categories' => $this->generateContentSitemaps('categories', 'categories'),
            'pages' => $this->generateContentSitemaps('pages', 'pages')
        ];
        
        // Add all content type indexes to master index
        foreach ($contentTypes as $type => $indexFile) {
            if ($indexFile) {
                $masterIndex->addSitemap(
                    "{$this->baseUrl}/{$indexFile}",
                    date('c')
                );
            }
        }
        
        // Generate master sitemap index
        $this->generateSitemapIndex($masterIndex, 'sitemap.xml');
        
        echo "Master sitemap index generated: sitemap.xml\n";
    }
    
    private function generateContentSitemaps($table, $urlPrefix)
    {
        echo "Generating sitemaps for {$table}...\n";
        
        // Get total count
        $whereClause = $this->getWhereClause($table);
        $countStmt = $this->pdo->query("SELECT COUNT(*) as total FROM {$table} WHERE {$whereClause}");
        $totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($totalItems === 0) {
            echo "No items found for {$table}\n";
            return null;
        }
        
        echo "Total {$table}: {$totalItems}\n";
        
        $sitemapCounter = 0;
        $urlCounter = 0;
        $contentIndex = new Sitemap();
        $currentSitemap = new Sitemap();
        
        $limit = 1000;
        $offset = 0;
        
        while ($offset < $totalItems) {
            $stmt = $this->pdo->prepare("
                SELECT slug, updated_at, priority
                FROM {$table} 
                WHERE {$whereClause}
                ORDER BY id 
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($urlCounter >= $this->chunkSize) {
                    // Save current sitemap
                    $filename = "sitemap-{$table}-{$sitemapCounter}.xml";
                    $this->saveSitemap($currentSitemap, $filename);
                    
                    // Add to content index
                    $contentIndex->addSitemap(
                        "{$this->baseUrl}/{$filename}",
                        date('c')
                    );
                    
                    echo "Generated {$filename} with {$urlCounter} URLs\n";
                    
                    // Reset
                    $currentSitemap = new Sitemap();
                    $urlCounter = 0;
                    $sitemapCounter++;
                }
                
                $priority = $this->getPriorityForTable($table, $item);
                $frequency = $this->getFrequencyForTable($table);
                
                $currentSitemap->add(
                    "{$this->baseUrl}/{$urlPrefix}/{$item['slug']}",
                    date('c', strtotime($item['updated_at'])),
                    $priority,
                    $frequency
                );
                
                $urlCounter++;
            }
            
            $offset += $limit;
            $stmt = null;
            
            // Memory management
            if ($offset % 50000 === 0) {
                gc_collect_cycles();
                echo "Memory usage: " . memory_get_usage(true) / 1024 / 1024 . " MB\n";
            }
        }
        
        // Handle remaining URLs
        if ($urlCounter > 0) {
            $filename = "sitemap-{$table}-{$sitemapCounter}.xml";
            $this->saveSitemap($currentSitemap, $filename);
            
            $contentIndex->addSitemap(
                "{$this->baseUrl}/{$filename}",
                date('c')
            );
            
            echo "Generated {$filename} with {$urlCounter} URLs\n";
        }
        
        // Generate content type index if multiple files
        if ($sitemapCounter > 0) {
            $indexFilename = "sitemap-{$table}-index.xml";
            $this->generateSitemapIndex($contentIndex, $indexFilename);
            echo "Generated index for {$table}: {$indexFilename}\n";
            return $indexFilename;
        } else {
            // Only one file, use it directly
            return "sitemap-{$table}-0.xml";
        }
    }
    
    private function getWhereClause($table)
    {
        switch ($table) {
            case 'posts':
                return 'published = 1';
            case 'products':
                return 'active = 1';
            case 'categories':
                return 'active = 1';
            case 'pages':
                return 'published = 1';
            default:
                return '1=1';
        }
    }
    
    private function getPriorityForTable($table, $item)
    {
        if (isset($item['priority'])) {
            return $item['priority'];
        }
        
        switch ($table) {
            case 'posts': return '0.7';
            case 'products': return '0.8';
            case 'categories': return '0.6';
            case 'pages': return '0.8';
            default: return '0.5';
        }
    }
    
    private function getFrequencyForTable($table)
    {
        switch ($table) {
            case 'posts': return 'monthly';
            case 'products': return 'weekly';
            case 'categories': return 'monthly';
            case 'pages': return 'monthly';
            default: return 'monthly';
        }
    }
    
    private function saveSitemap($sitemap, $filename)
    {
        $xml = $sitemap->renderXml();
        file_put_contents($this->outputDir . $filename, $xml);
        
        // Clear memory
        unset($sitemap, $xml);
    }
    
    private function generateSitemapIndex($sitemapIndex, $filename)
    {
        $items = $sitemapIndex->getModel()->getSitemaps();
        $xml = view('sitemap.sitemapindex', compact('items'))->render();
        file_put_contents($this->outputDir . $filename, $xml);
    }
}
```

## Streaming Generation for Massive Datasets

### Generator-Based Approach

```php
<?php
use Rumenx\Sitemap\Sitemap;

class StreamingSitemapGenerator
{
    private $baseUrl;
    private $outputDir;
    private $pdo;
    
    public function __construct($baseUrl, $outputDir, $dbConfig)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->outputDir = rtrim($outputDir, '/') . '/';
        $this->pdo = new PDO(
            "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}",
            $dbConfig['user'],
            $dbConfig['pass'],
            [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false]
        );
        
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    public function generateStreamingSitemap($table, $urlPrefix)
    {
        $sitemapIndex = new Sitemap();
        $sitemapCounter = 0;
        
        foreach ($this->getContentStream($table) as $chunk) {
            if (empty($chunk)) continue;
            
            $sitemap = new Sitemap();
            
            foreach ($chunk as $item) {
                $sitemap->add(
                    "{$this->baseUrl}/{$urlPrefix}/{$item['slug']}",
                    date('c', strtotime($item['updated_at'])),
                    $item['priority'] ?? '0.7',
                    'monthly'
                );
            }
            
            $filename = "sitemap-{$table}-{$sitemapCounter}.xml";
            $xml = $sitemap->renderXml();
            file_put_contents($this->outputDir . $filename, $xml);
            
            $sitemapIndex->addSitemap(
                "{$this->baseUrl}/{$filename}",
                date('c')
            );
            
            echo "Generated {$filename} with " . count($chunk) . " URLs\n";
            
            // Clear memory
            unset($sitemap, $xml, $chunk);
            gc_collect_cycles();
            
            $sitemapCounter++;
        }
        
        // Generate index
        if ($sitemapCounter > 0) {
            $indexFilename = "sitemap-{$table}-index.xml";
            $this->generateSitemapIndex($sitemapIndex, $indexFilename);
            echo "Generated {$indexFilename}\n";
        }
    }
    
    private function getContentStream($table, $chunkSize = 50000)
    {
        $offset = 0;
        $batchSize = 1000;
        
        while (true) {
            $stmt = $this->pdo->prepare("
                SELECT slug, updated_at, priority
                FROM {$table} 
                WHERE " . $this->getWhereClause($table) . "
                ORDER BY id 
                LIMIT :batch_size OFFSET :offset
            ");
            
            $stmt->bindValue(':batch_size', $batchSize, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $batch = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($batch)) {
                break; // No more data
            }
            
            // Yield chunks of specified size
            static $currentChunk = [];
            $currentChunk = array_merge($currentChunk, $batch);
            
            while (count($currentChunk) >= $chunkSize) {
                yield array_splice($currentChunk, 0, $chunkSize);
            }
            
            $offset += $batchSize;
        }
        
        // Yield remaining items
        if (!empty($currentChunk)) {
            yield $currentChunk;
        }
    }
    
    private function getWhereClause($table)
    {
        switch ($table) {
            case 'posts': return 'published = 1';
            case 'products': return 'active = 1';
            default: return '1=1';
        }
    }
    
    private function generateSitemapIndex($sitemapIndex, $filename)
    {
        $items = $sitemapIndex->getModel()->getSitemaps();
        $xml = view('sitemap.sitemapindex', compact('items'))->render();
        file_put_contents($this->outputDir . $filename, $xml);
    }
}

// Usage
$generator = new StreamingSitemapGenerator($baseUrl, $outputDir, $dbConfig);
$generator->generateStreamingSitemap('products', 'products');
```

## Parallel Processing

### Multi-Process Generation

```php
<?php
use Rumenx\Sitemap\Sitemap;

class ParallelSitemapGenerator
{
    private $baseUrl;
    private $outputDir;
    private $dbConfig;
    private $maxProcesses = 4;
    
    public function __construct($baseUrl, $outputDir, $dbConfig)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->outputDir = rtrim($outputDir, '/') . '/';
        $this->dbConfig = $dbConfig;
        
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    public function generateParallelSitemaps($table, $urlPrefix)
    {
        // Get total count and calculate ranges
        $pdo = new PDO(
            "mysql:host={$this->dbConfig['host']};dbname={$this->dbConfig['name']}",
            $this->dbConfig['user'],
            $this->dbConfig['pass']
        );
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM {$table} WHERE active = 1");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $chunkSize = ceil($total / $this->maxProcesses);
        $processes = [];
        
        echo "Generating {$table} sitemaps in {$this->maxProcesses} parallel processes...\n";
        echo "Total items: {$total}, chunk size: {$chunkSize}\n";
        
        // Start processes
        for ($i = 0; $i < $this->maxProcesses; $i++) {
            $offset = $i * $chunkSize;
            $limit = min($chunkSize, $total - $offset);
            
            if ($limit <= 0) break;
            
            $cmd = sprintf(
                'php %s --table=%s --url-prefix=%s --offset=%d --limit=%d --process=%d',
                __DIR__ . '/generate-sitemap-chunk.php',
                escapeshellarg($table),
                escapeshellarg($urlPrefix),
                $offset,
                $limit,
                $i
            );
            
            $process = proc_open($cmd, [], $pipes);
            $processes[] = $process;
            
            echo "Started process {$i}: offset {$offset}, limit {$limit}\n";
        }
        
        // Wait for all processes to complete
        foreach ($processes as $i => $process) {
            $status = proc_close($process);
            echo "Process {$i} completed with status {$status}\n";
        }
        
        // Combine results into index
        $this->createCombinedIndex($table);
    }
    
    private function createCombinedIndex($table)
    {
        $sitemapIndex = new Sitemap();
        
        // Find all generated chunk files
        $pattern = $this->outputDir . "sitemap-{$table}-chunk-*.xml";
        $files = glob($pattern);
        
        foreach ($files as $file) {
            $filename = basename($file);
            $sitemapIndex->addSitemap(
                "{$this->baseUrl}/{$filename}",
                date('c', filemtime($file))
            );
        }
        
        // Generate index
        $indexFilename = "sitemap-{$table}-index.xml";
        $this->generateSitemapIndex($sitemapIndex, $indexFilename);
        
        echo "Generated combined index: {$indexFilename}\n";
    }
    
    private function generateSitemapIndex($sitemapIndex, $filename)
    {
        $items = $sitemapIndex->getModel()->getSitemaps();
        $xml = view('sitemap.sitemapindex', compact('items'))->render();
        file_put_contents($this->outputDir . $filename, $xml);
    }
}
```

### Chunk Generation Script (generate-sitemap-chunk.php)

```php
#!/usr/bin/env php
<?php
/**
 * Generate sitemap chunk for parallel processing
 */

require 'vendor/autoload.php';

use Rumenx\Sitemap\Sitemap;

// Parse command line arguments
$options = getopt('', [
    'table:',
    'url-prefix:',
    'offset:',
    'limit:',
    'process:'
]);

$table = $options['table'];
$urlPrefix = $options['url-prefix'];
$offset = (int)$options['offset'];
$limit = (int)$options['limit'];
$processId = (int)$options['process'];

// Database configuration (you might want to load this from config)
$dbConfig = [
    'host' => 'localhost',
    'name' => 'yourdb',
    'user' => 'dbuser',
    'pass' => 'dbpass'
];

$baseUrl = 'https://example.com';
$outputDir = '/path/to/output/';

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}",
        $dbConfig['user'],
        $dbConfig['pass']
    );
    
    $sitemap = new Sitemap();
    
    $stmt = $pdo->prepare("
        SELECT slug, updated_at 
        FROM {$table} 
        WHERE active = 1 
        ORDER BY id 
        LIMIT :limit OFFSET :offset
    ");
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $count = 0;
    while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sitemap->add(
            "{$baseUrl}/{$urlPrefix}/{$item['slug']}",
            date('c', strtotime($item['updated_at'])),
            '0.8',
            'weekly'
        );
        $count++;
    }
    
    // Save chunk file
    $filename = "sitemap-{$table}-chunk-{$processId}.xml";
    $xml = $sitemap->renderXml();
    file_put_contents($outputDir . $filename, $xml);
    
    echo "Process {$processId}: Generated {$filename} with {$count} URLs\n";
    
} catch (Exception $e) {
    echo "Process {$processId} error: " . $e->getMessage() . "\n";
    exit(1);
}
```

## Memory Monitoring and Optimization

### Memory-Aware Generation

```php
<?php
use Rumenx\Sitemap\Sitemap;

class MemoryOptimizedGenerator
{
    private $maxMemoryMB = 128; // Maximum memory usage in MB
    private $checkInterval = 1000; // Check memory every N URLs
    private $itemCount = 0;
    
    public function generateWithMemoryLimit($table)
    {
        $sitemap = new Sitemap();
        $sitemapIndex = new Sitemap();
        $sitemapCounter = 0;
        
        $pdo = new PDO(...); // Your DB connection
        
        $stmt = $pdo->prepare("SELECT slug, updated_at FROM {$table} WHERE active = 1");
        $stmt->execute();
        
        while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sitemap->add(
                "https://example.com/{$table}/{$item['slug']}",
                date('c', strtotime($item['updated_at'])),
                '0.7',
                'monthly'
            );
            
            $this->itemCount++;
            
            // Check memory usage periodically
            if ($this->itemCount % $this->checkInterval === 0) {
                $memoryMB = memory_get_usage(true) / 1024 / 1024;
                
                echo "Memory usage: {$memoryMB} MB (items: {$this->itemCount})\n";
                
                if ($memoryMB > $this->maxMemoryMB) {
                    // Save current sitemap and start fresh
                    $filename = "sitemap-{$table}-{$sitemapCounter}.xml";
                    $xml = $sitemap->renderXml();
                    file_put_contents($filename, $xml);
                    
                    $sitemapIndex->addSitemap("https://example.com/{$filename}", date('c'));
                    
                    echo "Saved {$filename} due to memory limit\n";
                    
                    // Clean up
                    unset($sitemap, $xml);
                    gc_collect_cycles();
                    
                    // Start new sitemap
                    $sitemap = new Sitemap();
                    $sitemapCounter++;
                    
                    echo "Memory after cleanup: " . (memory_get_usage(true) / 1024 / 1024) . " MB\n";
                }
            }
        }
        
        // Save final sitemap
        if ($this->itemCount > 0) {
            $filename = "sitemap-{$table}-{$sitemapCounter}.xml";
            $xml = $sitemap->renderXml();
            file_put_contents($filename, $xml);
            
            $sitemapIndex->addSitemap("https://example.com/{$filename}", date('c'));
            echo "Saved final {$filename}\n";
        }
        
        // Generate index
        $this->generateSitemapIndex($sitemapIndex, "sitemap-{$table}-index.xml");
    }
    
    private function generateSitemapIndex($sitemapIndex, $filename)
    {
        $items = $sitemapIndex->getModel()->getSitemaps();
        $xml = view('sitemap.sitemapindex', compact('items'))->render();
        file_put_contents($filename, $xml);
    }
}
```

## Performance Tips

### Optimization Strategies

1. **Database Optimization**
   - Use proper indexes on frequently queried columns
   - Consider read replicas for large datasets
   - Use `LIMIT` and `OFFSET` for pagination
   - Avoid `SELECT *` - only fetch needed columns

2. **Memory Management**
   - Use unbuffered queries: `PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false`
   - Call `gc_collect_cycles()` periodically
   - Unset large variables when done
   - Monitor memory usage with `memory_get_usage()`

3. **File I/O Optimization**
   - Write files in chunks
   - Use efficient file paths
   - Consider using streams for very large files
   - Implement proper error handling

4. **Scaling Strategies**
   - Use queue systems for background processing
   - Implement intelligent caching
   - Consider cloud storage for sitemap files
   - Use CDN for sitemap delivery

## Next Steps

- Explore [Memory Optimization](memory-optimization.md) for detailed memory management
- Check [Automated Generation](automated-generation.md) for scheduling strategies
- See [Caching Strategies](caching-strategies.md) for performance optimization
- Learn about [Framework Integration](framework-integration.md) for Laravel/Symfony patterns
