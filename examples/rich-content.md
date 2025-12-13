# Rich Content Examples

Learn how to create sitemaps with images, videos, translations, alternates, and Google News content using the `rumenx/php-sitemap` package.

## Images in Sitemaps

### Basic Image Sitemap

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Add page with single image
$sitemap->add(
    'https://example.com/gallery/photo1',
    date('c'),
    '0.8',
    'monthly',
    [
        [
            'url' => 'https://example.com/images/photo1.jpg',
            'title' => 'Beautiful Sunset',
            'caption' => 'A stunning sunset over the mountains',
            'geo_location' => 'Colorado, USA',
            'license' => 'https://example.com/license'
        ]
    ],
    'Photo Gallery - Beautiful Sunset'
);

// Add page with multiple images
$images = [
    [
        'url' => 'https://example.com/images/gallery1.jpg',
        'title' => 'Gallery Image 1',
        'caption' => 'First image in the gallery'
    ],
    [
        'url' => 'https://example.com/images/gallery2.jpg',
        'title' => 'Gallery Image 2',
        'caption' => 'Second image in the gallery'
    ],
    [
        'url' => 'https://example.com/images/gallery3.jpg',
        'title' => 'Gallery Image 3'
        // caption is optional
    ]
];

$sitemap->add(
    'https://example.com/gallery/collection',
    date('c'),
    '0.9',
    'weekly',
    $images,
    'Photo Collection Gallery'
);

echo $sitemap->renderXml();
```

### Image Sitemap from Database

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);

// Get posts with images
$stmt = $pdo->query("
    SELECT p.slug, p.title, p.updated_at,
           i.url as image_url, i.title as image_title, 
           i.caption, i.alt_text, i.geo_location
    FROM posts p
    LEFT JOIN post_images i ON p.id = i.post_id
    WHERE p.published = 1
    ORDER BY p.updated_at DESC
");

$posts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $slug = $row['slug'];
    
    if (!isset($posts[$slug])) {
        $posts[$slug] = [
            'slug' => $slug,
            'title' => $row['title'],
            'updated_at' => $row['updated_at'],
            'images' => []
        ];
    }
    
    if ($row['image_url']) {
        $image = [
            'url' => $row['image_url'],
            'title' => $row['image_title'] ?: $row['title']
        ];
        
        if ($row['caption']) $image['caption'] = $row['caption'];
        if ($row['geo_location']) $image['geo_location'] = $row['geo_location'];
        
        $posts[$slug]['images'][] = $image;
    }
}

foreach ($posts as $post) {
    $sitemap->add(
        "https://example.com/blog/{$post['slug']}",
        date('c', strtotime($post['updated_at'])),
        '0.7',
        'monthly',
        $post['images'],
        $post['title']
    );
}

echo $sitemap->renderXml();
```

## Videos in Sitemaps

### Basic Video Sitemap

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

$videos = [
    [
        'title' => 'How to Use Our Product',
        'description' => 'A comprehensive tutorial showing how to use our product effectively',
        'content_loc' => 'https://example.com/videos/tutorial.mp4',
        'player_loc' => 'https://example.com/player?video=tutorial',
        'thumbnail_loc' => 'https://example.com/thumbs/tutorial.jpg',
        'duration' => 300, // 5 minutes in seconds
        'publication_date' => '2025-01-15T10:00:00+00:00',
        'expiration_date' => '2026-01-15T10:00:00+00:00',
        'rating' => 4.5,
        'view_count' => 15000,
        'family_friendly' => 'yes',
        'category' => 'Education',
        'tags' => ['tutorial', 'product', 'howto'],
        'uploader' => 'Example Company',
        'uploader_info' => 'https://example.com/about'
    ]
];

$sitemap->add(
    'https://example.com/tutorials/product-guide',
    date('c'),
    '0.9',
    'monthly',
    [], // images
    'Product Tutorial Video',
    [], // translations
    $videos // videos
);

