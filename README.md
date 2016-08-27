# [laravel-sitemap](http://roumen.it/projects/laravel-sitemap) package

[![Latest Stable Version](https://poser.pugx.org/roumen/sitemap/version.png)](https://packagist.org/packages/roumen/sitemap) [![Total Downloads](https://poser.pugx.org/roumen/sitemap/d/total.png)](https://packagist.org/packages/roumen/sitemap) [![Build Status](https://travis-ci.org/RoumenDamianoff/laravel-sitemap.png?branch=master)](https://travis-ci.org/RoumenDamianoff/laravel-sitemap) [![License](https://poser.pugx.org/roumen/sitemap/license.png)](https://packagist.org/packages/roumen/sitemap)

A not so simple sitemap generator for Laravel 5.


## Notes

Branch dev-master is for development and is UNSTABLE!

## Installation

Run the following command and provide the latest stable version (e.g v2.6.4) :

```bash
composer require roumen/sitemap
```

or add the following to your `composer.json` file :

```json
"roumen/sitemap": "2.6.*"
```

Then register this service provider with Laravel :

```php
'Roumen\Sitemap\SitemapServiceProvider',
```

#### for Laravel 5.2/5.3 service provider should be :

```php
Roumen\Sitemap\SitemapServiceProvider::class,
```

Publish needed assets (styles, views, config files) :

```bash
php artisan vendor:publish --provider="Roumen\Sitemap\SitemapServiceProvider"
```
*Note:* Composer won't update them after `composer update`, you'll need to do it manually!

## Examples

- [How to generate dynamic sitemap (with optional caching)](https://github.com/RoumenDamianoff/laravel-sitemap/wiki/Dynamic-sitemap)

- [How to generate BIG sitemaps (with more than 1M items)](https://github.com/RoumenDamianoff/laravel-sitemap/wiki/Generate-BIG-sitemaps)

- [How to generate sitemap to a file](https://github.com/RoumenDamianoff/laravel-sitemap/wiki/Generate-sitemap)

- [How to use multiple sitemaps with sitemap index](https://github.com/RoumenDamianoff/laravel-sitemap/wiki/Sitemap-index)

and more in the [Wiki](https://github.com/RoumenDamianoff/laravel-sitemap/wiki).
