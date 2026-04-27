<?php

declare(strict_types=1);

namespace SertxuDeveloper\TranslationsChecker\Console\Commands;

use Illuminate\Console\Command;
use SertxuDeveloper\TranslationsChecker\Services\TranslationCheckerService;

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

    /**
     * Execute the console command.
     */
    public function handle(TranslationCheckerService $service): int
    {
        $directory = (string) ($this->option('directory') ?: app()->langPath());

        if (! is_dir($directory)) {
            $this->error("The directory {$directory} does not exist.");

            return self::FAILURE;
        }

        $this->info("Checking translations in {$directory}");

        $results = $service->check($directory);

        foreach ($results['missingFiles'] as $missingFile) {
            $this->error($missingFile);
        }

        foreach ($results['missingTranslations'] as $missingTranslation) {
            $this->error("Missing the translation with key: {$missingTranslation}");
        }

        foreach ($results['emptyTranslations'] as $emptyTranslation) {
            $this->error("Empty translation found in: {$emptyTranslation['language']}.{$emptyTranslation['file']} -> {$emptyTranslation['key']}");
        }

        $hasErrors = ! empty($results['missingFiles'])
            || ! empty($results['missingTranslations'])
            || ! empty($results['emptyTranslations']);

        if (! $hasErrors) {
            $this->info('✔ All translations are okay!');
        }

        return $hasErrors ? self::FAILURE : self::SUCCESS;
    }
}
