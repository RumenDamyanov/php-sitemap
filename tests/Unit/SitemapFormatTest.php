<?php

use Rumenx\Sitemap\SitemapFormat;

test('SitemapFormat lists packaged render formats', function () {
    expect(SitemapFormat::ALL)->toContain(SitemapFormat::XML)
        ->and(SitemapFormat::ALL)->toContain(SitemapFormat::GOOGLE_NEWS)
        ->and(SitemapFormat::ALL)->toContain(SitemapFormat::ROR_RSS);
});

test('SitemapFormat::isRenderable accepts packaged names only', function () {
    expect(SitemapFormat::isRenderable('xml'))->toBeTrue();
    expect(SitemapFormat::isRenderable('html'))->toBeTrue();
    expect(SitemapFormat::isRenderable('rss'))->toBeFalse();
    expect(SitemapFormat::isRenderable('../Config/config'))->toBeFalse();
});

test('SitemapFormat::isConfigurable keeps historical rss/rdf aliases', function () {
    expect(SitemapFormat::isConfigurable('rss'))->toBeTrue();
    expect(SitemapFormat::isConfigurable('rdf'))->toBeTrue();
    expect(SitemapFormat::isConfigurable('xml-mobile'))->toBeTrue();
    expect(SitemapFormat::isConfigurable('sitemapindex'))->toBeTrue();
    expect(SitemapFormat::isConfigurable('json'))->toBeFalse();
});
