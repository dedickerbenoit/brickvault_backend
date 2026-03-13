<?php

namespace Tests\Unit\Services;

use App\Services\LegoTranslationService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LegoTranslationServiceTest extends TestCase
{
    #[Test]
    public function translate_batch_returns_translations_from_graphql()
    {
        config(['lego.graphql.rate_limit_ms' => 0]);

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

        $service = new LegoTranslationService;
        $results = $service->translateBatch(['75192-1', '10497-1'], 'fr');

        $this->assertCount(2, $results);
        $this->assertEquals('Faucon Millenium', $results['75192']);
        $this->assertEquals('Explorateur galactique', $results['10497']);
    }

    #[Test]
    public function translate_batch_handles_api_failure()
    {
        config(['lego.graphql.rate_limit_ms' => 0]);

        Http::fake([
            'www.lego.com/api/graphql/*' => Http::response(null, 500),
        ]);

        $service = new LegoTranslationService;
        $results = $service->translateBatch(['75192-1'], 'fr');

        $this->assertEmpty($results);
    }

    #[Test]
    public function translate_batch_ignores_non_matching_products()
    {
        config(['lego.graphql.rate_limit_ms' => 0]);

        Http::fake([
            'www.lego.com/api/graphql/*' => Http::response([
                'data' => [
                    'searchSuggestions' => [
                        ['__typename' => 'SingleVariantProduct', 'productCode' => '99999', 'name' => 'Autre set'],
                        ['__typename' => 'SearchSuggestion', 'text' => 'star wars'],
                    ],
                ],
            ]),
        ]);

        $service = new LegoTranslationService;
        $results = $service->translateBatch(['75192-1'], 'fr');

        $this->assertEmpty($results);
    }

    #[Test]
    public function scrape_set_name_extracts_title_from_html()
    {
        config([
            'lego.scraping.base_url' => 'https://www.lego.com',
            'lego.scraping.rate_limit_ms' => 0,
        ]);

        Http::fake([
            'www.lego.com/*' => Http::response(
                '<html><body><h1 data-test="product-overview-name">Le Faucon Millenium</h1></body></html>'
            ),
        ]);

        $service = new LegoTranslationService;
        $result = $service->scrapeSetName('75192-1', 'fr');

        $this->assertEquals('Le Faucon Millenium', $result);
    }

    #[Test]
    public function scrape_set_name_returns_null_on_404()
    {
        config([
            'lego.scraping.base_url' => 'https://www.lego.com',
            'lego.scraping.rate_limit_ms' => 0,
        ]);

        Http::fake([
            'www.lego.com/*' => Http::response(null, 404),
        ]);

        $service = new LegoTranslationService;
        $result = $service->scrapeSetName('99999-1', 'fr');

        $this->assertNull($result);
    }

    #[Test]
    public function scrape_theme_name_extracts_h1_from_html()
    {
        config([
            'lego.scraping.base_url' => 'https://www.lego.com',
            'lego.scraping.rate_limit_ms' => 0,
        ]);

        Http::fake([
            'www.lego.com/*' => Http::response(
                '<html><body><h1>Star Wars</h1></body></html>'
            ),
        ]);

        $service = new LegoTranslationService;
        $result = $service->scrapeThemeName('star-wars', 'fr');

        $this->assertEquals('Star Wars', $result);
    }

    #[Test]
    public function scrape_theme_name_returns_null_on_failure()
    {
        config([
            'lego.scraping.base_url' => 'https://www.lego.com',
            'lego.scraping.rate_limit_ms' => 0,
        ]);

        Http::fake([
            'www.lego.com/*' => Http::response(null, 404),
        ]);

        $service = new LegoTranslationService;
        $result = $service->scrapeThemeName('unknown-theme', 'fr');

        $this->assertNull($result);
    }

    #[Test]
    public function scrape_new_sets_page_parses_next_data()
    {
        config([
            'lego.scraping.base_url' => 'https://www.lego.com',
            'lego.scraping.rate_limit_ms' => 0,
        ]);

        $apolloState = [
            'ProductQueryResult:0' => ['total' => 2],
            'Product:21637' => [
                'productCode' => '21637',
                'name' => 'Venusaur, Charizard & Blastoise',
                'primaryImage' => ['__ref' => 'Image:21637'],
            ],
            'Image:21637' => ['url' => 'https://www.lego.com/cdn/img/21637.jpg'],
            'Product:72153' => [
                'productCode' => '72153',
                'name' => 'Batmobile Chase',
                'primaryImage' => ['__ref' => 'Image:72153'],
            ],
            'Image:72153' => ['url' => 'https://www.lego.com/cdn/img/72153.jpg'],
        ];

        $html = '<html><body><script id="__NEXT_DATA__" type="application/json">'
            .json_encode(['props' => ['apolloState' => $apolloState]])
            .'</script></body></html>';

        Http::fake([
            'www.lego.com/*' => Http::response($html),
        ]);

        $service = new LegoTranslationService;
        $result = $service->scrapeNewSetsPage(1, 'en');

        $this->assertEquals(2, $result['total']);
        $this->assertCount(2, $result['products']);
        $this->assertEquals('21637', $result['products'][0]['code']);
        $this->assertEquals('Venusaur, Charizard & Blastoise', $result['products'][0]['name']);
        $this->assertEquals('https://www.lego.com/cdn/img/21637.jpg', $result['products'][0]['img_url']);
    }

    #[Test]
    public function scrape_new_sets_page_returns_empty_without_next_data()
    {
        config([
            'lego.scraping.base_url' => 'https://www.lego.com',
            'lego.scraping.rate_limit_ms' => 0,
        ]);

        Http::fake([
            'www.lego.com/*' => Http::response('<html><body>No data</body></html>'),
        ]);

        $service = new LegoTranslationService;
        $result = $service->scrapeNewSetsPage(1, 'en');

        $this->assertEquals(0, $result['total']);
        $this->assertEmpty($result['products']);
    }

    #[Test]
    public function extract_product_code_removes_suffix()
    {
        $this->assertEquals('75192', LegoTranslationService::extractProductCode('75192-1'));
        $this->assertEquals('10497', LegoTranslationService::extractProductCode('10497-1'));
        $this->assertEquals('21637', LegoTranslationService::extractProductCode('21637-12'));
    }
}