echo $sitemap->renderXml();
```

### Video Sitemap from Database

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);

$stmt = $pdo->query("
    SELECT p.slug, p.title, p.updated_at,
           v.title as video_title, v.description, v.video_url,
           v.thumbnail_url, v.duration, v.created_at, v.category,
           v.view_count, v.rating
    FROM posts p
    LEFT JOIN post_videos v ON p.id = v.post_id
    WHERE p.published = 1 AND v.id IS NOT NULL
    ORDER BY p.updated_at DESC
");

$posts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $slug = $row['slug'];
    
    if (!isset($posts[$slug])) {
        $posts[$slug] = [
            'slug' => $slug,
            'title' => $row['title'],
            'updated_at' => $row['updated_at'],
            'videos' => []
        ];
    }
    
    $video = [
        'title' => $row['video_title'],
        'description' => $row['description'],
        'content_loc' => $row['video_url'],
        'thumbnail_loc' => $row['thumbnail_url'],
        'duration' => (int)$row['duration'],
        'publication_date' => date('c', strtotime($row['created_at'])),
        'family_friendly' => 'yes',
        'category' => $row['category']
    ];
    
    if ($row['view_count']) $video['view_count'] = (int)$row['view_count'];
    if ($row['rating']) $video['rating'] = (float)$row['rating'];
    
    $posts[$slug]['videos'][] = $video;
}

foreach ($posts as $post) {
    $sitemap->add(
        "https://example.com/videos/{$post['slug']}",
        date('c', strtotime($post['updated_at'])),
        '0.8',
        'weekly',
        [], // images
        $post['title'],
        [], // translations
        $post['videos']
    );
}

echo $sitemap->renderXml();
```

## Multi-language Sitemaps

### Basic Translation Sitemap

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Multi-language page
$translations = [
    ['language' => 'fr', 'url' => 'https://example.com/fr/about'],
    ['language' => 'de', 'url' => 'https://example.com/de/about'],
    ['language' => 'es', 'url' => 'https://example.com/es/about'],
    ['language' => 'it', 'url' => 'https://example.com/it/about']
];

$sitemap->add(
    'https://example.com/en/about', // English version (canonical)
    date('c'),
    '0.8',
    'monthly',
    [], // images
    'About Us', // title
    $translations // translations
);

// Another multilingual page
$productTranslations = [
    ['language' => 'fr', 'url' => 'https://example.com/fr/produits/widget-deluxe'],
    ['language' => 'de', 'url' => 'https://example.com/de/produkte/widget-deluxe'],
    ['language' => 'es', 'url' => 'https://example.com/es/productos/widget-deluxe']
];

$sitemap->add(
    'https://example.com/en/products/deluxe-widget',
    date('c'),
    '0.9',
    'weekly',
    [], // images
    'Deluxe Widget Product',
    $productTranslations
);

echo $sitemap->renderXml();
```

### Database-Driven Multi-language Sitemap

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);

// Get posts with their translations
$stmt = $pdo->query("
    SELECT p.slug, p.title, p.updated_at, p.lang, p.translation_group_id,
           t.lang as trans_lang, t.slug as trans_slug
    FROM posts p
    LEFT JOIN posts t ON p.translation_group_id = t.translation_group_id AND t.lang != p.lang
    WHERE p.published = 1 AND p.lang = 'en'
    ORDER BY p.updated_at DESC
");

$posts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $slug = $row['slug'];
    
    if (!isset($posts[$slug])) {
        $posts[$slug] = [
            'slug' => $slug,
            'title' => $row['title'],
            'updated_at' => $row['updated_at'],
            'translations' => []
        ];
    }
    
    if ($row['trans_lang'] && $row['trans_slug']) {
        $posts[$slug]['translations'][] = [
            'language' => $row['trans_lang'],
            'url' => "https://example.com/{$row['trans_lang']}/blog/{$row['trans_slug']}"
        ];
    }
}

foreach ($posts as $post) {
    $sitemap->add(
        "https://example.com/en/blog/{$post['slug']}",
        date('c', strtotime($post['updated_at'])),
        '0.7',
        'monthly',
        [], // images
        $post['title'],
        $post['translations']
    );
}

echo $sitemap->renderXml();
```

## Google News Sitemaps

### Basic News Sitemap

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Add news article
$googleNews = [
    'sitename' => 'Example News',
    'language' => 'en',
    'genres' => 'PressRelease, Blog',
    'publication_date' => '2025-01-29T10:00:00+00:00',
    'title' => 'Breaking: Major Technology Breakthrough Announced',
    'keywords' => 'technology, breakthrough, innovation, science'
];

