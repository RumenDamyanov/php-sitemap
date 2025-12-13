# Google News Sitemap Examples

Create optimized sitemaps specifically for Google News with proper formatting, timing, and content guidelines.

## Google News Requirements

### Key Guidelines

- **Time Limit**: Only include articles published within the last 2 days
- **Article Quality**: Must be substantial news content (not press releases or job listings)
- **Language**: Use proper language codes (en, fr, de, etc.)
- **Genres**: Use appropriate genres (PressRelease, Satire, Blog, OpEd, Opinion, UserGenerated)
- **Keywords**: Include relevant, descriptive keywords (max 10 keywords)

## Basic Google News Sitemap

### Simple News Articles

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Breaking news article
$googleNews1 = [
    'sitename' => 'Daily News Today',
    'language' => 'en',
    'genres' => 'PressRelease',
    'publication_date' => date('c', strtotime('-2 hours')),
    'title' => 'Major Economic Policy Changes Announced',
    'keywords' => 'economy, policy, government, finance, business'
];

$sitemap->add(
    'https://example.com/news/economic-policy-changes',
    date('c', strtotime('-2 hours')),
    '1.0',
    'always',
    [], // images
    'Major Economic Policy Changes Announced',
    [], // translations
    [], // videos
    [], // alternates
    $googleNews1
);

// Opinion piece
$googleNews2 = [
    'sitename' => 'Daily News Today',
    'language' => 'en',
    'genres' => 'Opinion',
    'publication_date' => date('c', strtotime('-6 hours')),
    'title' => 'Expert Analysis: What the Policy Changes Mean',
    'keywords' => 'analysis, expert opinion, policy impact, economics'
];

$sitemap->add(
    'https://example.com/opinion/policy-analysis',
    date('c', strtotime('-6 hours')),
    '0.9',
    'always',
    [], // images
    'Expert Analysis: What the Policy Changes Mean',
    [], // translations
    [], // videos
    [], // alternates
    $googleNews2
);

// Blog post
$googleNews3 = [
    'sitename' => 'Daily News Today',
    'language' => 'en',
    'genres' => 'Blog',
    'publication_date' => date('c', strtotime('-12 hours')),
    'title' => 'Behind the Scenes: How Policy Decisions Are Made',
    'keywords' => 'government process, policy making, transparency'
];

$sitemap->add(
    'https://example.com/blog/policy-making-process',
    date('c', strtotime('-12 hours')),
    '0.8',
    'always',
    [], // images
    'Behind the Scenes: How Policy Decisions Are Made',
    [], // translations
    [], // videos
    [], // alternates
    $googleNews3
);

echo $sitemap->renderXml();
```

## Database-Driven News Sitemap

### Recent Articles from Database

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateGoogleNewsSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
    
    // Get articles from the last 2 days (Google News requirement)
    $stmt = $pdo->query("
        SELECT slug, title, updated_at, created_at, 
               news_keywords, news_genres, language,
               excerpt, author, category
        FROM news_articles 
        WHERE published = 1 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
        AND news_approved = 1
        ORDER BY created_at DESC
    ");
    
    while ($article = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $googleNews = [
            'sitename' => 'Your News Organization',
            'language' => $article['language'] ?: 'en',
            'publication_date' => date('c', strtotime($article['created_at'])),
            'title' => $article['title']
        ];
        
        // Add genres if specified
        if ($article['news_genres']) {
            $googleNews['genres'] = $article['news_genres'];
        } else {
            // Default genre based on category
            $googleNews['genres'] = getDefaultGenre($article['category']);
        }
        
        // Add keywords if specified
        if ($article['news_keywords']) {
            $googleNews['keywords'] = $article['news_keywords'];
        } else {
            // Generate keywords from title and excerpt
            $googleNews['keywords'] = generateKeywords($article['title'], $article['excerpt']);
        }
        
        $sitemap->add(
            "https://example.com/news/{$article['slug']}",
            date('c', strtotime($article['updated_at'])),
            '1.0', // High priority for news
            'always', // News content changes frequently
            [], // images
            $article['title'],
            [], // translations
            [], // videos
            [], // alternates
            $googleNews
        );
    }
    
    return $sitemap->renderXml();
}

function getDefaultGenre($category)
{
    $genreMap = [
        'breaking' => 'PressRelease',
        'opinion' => 'Opinion',
        'editorial' => 'OpEd',
        'blog' => 'Blog',
        'satire' => 'Satire',
        'user-content' => 'UserGenerated'
    ];
    
    return $genreMap[$category] ?? 'PressRelease';
}

function generateKeywords($title, $excerpt)
{
    // Simple keyword extraction (you might want to use a more sophisticated approach)
    $text = strtolower($title . ' ' . $excerpt);
    $text = preg_replace('/[^a-z0-9\s]/', '', $text);
    $words = array_filter(explode(' ', $text));
    
    // Remove common stop words
    $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were'];
    $keywords = array_diff($words, $stopWords);
    
    // Get most common words
    $wordCounts = array_count_values($keywords);
    arsort($wordCounts);
    
    // Return top 10 keywords
    return implode(', ', array_slice(array_keys($wordCounts), 0, 10));
}

// Generate and output the sitemap
header('Content-Type: application/xml; charset=utf-8');
echo generateGoogleNewsSitemap();
```

