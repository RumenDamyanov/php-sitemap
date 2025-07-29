# Security Policy

## Supported Versions

We actively support the following versions of php-sitemap with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

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
4. **File Permissions**: Ensure proper file permissions when storing sitemap files
5. **HTTPS**: Use HTTPS for all sitemap URLs in production

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
