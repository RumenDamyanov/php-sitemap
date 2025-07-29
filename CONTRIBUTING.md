# Contributing to php-sitemap

Thank you for your interest in contributing to php-sitemap! We welcome contributions from the community and are grateful for any help you can provide.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Making Changes](#making-changes)
- [Testing](#testing)
- [Submitting Changes](#submitting-changes)
- [Coding Standards](#coding-standards)
- [Documentation](#documentation)
- [Community](#community)

## Code of Conduct

By participating in this project, you agree to abide by our code of conduct:

- Be respectful and inclusive
- Use welcoming and inclusive language
- Be collaborative and constructive
- Focus on what is best for the community
- Show empathy towards other community members

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git

### Fork and Clone

1. Fork the repository on GitHub
2. Clone your fork locally:

   ```bash
   git clone https://github.com/YOUR_USERNAME/php-sitemap.git
   cd php-sitemap
   ```

3. Add the upstream repository:

   ```bash
   git remote add upstream https://github.com/RumenDamyanov/php-sitemap.git
   ```

## Development Setup

1. Install dependencies:

   ```bash
   composer install
   ```

2. Run tests to ensure everything is working:

   ```bash
   composer test
   ```

3. Check code coverage:

   ```bash
   composer coverage-html
   ```

4. Run static analysis:

   ```bash
   composer analyze
   ```

5. Check coding standards:

   ```bash
   composer style
   ```

## Making Changes

### Branching Strategy

- Create a new branch for each feature or bugfix
- Use descriptive branch names:
  - `feature/add-video-sitemap-support`
  - `bugfix/fix-xml-escaping`
  - `docs/update-readme-examples`

```bash
git checkout -b feature/your-feature-name
```

### Commit Messages

Follow conventional commit format:

- `feat:` for new features
- `fix:` for bug fixes
- `docs:` for documentation changes
- `style:` for formatting changes
- `refactor:` for code refactoring
- `test:` for test additions or changes
- `chore:` for maintenance tasks

Examples:

- `feat: add Google News sitemap support`
- `fix: resolve XML escaping issue in video titles`
- `docs: update installation instructions`

## Testing

We use Pest for testing. All contributions should include appropriate tests.

### Running Tests

```bash
# Run all tests
composer test

# Run tests with text coverage report
composer coverage

# Generate full HTML coverage report
composer coverage-html

# Run specific test file
./vendor/bin/pest tests/Unit/SitemapTest.php

# Run tests in watch mode
./vendor/bin/pest --watch
```

### Code Quality Checks

```bash
# Run static analysis
composer analyze

# Check coding standards
composer style

# Auto-fix coding standards
composer style-fix
```

### Writing Tests

- **Unit Tests**: Test individual classes and methods in `tests/Unit/`
- **Feature Tests**: Test complete functionality in `tests/Feature/`
- Aim for 100% code coverage
- Test both success and failure scenarios
- Use descriptive test names

Example test:

```php
test('sitemap can add item with all parameters', function () {
    $sitemap = new Sitemap();
    $sitemap->add(
        'https://example.com',
        '2025-06-09',
        '1.0',
        'daily'
    );
    
    expect($sitemap->getModel()->getItems())->toHaveCount(1);
});
```

## Submitting Changes

### Pull Request Process

1. Ensure your code follows our coding standards (`composer style`)
2. Run static analysis and fix any issues (`composer analyze`)
3. Run tests and ensure they pass (`composer test`)
4. Update documentation if needed
5. Commit your changes with clear messages
6. Push to your fork and create a pull request

### Pull Request Guidelines

- **Title**: Clear and descriptive
- **Description**: Explain what changes you made and why
- **Testing**: Describe how you tested your changes
- **Breaking Changes**: Clearly mark any breaking changes
- **Documentation**: Update relevant documentation

### Pull Request Template

```markdown
## Description
Brief description of the changes.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests pass locally (`composer test`)
- [ ] New tests added for new functionality
- [ ] Code coverage maintained (`composer coverage-html`)
- [ ] Static analysis passes (`composer analyze`)
- [ ] Code style follows standards (`composer style`)

## Checklist
- [ ] Code follows project coding standards
- [ ] Self-review completed
- [ ] Documentation updated if needed
- [ ] No breaking changes (or marked as breaking)
```

## Coding Standards

### PHP Standards

- Follow **PSR-12** coding standards (enforced by `composer style`)
- Use **strict types** in all PHP files
- Add **PHPDoc** comments for all public methods
- Use **type hints** for all parameters and return types
- Pass **PHPStan** level 6 analysis (`composer analyze`)

### Code Quality

- Use meaningful variable and method names
- Keep methods focused and small
- Avoid deep nesting (max 3 levels)
- Handle errors gracefully
- Follow SOLID principles

### Quality Tools

We use several tools to maintain code quality:

- **Pest**: For testing (`composer test`)
- **PHPStan**: For static analysis (`composer analyze`)
- **PHP_CodeSniffer**: For coding standards (`composer style` / `composer style-fix`)

### Example Code Style

```php
<?php

declare(strict_types=1);

namespace Rumenx\Sitemap;

/**
 * Example class demonstrating our coding standards.
 */
class ExampleClass
{
    /**
     * Example method with proper documentation.
     *
     * @param string $url The URL to process.
     * @param array<string, mixed> $options Additional options.
     * @return bool True on success, false on failure.
     */
    public function processUrl(string $url, array $options = []): bool
    {
        // Implementation here
        return true;
    }
}
```

## Documentation

### Code Documentation

- All public methods must have PHPDoc comments
- Include parameter types and descriptions
- Document return types and possible exceptions
- Add usage examples for complex methods

### README Updates

- Update examples if you add new features
- Keep installation instructions current
- Add new features to the features list

### Changelog

We maintain a changelog following [Keep a Changelog](https://keepachangelog.com/):

- Add entries to `UNRELEASED` section
- Use categories: Added, Changed, Deprecated, Removed, Fixed, Security

## Community

### Getting Help

- **GitHub Issues**: For bug reports and feature requests
- **GitHub Discussions**: For questions and general discussion
- **Security Issues**: Email <security@rumenx.com>

### Recognition

Contributors are recognized in:

- GitHub contributors list
- Release notes for significant contributions
- README acknowledgments

## Release Process

Releases are handled by maintainers:

1. Version bumping follows [Semantic Versioning](https://semver.org/)
2. Changelog is updated with release notes
3. Tags are created for each release
4. Packagist is automatically updated

## Questions?

If you have any questions about contributing, please:

1. Check existing GitHub issues and discussions
2. Create a new discussion for general questions
3. Create an issue for specific bugs or feature requests

Thank you for contributing to php-sitemap! 🎉
