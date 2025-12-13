<?php

/**
 * Unit tests for the Model class, covering item and sitemap management.
 */

test('Model class can be instantiated', function () {
    $model = new \Rumenx\Sitemap\Model();
    expect($model)->toBeInstanceOf(\Rumenx\Sitemap\Model::class);
});

test('Model can add and get items', function () {
    $model = new \Rumenx\Sitemap\Model();
    $model->addItem(['loc' => '/foo']);
    expect($model->getItems())->toBe([['loc' => '/foo']]);
});

test('Model can add and get sitemaps', function () {
    $model = new \Rumenx\Sitemap\Model();
    $model->addSitemap(['loc' => '/bar']);
    expect($model->getSitemaps())->toBe([['loc' => '/bar']]);
});

test('Model can reset sitemaps', function () {
    $model = new \Rumenx\Sitemap\Model();
    $model->addSitemap(['loc' => '/bar']);
    $model->resetSitemaps([['loc' => '/baz']]);
    expect($model->getSitemaps())->toBe([['loc' => '/baz']]);
});

test('Model respects escaping config', function () {
    $model = new \Rumenx\Sitemap\Model(['escaping' => false]);
    expect($model->getEscaping())->toBe(false);
    $model2 = new \Rumenx\Sitemap\Model(['escaping' => true]);
    expect($model2->getEscaping())->toBe(true);
});
