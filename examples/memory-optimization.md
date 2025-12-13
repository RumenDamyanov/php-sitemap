# Memory Optimization

Learn how to optimize memory usage when generating large sitemaps using the `rumenx/php-sitemap` package. This guide covers batch processing, streaming, chunking, and efficient database queries for handling millions of URLs.

## Memory-Efficient Database Queries

### Streaming Database Results

```php
<?php
use Rumenx\Sitemap\Sitemap;

class MemoryEfficientSitemapGenerator
{
    private $pdo;
    private $batchSize = 1000;
    
    public function __construct($dbConfig, $batchSize = 1000)
    {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
        
        // Optimize PDO for memory efficiency
        $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $this->pdo->setAttribute(PDO::ATTR_CURSOR, PDO::CURSOR_SCROLL);
        
        $this->batchSize = $batchSize;
    }
    
    public function generateProductSitemapStream($outputFile = 'php://output')
    {
        $handle = fopen($outputFile, 'w');
        
        // Write XML header
        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($handle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n");
        
        $stmt = $this->pdo->prepare("
            SELECT SQL_CALC_FOUND_ROWS
                slug, 
                name, 
                updated_at,
                CASE 
                    WHEN stock_quantity > 10 THEN '0.8'
                    WHEN stock_quantity > 0 THEN '0.7'
                    ELSE '0.5'
                END as priority
            FROM products 
            WHERE active = 1 
            ORDER BY id
            LIMIT :offset, :batch_size
        ");
        
        $offset = 0;
        $totalUrls = 0;
        
        do {
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':batch_size', $this->batchSize, PDO::PARAM_INT);
            $stmt->execute();
            
            $batchCount = 0;
            
            while ($product = $stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) {
                $url = $this->generateUrlEntry(
                    "https://example.com/products/{$product['slug']}",
                    date('c', strtotime($product['updated_at'])),
                    $product['priority'],
                    'weekly'
                );
                
                fwrite($handle, $url);
                $batchCount++;
                $totalUrls++;
                
                // Memory cleanup every 100 entries
                if ($totalUrls % 100 === 0) {
                    gc_collect_cycles();
                }
            }
            
            $stmt->closeCursor();
            $offset += $this->batchSize;
            
            // Show progress
            if ($totalUrls % 10000 === 0) {
                error_log("Generated {$totalUrls} URLs, memory: " . memory_get_usage(true) / 1024 / 1024 . "MB");
            }
            
        } while ($batchCount === $this->batchSize);
        
        // Write XML footer
        fwrite($handle, '</urlset>');
        fclose($handle);
        
        return $totalUrls;
    }
    
    private function generateUrlEntry($loc, $lastmod, $priority, $changefreq)
    {
        return "  <url>\n" .
               "    <loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n" .
               "    <lastmod>{$lastmod}</lastmod>\n" .
               "    <priority>{$priority}</priority>\n" .
               "    <changefreq>{$changefreq}</changefreq>\n" .
               "  </url>\n";
    }
    
    public function getMemoryUsage()
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'current_formatted' => $this->formatBytes(memory_get_usage(true)),
            'peak_formatted' => $this->formatBytes(memory_get_peak_usage(true))
        ];
    }
    
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}

// Usage
$config = [
    'host' => 'localhost',
    'name' => 'ecommerce',
    'user' => 'dbuser',
    'pass' => 'dbpass'
];

$generator = new MemoryEfficientSitemapGenerator($config, 1000);

// Stream directly to output
header('Content-Type: application/xml; charset=utf-8');
header('Content-Disposition: attachment; filename="sitemap.xml"');

$totalUrls = $generator->generateProductSitemapStream();
error_log("Generated sitemap with {$totalUrls} URLs");

$memoryUsage = $generator->getMemoryUsage();
error_log("Peak memory usage: {$memoryUsage['peak_formatted']}");
```

### Chunked Sitemap Generation

