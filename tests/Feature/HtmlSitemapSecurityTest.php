<?php

/**
 * The HTML view used to place loc in a quoted href after quote-blind ENT_XML1 escaping.
 */

test('html sitemap encodes quotes in loc and does not use a javascript url as href', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->add(
        'https://example.com/" onmouseover="alert(document.domain)',
        date('c'),
        '1.0',
        'daily',
        [],
        'Quoted'
    );
    $sitemap->add('javascript:alert(document.domain)', date('c'), '1.0', 'daily');
    $sitemap->add('https://example.com/?a=1&b=2', date('c'), '1.0', 'daily', [], 'Query');

    $html = $sitemap->render('html');

    expect($html)->toContain('href="https://example.com/&quot; onmouseover=&quot;alert(document.domain)"');
    expect($html)->not->toContain('href="https://example.com/"');
    expect($html)->not->toContain('href="javascript:');
    expect($html)->toContain('href="#"');
    expect($html)->toContain('href="https://example.com/?a=1&amp;b=2"');
    expect($html)->not->toContain('&amp;amp;');
});

test('html sitemap still encodes loc when model escaping is disabled', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap(['escaping' => false]);
    $sitemap->add('https://example.com/<script>', date('c'), '1.0', 'daily');

    $html = $sitemap->render('html');

    expect($html)->toContain('href="https://example.com/&lt;script&gt;"');
    expect($html)->not->toContain('<script>');
});
