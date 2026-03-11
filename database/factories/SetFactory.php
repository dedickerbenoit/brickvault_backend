<?php

namespace Database\Factories;

use App\Models\Set;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Set>
 */
class SetFactory extends Factory
{
    protected $model = Set::class;

    public function definition(): array
    {
        return [
            'set_num' => fake()->unique()->numerify('#####-1'),
            'name' => fake()->words(3, true),
            'theme_id' => Theme::factory(),
            'year' => fake()->numberBetween(2000, 2026),
            'num_parts' => fake()->numberBetween(100, 5000),
            'img_url' => fake()->imageUrl(640, 480, 'lego', true),
        ];
    }
}
