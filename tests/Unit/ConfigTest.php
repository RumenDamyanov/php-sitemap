<?php

/**
 * Unit tests for the configuration handling in the sitemap package.
 */

test('config file returns expected array structure', function () {
    $config = include __DIR__ . '/../../src/Config/config.php';
    expect($config)->toBeArray();
    expect($config)->toHaveKeys([
        'use_cache', 'cache_key', 'cache_duration', 'escaping', 'use_limit_size', 'max_size', 'use_styles', 'styles_location', 'use_gzip'
    ]);
});

test('config escaping is boolean', function () {
    $config = include __DIR__ . '/../../src/Config/config.php';
    expect(is_bool($config['escaping']))->toBeTrue();
});
