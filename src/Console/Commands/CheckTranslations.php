<?php

declare(strict_types=1);

namespace SertxuDeveloper\TranslationsChecker\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class CheckTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:check {--directory=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks if all translations are there for all languages.';

    public array $realLines = [];

    public array $emptyTranslations = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $directory = $this->option('directory') ?: app()->langPath();
        $directory = rtrim(str_replace('\\', '/', $directory), '/');

        if (! File::isDirectory($directory)) {
            $this->error('The passed directory ('.$directory.') does not exist.');

            return $this::FAILURE;
        }

        $this->info("Checking translations in $directory");

        $languages = $this->getLanguages($directory);
        $missingFiles = [];

        $rdi = new RecursiveDirectoryIterator($directory, FilesystemIterator::KEY_AS_PATHNAME);
        foreach (new RecursiveIteratorIterator($rdi, RecursiveIteratorIterator::SELF_FIRST) as $langFile => $info) {
            if (! File::isDirectory($langFile) && Str::endsWith($langFile, ['.json', '.php'])) {
                $fileName = basename($langFile);
                $filePath = str_replace('\\', '/', $langFile);
                $languageDir = Str::replaceLast('/'.$fileName, '', $filePath);

                $languagesWithMissingFile = $this->checkIfFileExistsForOtherLanguages($languages, $fileName, $directory);

                foreach ($languagesWithMissingFile as $languageWithMissingFile) {
                    $missingFiles[] = 'The language '.$languageWithMissingFile.' ('.$directory.'/'.$languageWithMissingFile.') is missing the file ( '.$fileName.' )';
                }

                $this->handleFile($languageDir, $langFile);
            }
        }
        $missingFiles = array_unique($missingFiles);

        $missing = [];
        foreach ($this->realLines as $key => $line) {

            foreach ($languages as $language) {

                $fileNameWithoutKey = substr($key, 0, strpos($key, '**'));
                $fileKey = basename($fileNameWithoutKey);
                $keyWithoutFile = substr($key, strpos($key, '**') + 2, strlen($key));

                $exists = $this->translationExistsAsJsonOrAsSubDir($directory, $language, $fileKey, $keyWithoutFile);

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
        $missing = array_unique($missing);

        foreach ($missingFiles as $missingFile) {
            $this->error($missingFile);
        }

        foreach ($missing as $missingTranslation) {
            $this->error('Missing the translation with key: '.$missingTranslation);
        }

        foreach ($this->emptyTranslations as $emptyTranslation) {
            $this->error("Empty translation found in: {$emptyTranslation['language']}.{$emptyTranslation['file']} -> {$emptyTranslation['key']}");
        }

        if (count($missingFiles) === 0 && count($missing) === 0) {
            $this->info('✔ All translations are okay!');
        }

        return count($missing) > 0 || count($missingFiles) > 0 || count($this->emptyTranslations) > 0 ? $this::FAILURE : $this::SUCCESS;
    }

    public function translationExistsAsJsonOrAsSubDir(string $directory, string $language, string $fileKey, string $keyWithoutFile): bool
    {
        $normalizedDir = rtrim(str_replace('\\', '/', $directory), '/');

        $existsAsSubDirValue = array_key_exists($normalizedDir.'/'.$language.'/'.$fileKey.'**'.$keyWithoutFile, $this->realLines);

        $fileKeyWithoutLangComponent = explode('.', $fileKey, 2)[1] ?? $fileKey;
        $existsAsJSONValue = array_key_exists($normalizedDir.'/'.$language.'.'.$fileKeyWithoutLangComponent.'**'.$keyWithoutFile, $this->realLines);

        return $existsAsSubDirValue || $existsAsJSONValue;
    }

    /**
     * Check if the given file exists for other languages.
     *
     * @return array<int, string>
     */
    private function checkIfFileExistsForOtherLanguages(array $languages, string $fileName, string $baseDirectory): array
    {
        $languagesWhereFileIsMissing = [];
        foreach ($languages as $language) {
            if (
                ! File::exists($baseDirectory.'/'.$language.'/'.$fileName)
                && ! File::exists($baseDirectory.'/'.$fileName)
            ) {
                $languagesWhereFileIsMissing[] = $language;
            }
        }

        return $languagesWhereFileIsMissing;
    }

    public function handleFile($languageDir, $langFile): void
    {
        $fileName = basename($langFile);
        $normalizedDir = rtrim(str_replace('\\', '/', $languageDir), '/');
        $language = basename($normalizedDir);

        if (Str::endsWith($fileName, '.json')) {
            $lines = json_decode(File::get($langFile), true);
        } else {
            $lines = include $langFile;
        }

        if (! is_array($lines)) {
            $this->warn('Skipping file ('.$langFile.') because it is empty.');

            return;
        }

        $this->checkForEmptyTranslations($lines, $language, $fileName);

        foreach ($this->flatLines($lines) as $index => $line) {
            $this->realLines[$normalizedDir.'/'.$fileName.'**'.$index] = $line;
        }
    }

    private function flatLines(array $lines): array
    {
        $result = [];

        foreach ($lines as $key => $line) {
            if (is_array($line)) {
                foreach ($this->flatLines($line) as $key2 => $line2) {
                    $result[$key.'.'.$key2] = $line2;
                }
            } else {
                $result[$key] = $line;
            }
        }

        return $result;
    }

    /**
     * Check for empty translations recursively.
     */
    private function checkForEmptyTranslations(array $lines, string $language, string $fileName, string $prefix = ''): void
    {

        foreach ($lines as $key => $value) {
            $currentKey = $prefix ? "$prefix.$key" : $key;

            if (is_array($value)) {
                $this->checkForEmptyTranslations($value, $language, $fileName, $currentKey);
            } else {
                // Check if value is empty (empty string, null, or false);
                if (trim($value) === '' || $value === null || $value === false) {
                    $this->emptyTranslations[] = [
                        'language' => $language,
                        'file' => Str::replace(['.php', '.json'], '', $fileName),
                        'key' => $currentKey,
                    ];
                }
            }
        }
    }

    /**
     * Get all languages from the given directory.
     */
    private function getLanguages(string $directory): array
    {
        $languages = [];

        if ($handle = opendir($directory)) {
            while (false !== ($languageDir = readdir($handle))) {
                if ($languageDir !== '.' && $languageDir !== '..') {
                    $languages[] = str_replace('.json', '', $languageDir);
                }
            }
        }

        closedir($handle);

        return $languages;
    }
}
