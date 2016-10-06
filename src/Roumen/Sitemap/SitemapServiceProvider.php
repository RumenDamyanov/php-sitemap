<?php namespace Roumen\Sitemap;

use Illuminate\Support\ServiceProvider;
use Roumen\Sitemap\Sitemap;

class SitemapServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../../views', 'sitemap');

        $config_file = __DIR__ . '/../../config/config.php';

        $this->mergeConfigFrom($config_file, 'sitemap');

        $this->publishes([
            $config_file => config_path('sitemap.php')
        ], 'config');

        $this->publishes([
            __DIR__ . '/../../views' => base_path('resources/views/vendor/sitemap')
        ], 'views');

        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/sitemap')
        ], 'public');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('sitemap', function ()
        {
            $config = config('sitemap');

            return new Sitemap($config);
        });

        $this->app->alias('sitemap', Sitemap::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['sitemap', Sitemap::class];
    }
}
