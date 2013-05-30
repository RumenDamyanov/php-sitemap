# [laravel4-sitemap](http://roumen.me/projects/laravel4-sitemap)

A simple sitemap generator for Laravel 4.


## Installation

Add the following to your `composer.json` file :

```json
"roumen/sitemap": "dev-master"
```

Then register this service provider with Laravel :

```php
'Roumen\Sitemap\SitemapServiceProvider',
```

## Example: Dynamic sitemap

```php
Route::get('sitemap', function(){

    $sitemap = App::make("sitemap");

    // set item's url, date, priority, freq
    $sitemap->add(URL::to(), '2012-08-25T20:10:00+02:00', '1.0', 'daily');
    $sitemap->add(URL::to('page'), '2012-08-26T12:30:00+02:00', '0.9', 'monthly');

    $posts = DB::table('posts')->order_by('created', 'desc')->get();
    foreach ($posts as $post)
    {
        $sitemap->add($post->slug, $post->modified, $post->priority, $post->freq);
    }

    // show your sitemap (options: 'xml' (default), 'html', 'txt', 'ror-rss', 'ror-rdf')
    return $sitemap->render('xml');

});
```