```php
<?php
use Rumenx\Sitemap\Sitemap;

class ChunkedSitemapGenerator
{
    private $pdo;
    private $maxUrlsPerSitemap = 50000;
    private $outputDir;
    
    public function __construct($dbConfig, $outputDir = 'sitemaps')
    {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
        $this->outputDir = rtrim($outputDir, '/');
        
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    public function generateChunkedSitemaps($table, $baseUrl, $urlPattern)
    {
        // Get total count
        $countStmt = $this->pdo->query("SELECT COUNT(*) as total FROM {$table} WHERE active = 1");
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $chunks = ceil($totalCount / $this->maxUrlsPerSitemap);
        $sitemapFiles = [];
        
        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $offset = $chunk * $this->maxUrlsPerSitemap;
            $filename = "sitemap-{$table}-" . ($chunk + 1) . ".xml";
            $filepath = $this->outputDir . '/' . $filename;
            
            $urlsGenerated = $this->generateChunk(
                $table,
                $baseUrl,
                $urlPattern,
                $offset,
                $this->maxUrlsPerSitemap,
                $filepath
            );
            
            if ($urlsGenerated > 0) {
                $sitemapFiles[] = [
                    'filename' => $filename,
                    'path' => $filepath,
                    'urls' => $urlsGenerated,
                    'url' => "{$baseUrl}/{$filename}"
                ];
            }
            
            // Memory cleanup after each chunk
            gc_collect_cycles();
            
            error_log("Generated chunk {$chunk + 1}/{$chunks} with {$urlsGenerated} URLs");
        }
        
        // Generate sitemap index
        $indexFile = $this->generateSitemapIndex($sitemapFiles, $baseUrl);
        
        return [
            'index_file' => $indexFile,
            'sitemap_files' => $sitemapFiles,
            'total_urls' => $totalCount,
            'total_chunks' => $chunks
        ];
    }
    
    private function generateChunk($table, $baseUrl, $urlPattern, $offset, $limit, $outputFile)
    {
        $sitemap = new Sitemap();
        
        $stmt = $this->pdo->prepare("
            SELECT slug, updated_at, priority_column
            FROM {$table} 
            WHERE active = 1 
            ORDER BY id 
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $urlCount = 0;
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $url = str_replace('{slug}', $row['slug'], $urlPattern);
            
            $sitemap->add(
                $url,
                date('c', strtotime($row['updated_at'])),
                $row['priority_column'] ?: '0.7',
                'weekly'
            );
            
            $urlCount++;
            
            // Clear row from memory
            unset($row);
        }
        
        if ($urlCount > 0) {
            $xml = $sitemap->renderXml();
            file_put_contents($outputFile, $xml);
            
            // Clear sitemap from memory
            unset($sitemap, $xml);
        }
        
        return $urlCount;
    }
    
    private function generateSitemapIndex($sitemapFiles, $baseUrl)
    {
        $sitemapIndex = new Sitemap();
        
        foreach ($sitemapFiles as $file) {
            $sitemapIndex->addSitemap($file['url'], date('c'));
        }
        
        $items = $sitemapIndex->getModel()->getSitemaps();
        $xml = view('sitemap.sitemapindex', compact('items'))->render();
        
        $indexFile = $this->outputDir . '/sitemap.xml';
        file_put_contents($indexFile, $xml);
        
        return $indexFile;
    }
}

// Usage
$config = [
    'host' => 'localhost',
    'name' => 'large_site',
    'user' => 'dbuser',
    'pass' => 'dbpass'
];

$generator = new ChunkedSitemapGenerator($config, 'public/sitemaps');

// Generate product sitemaps in chunks
$result = $generator->generateChunkedSitemaps(
    'products',
    'https://example.com',
    'https://example.com/products/{slug}'
);

echo "Generated {$result['total_chunks']} sitemap files with {$result['total_urls']} total URLs\n";
echo "Index file: {$result['index_file']}\n";
```

## Generator Pattern Implementation

### Lazy Loading with Generators

```php
<?php
use Rumenx\Sitemap\Sitemap;

class GeneratorBasedSitemapBuilder
{
    private $pdo;
    
    public function __construct($dbConfig)
    {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
        $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    }
    
    public function getProductUrls($batchSize = 1000)
    {
        $stmt = $this->pdo->prepare("
            SELECT slug, name, updated_at, stock_quantity
            FROM products 
            WHERE active = 1 
            ORDER BY id
        ");
        
        $stmt->execute();
        
        $batch = [];
        $count = 0;
        
        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $batch[] = [
                'loc' => "https://example.com/products/{$product['slug']}",
                'lastmod' => date('c', strtotime($product['updated_at'])),
                'priority' => $product['stock_quantity'] > 0 ? '0.8' : '0.5',
                'changefreq' => 'weekly'
            ];
            
            $count++;
            
            if ($count === $batchSize) {
                yield $batch;
                $batch = [];
                $count = 0;
                
                // Force garbage collection
                gc_collect_cycles();
            }
        }
        
        // Yield remaining items
        if (!empty($batch)) {
            yield $batch;
        }
    }
    
    public function getBlogUrls($batchSize = 1000)
    {
        $stmt = $this->pdo->prepare("
            SELECT slug, title, published_at, updated_at
            FROM posts 
            WHERE published = 1 AND published_at <= NOW()
            ORDER BY published_at DESC
        ");
        
        $stmt->execute();
        
        $batch = [];
        $count = 0;
        
        while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lastmod = $post['updated_at'] ?: $post['published_at'];
            
            $batch[] = [
                'loc' => "https://example.com/blog/{$post['slug']}",
                'lastmod' => date('c', strtotime($lastmod)),
                'priority' => '0.7',
                'changefreq' => 'monthly'
            ];
            
            $count++;
            
            if ($count === $batchSize) {
                yield $batch;
                $batch = [];
                $count = 0;
                gc_collect_cycles();
            }
        }
        
        if (!empty($batch)) {
            yield $batch;
        }
    }
    
    public function generateSitemapWithGenerator($generators, $outputFile = 'php://output')
    {
        $handle = fopen($outputFile, 'w');
        
        // Write XML header
        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($handle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n");
        
        $totalUrls = 0;
        
        foreach ($generators as $generator) {
            foreach ($generator as $batch) {
                foreach ($batch as $url) {
                    $urlXml = $this->generateUrlXml($url);
                    fwrite($handle, $urlXml);
                    $totalUrls++;
                    
                    if ($totalUrls % 10000 === 0) {
                        error_log("Generated {$totalUrls} URLs, memory: " . $this->getMemoryUsage());
                    }
                }
                
                // Clear batch from memory
                unset($batch);
            }
        }
        
        // Write XML footer
        fwrite($handle, '</urlset>');
        fclose($handle);
        
        return $totalUrls;
    }
    
    private function generateUrlXml($url)
    {
        $xml = "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($url['loc'], ENT_XML1) . "</loc>\n";
        
        if (isset($url['lastmod'])) {
            $xml .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
        }
        
        if (isset($url['priority'])) {
            $xml .= "    <priority>{$url['priority']}</priority>\n";
        }
        
        if (isset($url['changefreq'])) {
            $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
        }
        
        $xml .= "  </url>\n";
        
        return $xml;
    }
    
    private function getMemoryUsage()
    {
        $bytes = memory_get_usage(true);
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

// Usage
$config = [
    'host' => 'localhost',
    'name' => 'website',
    'user' => 'dbuser',
    'pass' => 'dbpass'
];

$builder = new GeneratorBasedSitemapBuilder($config);

// Create generators for different content types
$generators = [
    $builder->getProductUrls(1000),
    $builder->getBlogUrls(1000)
];

// Generate sitemap with minimal memory usage
header('Content-Type: application/xml; charset=utf-8');
$totalUrls = $builder->generateSitemapWithGenerator($generators);

error_log("Generated sitemap with {$totalUrls} URLs using minimal memory");
```

