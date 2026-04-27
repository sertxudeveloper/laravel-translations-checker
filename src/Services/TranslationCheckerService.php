<?php

declare(strict_types=1);

namespace SertxuDeveloper\TranslationsChecker\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TranslationCheckerService
{
    /**
     * Check translations in the given directory.
     *
     * @return array{missingFiles: array<int, string>, missingTranslations: array<int, string>, emptyTranslations: array<int, array{language: string, file: string, key: string}>}
     */
    public function check(string $directory): array
    {
        $directory = rtrim(str_replace('\\', '/', $directory), '/');

        $languages = $this->getLanguages($directory);
        $files = $this->scanTranslationFiles($directory);

        $missingFiles = $this->findMissingFiles($languages, $files, $directory);
        $translations = $this->loadTranslations($files, $directory);
        $missingTranslations = $this->findMissingTranslations($translations, $languages, $directory);
        $emptyTranslations = $this->findEmptyTranslations($translations);

        return [
            'missingFiles' => $missingFiles,
            'missingTranslations' => $missingTranslations,
            'emptyTranslations' => $emptyTranslations,
        ];
    }

    /**
     * Get all languages from the given directory.
     *
     * @return array<int, string>
     */
    protected function getLanguages(string $directory): array
    {
        $languages = [];

        $entries = scandir($directory);

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $languages[] = str_replace('.json', '', $entry);
        }

