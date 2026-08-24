<?php

/**
 * Regression tests for GHSA-3j73-g385-2pc5:
 * XML injection via unescaped Google News fields and TypeError (DoS) in render('google-news').
 */

test('google-news render escapes injected XML in googlenews fields', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->addItem([
        'loc' => 'https://news.example/art1',
        'title' => 'Breaking',
        'googlenews' => [
            'sitename' => 'The Example News',
            'language' => 'en</news:language><evil lang="1"/>',
            'access' => 'Subscription</news:access><evil access="1"/>',
            'publication_date' => date('c'),
            'genres' => ['PressRelease</news:genres><evil g="1"/>'],
            'keywords' => ['kw</news:keywords><evil k="1"/>'],
            'stock_tickers' => ['ST</news:stock_tickers><evil st="1"/>'],
        ],
    ]);

    $xml = $sitemap->render('google-news');

    expect($xml)->not()->toContain('<evil');
    expect($xml)->toContain('en&lt;/news:language&gt;&lt;evil lang=&quot;1&quot;/&gt;');
    expect($xml)->toContain('Subscription&lt;/news:access&gt;&lt;evil access=&quot;1&quot;/&gt;');
    expect($xml)->toContain('PressRelease&lt;/news:genres&gt;&lt;evil g=&quot;1&quot;/&gt;');
    expect($xml)->toContain('kw&lt;/news:keywords&gt;&lt;evil k=&quot;1&quot;/&gt;');
    expect($xml)->toContain('ST&lt;/news:stock_tickers&gt;&lt;evil st=&quot;1&quot;/&gt;');
});

test('google-news render does not TypeError when list fields are strings', function () {
    $sitemap = new \Rumenx\Sitemap\Sitemap();
    $sitemap->addItem([
        'loc' => 'https://news.example/art1',
        'title' => 'Breaking',
        'googlenews' => [
            'sitename' => 'The Example News',
            'language' => 'en',
            'publication_date' => date('c'),
            'genres' => 'PressRelease',
            'keywords' => 'economy, policy',
            'stock_tickers' => 'EXMPL:US',
        ],
    ]);

    $xml = $sitemap->render('google-news');

    expect($xml)->toContain('<news:genres>PressRelease</news:genres>');
    expect($xml)->toContain('<news:keywords>economy, policy</news:keywords>');
    expect($xml)->toContain('<news:stock_tickers>EXMPL:US</news:stock_tickers>');
});