## Efficient Object Management

### Object Pooling for Sitemap Items

```php
<?php
use Rumenx\Sitemap\Sitemap;

class SitemapItemPool
{
    private $pool = [];
    private $maxPoolSize = 1000;
    private $created = 0;
    private $reused = 0;
    
    public function get()
    {
        if (!empty($this->pool)) {
            $this->reused++;
            return array_pop($this->pool);
        }
        
        $this->created++;
        return new SitemapItem();
    }
    
    public function release($item)
    {
        if (count($this->pool) < $this->maxPoolSize) {
            $item->reset();
            $this->pool[] = $item;
        }
    }
    
    public function getStats()
    {
        return [
            'created' => $this->created,
            'reused' => $this->reused,
            'pool_size' => count($this->pool),
            'reuse_rate' => $this->reused > 0 ? round(($this->reused / ($this->created + $this->reused)) * 100, 2) : 0
        ];
    }
}

class SitemapItem
{
    public $loc;
    public $lastmod;
    public $priority;
    public $changefreq;
    
    public function reset()
    {
        $this->loc = null;
        $this->lastmod = null;
        $this->priority = null;
        $this->changefreq = null;
    }
    
    public function setData($loc, $lastmod, $priority, $changefreq)
    {
        $this->loc = $loc;
        $this->lastmod = $lastmod;
        $this->priority = $priority;
        $this->changefreq = $changefreq;
    }
    
    public function toXml()
    {
        $xml = "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($this->loc, ENT_XML1) . "</loc>\n";
        
        if ($this->lastmod) {
            $xml .= "    <lastmod>{$this->lastmod}</lastmod>\n";
        }
        
        if ($this->priority) {
            $xml .= "    <priority>{$this->priority}</priority>\n";
        }
        
        if ($this->changefreq) {
            $xml .= "    <changefreq>{$this->changefreq}</changefreq>\n";
        }
        
        $xml .= "  </url>\n";
        
        return $xml;
    }
}

class PooledSitemapGenerator
{
    private $pdo;
    private $pool;
    
    public function __construct($dbConfig)
    {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
        $this->pool = new SitemapItemPool();
    }
    
    public function generateSitemap($outputFile = 'php://output')
    {
        $handle = fopen($outputFile, 'w');
        
        // Write XML header
        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($handle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n");
        
        $stmt = $this->pdo->prepare("
            SELECT slug, updated_at, stock_quantity
            FROM products 
            WHERE active = 1 
            ORDER BY id
        ");
        
        $stmt->execute();
        $totalUrls = 0;
        
        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Get item from pool
            $item = $this->pool->get();
            
            // Set data
            $item->setData(
                "https://example.com/products/{$product['slug']}",
                date('c', strtotime($product['updated_at'])),
                $product['stock_quantity'] > 0 ? '0.8' : '0.5',
                'weekly'
            );
            
            // Write XML
            fwrite($handle, $item->toXml());
            
            // Return item to pool
            $this->pool->release($item);
            
            $totalUrls++;
            
            if ($totalUrls % 10000 === 0) {
                error_log("Generated {$totalUrls} URLs, memory: " . $this->getMemoryUsage());
                error_log("Pool stats: " . json_encode($this->pool->getStats()));
            }
        }
        
        // Write XML footer
        fwrite($handle, '</urlset>');
        fclose($handle);
        
        return [
            'total_urls' => $totalUrls,
            'pool_stats' => $this->pool->getStats()
        ];
    }
    
    private function getMemoryUsage()
    {
        return round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB';
    }
}

// Usage
$config = [
    'host' => 'localhost',
    'name' => 'ecommerce',
    'user' => 'dbuser',
    'pass' => 'dbpass'
];

$generator = new PooledSitemapGenerator($config);

header('Content-Type: application/xml; charset=utf-8');
$result = $generator->generateSitemap();

error_log("Generated {$result['total_urls']} URLs");
error_log("Object reuse rate: {$result['pool_stats']['reuse_rate']}%");
```

