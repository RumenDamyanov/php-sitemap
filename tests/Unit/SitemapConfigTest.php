<?php

namespace Rumenx\Sitemap\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rumenx\Sitemap\Config\SitemapConfig;

class SitemapConfigTest extends TestCase
{
    public function test_can_create_with_defaults(): void
    {
        $config = new SitemapConfig();

        $this->assertTrue($config->isEscaping());
        $this->assertFalse($config->isCacheEnabled());
        $this->assertNull($config->getCachePath());
        $this->assertFalse($config->isLimitSizeEnabled());
        $this->assertEquals(10485760, $config->getMaxSize());
        $this->assertFalse($config->isGzipEnabled());
        $this->assertTrue($config->areStylesEnabled());
        $this->assertNull($config->getDomain());
        $this->assertFalse($config->isStrictMode());
        $this->assertEquals('xml', $config->getDefaultFormat());
    }

    public function test_can_create_with_custom_values(): void
    {
        $config = new SitemapConfig(
            escaping: false,
            useCache: true,
            cachePath: '/tmp/cache',
            useLimitSize: true,
            maxSize: 5000000,
            useGzip: true,
            useStyles: false,
            domain: 'https://example.com',
            strictMode: true,
            defaultFormat: 'txt'
        );

        $this->assertFalse($config->isEscaping());
        $this->assertTrue($config->isCacheEnabled());
        $this->assertEquals('/tmp/cache', $config->getCachePath());
        $this->assertTrue($config->isLimitSizeEnabled());
        $this->assertEquals(5000000, $config->getMaxSize());
        $this->assertTrue($config->isGzipEnabled());
        $this->assertFalse($config->areStylesEnabled());
        $this->assertEquals('https://example.com', $config->getDomain());
        $this->assertTrue($config->isStrictMode());
        $this->assertEquals('txt', $config->getDefaultFormat());
    }

    public function test_can_create_from_array(): void
    {
        $config = SitemapConfig::fromArray([
            'escaping' => false,
            'use_cache' => true,
            'cache_path' => '/tmp/cache',
            'strict_mode' => true,
        ]);

        $this->assertFalse($config->isEscaping());
        $this->assertTrue($config->isCacheEnabled());
        $this->assertEquals('/tmp/cache', $config->getCachePath());
        $this->assertTrue($config->isStrictMode());
    }

    public function test_can_export_to_array(): void
    {
        $config = new SitemapConfig(
            escaping: false,
            useCache: true,
            strictMode: true
        );

        $array = $config->toArray();

        $this->assertEquals(false, $array['escaping']);
        $this->assertEquals(true, $array['use_cache']);
        $this->assertEquals(true, $array['strict_mode']);
        $this->assertArrayHasKey('max_size', $array);
        $this->assertArrayHasKey('default_format', $array);
    }

    public function test_can_set_escaping(): void
    {
        $config = new SitemapConfig();
        $result = $config->setEscaping(false);

        $this->assertSame($config, $result);
        $this->assertFalse($config->isEscaping());
    }

    public function test_can_set_use_cache(): void
    {
        $config = new SitemapConfig();
        $result = $config->setUseCache(true);

        $this->assertSame($config, $result);
        $this->assertTrue($config->isCacheEnabled());
    }

    public function test_can_set_cache_path(): void
    {
        $config = new SitemapConfig();
        $result = $config->setCachePath('/custom/path');

        $this->assertSame($config, $result);
        $this->assertEquals('/custom/path', $config->getCachePath());
    }

    public function test_can_set_use_limit_size(): void
    {
        $config = new SitemapConfig();
        $result = $config->setUseLimitSize(true);

        $this->assertSame($config, $result);
        $this->assertTrue($config->isLimitSizeEnabled());
    }

    public function test_can_set_max_size(): void
    {
        $config = new SitemapConfig();
        $result = $config->setMaxSize(5000000);

        $this->assertSame($config, $result);
        $this->assertEquals(5000000, $config->getMaxSize());
    }

    public function test_throws_exception_for_invalid_max_size(): void
    {
        $config = new SitemapConfig();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('maxSize must be greater than 0');
        $config->setMaxSize(0);
    }

    public function test_throws_exception_for_negative_max_size(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('maxSize must be greater than 0');
        new SitemapConfig(maxSize: -1);
    }

    public function test_can_set_use_gzip(): void
    {
        $config = new SitemapConfig();
        $result = $config->setUseGzip(true);

        $this->assertSame($config, $result);
        $this->assertTrue($config->isGzipEnabled());
    }

    public function test_can_set_use_styles(): void
    {
        $config = new SitemapConfig();
        $result = $config->setUseStyles(false);

        $this->assertSame($config, $result);
        $this->assertFalse($config->areStylesEnabled());
    }

    public function test_can_set_domain(): void
    {
        $config = new SitemapConfig();
        $result = $config->setDomain('https://example.com');

        $this->assertSame($config, $result);
        $this->assertEquals('https://example.com', $config->getDomain());
    }

    public function test_throws_exception_for_invalid_domain(): void
    {
        $config = new SitemapConfig();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid domain');
        $config->setDomain('not-a-url');
    }

    public function test_throws_exception_for_invalid_domain_in_constructor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid domain');
        new SitemapConfig(domain: 'not-a-url');
    }

    public function test_can_set_strict_mode(): void
    {
        $config = new SitemapConfig();
        $result = $config->setStrictMode(true);

        $this->assertSame($config, $result);
        $this->assertTrue($config->isStrictMode());
    }

    public function test_can_set_default_format(): void
    {
        $config = new SitemapConfig();
        $result = $config->setDefaultFormat('html');

        $this->assertSame($config, $result);
        $this->assertEquals('html', $config->getDefaultFormat());
    }

    public function test_throws_exception_for_invalid_default_format(): void
    {
        $config = new SitemapConfig();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid default format');
        $config->setDefaultFormat('invalid');
    }

    public function test_throws_exception_for_invalid_format_in_constructor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid default format');
        new SitemapConfig(defaultFormat: 'invalid');
    }

    public function test_fluent_interface_chaining(): void
    {
        $config = new SitemapConfig();
        $result = $config
            ->setEscaping(false)
            ->setUseCache(true)
            ->setCachePath('/tmp')
            ->setStrictMode(true)
            ->setDefaultFormat('txt');

        $this->assertSame($config, $result);
        $this->assertFalse($config->isEscaping());
        $this->assertTrue($config->isCacheEnabled());
        $this->assertEquals('/tmp', $config->getCachePath());
        $this->assertTrue($config->isStrictMode());
        $this->assertEquals('txt', $config->getDefaultFormat());
    }
}
