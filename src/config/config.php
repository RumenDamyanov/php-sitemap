<?php

/* Simple configuration file for Laravel Sitemap package */
return [
	'use_cache'			=> 	false,
	'cache_key'			=> 	'laravel-sitemap.' . config('app.url'),
	'cache_duration'	=> 	3600,
	'escaping'			=> 	true,
	'use_limit_size'	=> 	false,
	'use_styles'		=> 	true,
];