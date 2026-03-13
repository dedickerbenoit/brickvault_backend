<?php

namespace Tests\Feature;

use App\Models\Set;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportNewSetsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'lego.scraping.base_url' => 'https://www.lego.com',
            'lego.scraping.rate_limit_ms' => 0,
        ]);
    }

    private function buildNextDataHtml(array $products, int $total): string
    {
        $apolloState = [];

        $apolloState['ProductQueryResult:0'] = ['total' => $total];

        foreach ($products as $product) {
            $key = 'Product:' . $product['code'];
            $imgKey = 'Image:' . $product['code'];

            $apolloState[$key] = [
                'productCode' => $product['code'],
                'name' => $product['name'],
                'primaryImage' => ['__ref' => $imgKey],
            ];

            $apolloState[$imgKey] = [
                'url' => $product['img_url'] ?? 'https://www.lego.com/cdn/img/' . $product['code'] . '.jpg',
            ];
        }

        $nextData = json_encode([
            'props' => ['apolloState' => $apolloState],
        ]);

        return '<html><head></head><body><script id="__NEXT_DATA__" type="application/json">' . $nextData . '</script></body></html>';
    }

    #[Test]
    public function it_creates_missing_sets_with_en_and_fr_names()
    {
        $enHtml = $this->buildNextDataHtml([
            ['code' => '21637', 'name' => 'Venusaur, Charizard & Blastoise'],
            ['code' => '72153', 'name' => 'Batmobile Chase'],
        ], 2);

        $frHtml = $this->buildNextDataHtml([
            ['code' => '21637', 'name' => 'Florizarre, Dracaufeu et Tortank'],
            ['code' => '72153', 'name' => 'La poursuite en Batmobile'],
        ], 2);

        Http::fake([
            'www.lego.com/en-US/categories/new-sets-and-products*' => Http::response($enHtml),
            'www.lego.com/fr-FR/categories/new-sets-and-products*' => Http::response($frHtml),
        ]);

        $this->artisan('sets:import-new')
            ->assertSuccessful();

        $set1 = Set::where('set_num', '21637-1')->first();
        $this->assertNotNull($set1);
        $this->assertEquals('Venusaur, Charizard & Blastoise', $set1->getNameTranslations()['en']);
        $this->assertEquals('Florizarre, Dracaufeu et Tortank', $set1->getNameTranslations()['fr']);
        $this->assertEquals(date('Y'), $set1->year);
        $this->assertNull($set1->theme_id);

        $set2 = Set::where('set_num', '72153-1')->first();
        $this->assertNotNull($set2);
        $this->assertEquals('Batmobile Chase', $set2->getNameTranslations()['en']);
        $this->assertEquals('La poursuite en Batmobile', $set2->getNameTranslations()['fr']);
    }

    #[Test]
    public function it_adds_fr_translation_to_existing_set_without_fr()
    {
        Set::factory()->create([
            'set_num' => '21637-1',
            'name' => ['en' => 'Venusaur, Charizard & Blastoise'],
        ]);

        $enHtml = $this->buildNextDataHtml([
            ['code' => '21637', 'name' => 'Venusaur, Charizard & Blastoise'],
        ], 1);

        $frHtml = $this->buildNextDataHtml([
            ['code' => '21637', 'name' => 'Florizarre, Dracaufeu et Tortank'],
        ], 1);

        Http::fake([
            'www.lego.com/en-US/categories/new-sets-and-products*' => Http::response($enHtml),
            'www.lego.com/fr-FR/categories/new-sets-and-products*' => Http::response($frHtml),
        ]);

        $this->artisan('sets:import-new')
            ->assertSuccessful();

        $set = Set::where('set_num', '21637-1')->first();
        $this->assertEquals('Venusaur, Charizard & Blastoise', $set->getNameTranslations()['en']);
        $this->assertEquals('Florizarre, Dracaufeu et Tortank', $set->getNameTranslations()['fr']);
    }

    #[Test]
    public function it_skips_sets_already_translated()
    {
        Set::factory()->create([
            'set_num' => '21637-1',
            'name' => ['en' => 'Venusaur, Charizard & Blastoise', 'fr' => 'Déjà traduit'],
        ]);

        $enHtml = $this->buildNextDataHtml([
            ['code' => '21637', 'name' => 'Venusaur, Charizard & Blastoise'],
        ], 1);

        $frHtml = $this->buildNextDataHtml([
            ['code' => '21637', 'name' => 'Florizarre, Dracaufeu et Tortank'],
        ], 1);

        Http::fake([
            'www.lego.com/en-US/categories/new-sets-and-products*' => Http::response($enHtml),
            'www.lego.com/fr-FR/categories/new-sets-and-products*' => Http::response($frHtml),
        ]);

        $this->artisan('sets:import-new')
            ->assertSuccessful();

        $set = Set::where('set_num', '21637-1')->first();
        $this->assertEquals('Déjà traduit', $set->getNameTranslations()['fr']);
    }

    #[Test]
    public function it_handles_missing_next_data_gracefully()
    {
        $htmlWithoutNextData = '<html><head></head><body><p>No data here</p></body></html>';

        Http::fake([
            'www.lego.com/en-US/categories/new-sets-and-products*' => Http::response($htmlWithoutNextData),
        ]);

        $this->artisan('sets:import-new')
            ->assertSuccessful();

        $this->assertEquals(0, Set::count());
    }

    #[Test]
    public function it_handles_pagination()
    {
        $enPage1Products = [];
        $frPage1Products = [];
        for ($i = 1; $i <= 18; $i++) {
            $code = (string) (70000 + $i);
            $enPage1Products[] = ['code' => $code, 'name' => "Set EN {$code}"];
            $frPage1Products[] = ['code' => $code, 'name' => "Set FR {$code}"];
        }

        $enPage2Products = [
            ['code' => '80001', 'name' => 'Set EN 80001'],
        ];
        $frPage2Products = [
            ['code' => '80001', 'name' => 'Set FR 80001'],
        ];

        $enPage1Html = $this->buildNextDataHtml($enPage1Products, 19);
        $frPage1Html = $this->buildNextDataHtml($frPage1Products, 19);
        $enPage2Html = $this->buildNextDataHtml($enPage2Products, 19);
        $frPage2Html = $this->buildNextDataHtml($frPage2Products, 19);

        Http::fake([
            'www.lego.com/en-US/categories/new-sets-and-products?page=1' => Http::response($enPage1Html),
            'www.lego.com/fr-FR/categories/new-sets-and-products?page=1' => Http::response($frPage1Html),
            'www.lego.com/en-US/categories/new-sets-and-products?page=2' => Http::response($enPage2Html),
            'www.lego.com/fr-FR/categories/new-sets-and-products?page=2' => Http::response($frPage2Html),
        ]);

        $this->artisan('sets:import-new')
            ->assertSuccessful();

        $this->assertEquals(19, Set::count());

        $lastSet = Set::where('set_num', '80001-1')->first();
        $this->assertNotNull($lastSet);
        $this->assertEquals('Set EN 80001', $lastSet->getNameTranslations()['en']);
        $this->assertEquals('Set FR 80001', $lastSet->getNameTranslations()['fr']);
    }
}