$sitemap->add(
    'https://example.com/news/tech-breakthrough-2025',
    date('c'),
    '1.0',
    'always',
    [], // images
    'Breaking: Major Technology Breakthrough Announced',
    [], // translations
    [], // videos
    [], // alternates
    $googleNews // Google News
);

// Add another news article
$googleNews2 = [
    'sitename' => 'Example News',
    'language' => 'en',
    'genres' => 'Opinion',
    'publication_date' => '2025-01-29T08:30:00+00:00',
    'title' => 'Industry Expert Opinion on Market Trends',
    'keywords' => 'market, trends, analysis, opinion'
];

$sitemap->add(
    'https://example.com/news/market-trends-opinion',
    date('c'),
    '0.9',
    'always',
    [], // images
    'Industry Expert Opinion on Market Trends',
    [], // translations
    [], // videos
    [], // alternates
    $googleNews2
);

echo $sitemap->renderXml();
```

### News Sitemap from Database

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);

// Get recent news articles (last 2 days for Google News)
$stmt = $pdo->query("
    SELECT slug, title, updated_at, created_at, 
           news_keywords, news_genres, language
    FROM news_articles 
    WHERE published = 1 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
    ORDER BY created_at DESC
");

while ($article = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $googleNews = [
        'sitename' => 'Your News Site',
        'language' => $article['language'] ?: 'en',
        'publication_date' => date('c', strtotime($article['created_at'])),
        'title' => $article['title']
    ];
    
    if ($article['news_genres']) {
        $googleNews['genres'] = $article['news_genres'];
    }
    
    if ($article['news_keywords']) {
        $googleNews['keywords'] = $article['news_keywords'];
    }
    
    $sitemap->add(
        "https://example.com/news/{$article['slug']}",
        date('c', strtotime($article['updated_at'])),
        '1.0',
        'always',
        [], // images
        $article['title'],
        [], // translations
        [], // videos
        [], // alternates
        $googleNews
    );
}

echo $sitemap->renderXml();
```

## Alternate Media Sitemaps

### Mobile and Print Alternates

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Page with mobile and print alternates
$alternates = [
    [
        'media' => 'only screen and (max-width: 640px)',
        'url' => 'https://m.example.com/products/smartphone'
    ],
    [
        'media' => 'print',
        'url' => 'https://example.com/products/smartphone/print'
    ]
];

$sitemap->add(
    'https://example.com/products/smartphone',
    date('c'),
    '0.9',
    'weekly',
    [], // images
    'Latest Smartphone Model',
    [], // translations
    [], // videos
    $alternates // alternates
);

echo $sitemap->renderXml();
```

## Complete Rich Content Example

### All Features Combined

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();

// Page with all rich content types
$images = [
    [
        'url' => 'https://example.com/images/product-main.jpg',
        'title' => 'Premium Product Main Image',
        'caption' => 'Our flagship product in all its glory',
        'geo_location' => 'New York, USA'
    ],
    [
        'url' => 'https://example.com/images/product-detail.jpg',
        'title' => 'Product Detail View',
        'caption' => 'Detailed view showing key features'
    ]
];

$videos = [
    [
        'title' => 'Product Demo Video',
        'description' => 'Watch our product in action with this comprehensive demo',
        'content_loc' => 'https://example.com/videos/product-demo.mp4',
        'thumbnail_loc' => 'https://example.com/thumbs/product-demo.jpg',
        'duration' => 180,
        'publication_date' => date('c', strtotime('-1 week')),
        'family_friendly' => 'yes',
        'category' => 'Technology',
        'tags' => ['demo', 'product', 'tutorial']
    ]
];

$translations = [
    ['language' => 'fr', 'url' => 'https://example.com/fr/produits/premium'],
    ['language' => 'de', 'url' => 'https://example.com/de/produkte/premium'],
    ['language' => 'es', 'url' => 'https://example.com/es/productos/premium']
];

$alternates = [
    [
        'media' => 'only screen and (max-width: 640px)',
        'url' => 'https://m.example.com/products/premium'
    ]
];

$googleNews = [
    'sitename' => 'Tech News Today',
    'language' => 'en',
    'genres' => 'PressRelease',
    'publication_date' => date('c', strtotime('-2 hours')),
    'title' => 'Revolutionary Premium Product Launched',
    'keywords' => 'product launch, technology, innovation'
];

$sitemap->add(
    'https://example.com/products/premium',
    date('c'),
    '1.0',
    'weekly',
    $images,
    'Premium Product Launch',
    $translations,
    $videos,
    $alternates,
    $googleNews
);

echo $sitemap->renderXml();
```

