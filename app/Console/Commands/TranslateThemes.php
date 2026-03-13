<?php

namespace App\Console\Commands;

use App\Models\Theme;
use App\Services\LegoTranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TranslateThemes extends Command
{
    protected $signature = 'themes:translate
                          {--locale=fr : Target locale}
                          {--with-scraping : Enable scraping fallback (slow, high memory)}';

    protected $description = 'Translate LEGO theme names (known mapping + optional scraping fallback)';

    private int $mappedCount = 0;

    private int $scrapedCount = 0;

    private int $skippedCount = 0;

    public function handle(LegoTranslationService $service): int
    {
        $locale = $this->option('locale');
        $withScraping = $this->option('with-scraping');

        $this->info("Translating themes to [{$locale}]...");

        $knownTranslations = $this->loadKnownTranslations($locale);
        $themes = Theme::all();
        $bar = $this->output->createProgressBar($themes->count());
        $bar->start();

        foreach ($themes as $theme) {
            $enName = $theme->getNameTranslations()['en'] ?? null;

            if (! $enName) {
                $bar->advance();

                continue;
            }

            // 1. Known mapping
            if (isset($knownTranslations[$enName])) {
                $theme->setNameTranslation($locale, $knownTranslations[$enName]);
                $theme->save();
                $this->mappedCount++;
                $bar->advance();

                continue;
            }

            // 2. Scraping fallback (only if explicitly enabled)
            if ($withScraping) {
                $slug = Str::slug($enName);
                $scraped = $service->scrapeThemeName($slug, $locale);

                if ($scraped) {
                    $theme->setNameTranslation($locale, $scraped);
                    $theme->save();
                    $this->scrapedCount++;
                    $bar->advance();

                    continue;
                }
            }

            // 3. Skip — no translation found
            $this->skippedCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Source', 'Count'],
            [
                ['Known mapping', $this->mappedCount],
                ['Scraping', $this->scrapedCount],
                ['Skipped', $this->skippedCount],
                ['Total', $this->mappedCount + $this->scrapedCount + $this->skippedCount],
            ]
        );

        return Command::SUCCESS;
    }

    private function loadKnownTranslations(string $locale): array
    {
        $path = resource_path("data/theme-translations-{$locale}.json");

        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }
}