## Database Query Optimization

### Optimized Query Strategies

```php
<?php
class OptimizedDatabaseQueries
{
    private $pdo;
    
    public function __construct($dbConfig)
    {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
        
        // Optimize PDO settings
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $this->pdo->exec("SET SESSION query_cache_type = OFF");
        $this->pdo->exec("SET SESSION sql_buffer_result = OFF");
    }
    
    public function getProductUrlsOptimized()
    {
        // Use covering index to avoid accessing row data
        $sql = "
            SELECT 
                id,
                slug,
                updated_at,
                CASE 
                    WHEN stock_quantity > 10 THEN '0.8'
                    WHEN stock_quantity > 0 THEN '0.7'
                    ELSE '0.5'
                END as priority
            FROM products 
            USE INDEX (idx_active_updated) 
            WHERE active = 1 
            ORDER BY id
        ";
        
        return $this->pdo->query($sql);
    }
    
    public function getBlogUrlsWithJoinOptimization()
    {
        // Optimized join to avoid N+1 queries
        $sql = "
            SELECT 
                p.slug,
                p.updated_at,
                p.published_at,
                c.slug as category_slug
            FROM posts p
            STRAIGHT_JOIN categories c ON p.category_id = c.id
            WHERE p.published = 1 
            AND p.published_at <= NOW()
            AND c.active = 1
            ORDER BY p.id
        ";
        
        return $this->pdo->query($sql);
    }
    
    public function getUrlsWithTemporaryTable($table, $conditions = [])
    {
        // Create temporary table for complex filtering
        $tempTable = "temp_sitemap_" . uniqid();
        
        $whereClause = '';
        if (!empty($conditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }
        
        $this->pdo->exec("
            CREATE TEMPORARY TABLE {$tempTable} 
            ENGINE=MEMORY
            AS
            SELECT id, slug, updated_at
            FROM {$table}
            {$whereClause}
            ORDER BY id
        ");
        
        $stmt = $this->pdo->query("SELECT * FROM {$tempTable}");
        
        // Cleanup
        $this->pdo->exec("DROP TEMPORARY TABLE {$tempTable}");
        
        return $stmt;
    }
    
    public function createOptimalIndexes()
    {
        $indexes = [
            // For products table
            "CREATE INDEX IF NOT EXISTS idx_active_updated ON products (active, updated_at, id)",
            "CREATE INDEX IF NOT EXISTS idx_active_stock ON products (active, stock_quantity, id)",
            
            // For posts table
            "CREATE INDEX IF NOT EXISTS idx_published_date ON posts (published, published_at, id)",
            "CREATE INDEX IF NOT EXISTS idx_category_published ON posts (category_id, published, id)",
            
            // For categories table
            "CREATE INDEX IF NOT EXISTS idx_active_name ON categories (active, name, id)"
        ];
        
        foreach ($indexes as $sql) {
            try {
                $this->pdo->exec($sql);
                echo "Created index: " . substr($sql, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "Index creation failed: " . $e->getMessage() . "\n";
            }
        }
    }
    
    public function analyzeQueryPerformance($sql)
    {
        // Analyze query execution plan
        $explainStmt = $this->pdo->prepare("EXPLAIN " . $sql);
        $explainStmt->execute();
        $plan = $explainStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check for performance issues
        $issues = [];
        foreach ($plan as $row) {
            if ($row['type'] === 'ALL') {
                $issues[] = "Full table scan on {$row['table']}";
            }
            if ($row['Extra'] && strpos($row['Extra'], 'Using filesort') !== false) {
                $issues[] = "Filesort required for {$row['table']}";
            }
            if ($row['rows'] > 100000) {
                $issues[] = "Large number of rows scanned: {$row['rows']}";
            }
        }
        
        return [
            'plan' => $plan,
            'issues' => $issues
        ];
    }
}

// Usage
$config = [
    'host' => 'localhost',
    'name' => 'large_site',
    'user' => 'dbuser',
    'pass' => 'dbpass'
];

$queries = new OptimizedDatabaseQueries($config);

// Create optimal indexes
$queries->createOptimalIndexes();

// Use optimized queries
$productStmt = $queries->getProductUrlsOptimized();
$blogStmt = $queries->getBlogUrlsWithJoinOptimization();

// Analyze performance
$analysis = $queries->analyzeQueryPerformance("
    SELECT slug, updated_at FROM products WHERE active = 1 ORDER BY id
");

if (!empty($analysis['issues'])) {
    echo "Query performance issues:\n";
    foreach ($analysis['issues'] as $issue) {
        echo "- {$issue}\n";
    }
}
```