## Multi-Language News Sitemap

### International News Site

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateMultiLanguageNewsSitemap()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
    
    // Get recent articles in all languages
    $stmt = $pdo->query("
        SELECT a.slug, a.title, a.updated_at, a.created_at, 
               a.news_keywords, a.news_genres, a.language,
               t.language as trans_lang, t.slug as trans_slug
        FROM news_articles a
        LEFT JOIN article_translations t ON a.translation_group_id = t.translation_group_id 
            AND t.language != a.language
        WHERE a.published = 1 
        AND a.created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
        ORDER BY a.created_at DESC
    ");
    
    $articles = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $slug = $row['slug'];
        $lang = $row['language'];
        
        if (!isset($articles["{$slug}_{$lang}"])) {
            $articles["{$slug}_{$lang}"] = [
                'slug' => $slug,
                'title' => $row['title'],
                'language' => $lang,
                'updated_at' => $row['updated_at'],
                'created_at' => $row['created_at'],
                'news_keywords' => $row['news_keywords'],
                'news_genres' => $row['news_genres'],
                'translations' => []
            ];
        }
        
        if ($row['trans_lang'] && $row['trans_slug']) {
            $articles["{$slug}_{$lang}"]['translations'][] = [
                'language' => $row['trans_lang'],
                'url' => "https://example.com/{$row['trans_lang']}/news/{$row['trans_slug']}"
            ];
        }
    }
    
    foreach ($articles as $article) {
        $googleNews = [
            'sitename' => getLocalizedSiteName($article['language']),
            'language' => $article['language'],
            'publication_date' => date('c', strtotime($article['created_at'])),
            'title' => $article['title'],
            'genres' => $article['news_genres'] ?: 'PressRelease',
            'keywords' => $article['news_keywords'] ?: 'news, breaking, update'
        ];
        
        $baseUrl = "https://example.com/{$article['language']}/news/{$article['slug']}";
        
        $sitemap->add(
            $baseUrl,
            date('c', strtotime($article['updated_at'])),
            '1.0',
            'always',
            [], // images
            $article['title'],
            $article['translations'],
            [], // videos
            [], // alternates
            $googleNews
        );
    }
    
    return $sitemap->renderXml();
}

function getLocalizedSiteName($language)
{
    $siteNames = [
        'en' => 'Global News Today',
        'fr' => 'Actualités Globales',
        'de' => 'Globale Nachrichten',
        'es' => 'Noticias Globales',
        'it' => 'Notizie Globali'
    ];
    
    return $siteNames[$language] ?? 'Global News Today';
}

header('Content-Type: application/xml; charset=utf-8');
echo generateMultiLanguageNewsSitemap();
```

## Advanced News Sitemap with Images

### News Articles with Featured Images

```php
<?php
use Rumenx\Sitemap\Sitemap;

