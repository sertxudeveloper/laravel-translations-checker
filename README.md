<p align="center"><img src="/art/socialcard.png" alt="Translations Checker"></p>

# Laravel Translations Checker

![](https://img.shields.io/github/v/release/sertxudeveloper/laravel-translations-checker) ![](https://github.com/sertxudeveloper/laravel-translations-checker/actions/workflows/tests.yml/badge.svg) ![](https://img.shields.io/github/license/sertxudeveloper/laravel-translations-checker) ![](https://img.shields.io/github/repo-size/sertxudeveloper/laravel-translations-checker) ![](https://img.shields.io/packagist/dt/sertxudeveloper/laravel-translations-checker) ![](https://img.shields.io/github/issues/sertxudeveloper/laravel-translations-checker) ![](https://img.shields.io/packagist/php-v/sertxudeveloper/laravel-translations-checker) [![Codecov Test coverage](https://img.shields.io/codecov/c/github/sertxudeveloper/laravel-translations-checker)](https://app.codecov.io/gh/sertxudeveloper/laravel-translations-checker)

Check Laravel translation files for missing translations.

This package scans your Laravel translation files and reports:
- Missing translation files across languages
- Translation keys that exist in one language but not others
- Empty or blank translation values

## Requirements

This package requires PHP 8.2+ and Laravel 11.0+.

## Installation

You can install the package via composer:

```bash
composer require sertxudeveloper/laravel-translations-checker
```

## Usage

Run the check command to scan your translation files:

```bash
php artisan translations:check
```

By default, it checks the `lang` directory in your application. You can specify a different directory:

```bash
php artisan translations:check --directory=resources/lang
```

The command returns exit code 1 if any issues are found, making it suitable for CI/CD pipelines.

### Example output

```
Missing translations:
- The language es (resources/lang/es) is missing the file (auth.php)
- es.validation.required

Empty translations:
- en.messages.welcome (empty value)
```

## Using the Service

You can also use the underlying service in your own code:

```php
use SertxuDeveloper\TranslationsChecker\Services\TranslationCheckerService;

$checker = app(TranslationCheckerService::class);
$result = $checker->check(resource_path('lang'));

$result['missingFiles'];        // Files missing in some languages
$result['missingTranslations']; // Keys missing in some languages
$result['emptyTranslations'];   // Keys with empty values
```

## Testing

This package contains tests. Run them using:

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](https://github.com/sertxudeveloper/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Sergio Peris](https://github.com/sertxudev)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

<br><br>
<p align="center">Copyright © 2026 Sertxu Developer</p>
