<?php

declare(strict_types=1);

namespace SertxuDeveloper\TranslationsChecker\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use SertxuDeveloper\TranslationsChecker\TranslationsCheckerServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            TranslationsCheckerServiceProvider::class,
        ];
    }
}
