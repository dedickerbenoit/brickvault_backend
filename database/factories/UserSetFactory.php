<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Set;
use App\Models\UserSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSet>
 */
class UserSetFactory extends Factory
{
    protected $model = UserSet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'set_id' => Set::factory(),
            'purchase_price' => fake()->randomFloat(2, 10, 500),
            'purchase_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'condition' => fake()->randomElement(['new', 'opened', 'built']),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function forSet(Set $set): static
    {
        return $this->state(fn (array $attributes) => [
            'set_id' => $set->id,
        ]);
    }
}
