# [laravel-sitemap](http://roumen.it/projects/laravel-sitemap) package

[![Latest Stable Version](https://poser.pugx.org/roumen/sitemap/version.png)](https://packagist.org/packages/roumen/sitemap) [![Total Downloads](https://poser.pugx.org/roumen/sitemap/d/total.png)](https://packagist.org/packages/roumen/sitemap) [![Build Status](https://travis-ci.org/RoumenDamianoff/laravel-sitemap.png?branch=master)](https://travis-ci.org/RoumenDamianoff/laravel-sitemap) [![License](https://poser.pugx.org/roumen/sitemap/license.png)](https://packagist.org/packages/roumen/sitemap)

A simple sitemap generator for Laravel 5.

## Notes

Latest supported version for Laravel 4 is 2.4.* (e.g v2.4.21)

Branch dev-master is for development and is unstable

## Installation

Run the following command and provide the latest stable version (e.g v2.5.9) :

```bash
composer require roumen/sitemap
```

or add the following to your `composer.json` file :

```json
"roumen/sitemap": "2.5.*"
```

Then register this service provider with Laravel :

```php
'Roumen\Sitemap\SitemapServiceProvider',
```

Publish configuration file (OPTIONAL) :

```bash
php artisan vendor:publish
```

## Examples

[How to generate dynamic sitemap (with optional caching)](https://github.com/RoumenDamianoff/laravel-sitemap/wiki/Dynamic-sitemap)

[How to use multiple sitemaps with sitemap index](https://github.com/RoumenDamianoff/laravel-sitemap/wiki/Sitemap-index)

[How to generate simple sitemap file](https://github.com/RoumenDamianoff/laravel-sitemap/wiki/Generate-sitemap)

[How to generate BIG sitemaps (with more than 1M items)](https://github.com/RoumenDamianoff/laravel-sitemap/wiki/Generate-BIG-sitemaps)

and more in the [Wiki](https://github.com/RoumenDamianoff/laravel-sitemap/wiki)
