<?php

namespace Database\Factories;

use App\Models\Set;
use App\Models\User;
use App\Models\UserWishlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserWishlist>
 */
class UserWishlistFactory extends Factory
{
    protected $model = UserWishlist::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'set_id' => Set::factory(),
            'priority' => fake()->numberBetween(0, 5),
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

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 5,
        ]);
    }
}
