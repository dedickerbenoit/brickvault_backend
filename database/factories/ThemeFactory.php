<?php

namespace Database\Factories;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Theme>
 */
class ThemeFactory extends Factory
{
    protected $model = Theme::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->numberBetween(1, 99999),
            'name' => ['en' => fake()->randomElement(['Star Wars', 'City', 'Technic', 'Creator Expert', 'Ideas', 'Harry Potter', 'Ninjago', 'Marvel'])],
            'parent_id' => null,
        ];
    }
}
