# Security Policy

## Supported Versions

We actively support the following versions of php-sitemap with security updates:

| Version | PHP        | Supported          |
| ------- | ---------- | ------------------ |
| 1.1.x   | 8.3+       | :white_check_mark: |
| 1.0.x   | 8.2+       | :white_check_mark: until PHP 8.2 EOL (31 Dec 2026) |

Runtime PHP must be a patched build. PHP2Shell ([CVE-2026-109618](https://www.cve.org/CVERecord?id=CVE-2026-109618), [CVE-2026-109619](https://www.cve.org/CVERecord?id=CVE-2026-109619)) is in the PHP engine, not in this library. Upgrade the interpreter to **8.5.12+**, **8.4.27+**, **8.3.36+**, or **8.2.35+**. There is no application-level workaround; see the [PHP Foundation advisory](https://thephp.foundation/blog/2026/10/21/todays-php-security-release-and-how-were-strengthening-php-security/) and [CISA alert](https://www.cisa.gov/news-events/alerts/2026/10/21/critical-vulnerabilities-php-require-immediate-patching).

PHP 8.3 remains in upstream security support until **31 December 2027**. PHP 8.2 ends on **31 December 2026**.

## Reporting a Vulnerability

If you discover a security vulnerability in php-sitemap, please report it responsibly by following these steps:

### How to Report

1. **Do NOT create a public GitHub issue** for security vulnerabilities
2. Send an email to **<security@rumenx.com>** with the following information:
   - Description of the vulnerability
   - Steps to reproduce the issue
   - Potential impact and severity
   - Your contact information for follow-up

### What to Expect

- **Initial Response**: We will acknowledge receipt of your report within **48 hours**
- **Assessment**: We will assess the vulnerability and determine its severity within **5 business days**
- **Updates**: We will provide regular updates on our progress
- **Resolution**: Critical vulnerabilities will be patched within **7 days**, others within **30 days**
- **Disclosure**: We will coordinate responsible disclosure with you

### Security Best Practices

When using php-sitemap in production:

1. **Keep Updated**: Always use the latest stable version
2. **Input Validation**: Validate and sanitize all user inputs before adding to sitemaps
3. **Output Escaping**: The package automatically escapes XML output when enabled (default)
4. **File Permissions**: Ensure proper file permissions when storing sitemap files. `store()` accepts a basename only for `$filename`; nested directories belong in `$path`. Do not pass request or CLI user input as `$filename` or `$format`.
5. **PHP runtime**: Keep PHP itself updated. Engine issues such as PHP2Shell are not fixable from Composer packages.
6. **HTTPS**: Use HTTPS for all sitemap URLs in production

### Scope

Security reports should focus on:

- XML injection vulnerabilities
- Path traversal issues
- File system security
- Memory exhaustion attacks
- Code injection possibilities

### Recognition

We appreciate security researchers who help keep php-sitemap secure. With your permission, we will:

- Credit you in our security advisories
- List you in our contributors section
- Provide a reference for responsible disclosure

## Contact

For security-related questions or concerns:

- Email: <security@rumenx.com>
- For general issues: <https://github.com/RumenDamyanov/php-sitemap/issues>

Thank you for helping keep php-sitemap secure!
