<?php

namespace App\Console\Commands;

use App\Models\Set;
use App\Services\LegoTranslationService;
use Illuminate\Console\Command;

class ImportNewSets extends Command
{
    protected $signature = 'sets:import-new
                          {--pages=all : Number of pages to scrape (or "all")}';

    protected $description = 'Import new LEGO sets from LEGO.com new products page';

    private int $created = 0;

    private int $translated = 0;

    private int $skipped = 0;

    public function handle(LegoTranslationService $service): int
    {
        $this->info('Scraping LEGO.com new sets...');

        $firstPageEn = $service->scrapeNewSetsPage(1, 'en');
        $total = $firstPageEn['total'];

        if ($total === 0) {
            $this->warn('No products found on LEGO.com (total=0). __NEXT_DATA__ may have changed.');

            return Command::SUCCESS;
        }

        $perPage = 18;
        $totalPages = (int) ceil($total / $perPage);

        $pagesOption = $this->option('pages');
        $maxPages = $pagesOption === 'all' ? $totalPages : min((int) $pagesOption, $totalPages);

        $this->info("Found {$total} products across {$totalPages} pages. Scraping {$maxPages} page(s).");

        $firstPageFr = $service->scrapeNewSetsPage(1, 'fr');
        $this->processPage($firstPageEn['products'], $firstPageFr['products']);

        for ($page = 2; $page <= $maxPages; $page++) {
            $pageEn = $service->scrapeNewSetsPage($page, 'en');
            $pageFr = $service->scrapeNewSetsPage($page, 'fr');
            $this->processPage($pageEn['products'], $pageFr['products']);
        }

        $this->newLine();
        $this->table(
            ['Action', 'Count'],
            [
                ['Created', $this->created],
                ['Translated', $this->translated],
                ['Skipped', $this->skipped],
                ['Total', $this->created + $this->translated + $this->skipped],
            ]
        );

        return Command::SUCCESS;
    }

    private function processPage(array $enProducts, array $frProducts): void
    {
        $frByCode = [];
        foreach ($frProducts as $product) {
            $frByCode[$product['code']] = $product['name'];
        }

        foreach ($enProducts as $product) {
            $setNum = $product['code'].'-1';
            $frName = $frByCode[$product['code']] ?? null;

            $set = Set::where('set_num', $setNum)->first();

            if (! $set) {
                $name = ['en' => $product['name']];
                if ($frName) {
                    $name['fr'] = $frName;
                }

                Set::create([
                    'set_num' => $setNum,
                    'name' => $name,
                    'img_url' => $product['img_url'],
                    'year' => date('Y'),
                    'theme_id' => null,
                ]);

                $this->created++;

                continue;
            }

            $translations = $set->getNameTranslations();

            if (isset($translations['fr'])) {
                $this->skipped++;

                continue;
            }

            if ($frName) {
                $set->setNameTranslation('fr', $frName);
                $set->save();
                $this->translated++;
            } else {
                $this->skipped++;
            }
        }
    }
}
