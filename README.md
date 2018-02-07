# [laravel-sitemap](https://laravelium.com) package

[![Latest Stable Version](https://poser.pugx.org/roumen/sitemap/version.png)](https://packagist.org/packages/roumen/sitemap) [![Total Downloads](https://poser.pugx.org/roumen/sitemap/d/total.png)](https://packagist.org/packages/roumen/sitemap) [![Build Status](https://travis-ci.org/Laravelium/laravel-sitemap.png?branch=master)](https://travis-ci.org/Laravelium/laravel-sitemap) [![License](https://poser.pugx.org/roumen/sitemap/license.png)](https://packagist.org/packages/roumen/sitemap)

A not so simple sitemap generator for Laravel 5.


## Notes

Branch dev-master is for development and is UNSTABLE!

## Installation

Run the following command and provide the latest stable version (e.g v2.8.1) :

```bash
composer require roumen/sitemap
```

or add the following to your `composer.json` file :

#### For Laravel 5.6
```json
"roumen/sitemap": "2.8.*"
```

#### For Laravel 5.5
```json
"roumen/sitemap": "2.7.*"
```

#### For Laravel 5.4 and lower
```json
"roumen/sitemap": "2.6.*"
```

If you are using laravel 5.5 or higher you can skip the service provider registration!

#### for Laravel 5.4 and lower register this service provider with Laravel :
```php
Roumen\Sitemap\SitemapServiceProvider::class,
```

Publish needed assets (styles, views, config files) :

```bash
php artisan vendor:publish --provider="Roumen\Sitemap\SitemapServiceProvider"
```
*Note:* Composer won't update them after `composer update`, you'll need to do it manually!

## Examples

- [How to generate dynamic sitemap (with optional caching)](https://github.com/Laravelium/laravel-sitemap/wiki/Dynamic-sitemap)

- [How to generate BIG sitemaps (with more than 1M items)](https://github.com/Laravelium/laravel-sitemap/wiki/Generate-BIG-sitemaps)

- [How to generate sitemap to a file](https://github.com/Laravelium/laravel-sitemap/wiki/Generate-sitemap)

- [How to use multiple sitemaps with sitemap index](https://github.com/Laravelium/laravel-sitemap/wiki/Sitemap-index)

and more in the [Wiki](https://github.com/Laravelium/laravel-sitemap/wiki).
