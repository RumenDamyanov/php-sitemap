<?php

namespace Rumenx\Sitemap\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rumenx\Sitemap\Validation\SitemapValidator;

class SitemapValidatorTest extends TestCase
{
    public function test_validate_url_passes_with_valid_http_url(): void
    {
        $this->assertTrue(SitemapValidator::validateUrl('http://example.com'));
        $this->assertTrue(SitemapValidator::validateUrl('https://example.com/page'));
    }

    public function test_validate_url_throws_exception_for_empty_url(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URL cannot be empty');
        SitemapValidator::validateUrl('');
    }

    public function test_validate_url_throws_exception_for_invalid_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format');
        SitemapValidator::validateUrl('not-a-url');
    }

    public function test_validate_url_throws_exception_for_invalid_scheme(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URL must use http or https scheme');
        SitemapValidator::validateUrl('ftp://example.com');
    }

    public function test_validate_priority_passes_with_valid_values(): void
    {
        $this->assertTrue(SitemapValidator::validatePriority('0.5'));
        $this->assertTrue(SitemapValidator::validatePriority('0.0'));
        $this->assertTrue(SitemapValidator::validatePriority('1.0'));
        $this->assertTrue(SitemapValidator::validatePriority(null));
    }

    public function test_validate_priority_throws_exception_for_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Priority must be between 0.0 and 1.0');
        SitemapValidator::validatePriority('-0.1');
    }

    public function test_validate_priority_throws_exception_for_above_one(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Priority must be between 0.0 and 1.0');
        SitemapValidator::validatePriority('1.5');
    }

    public function test_validate_frequency_passes_with_valid_values(): void
    {
        $validFrequencies = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];

        foreach ($validFrequencies as $freq) {
            $this->assertTrue(SitemapValidator::validateFrequency($freq));
        }

        $this->assertTrue(SitemapValidator::validateFrequency(null));
    }

    public function test_validate_frequency_throws_exception_for_invalid_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid frequency');
        SitemapValidator::validateFrequency('sometimes');
    }

    public function test_validate_lastmod_passes_with_valid_dates(): void
    {
        $this->assertTrue(SitemapValidator::validateLastmod('2023-12-01'));
        $this->assertTrue(SitemapValidator::validateLastmod('2023-12-01T10:30:00+00:00'));
        $this->assertTrue(SitemapValidator::validateLastmod(null));
    }

    public function test_validate_lastmod_throws_exception_for_invalid_date(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format');
        SitemapValidator::validateLastmod('not-a-date');
    }

    public function test_validate_image_passes_with_valid_data(): void
    {
        $image = ['url' => 'https://example.com/image.jpg'];
        $this->assertTrue(SitemapValidator::validateImage($image));
    }

    public function test_validate_image_throws_exception_for_missing_url(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image must have a URL');
        SitemapValidator::validateImage(['title' => 'Image']);
    }

    public function test_validate_image_throws_exception_for_invalid_url(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format');
        SitemapValidator::validateImage(['url' => 'not-a-url']);
    }

    public function test_validate_item_passes_with_valid_data(): void
    {
        $this->assertTrue(SitemapValidator::validateItem(
            'https://example.com',
            '2023-12-01',
            '0.8',
            'daily',
            [
                ['url' => 'https://example.com/image.jpg'],
            ]
        ));
    }

    public function test_validate_item_passes_with_minimal_data(): void
    {
        $this->assertTrue(SitemapValidator::validateItem('https://example.com'));
    }

    public function test_validate_item_throws_exception_for_invalid_url(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format');
        SitemapValidator::validateItem('not-a-url');
    }

    public function test_validate_item_throws_exception_for_invalid_priority(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Priority must be between 0.0 and 1.0');
        SitemapValidator::validateItem('https://example.com', null, '2.0');
    }

    public function test_validate_item_throws_exception_for_invalid_frequency(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid frequency');
        SitemapValidator::validateItem('https://example.com', null, null, 'sometimes');
    }

    public function test_validate_item_throws_exception_for_invalid_lastmod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format');
        SitemapValidator::validateItem('https://example.com', 'not-a-date');
    }

    public function test_validate_item_throws_exception_for_invalid_image(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format');
        SitemapValidator::validateItem(
            'https://example.com',
            null,
            null,
            null,
            [['url' => 'not-a-url']]
        );
    }
}
