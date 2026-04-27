<?php

declare(strict_types=1);

const BASIC_LANG_DIR = "tests/resources/lang/basic/";
const MULTIPLE_LANG_DIR = "tests/resources/lang/multi_langs/";
const JSON_LANG_DIR = "tests/resources/lang/json/";

it('returns errors if one key is missing', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => BASIC_LANG_DIR . 'one_missing_key',
    ]);

    $command->assertExitCode(1);
    $command->expectsOutput('Missing the translation with key: es.test.test_key');
});

it('returns errors if multiple keys are missing', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => BASIC_LANG_DIR . 'two_missing_keys',
    ]);

    $command->expectsOutput('Missing the translation with key: es.test.test_key');
    $command->expectsOutput('Missing the translation with key: es.test.test_key2');
});

it('fails if key is missing', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => BASIC_LANG_DIR . 'one_missing_file',
    ]);

    $command->assertExitCode(1);
    $command->expectsOutput('The language es (tests/resources/lang/basic/one_missing_file/es) is missing the file ( test.php )');
});

it('fails if value is empty', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => BASIC_LANG_DIR . 'one_missing_value',
    ]);

    $command->assertExitCode(1);
    $command->expectsOutput('Empty translation found in: es.test -> test_key');
});

it('is successful if none keys are missing', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => BASIC_LANG_DIR . 'zero_missing_keys',
    ]);

    $command->assertExitCode(0);
    $command->expectsOutput('✔ All translations are okay!');
});

it('handles a single language', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => MULTIPLE_LANG_DIR . 'one_language',
    ]);

    $command->assertExitCode(0);
    $command->expectsOutput('✔ All translations are okay!');
});

it('handles two languages', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => MULTIPLE_LANG_DIR . 'two_languages',
    ]);

    $command->assertExitCode(0);
    $command->expectsOutput('✔ All translations are okay!');
});

it('handles ten languages', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => MULTIPLE_LANG_DIR . 'ten_languages',
    ]);

    $command->assertExitCode(0);
    $command->expectsOutput('✔ All translations are okay!');
});

it('handles one top-level language file', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => JSON_LANG_DIR . 'toplevel_json_files/one',
    ]);

    $command->assertExitCode(0);
    $command->expectsOutput('✔ All translations are okay!');
});

it('handles two top-level language file', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => JSON_LANG_DIR . 'toplevel_json_files/two',
    ]);

    $command->assertExitCode(0);
    $command->expectsOutput('✔ All translations are okay!');
});

it('handles missing key in top-level language file', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => JSON_LANG_DIR . 'toplevel_json_files/missing_key_in_one_lang',
    ]);

    $command->assertExitCode(1);
    $command->expectsOutput('Missing the translation with key: es.test_key');
});

it('handles slashes in json keys', function () {
    $command = $this->artisan('translations:check', [
        '--directory' => JSON_LANG_DIR . 'toplevel_json_files/slashes_in_title',
    ]);

    $command->assertExitCode(0);
    $command->expectsOutput('✔ All translations are okay!');
});

it('returns error if directory is missing', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => 'missing_directory',
    ]);

    $command->assertExitCode(1);
    $command->expectsOutput('The directory "missing_directory" does not exist.');
});

it('skips non-array PHP translation files', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => BASIC_LANG_DIR . 'non_array_php',
    ]);

    $command->assertExitCode(0);
    $command->expectsOutput('✔ All translations are okay!');
});

it('skips non-array JSON translation files', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => JSON_LANG_DIR . 'non_array_json',
    ]);

    $command->assertExitCode(0);
    $command->expectsOutput('✔ All translations are okay!');
});

it('flattens nested translation arrays correctly', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => BASIC_LANG_DIR . 'nested_translations',
    ]);

    $command->assertExitCode(0);
    $command->expectsOutput('✔ All translations are okay!');
});

it('recursively finds empty translations in nested arrays', function (): void {
    $command = $this->artisan('translations:check', [
        '--directory' => BASIC_LANG_DIR . 'nested_empty',
    ]);

    $command->assertExitCode(1);
    $command->expectsOutput('Empty translation found in: es.test -> parent.empty_child');
});

it('handles deeply nested empty translations', function (): void{
    $command = $this->artisan('translations:check', [
        '--directory' => BASIC_LANG_DIR . 'nested_empty',
    ]);

    $command->assertExitCode(1);
    $command->expectsOutput('Missing the translation with key: en.test.parent.nested.deep');
    $command->expectsOutput('Missing the translation with key: en.test.parent.nested.valid');
    $command->expectsOutput('Empty translation found in: en.test -> parent.empty_child');
    $command->expectsOutput('Empty translation found in: es.test -> parent.child');
    $command->expectsOutput('Empty translation found in: es.test -> parent.empty_child');
    $command->expectsOutput('Empty translation found in: es.test -> parent.nested.deep');
    $command->expectsOutput('Empty translation found in: es.test -> simple');
});
