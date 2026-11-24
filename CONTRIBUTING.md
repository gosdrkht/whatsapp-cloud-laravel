# Contributing to WhatsApp Cloud Laravel

Thank you for considering contributing to **WhatsApp Cloud Laravel**!

## Development Setup

```bash
git clone https://github.com/gosdrkht/whatsapp-cloud-laravel.git
cd whatsapp-cloud-laravel
composer install
```

## Quality Checks

Before opening a pull request, make sure all tests, formatting, and static analysis checks pass:

```bash
composer test      # Run Pest test suite
composer lint      # Check formatting with Laravel Pint
composer analyse   # Run PHPStan static analysis
```

To auto-format your changes:

```bash
composer format
```

## Pull Request Guidelines

- Keep PRs focused on a single feature or bug fix.
- Add Pest unit or feature tests covering your changes.
- Never commit real Meta access tokens, app secrets, or phone number IDs.
- Follow PSR-12 / Laravel coding standards (`strict_types=1`).
