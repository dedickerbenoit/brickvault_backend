<?php

namespace Tests\Feature;

use App\Models\Set;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TranslateSetsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'lego.graphql.rate_limit_ms' => 0,
            'lego.scraping.base_url' => 'https://www.lego.com',
            'lego.scraping.rate_limit_ms' => 0,
        ]);
    }

    #[Test]
    public function it_translates_sets_via_graphql_api()
    {
        Set::factory()->create(['set_num' => '75192-1', 'name' => ['en' => 'Millennium Falcon']]);
        Set::factory()->create(['set_num' => '10497-1', 'name' => ['en' => 'Galaxy Explorer']]);

        Http::fake([
            'www.lego.com/api/graphql/*' => Http::sequence()
                ->push([
                    'data' => [
                        'searchSuggestions' => [
                            ['__typename' => 'SingleVariantProduct', 'productCode' => '75192', 'name' => 'Faucon Millenium'],
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'searchSuggestions' => [
                            ['__typename' => 'SingleVariantProduct', 'productCode' => '10497', 'name' => 'Explorateur galactique'],
                        ],
                    ],
                ]),
        ]);

        $this->artisan('sets:translate --locale=fr')
            ->assertSuccessful();

        $set1 = Set::where('set_num', '75192-1')->first();
        $this->assertEquals('Faucon Millenium', $set1->getNameTranslations()['fr']);
        $this->assertEquals('Millennium Falcon', $set1->getNameTranslations()['en']);

        $set2 = Set::where('set_num', '10497-1')->first();
        $this->assertEquals('Explorateur galactique', $set2->getNameTranslations()['fr']);
    }

    #[Test]
    public function it_uses_scraping_fallback_when_enabled()
    {
        Set::factory()->create(['set_num' => '75192-1', 'name' => ['en' => 'Millennium Falcon']]);

        Http::fake([
            'www.lego.com/api/graphql/*' => Http::response([
                'data' => ['searchSuggestions' => []],
            ]),
            'www.lego.com/fr-FR/product/*' => Http::response(
                '<html><body><h1 data-test="product-overview-name">Le Faucon Millenium</h1></body></html>'
            ),
        ]);

        $this->artisan('sets:translate --locale=fr --with-scraping')
            ->assertSuccessful();

        $set = Set::where('set_num', '75192-1')->first();
        $this->assertEquals('Le Faucon Millenium', $set->getNameTranslations()['fr']);
    }

    #[Test]
    public function it_only_translates_new_sets_with_new_only_flag()
    {
        Set::factory()->create([
            'set_num' => '75192-1',
            'name' => ['en' => 'Millennium Falcon', 'fr' => 'Faucon Millenium'],
        ]);
        Set::factory()->create([
            'set_num' => '10497-1',
            'name' => ['en' => 'Galaxy Explorer'],
        ]);

        Http::fake([
            'www.lego.com/api/graphql/*' => Http::response([
                'data' => [
                    'searchSuggestions' => [
                        ['__typename' => 'SingleVariantProduct', 'productCode' => '10497', 'name' => 'Explorateur galactique'],
                    ],
                ],
            ]),
        ]);

        $this->artisan('sets:translate --locale=fr --new-only')
            ->assertSuccessful();

        $set1 = Set::where('set_num', '75192-1')->first();
        $this->assertEquals('Faucon Millenium', $set1->getNameTranslations()['fr']);

        $set2 = Set::where('set_num', '10497-1')->first();
        $this->assertEquals('Explorateur galactique', $set2->getNameTranslations()['fr']);
    }
}
