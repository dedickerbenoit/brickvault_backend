<?php

namespace Tests\Feature;

use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TranslateThemesCommandTest extends TestCase
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

    #[Test]
    public function it_translates_known_themes_from_mapping()
    {
        Theme::factory()->create([
            'name' => ['en' => 'Botanical Collection'],
        ]);

        $this->artisan('themes:translate --locale=fr')
            ->assertSuccessful();

        $theme = Theme::first();
        $this->assertEquals('Collection Botanique', $theme->getNameTranslations()['fr']);
    }

    #[Test]
    public function it_skips_themes_without_known_translation()
    {
        Theme::factory()->create([
            'name' => ['en' => 'Unknown Theme XYZ'],
        ]);

        Http::fake();

        $this->artisan('themes:translate --locale=fr')
            ->assertSuccessful();

        $theme = Theme::first();
        $this->assertArrayNotHasKey('fr', $theme->getNameTranslations());
    }

    #[Test]
    public function it_uses_scraping_fallback_when_enabled()
    {
        Theme::factory()->create([
            'name' => ['en' => 'Unknown Theme XYZ'],
        ]);

        Http::fake([
            'www.lego.com/fr-FR/themes/unknown-theme-xyz' => Http::response(
                '<html><body><h1>Thème Inconnu XYZ</h1></body></html>'
            ),
        ]);

        $this->artisan('themes:translate --locale=fr --with-scraping')
            ->assertSuccessful();

        $theme = Theme::first();
        $this->assertEquals('Thème Inconnu XYZ', $theme->getNameTranslations()['fr']);
    }

    #[Test]
    public function it_skips_themes_without_en_name()
    {
        Theme::factory()->create([
            'name' => ['de' => 'Nur Deutsch'],
        ]);

        $this->artisan('themes:translate --locale=fr')
            ->assertSuccessful();

        $theme = Theme::first();
        $this->assertArrayNotHasKey('fr', $theme->getNameTranslations());
    }

    #[Test]
    public function it_prefers_mapping_over_scraping()
    {
        Theme::factory()->create([
            'name' => ['en' => 'Education'],
        ]);

        Http::fake([
            'www.lego.com/*' => Http::response(
                '<html><body><h1>Éducation LEGO</h1></body></html>'
            ),
        ]);

        $this->artisan('themes:translate --locale=fr --with-scraping')
            ->assertSuccessful();

        $theme = Theme::first();
        $this->assertEquals('Éducation', $theme->getNameTranslations()['fr']);

        Http::assertNothingSent();
    }
}