## Memory Monitoring and Limits

### Memory Tracking and Limits

```php
<?php
class MemoryMonitor
{
    private $memoryLimit;
    private $warningThreshold;
    private $criticalThreshold;
    private $measurements = [];
    
    public function __construct($memoryLimitMB = 256)
    {
        $this->memoryLimit = $memoryLimitMB * 1024 * 1024;
        $this->warningThreshold = $this->memoryLimit * 0.8;  // 80%
        $this->criticalThreshold = $this->memoryLimit * 0.9; // 90%
        
        // Set PHP memory limit
        ini_set('memory_limit', $memoryLimitMB . 'M');
    }
    
    public function checkMemory($label = null)
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        
        $measurement = [
            'timestamp' => microtime(true),
            'label' => $label,
            'current' => $current,
            'peak' => $peak,
            'current_formatted' => $this->formatBytes($current),
            'peak_formatted' => $this->formatBytes($peak),
            'percentage' => ($current / $this->memoryLimit) * 100
        ];
        
        $this->measurements[] = $measurement;
        
        // Check thresholds
        if ($current >= $this->criticalThreshold) {
            $this->handleCriticalMemory($measurement);
        } elseif ($current >= $this->warningThreshold) {
            $this->handleWarningMemory($measurement);
        }
        
        return $measurement;
    }
    
    private function handleWarningMemory($measurement)
    {
        error_log("Memory warning: {$measurement['current_formatted']} used ({$measurement['percentage']}%)");
        
        // Force garbage collection
        gc_collect_cycles();
        
        // Optional: Clear some caches
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
    
    private function handleCriticalMemory($measurement)
    {
        error_log("Critical memory usage: {$measurement['current_formatted']} used ({$measurement['percentage']}%)");
        
        // Aggressive cleanup
        gc_collect_cycles();
        
        throw new Exception("Memory usage critical: {$measurement['current_formatted']} used");
    }
    
    public function optimizeMemory()
    {
        // Force garbage collection
        $collected = gc_collect_cycles();
        
        // Clear realpath cache
        clearstatcache(true);
        
        // Optionally clear opcache
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        return [
            'cycles_collected' => $collected,
            'memory_after' => memory_get_usage(true),
            'memory_after_formatted' => $this->formatBytes(memory_get_usage(true))
        ];
    }
    
    public function getMemoryReport()
    {
        $report = [
            'memory_limit' => $this->formatBytes($this->memoryLimit),
            'current_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_usage' => $this->formatBytes(memory_get_peak_usage(true)),
            'measurements_count' => count($this->measurements),
            'warnings' => 0,
            'critical' => 0
        ];
        
        foreach ($this->measurements as $measurement) {
            if ($measurement['current'] >= $this->criticalThreshold) {
                $report['critical']++;
            } elseif ($measurement['current'] >= $this->warningThreshold) {
                $report['warnings']++;
            }
        }
        
        return $report;
    }
    
    public function getMeasurements()
    {
        return $this->measurements;
    }
    
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}

class MonitoredSitemapGenerator
{
    private $pdo;
    private $monitor;
    
    public function __construct($dbConfig, $memoryLimitMB = 128)
    {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
        $this->monitor = new MemoryMonitor($memoryLimitMB);
    }
    
    public function generateSitemapWithMonitoring($outputFile = 'php://output')
    {
        $this->monitor->checkMemory('Start generation');
        
        $handle = fopen($outputFile, 'w');
        
        // Write XML header
        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($handle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n");
        
        $this->monitor->checkMemory('XML header written');
        
        $stmt = $this->pdo->prepare("
            SELECT slug, updated_at 
            FROM products 
            WHERE active = 1 
            ORDER BY id
        ");
        
        $stmt->execute();
        $this->monitor->checkMemory('Query executed');
        
        $totalUrls = 0;
        $batchSize = 1000;
        
        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $url = "  <url>\n" .
                   "    <loc>https://example.com/products/{$product['slug']}</loc>\n" .
                   "    <lastmod>" . date('c', strtotime($product['updated_at'])) . "</lastmod>\n" .
                   "  </url>\n";
            
            fwrite($handle, $url);
            $totalUrls++;
            
            // Monitor memory every batch
            if ($totalUrls % $batchSize === 0) {
                $this->monitor->checkMemory("Generated {$totalUrls} URLs");
                
                // Optimize memory if needed
                if (memory_get_usage(true) > $this->monitor->warningThreshold) {
                    $optimization = $this->monitor->optimizeMemory();
                    error_log("Memory optimized: collected {$optimization['cycles_collected']} cycles");
                }
            }
        }
        
        // Write XML footer
        fwrite($handle, '</urlset>');
        fclose($handle);
        
        $this->monitor->checkMemory('Generation complete');
        
        return [
            'total_urls' => $totalUrls,
            'memory_report' => $this->monitor->getMemoryReport(),
            'measurements' => $this->monitor->getMeasurements()
        ];
    }
}

// Usage
$config = [
    'host' => 'localhost',
    'name' => 'ecommerce',
    'user' => 'dbuser',
    'pass' => 'dbpass'
];

$generator = new MonitoredSitemapGenerator($config, 128); // 128MB limit

try {
    header('Content-Type: application/xml; charset=utf-8');
    $result = $generator->generateSitemapWithMonitoring();
    
    error_log("Generated {$result['total_urls']} URLs");
    error_log("Memory warnings: {$result['memory_report']['warnings']}");
    error_log("Memory critical: {$result['memory_report']['critical']}");
    
} catch (Exception $e) {
    error_log("Memory error: " . $e->getMessage());
    http_response_code(500);
    echo "Sitemap generation failed due to memory constraints";
}
```