## Complex Database Example

### E-commerce Product with All Features

```php
<?php
use Rumenx\Sitemap\Sitemap;

$sitemap = new Sitemap();
$pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);

// Complex query to get products with all related content
$stmt = $pdo->query("
    SELECT 
        p.id, p.slug, p.name, p.updated_at,
        GROUP_CONCAT(DISTINCT CONCAT(pi.url, '|', pi.title, '|', pi.caption) SEPARATOR ';') as images,
        GROUP_CONCAT(DISTINCT CONCAT(pv.url, '|', pv.title, '|', pv.description, '|', pv.duration) SEPARATOR ';') as videos,
        GROUP_CONCAT(DISTINCT CONCAT(pt.lang, '|', pt.slug) SEPARATOR ';') as translations
    FROM products p
    LEFT JOIN product_images pi ON p.id = pi.product_id
    LEFT JOIN product_videos pv ON p.id = pv.product_id
    LEFT JOIN product_translations pt ON p.translation_group_id = pt.translation_group_id AND pt.lang != 'en'
    WHERE p.active = 1 AND p.lang = 'en'
    GROUP BY p.id
    ORDER BY p.updated_at DESC
    LIMIT 1000
");

while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $images = [];
    $videos = [];
    $translations = [];
    
    // Parse images
    if ($product['images']) {
        foreach (explode(';', $product['images']) as $imageData) {
            $parts = explode('|', $imageData);
            if (count($parts) >= 2) {
                $image = [
                    'url' => $parts[0],
                    'title' => $parts[1]
                ];
                if (!empty($parts[2])) $image['caption'] = $parts[2];
                $images[] = $image;
            }
        }
    }
    
    // Parse videos
    if ($product['videos']) {
        foreach (explode(';', $product['videos']) as $videoData) {
            $parts = explode('|', $videoData);
            if (count($parts) >= 3) {
                $video = [
                    'content_loc' => $parts[0],
                    'title' => $parts[1],
                    'description' => $parts[2],
                    'family_friendly' => 'yes'
                ];
                if (!empty($parts[3])) $video['duration'] = (int)$parts[3];
                $videos[] = $video;
            }
        }
    }
    
    // Parse translations
    if ($product['translations']) {
        foreach (explode(';', $product['translations']) as $transData) {
            $parts = explode('|', $transData);
            if (count($parts) >= 2) {
                $translations[] = [
                    'language' => $parts[0],
                    'url' => "https://example.com/{$parts[0]}/products/{$parts[1]}"
                ];
            }
        }
    }
    
    $sitemap->add(
        "https://example.com/products/{$product['slug']}",
        date('c', strtotime($product['updated_at'])),
        '0.9',
        'weekly',
        $images,
        $product['name'],
        $translations,
        $videos
    );
}

echo $sitemap->renderXml();
```

## Best Practices for Rich Content

### Optimization Tips

1. **Images**
   - Use high-quality images with descriptive titles
   - Include relevant captions and geo-location when applicable
   - Ensure image URLs are accessible and properly formatted

2. **Videos**
   - Provide accurate duration and publication dates
   - Use family-friendly ratings appropriately
   - Include relevant tags and categories

3. **Translations**
   - Use proper language codes (ISO 639-1)
   - Ensure translated URLs are accessible
   - Maintain consistent URL structure across languages

4. **Google News**
   - Only include articles from the last 2 days
   - Use appropriate genres and keywords
   - Ensure publication dates are accurate

5. **Performance**
   - Limit the number of images/videos per URL (recommended max: 1000 images, 32 videos)
   - Use database indexing for better query performance
   - Consider pagination for large datasets

## Next Steps

- Explore [Google News Examples](google-news.md) for news-specific sitemaps
- Check [E-commerce Examples](e-commerce.md) for product catalog optimization
- See [Multilingual Examples](multilingual.md) for advanced translation handling
- Learn about [Performance Optimization](memory-optimization.md) for large rich content datasets
