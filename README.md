# **[Laravelium Sitemap](https://laravelium.com) package**

[![License](https://poser.pugx.org/laravelium/sitemap/license)](https://packagist.org/packages/laravelium/sitemap) [![Build Status](https://travis-ci.org/Laravelium/laravel-sitemap.svg?branch=master)](https://travis-ci.org/Laravelium/laravel-sitemap) [![Coverage Status](https://coveralls.io/repos/github/Laravelium/laravel-sitemap/badge.svg?branch=master)](https://coveralls.io/github/Laravelium/laravel-sitemap?branch=master) [![Style Status](https://github.styleci.io/repos/10392044/shield?style=normal&branch=master)](https://github.styleci.io/repos/10392044) [![Latest Stable Version](https://poser.pugx.org/laravelium/sitemap/v/stable)](https://packagist.org/packages/laravelium/sitemap) [![Total Downloads](https://poser.pugx.org/laravelium/sitemap/downloads)](https://packagist.org/packages/laravelium/sitemap)

*Laravelium Sitemap generator for Laravel.*

## Notes

- Dev Branches are for development and are **UNSTABLE** (*use on your own risk*)!

## Installation

Run the following command and provide the latest stable version (e.g v7.0.\*) :

```bash
composer require laravelium/sitemap
```

*or add the following to your `composer.json` file :*

#### For Laravel 7.0
```json
"laravelium/sitemap": "7.0.*"
```
(development branch)
```json
"laravelium/sitemap": "7.0.x-dev"
```

#### For Laravel 6.0
```json
"laravelium/sitemap": "6.0.*"
```
(development branch)
```json
"laravelium/sitemap": "6.0.x-dev"
```

#### For Laravel 5.8
```json
"laravelium/sitemap": "3.1.*"
```
(development branch)
```json
"laravelium/sitemap": "3.1.x-dev"
```

#### For Laravel 5.7
```json
"laravelium/sitemap": "3.0.*"
```
(development branch)
```json
"laravelium/sitemap": "3.0.x-dev"
```

#### For Laravel 5.6
```json
"laravelium/sitemap": "2.8.*"
```
(development branch)
```json
"laravelium/sitemap": "2.8.x-dev"
```

#### For Laravel 5.5
```json
"laravelium/sitemap": "2.7.*"
```
(development branch)
```json
"laravelium/sitemap": "2.7.x-dev"
```

*Publish needed assets (styles, views, config files) :*

```bash
php artisan vendor:publish --provider="Laravelium\Sitemap\SitemapServiceProvider"
```
**Note:** *Composer won't update them after `composer update`, you'll need to do it manually!*

## Examples

- [How to generate dynamic sitemap (with optional caching)](https://github.com/Laravelium/laravel-sitemap/wiki/Dynamic-sitemap)

- [How to generate BIG sitemaps (with more than 1M items)](https://github.com/Laravelium/laravel-sitemap/wiki/Sitemap-index)

- [How to generate sitemap to a file](https://github.com/Laravelium/laravel-sitemap/wiki/Generate-sitemap)

- [How to use multiple sitemaps with sitemap index](https://github.com/Laravelium/laravel-sitemap/wiki/Generate-BIG-sitemaps)

and more in the [Wiki](https://github.com/Laravelium/laravel-sitemap/wiki).

## Contribution guidelines

Before submiting new merge request or creating new issue, please read [contribution guidelines](https://gitlab.com/Laravelium/Sitemap/blob/master/CONTRIBUTING.md).

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).