# [laravel-sitemap](http://roumen.it/projects/laravel-sitemap) package

[![Latest Stable Version](https://poser.pugx.org/roumen/sitemap/version.png)](https://packagist.org/packages/roumen/sitemap) [![Total Downloads](https://poser.pugx.org/roumen/sitemap/d/total.png)](https://packagist.org/packages/roumen/sitemap) [![Build Status](https://travis-ci.org/RoumenDamianoff/laravel-sitemap.png?branch=master)](https://travis-ci.org/RoumenDamianoff/laravel-sitemap) [![License](https://poser.pugx.org/roumen/sitemap/license.png)](https://packagist.org/packages/roumen/sitemap)

A simple sitemap generator for Laravel 4.


## Installation

Run the following command:

```bash
$ composer require roument/sitemap
```

Then register this service provider with Laravel :

```php
'Roumen\Sitemap\SitemapServiceProvider',
```

Publish configuration file. (OPTIONAL)

    php artisan config:publish roumen/sitemap


## Examples

[How to generate dynamic sitemap (with optional caching)](https://github.com/RoumenDamianoff/laravel-sitemap/wiki/Dynamic-sitemap)

[How to use multiple sitemaps with sitemap index](https://github.com/RoumenDamianoff/laravel-sitemap/wiki/Sitemap-index)

[How to generate sitemap file](https://github.com/RoumenDamianoff/laravel-sitemap/wiki/Generate-sitemap)

and more in the [Wiki](https://github.com/RoumenDamianoff/laravel-sitemap/wiki)