        return array_unique($languages);
    }

    /**
     * Scan all translation files in the directory.
     *
     * @return array<int, array{path: string, name: string, directory: string}>
     */
    protected function scanTranslationFiles(string $directory): array
    {
        $files = [];

        $rdi = new \RecursiveDirectoryIterator($directory, \FilesystemIterator::KEY_AS_PATHNAME);
        foreach (new \RecursiveIteratorIterator($rdi, \RecursiveIteratorIterator::SELF_FIRST) as $langFile => $info) {
            if (! File::isDirectory($langFile) && Str::endsWith($langFile, ['.json', '.php'])) {
                $files[] = [
                    'path' => str_replace('\\', '/', $langFile),
                    'name' => basename($langFile),
                    'directory' => str_replace('\\', '/', dirname($langFile)),
                ];
            }
        }

        return $files;
    }

    /**
     * Find translation files missing in some languages.
     *
     * @param  array<int, string>  $languages
     * @param  array<int, array{path: string, name: string, directory: string}>  $files
     * @return array<int, string>
     */
    protected function findMissingFiles(array $languages, array $files, string $directory): array
    {
        $missingFiles = [];
        $fileNames = array_unique(array_column($files, 'name'));

        foreach ($fileNames as $fileName) {
            foreach ($languages as $language) {
                $existsInSubDir = File::exists($directory.'/'.$language.'/'.$fileName);
                $existsAsTopLevel = File::exists($directory.'/'.$fileName);

                if (! $existsInSubDir && ! $existsAsTopLevel) {
                    $missingFiles[] = 'The language '.$language.' ('.$directory.'/'.$language.') is missing the file ( '.$fileName.' )';
                }
            }
        }

        return array_unique($missingFiles);
    }

    /**
     * Load all translations from the scanned files.
     *
     * @param  array<int, array{path: string, name: string, directory: string}>  $files
     * @return array<string, array{language: string, file: string, lines: array<string, mixed>}>
     */
    protected function loadTranslations(array $files, string $directory): array
    {
        $translations = [];

        foreach ($files as $file) {
            $language = basename($file['directory']);
            $fileName = $file['name'];

            if (Str::endsWith($fileName, '.json')) {
                $lines = json_decode(File::get($file['path']), true);
            } else {
                $lines = include $file['path'];
            }

            if (! is_array($lines)) {
                continue;
            }

            $translations[$file['path']] = [
                'language' => $language,
                'file' => $fileName,
                'lines' => $lines,
                'directory' => $file['directory'],
            ];
        }

        return $translations;
    }

    /**
     * Find missing translations across all languages.
     *
     * @param  array<string, array{language: string, file: string, lines: array<string, mixed>, directory: string}>  $translations
     * @param  array<int, string>  $languages
     * @return array<int, string>
     */
    protected function findMissingTranslations(array $translations, array $languages, string $directory): array
    {
        $realLines = $this->buildRealLines($translations);
        $missing = [];

        foreach ($realLines as $key => $line) {
            $pos = strpos($key, '**');
            $fileNameWithoutKey = substr($key, 0, $pos);
            $fileKey = basename($fileNameWithoutKey);
            $keyWithoutFile = substr($key, $pos + 2);

            foreach ($languages as $language) {
                $exists = $this->translationExists($directory, $language, $fileKey, $keyWithoutFile, $realLines);

                if (! $exists) {
                    $fileName = Str::replace(['.php', '.json'], '', $fileKey);

                    if (in_array($fileName, $languages)) {
                        $missing[] = $language.'.'.$keyWithoutFile;
                    } else {
                        $missing[] = $language.'.'.$fileName.'.'.$keyWithoutFile;
                    }
                }
            }
        }

        return array_unique($missing);
    }

    /**
     * Build the realLines lookup array.
     *
     * @param  array<string, array{language: string, file: string, lines: array<string, mixed>, directory: string}>  $translations
     * @return array<string, mixed>
     */
    protected function buildRealLines(array $translations): array
    {
        $realLines = [];

        foreach ($translations as $filePath => $translation) {
            $flatLines = $this->flattenLines($translation['lines']);

            foreach ($flatLines as $index => $line) {
                $realLines[$translation['directory'].'/'.$translation['file'].'**'.$index] = $line;
            }
        }

        return $realLines;
    }

    /**
     * Check if a translation exists for a given language.
     *
     * @param  array<string, mixed>  $allLines
     */
    protected function translationExists(string $directory, string $language, string $fileKey, string $keyWithoutFile, array $allLines): bool
    {
        $normalizedDir = rtrim(str_replace('\\', '/', $directory), '/');

        $existsAsSubDirValue = array_key_exists($normalizedDir.'/'.$language.'/'.$fileKey.'**'.$keyWithoutFile, $allLines);

        $fileKeyWithoutLangComponent = explode('.', $fileKey, 2)[1] ?? $fileKey;
        $existsAsJSONValue = array_key_exists($normalizedDir.'/'.$language.'.'.$fileKeyWithoutLangComponent.'**'.$keyWithoutFile, $allLines);

        return $existsAsSubDirValue || $existsAsJSONValue;
    }

    /**
     * Flatten nested translation arrays.
     *
     * @param  array<string, mixed>  $lines
     * @return array<string, mixed>
     */
    protected function flattenLines(array $lines): array
    {
        $result = [];

        foreach ($lines as $key => $line) {
            if (is_array($line)) {
                foreach ($this->flattenLines($line) as $key2 => $line2) {
                    $result[$key.'.'.$key2] = $line2;
                }
            } else {
                $result[$key] = $line;
            }
        }

        return $result;
    }

    /**
     * Find all empty translations.
     *
     * @param  array<string, array{language: string, file: string, lines: array<string, mixed>, directory: string}>  $translations
     * @return array<int, array{language: string, file: string, key: string}>
     */
    protected function findEmptyTranslations(array $translations): array
    {
        $empty = [];

        foreach ($translations as $translation) {
            $this->checkEmptyRecursively(
                $translation['lines'],
                $translation['language'],
                $translation['file'],
                '',
                $empty
            );
        }

        return $empty;
    }

    /**
     * Recursively check for empty translations.
     *
     * @param  array<string, mixed>  $lines
     * @param  array<int, array{language: string, file: string, key: string}>  $empty
     */
    protected function checkEmptyRecursively(array $lines, string $language, string $fileName, string $prefix, array &$empty): void
    {
        foreach ($lines as $key => $value) {
            $currentKey = $prefix ? $prefix.'.'.$key : $key;

            if (is_array($value)) {
                $this->checkEmptyRecursively($value, $language, $fileName, $currentKey, $empty);
            } elseif (trim((string) $value) === '' || $value === null || $value === false) {
                $empty[] = [
                    'language' => $language,
                    'file' => Str::replace(['.php', '.json'], '', $fileName),
                    'key' => $currentKey,
                ];
            }
        }
    }
}