function generateNewsWithImages()
{
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
    
    $stmt = $pdo->query("
        SELECT a.slug, a.title, a.updated_at, a.created_at,
               a.news_keywords, a.news_genres, a.language,
               i.url as image_url, i.caption, i.credit
        FROM news_articles a
        LEFT JOIN article_images i ON a.id = i.article_id AND i.is_featured = 1
        WHERE a.published = 1 
        AND a.created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
        ORDER BY a.created_at DESC
    ");
    
    while ($article = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $images = [];
        
        if ($article['image_url']) {
            $images[] = [
                'url' => $article['image_url'],
                'title' => $article['title'],
                'caption' => $article['caption'] ?: $article['title']
            ];
        }
        
        $googleNews = [
            'sitename' => 'Breaking News Network',
            'language' => $article['language'] ?: 'en',
            'publication_date' => date('c', strtotime($article['created_at'])),
            'title' => $article['title'],
            'genres' => $article['news_genres'] ?: 'PressRelease',
            'keywords' => $article['news_keywords'] ?: generateKeywordsFromTitle($article['title'])
        ];
        
        $sitemap->add(
            "https://example.com/news/{$article['slug']}",
            date('c', strtotime($article['updated_at'])),
            '1.0',
            'always',
            $images,
            $article['title'],
            [], // translations
            [], // videos
            [], // alternates
            $googleNews
        );
    }
    
    return $sitemap->renderXml();
}

function generateKeywordsFromTitle($title)
{
    // Extract meaningful keywords from title
    $title = strtolower($title);
    $title = preg_replace('/[^a-z0-9\s]/', '', $title);
    $words = array_filter(explode(' ', $title));
    
    $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
    $keywords = array_diff($words, $stopWords);
    
    return implode(', ', array_slice($keywords, 0, 5));
}

header('Content-Type: application/xml; charset=utf-8');
echo generateNewsWithImages();
```

## News Sitemap with Caching

### Cached News Sitemap for Performance

```php
<?php
use Rumenx\Sitemap\Sitemap;

class CachedNewsSitemap
{
    private $cacheFile = 'cache/google-news-sitemap.xml';
    private $cacheMinutes = 15; // Refresh every 15 minutes for news
    
    public function getSitemap()
    {
        // Check if cache is valid
        if ($this->isCacheValid()) {
            header('Content-Type: application/xml; charset=utf-8');
            readfile($this->cacheFile);
            return;
        }
        
        // Generate new sitemap
        $xml = $this->generateNewsSitemap();
        
        // Save to cache
        $this->saveToCache($xml);
        
        // Output
        header('Content-Type: application/xml; charset=utf-8');
        echo $xml;
    }
    
    private function isCacheValid()
    {
        if (!file_exists($this->cacheFile)) {
            return false;
        }
        
        $cacheTime = filemtime($this->cacheFile);
        $maxAge = $this->cacheMinutes * 60;
        
        return (time() - $cacheTime) < $maxAge;
    }
    
    private function generateNewsSitemap()
    {
        $sitemap = new Sitemap();
        $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
        
        $stmt = $pdo->query("
            SELECT slug, title, updated_at, created_at,
                   news_keywords, news_genres, language, summary
            FROM news_articles 
            WHERE published = 1 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
            ORDER BY created_at DESC
            LIMIT 1000
        ");
        
        while ($article = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $googleNews = [
                'sitename' => 'Live News Network',
                'language' => $article['language'] ?: 'en',
                'publication_date' => date('c', strtotime($article['created_at'])),
                'title' => $article['title'],
                'genres' => $this->determineGenre($article),
                'keywords' => $this->getKeywords($article)
            ];
            
            $sitemap->add(
                "https://example.com/news/{$article['slug']}",
                date('c', strtotime($article['updated_at'])),
                $this->calculatePriority($article),
                'always',
                [], // images
                $article['title'],
                [], // translations
                [], // videos
                [], // alternates
                $googleNews
            );
        }
        
        return $sitemap->renderXml();
    }
    
    private function determineGenre($article)
    {
        if ($article['news_genres']) {
            return $article['news_genres'];
        }
        
        // Determine genre based on content analysis
        $title = strtolower($article['title']);
        $summary = strtolower($article['summary']);
        
        if (strpos($title, 'opinion') !== false || strpos($summary, 'opinion') !== false) {
            return 'Opinion';
        }
        
        if (strpos($title, 'breaking') !== false || strpos($title, 'urgent') !== false) {
            return 'PressRelease';
        }
        
        return 'PressRelease'; // Default
    }
    
    private function getKeywords($article)
    {
        if ($article['news_keywords']) {
            return $article['news_keywords'];
        }
        
        // Generate keywords from title and summary
        $text = $article['title'] . ' ' . $article['summary'];
        return $this->extractKeywords($text);
    }
    
    private function extractKeywords($text)
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        $words = array_filter(explode(' ', $text));
        
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'been', 'have', 'has', 'had', 'will', 'would', 'could', 'should'];
        $keywords = array_diff($words, $stopWords);
        
        $wordCounts = array_count_values($keywords);
        arsort($wordCounts);
        
        return implode(', ', array_slice(array_keys($wordCounts), 0, 8));
    }
    
    private function calculatePriority($article)
    {
        $age = time() - strtotime($article['created_at']);
        $hours = $age / 3600;
        
        // Higher priority for newer articles
        if ($hours < 2) return '1.0';
        if ($hours < 6) return '0.9';
        if ($hours < 12) return '0.8';
        if ($hours < 24) return '0.7';
        return '0.6';
    }
    
    private function saveToCache($xml)
    {
        $cacheDir = dirname($this->cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        file_put_contents($this->cacheFile, $xml);
    }
    
    public function invalidateCache()
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }
}

