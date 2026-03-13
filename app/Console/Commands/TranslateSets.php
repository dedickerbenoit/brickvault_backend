<?php

namespace App\Console\Commands;

use App\Models\Set;
use App\Services\LegoTranslationService;
use Illuminate\Console\Command;

class TranslateSets extends Command
{
    protected $signature = 'sets:translate
                          {--locale=fr : Target locale}
                          {--batch-size=50 : Number of sets per API batch}
                          {--with-scraping : Enable scraping fallback}
                          {--new-only : Only translate sets missing the target locale}';

    protected $description = 'Translate LEGO set names via LEGO GraphQL API (+ optional scraping fallback)';

    private int $apiCount = 0;

    private int $scrapingCount = 0;

    private int $failedCount = 0;

    public function handle(LegoTranslationService $service): int
    {
        $locale = $this->option('locale');
        $batchSize = (int) $this->option('batch-size');
        $withScraping = $this->option('with-scraping');
        $newOnly = $this->option('new-only');

        if (! in_array($locale, ['en', 'fr', 'de', 'es'])) {
            $this->error("Unsupported locale [{$locale}].");

            return Command::FAILURE;
        }

        $this->info("Translating sets to [{$locale}]...");

        $query = Set::query();

        if ($newOnly) {
            $query->whereNull("name->{$locale}");
        }

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->select(['id', 'set_num', 'name'])
            ->chunk($batchSize, function ($sets) use ($service, $locale, $withScraping, $bar) {
                $setNums = $sets->pluck('set_num')->toArray();
                $translations = $service->translateBatch($setNums, $locale);

                foreach ($sets as $set) {
                    $productId = LegoTranslationService::extractProductCode($set->set_num);
                    $translatedName = $translations[$productId] ?? null;

                    if ($translatedName) {
                        $set->setNameTranslation($locale, $translatedName);
                        $set->save();
                        $this->apiCount++;
                    } elseif ($withScraping) {
                        $scraped = $service->scrapeSetName($set->set_num, $locale);

                        if ($scraped) {
                            $set->setNameTranslation($locale, $scraped);
                            $set->save();
                            $this->scrapingCount++;
                        } else {
                            $this->failedCount++;
                        }
                    } else {
                        $this->failedCount++;
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Source', 'Count'],
            [
                ['API (GraphQL)', $this->apiCount],
                ['Scraping', $this->scrapingCount],
                ['Failed', $this->failedCount],
                ['Total', $this->apiCount + $this->scrapingCount + $this->failedCount],
            ]
        );

        return Command::SUCCESS;
    }
}
