<?php

namespace Tests\Unit\Models\Concerns;

use App\Models\Set;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HasTranslatableNameTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function accessor_returns_name_for_current_locale()
    {
        $set = Set::factory()->create([
            'name' => ['en' => 'Millennium Falcon', 'fr' => 'Faucon Millenium'],
        ]);

        app()->setLocale('en');
        $set->refresh();
        $this->assertEquals('Millennium Falcon', $set->name);

        app()->setLocale('fr');
        $set->refresh();
        $this->assertEquals('Faucon Millenium', $set->name);
    }

    #[Test]
    public function accessor_falls_back_to_english()
    {
        $set = Set::factory()->create([
            'name' => ['en' => 'Galaxy Explorer'],
        ]);

        app()->setLocale('fr');
        $set->refresh();
        $this->assertEquals('Galaxy Explorer', $set->name);
    }

    #[Test]
    public function mutator_wraps_string_in_json()
    {
        $set = Set::factory()->create([
            'name' => 'Star Destroyer',
        ]);

        $translations = $set->getNameTranslations();
        $this->assertEquals(['en' => 'Star Destroyer'], $translations);
    }

    #[Test]
    public function mutator_accepts_array()
    {
        $set = Set::factory()->create([
            'name' => ['en' => 'X-Wing', 'fr' => 'X-Wing'],
        ]);

        $translations = $set->getNameTranslations();
        $this->assertEquals('X-Wing', $translations['en']);
        $this->assertEquals('X-Wing', $translations['fr']);
    }

    #[Test]
    public function get_name_translations_returns_all_locales()
    {
        $set = Set::factory()->create([
            'name' => ['en' => 'Batmobile', 'fr' => 'Batmobile', 'de' => 'Batmobil'],
        ]);

        $translations = $set->getNameTranslations();
        $this->assertCount(3, $translations);
        $this->assertEquals('Batmobil', $translations['de']);
    }

    #[Test]
    public function set_name_translation_merges_locale()
    {
        $set = Set::factory()->create([
            'name' => ['en' => 'Hogwarts Castle'],
        ]);

        $set->setNameTranslation('fr', 'Château de Poudlard');
        $set->save();

        $set->refresh();
        $translations = $set->getNameTranslations();
        $this->assertEquals('Hogwarts Castle', $translations['en']);
        $this->assertEquals('Château de Poudlard', $translations['fr']);
    }

    #[Test]
    public function trait_works_on_theme_model()
    {
        $theme = Theme::factory()->create([
            'name' => ['en' => 'Star Wars', 'fr' => 'Star Wars'],
        ]);

        app()->setLocale('fr');
        $theme->refresh();
        $this->assertEquals('Star Wars', $theme->name);

        $translations = $theme->getNameTranslations();
        $this->assertArrayHasKey('en', $translations);
        $this->assertArrayHasKey('fr', $translations);
    }
}
