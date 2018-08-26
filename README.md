# [Laravelium-Sitemap](https://laravelium.com) package

[![Latest Stable Version](https://poser.pugx.org/laravelium/sitemap/version.png)](https://packagist.org/packages/laravelium/sitemap) [![Total Downloads](https://poser.pugx.org/roumen/sitemap/d/total.png)](https://packagist.org/packages/laravelium/sitemap) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://gitlab.com/Laravelium/Sitemap/blob/master/LICENSE) [![Contributing](https://img.shields.io/badge/PRs-welcome-blue.svg)](https://gitlab.com/Laravelium/Sitemap/blob/master/CONTRIBUTING.md)



A not so simple sitemap generator for Laravel 5.


## Notes

Branch dev-master is for development and is UNSTABLE!

## Installation

Run the following command and provide the latest stable version (e.g v3.0.0) :

```bash
composer require laravelium/sitemap
```

or add the following to your `composer.json` file :

#### For Laravel 5.7
```json
"laravelium/sitemap": "2.9.*"
```

#### For Laravel 5.6
```json
"laravelium/sitemap": "2.8.*"
```

#### For Laravel 5.5
```json
"laravelium/sitemap": "2.7.*"
```

Publish needed assets (styles, views, config files) :

```bash
php artisan vendor:publish --provider="Laravelium\Sitemap\SitemapServiceProvider"
```
*Note:* Composer won't update them after `composer update`, you'll need to do it manually!

## Examples

- [How to generate dynamic sitemap (with optional caching)](https://gitlab.com/Laravelium/Sitemap/wikis/Dynamic-sitemap)

- [How to generate BIG sitemaps (with more than 1M items)](https://gitlab.com/Laravelium/Sitemap/wikis/Generate-BIG-sitemaps)

- [How to generate sitemap to a file](https://gitlab.com/Laravelium/Sitemap/wikis/Generate-sitemap)

- [How to use multiple sitemaps with sitemap index](https://gitlab.com/Laravelium/Sitemap/wikis/Sitemap-index)

and more in the [Wiki](https://gitlab.com/Laravelium/Sitemap/wikis/home).
