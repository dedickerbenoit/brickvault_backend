<?php

namespace Database\Factories;

use App\Models\Set;
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
            'set_number' => fake()->unique()->numerify('#####'),
            'name' => fake()->words(3, true),
            'theme' => fake()->randomElement(['Creator Expert', 'Star Wars', 'City', 'Technic', 'Ideas', 'Harry Potter']),
            'year' => fake()->numberBetween(2000, 2026),
            'pieces' => fake()->numberBetween(100, 5000),
            'retail_price' => fake()->randomFloat(2, 10, 500),
            'image_url' => fake()->imageUrl(640, 480, 'lego', true),
        ];
    }
}
