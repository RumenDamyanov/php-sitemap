# [laravel-sitemap](http://roumen.it/projects/laravel-sitemap) package

[![Latest Stable Version](https://poser.pugx.org/roumen/sitemap/version.png)](https://packagist.org/packages/roumen/sitemap) [![Total Downloads](https://poser.pugx.org/roumen/sitemap/d/total.png)](https://packagist.org/packages/roumen/sitemap) [![Build Status](https://travis-ci.org/RoumenDamianoff/laravel-sitemap.png?branch=master)](https://travis-ci.org/RoumenDamianoff/laravel-sitemap) [![License](https://poser.pugx.org/roumen/sitemap/license.png)](https://packagist.org/packages/roumen/sitemap)

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

Publish configuration file. (OPTIONAL)

    php artisan config:publish roumen/sitemap


## Example: Dynamic sitemap

```php
Route::get('sitemap', function(){

    $sitemap = App::make("sitemap");

    // set cache (key (string), duration in minutes (Carbon|Datetime|int), turn on/off (boolean))
    $sitemap->setCache('Laravel.Sitemap.MySitemap.', 3600);

    // set item's url, date, priority, freq
    $sitemap->add(URL::to(), '2012-08-25T20:10:00+02:00', '1.0', 'daily');
    $sitemap->add(URL::to('page'), '2012-08-26T12:30:00+02:00', '0.9', 'monthly');

    $posts = DB::table('posts')->orderBy('created_at', 'desc')->get();
    foreach ($posts as $post)
    {
        $sitemap->add($post->slug, $post->modified, $post->priority, $post->freq);
    }

    // show your sitemap (options: 'xml' (default), 'html', 'txt', 'ror-rss', 'ror-rdf')
    return $sitemap->render('xml');

});
```
