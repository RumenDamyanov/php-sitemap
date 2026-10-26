# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-10-26

### Security

- Allowlist `render()` / `store()` formats so `$format` cannot include files outside `src/views/` (for example `../Config/config`).
- Document that PHP2Shell ([CVE-2026-109618](https://www.cve.org/CVERecord?id=CVE-2026-109618), [CVE-2026-109619](https://www.cve.org/CVERecord?id=CVE-2026-109619)) is in the PHP engine: this package cannot patch it. Run PHP **8.3.36+**, **8.4.27+**, or **8.5.12+** ([PHP Foundation advisory](https://thephp.foundation/blog/2026/10/21/todays-php-security-release-and-how-were-strengthening-php-security/), [CISA alert](https://www.cisa.gov/news-events/alerts/2026/10/21/critical-vulnerabilities-php-require-immediate-patching)).

### Changed

- Require PHP **8.3+**. PHP 8.2 reaches upstream end of life on 31 December 2026; stay on 1.0.x if you must remain on 8.2 (and use **8.2.35+** until then).
- CI tests PHP 8.3, 8.4, and 8.5. Coverage is collected on 8.4 only.
- Upgrade dev tooling to Pest 4 and PHPUnit 12 so `composer update` can resolve on PHP 8.5.

## [1.0.4] - 2026-09-03

### Security

- Reject directory separators, NUL, `.`, and `..` in `Sitemap::store()` `$filename` so files cannot be written outside the intended directory ([GHSA-r934-gc26-cjrx](https://github.com/RumenDamyanov/php-sitemap/security/advisories/GHSA-r934-gc26-cjrx)). Nested output directories still belong in `$path`.

## [1.0.3] - 2026-08-24

### Security

- Escape Google News `language`, `access`, `genres`, `keywords`, and `stock_tickers` (and coerce list fields to arrays) so `render('google-news')` cannot inject XML or TypeError ([GHSA-3j73-g385-2pc5](https://github.com/RumenDamyanov/php-sitemap/security/advisories/GHSA-3j73-g385-2pc5)).

## [1.0.2] - 2025-12-13

### Added

- Input validation, type-safe `SitemapConfig`, and fluent `add()` / `store()` chaining.

## [1.0.1] - 2025-07-29

### Added

- Initial 1.x stable line of the framework-agnostic sitemap generator.

[unreleased]: https://github.com/RumenDamyanov/php-sitemap/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/RumenDamyanov/php-sitemap/releases/tag/v1.1.0
[1.0.4]: https://github.com/RumenDamyanov/php-sitemap/releases/tag/v1.0.4
[1.0.3]: https://github.com/RumenDamyanov/php-sitemap/releases/tag/v1.0.3
[1.0.2]: https://github.com/RumenDamyanov/php-sitemap/releases/tag/v1.0.2
[1.0.1]: https://github.com/RumenDamyanov/php-sitemap/releases/tag/v1.0.1
