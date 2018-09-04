# **[Laravelium Sitemap](https://laravelium.com) package**

*Laravelium Sitemap generator for Laravel.*

## Notes

- Dev Branches are for development and are **UNSTABLE** (*use on your own risk*)!

## Installation

Run the following command and provide the latest stable version (e.g v3.0.\*) :

```bash
composer require laravelium/sitemap
```

*or add the following to your `composer.json` file :*

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

- [How to generate dynamic sitemap (with optional caching)](https://gitlab.com/Laravelium/Sitemap/wikis/Dynamic-sitemap)

- [How to generate BIG sitemaps (with more than 1M items)](https://gitlab.com/Laravelium/Sitemap/wikis/Generate-BIG-sitemaps)

- [How to generate sitemap to a file](https://gitlab.com/Laravelium/Sitemap/wikis/Generate-sitemap)

- [How to use multiple sitemaps with sitemap index](https://gitlab.com/Laravelium/Sitemap/wikis/Sitemap-index)

and more in the [Wiki](https://gitlab.com/Laravelium/Sitemap/wikis/home).

## Contribution guidelines

Before submiting new merge request or creating new issue, please read [contribution guidelines](https://gitlab.com/Laravelium/Sitemap/blob/master/CONTRIBUTING.md).

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).