<?php

/* Simple configuration file for Rumenx php-sitemap package */
return [
    'use_cache' => false,
    'cache_key' => 'php-sitemap.' . (function_exists('config') ? config('app.url') : ''),
    'cache_duration' => 3600,
    'escaping' => true,
    'use_limit_size' => false,
    'max_size' => null,
    'use_styles' => true,
    'styles_location' => '/vendor/php-sitemap/styles/',
    'use_gzip' => false
];
