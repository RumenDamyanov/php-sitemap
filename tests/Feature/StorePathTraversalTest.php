<?php

/**
 * Regression tests for GHSA-r934-gc26-cjrx:
 * Path traversal in Sitemap::store() via unsanitized $filename.
 */

test('store() writes inside the target directory for a simple filename', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->add('https://example.com/page1');

    $tempDir = sys_get_temp_dir() . '/php-sitemap-store-' . uniqid();
    mkdir($tempDir, 0755, true);

    expect($sitemap->store('xml', 'sitemap', $tempDir))->toBeTrue();
    expect(file_exists($tempDir . '/sitemap.xml'))->toBeTrue();

    unlink($tempDir . '/sitemap.xml');
    rmdir($tempDir);
});

test('store() rejects path traversal in filename (GHSA-r934-gc26-cjrx)', function (string $filename) {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->add('https://example.com/page1');

    $tempDir = sys_get_temp_dir() . '/php-sitemap-store-' . uniqid();
    mkdir($tempDir, 0755, true);

    $escaped = dirname($tempDir) . '/traversed_sitemap.xml';
    if (file_exists($escaped)) {
        unlink($escaped);
    }

    expect(fn () => $sitemap->store('xml', $filename, $tempDir))
        ->toThrow(\InvalidArgumentException::class);

    expect(file_exists($escaped))->toBeFalse();
    expect(file_exists($tempDir . '/traversed_sitemap.xml'))->toBeFalse();
    expect(glob($tempDir . '/*'))->toBe([]);

    rmdir($tempDir);
})->with([
    '../../../tmp/traversed_sitemap',
    '..\\..\\traversed_sitemap',
    '/tmp/traversed_sitemap',
    'nested/traversed_sitemap',
    '..',
    '.',
    '',
    "traversed\0sitemap",
]);