## Temporary File Management

### Using Temporary Files for Large Datasets

```php
<?php
class TempFileSitemapGenerator
{
    private $pdo;
    private $tempDir;
    private $tempFiles = [];
    
    public function __construct($dbConfig, $tempDir = null)
    {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
        
        $this->tempDir = $tempDir ?: sys_get_temp_dir();
        
        // Register cleanup handler
        register_shutdown_function([$this, 'cleanup']);
    }
    
    public function generateLargeSitemap($maxMemoryMB = 64)
    {
        $maxMemory = $maxMemoryMB * 1024 * 1024;
        
        // Phase 1: Generate temporary files for each content type
        $tempFiles = [
            'products' => $this->generateProductsToTempFile(),
            'categories' => $this->generateCategoriesToTempFile(),
            'blog' => $this->generateBlogToTempFile()
        ];
        
        // Phase 2: Merge temporary files into final sitemap
        $outputFile = $this->mergeTempFiles($tempFiles);
        
        return $outputFile;
    }
    
    private function generateProductsToTempFile()
    {
        $tempFile = tempnam($this->tempDir, 'sitemap_products_');
        $this->tempFiles[] = $tempFile;
        
        $handle = fopen($tempFile, 'w');
        
        $stmt = $this->pdo->prepare("
            SELECT slug, updated_at, stock_quantity
            FROM products 
            WHERE active = 1 
            ORDER BY id
        ");
        
        $stmt->execute();
        
        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $url = [
                'loc' => "https://example.com/products/{$product['slug']}",
                'lastmod' => date('c', strtotime($product['updated_at'])),
                'priority' => $product['stock_quantity'] > 0 ? '0.8' : '0.5',
                'changefreq' => 'weekly'
            ];
            
            fwrite($handle, json_encode($url) . "\n");
        }
        
        fclose($handle);
        
        return $tempFile;
    }
    
    private function generateCategoriesToTempFile()
    {
        $tempFile = tempnam($this->tempDir, 'sitemap_categories_');
        $this->tempFiles[] = $tempFile;
        
        $handle = fopen($tempFile, 'w');
        
        $stmt = $this->pdo->prepare("
            SELECT slug, updated_at
            FROM categories 
            WHERE active = 1 
            ORDER BY name
        ");
        
        $stmt->execute();
        
        while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $url = [
                'loc' => "https://example.com/categories/{$category['slug']}",
                'lastmod' => date('c', strtotime($category['updated_at'])),
                'priority' => '0.9',
                'changefreq' => 'weekly'
            ];
            
            fwrite($handle, json_encode($url) . "\n");
        }
        
        fclose($handle);
        
        return $tempFile;
    }
    
    private function generateBlogToTempFile()
    {
        $tempFile = tempnam($this->tempDir, 'sitemap_blog_');
        $this->tempFiles[] = $tempFile;
        
        $handle = fopen($tempFile, 'w');
        
        $stmt = $this->pdo->prepare("
            SELECT slug, published_at, updated_at
            FROM posts 
            WHERE published = 1 AND published_at <= NOW()
            ORDER BY published_at DESC
        ");
        
        $stmt->execute();
        
        while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lastmod = $post['updated_at'] ?: $post['published_at'];
            
            $url = [
                'loc' => "https://example.com/blog/{$post['slug']}",
                'lastmod' => date('c', strtotime($lastmod)),
                'priority' => '0.7',
                'changefreq' => 'monthly'
            ];
            
            fwrite($handle, json_encode($url) . "\n");
        }
        
        fclose($handle);
        
        return $tempFile;
    }
    
    private function mergeTempFiles($tempFiles)
    {
        $outputFile = tempnam($this->tempDir, 'sitemap_final_');
        $this->tempFiles[] = $outputFile;
        
        $handle = fopen($outputFile, 'w');
        
        // Write XML header
        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($handle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n");
        
        $totalUrls = 0;
        
        foreach ($tempFiles as $type => $tempFile) {
            if (!file_exists($tempFile)) {
                continue;
            }
            
            $tempHandle = fopen($tempFile, 'r');
            
            while (($line = fgets($tempHandle)) !== false) {
                $url = json_decode(trim($line), true);
                
                if ($url) {
                    $xml = $this->urlToXml($url);
                    fwrite($handle, $xml);
                    $totalUrls++;
                }
            }
            
            fclose($tempHandle);
            
            error_log("Merged {$type} URLs, total: {$totalUrls}");
        }
        
        // Write XML footer
        fwrite($handle, '</urlset>');
        fclose($handle);
        
        return $outputFile;
    }
    
    private function urlToXml($url)
    {
        $xml = "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($url['loc'], ENT_XML1) . "</loc>\n";
        
        if (isset($url['lastmod'])) {
            $xml .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
        }
        
        if (isset($url['priority'])) {
            $xml .= "    <priority>{$url['priority']}</priority>\n";
        }
        
        if (isset($url['changefreq'])) {
            $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
        }
        
        $xml .= "  </url>\n";
        
        return $xml;
    }
    
    public function cleanup()
    {
        foreach ($this->tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
        $this->tempFiles = [];
    }
    
    public function getTempFileInfo()
    {
        $info = [];
        
        foreach ($this->tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                $info[] = [
                    'file' => basename($tempFile),
                    'size' => filesize($tempFile),
                    'size_formatted' => $this->formatBytes(filesize($tempFile))
                ];
            }
        }
        
        return $info;
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

// Usage
$config = [
    'host' => 'localhost',
    'name' => 'large_ecommerce',
    'user' => 'dbuser',
    'pass' => 'dbpass'
];

$generator = new TempFileSitemapGenerator($config);

try {
    $sitemapFile = $generator->generateLargeSitemap(64); // 64MB memory limit
    
    // Output the sitemap
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Length: ' . filesize($sitemapFile));
    readfile($sitemapFile);
    
    // Cleanup is automatic via shutdown handler
    
} catch (Exception $e) {
    error_log("Sitemap generation failed: " . $e->getMessage());
    http_response_code(500);
    echo "Sitemap generation failed";
}
```