// Usage
$newsSitemap = new CachedNewsSitemap();
$newsSitemap->getSitemap();
```

## News Sitemap Command Line Tool

### CLI Tool for News Sitemap Generation

```php
#!/usr/bin/env php
<?php
/**
 * Generate Google News sitemap
 * Usage: php generate-news-sitemap.php [--output=path] [--hours=48]
 */

require 'vendor/autoload.php';

use Rumenx\Sitemap\Sitemap;

$options = getopt('', ['output:', 'hours:']);
$outputFile = $options['output'] ?? 'public/google-news-sitemap.xml';
$hoursBack = (int)($options['hours'] ?? 48); // Default: 48 hours (2 days)

echo "Generating Google News sitemap...\n";
echo "Looking back: {$hoursBack} hours\n";
echo "Output file: {$outputFile}\n\n";

try {
    $sitemap = new Sitemap();
    $pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
    
    $stmt = $pdo->prepare("
        SELECT slug, title, updated_at, created_at,
               news_keywords, news_genres, language, category
        FROM news_articles 
        WHERE published = 1 
        AND created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
        ORDER BY created_at DESC
    ");
    
    $stmt->bindValue(':hours', $hoursBack, PDO::PARAM_INT);
    $stmt->execute();
    
    $count = 0;
    while ($article = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $googleNews = [
            'sitename' => 'News Command Line',
            'language' => $article['language'] ?: 'en',
            'publication_date' => date('c', strtotime($article['created_at'])),
            'title' => $article['title'],
            'genres' => $article['news_genres'] ?: 'PressRelease',
            'keywords' => $article['news_keywords'] ?: 'news, update'
        ];
        
        $sitemap->add(
            "https://example.com/news/{$article['slug']}",
            date('c', strtotime($article['updated_at'])),
            '1.0',
            'always',
            [],
            $article['title'],
            [],
            [],
            [],
            $googleNews
        );
        
        $count++;
    }
    
    $xml = $sitemap->renderXml();
    
    // Ensure output directory exists
    $outputDir = dirname($outputFile);
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    file_put_contents($outputFile, $xml);
    
    echo "Successfully generated news sitemap with {$count} articles\n";
    echo "File saved: {$outputFile}\n";
    echo "File size: " . number_format(filesize($outputFile)) . " bytes\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
```

## Best Practices for Google News

### Optimization Guidelines

1. **Timing**
   - Only include articles from the last 48 hours
   - Update sitemap frequently (every 15-30 minutes)
   - Use accurate publication dates

2. **Content Quality**
   - Ensure articles meet Google News guidelines
   - Use descriptive, accurate titles
   - Include relevant keywords (max 10)

3. **Technical Requirements**
   - Use proper XML encoding (UTF-8)
   - Validate sitemap structure
   - Keep sitemap size under 50MB

4. **SEO Optimization**
   - Use appropriate genres for content type
   - Include high-quality images when relevant
   - Ensure fast page loading for news articles

## Next Steps

- Learn about [E-commerce Sitemaps](e-commerce.md) for product catalogs
- Explore [Caching Strategies](caching-strategies.md) for news performance
- Check [Automated Generation](automated-generation.md) for scheduled updates
- See [Framework Integration](framework-integration.md) for CMS integration