## Performance Benchmarking

### Memory Usage Comparison

```php
<?php
class SitemapPerformanceBenchmark
{
    private $config;
    
    public function __construct($dbConfig)
    {
        $this->config = $dbConfig;
    }
    
    public function runBenchmarks($urlCount = 100000)
    {
        $benchmarks = [
            'standard' => [$this, 'benchmarkStandard'],
            'streaming' => [$this, 'benchmarkStreaming'],
            'chunked' => [$this, 'benchmarkChunked'],
            'generator' => [$this, 'benchmarkGenerator'],
            'temp_files' => [$this, 'benchmarkTempFiles']
        ];
        
        $results = [];
        
        foreach ($benchmarks as $name => $method) {
            echo "Running {$name} benchmark...\n";
            
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            try {
                $result = call_user_func($method, $urlCount);
                
                $endTime = microtime(true);
                $endMemory = memory_get_usage(true);
                $peakMemory = memory_get_peak_usage(true);
                
                $results[$name] = [
                    'status' => 'success',
                    'time' => $endTime - $startTime,
                    'memory_start' => $startMemory,
                    'memory_end' => $endMemory,
                    'memory_peak' => $peakMemory,
                    'memory_used' => $peakMemory - $startMemory,
                    'urls_generated' => $result['urls'] ?? 0,
                    'additional_data' => $result
                ];
                
            } catch (Exception $e) {
                $results[$name] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'time' => microtime(true) - $startTime,
                    'memory_peak' => memory_get_peak_usage(true) - $startMemory
                ];
            }
            
            // Force garbage collection between benchmarks
            gc_collect_cycles();
            
            echo "Completed {$name} benchmark\n\n";
        }
        
        return $this->formatBenchmarkResults($results);
    }
    
    private function benchmarkStandard($urlCount)
    {
        $sitemap = new Sitemap();
        $pdo = new PDO("mysql:host={$this->config['host']};dbname={$this->config['name']}", 
                       $this->config['user'], $this->config['pass']);
        
        $stmt = $pdo->query("
            SELECT slug, updated_at 
            FROM products 
            WHERE active = 1 
            ORDER BY id 
            LIMIT {$urlCount}
        ");
        
        $urls = 0;
        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sitemap->add(
                "https://example.com/products/{$product['slug']}",
                date('c', strtotime($product['updated_at'])),
                '0.8',
                'weekly'
            );
            $urls++;
        }
        
        $xml = $sitemap->renderXml();
        
        return ['urls' => $urls, 'size' => strlen($xml)];
    }
    
    private function benchmarkStreaming($urlCount)
    {
        $generator = new MemoryEfficientSitemapGenerator($this->config);
        
        ob_start();
        $urls = $generator->generateProductSitemapStream();
        $xml = ob_get_clean();
        
        return ['urls' => $urls, 'size' => strlen($xml)];
    }
    
    private function benchmarkChunked($urlCount)
    {
        $generator = new ChunkedSitemapGenerator($this->config, 'temp_benchmark');
        
        $result = $generator->generateChunkedSitemaps(
            'products',
            'https://example.com',
            'https://example.com/products/{slug}'
        );
        
        return ['urls' => $result['total_urls'], 'chunks' => $result['total_chunks']];
    }
    
    private function benchmarkGenerator($urlCount)
    {
        $builder = new GeneratorBasedSitemapBuilder($this->config);
        
        $generators = [$builder->getProductUrls(1000)];
        
        ob_start();
        $urls = $builder->generateSitemapWithGenerator($generators);
        $xml = ob_get_clean();
        
        return ['urls' => $urls, 'size' => strlen($xml)];
    }
    
    private function benchmarkTempFiles($urlCount)
    {
        $generator = new TempFileSitemapGenerator($this->config);
        
        $sitemapFile = $generator->generateLargeSitemap();
        $size = filesize($sitemapFile);
        
        // Count URLs in file
        $handle = fopen($sitemapFile, 'r');
        $urls = 0;
        while (($line = fgets($handle)) !== false) {
            if (strpos($line, '<url>') !== false) {
                $urls++;
            }
        }
        fclose($handle);
        
        $generator->cleanup();
        
        return ['urls' => $urls, 'size' => $size];
    }
    
    private function formatBenchmarkResults($results)
    {
        $formatted = [];
        
        foreach ($results as $name => $result) {
            $formatted[$name] = [
                'status' => $result['status'],
                'time_seconds' => round($result['time'], 3),
                'memory_used_mb' => round(($result['memory_used'] ?? 0) / 1024 / 1024, 2),
                'memory_peak_mb' => round(($result['memory_peak'] ?? 0) / 1024 / 1024, 2),
                'urls_generated' => $result['urls_generated'] ?? 0,
                'urls_per_second' => $result['time'] > 0 ? round(($result['urls_generated'] ?? 0) / $result['time'], 0) : 0
            ];
            
            if ($result['status'] === 'failed') {
                $formatted[$name]['error'] = $result['error'];
            }
        }
        
        return $formatted;
    }
    
    public function printResults($results)
    {
        echo "Sitemap Generation Performance Benchmark Results\n";
        echo str_repeat("=", 60) . "\n\n";
        
        printf("%-15s %-8s %-10s %-12s %-10s %-12s\n", 
               'Method', 'Status', 'Time (s)', 'Memory (MB)', 'URLs', 'URLs/sec');
        echo str_repeat("-", 60) . "\n";
        
        foreach ($results as $name => $result) {
            printf("%-15s %-8s %-10s %-12s %-10s %-12s\n",
                   $name,
                   $result['status'],
                   $result['time_seconds'],
                   $result['memory_peak_mb'],
                   $result['urls_generated'],
                   $result['urls_per_second']
            );
        }
        
        echo "\n";
        
        // Find best performing method
        $bestTime = null;
        $bestMemory = null;
        $bestTimeMethod = '';
        $bestMemoryMethod = '';
        
        foreach ($results as $name => $result) {
            if ($result['status'] === 'success') {
                if ($bestTime === null || $result['time_seconds'] < $bestTime) {
                    $bestTime = $result['time_seconds'];
                    $bestTimeMethod = $name;
                }
                
                if ($bestMemory === null || $result['memory_peak_mb'] < $bestMemory) {
                    $bestMemory = $result['memory_peak_mb'];
                    $bestMemoryMethod = $name;
                }
            }
        }
        
        echo "Best Performance:\n";
        echo "- Fastest: {$bestTimeMethod} ({$bestTime}s)\n";
        echo "- Most Memory Efficient: {$bestMemoryMethod} ({$bestMemory}MB)\n";
    }
}

// Usage
$config = [
    'host' => 'localhost',
    'name' => 'benchmark_db',
    'user' => 'dbuser',
    'pass' => 'dbpass'
];

$benchmark = new SitemapPerformanceBenchmark($config);
$results = $benchmark->runBenchmarks(50000); // Test with 50k URLs
$benchmark->printResults($results);
```

## Next Steps

- Learn about [Automated Generation](automated-generation.md) for scheduled processing
- Explore [Caching Strategies](caching-strategies.md) for memory optimization
- Check [Large Scale Sitemaps](large-scale-sitemaps.md) for enterprise solutions
- See [Performance Monitoring](performance-monitoring.md) for production optimization
