<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Set;
use App\Models\UserSet;
use App\Models\UserWishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->json('GET', '/api/dashboard/stats');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    #[Test]
    public function it_returns_dashboard_stats_successfully()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->json('GET', '/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'sets_count',
                'total_value',
                'collections_count',
                'wishlist_count',
            ]);
    }

    #[Test]
    public function it_returns_zero_stats_for_new_user()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->json('GET', '/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'sets_count' => 0,
                'total_value' => '0.00',
                'collections_count' => 0,
                'wishlist_count' => 0,
            ]);
    }

    #[Test]
    public function it_calculates_sets_count_correctly()
    {
        $user = User::factory()->create();
        $sets = Set::factory()->count(3)->create();

        UserSet::factory()->forUser($user)->forSet($sets[0])->create();
        UserSet::factory()->forUser($user)->forSet($sets[1])->create();
        UserSet::factory()->forUser($user)->forSet($sets[2])->create();

        Passport::actingAs($user);

        $response = $this->json('GET', '/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'sets_count' => 3,
            ]);
    }

    #[Test]
    public function it_calculates_total_value_correctly()
    {
        $user = User::factory()->create();
        $sets = Set::factory()->count(2)->create();

        UserSet::factory()->forUser($user)->forSet($sets[0])->create([
            'purchase_price' => 100.50,
        ]);
        UserSet::factory()->forUser($user)->forSet($sets[1])->create([
            'purchase_price' => 49.99,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', '/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'sets_count' => 2,
                'total_value' => '150.49',
            ]);
    }

    #[Test]
    public function it_calculates_wishlist_count_correctly()
    {
        $user = User::factory()->create();
        $sets = Set::factory()->count(5)->create();

        UserWishlist::factory()->forUser($user)->forSet($sets[0])->create();
        UserWishlist::factory()->forUser($user)->forSet($sets[1])->create();
        UserWishlist::factory()->forUser($user)->forSet($sets[2])->create();

        Passport::actingAs($user);

        $response = $this->json('GET', '/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'wishlist_count' => 3,
            ]);
    }

    #[Test]
    public function it_only_returns_stats_for_authenticated_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $set1 = Set::factory()->create();
        $set2 = Set::factory()->create();

        // User 1 has 2 sets
        UserSet::factory()->forUser($user1)->forSet($set1)->create(['purchase_price' => 50.00]);
        UserSet::factory()->forUser($user1)->forSet($set2)->create(['purchase_price' => 75.00]);

        // User 2 has 1 set
        UserSet::factory()->forUser($user2)->forSet($set1)->create(['purchase_price' => 100.00]);

        Passport::actingAs($user1);

        $response = $this->json('GET', '/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'sets_count' => 2,
                'total_value' => '125.00',
            ]);
    }

    #[Test]
    public function it_returns_zero_for_collections_count()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->json('GET', '/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'collections_count' => 0,
            ]);
    }

    #[Test]
    public function it_handles_null_purchase_prices()
    {
        $user = User::factory()->create();
        $set = Set::factory()->create();

        UserSet::factory()->forUser($user)->forSet($set)->create([
            'purchase_price' => null,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', '/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'sets_count' => 1,
                'total_value' => '0.00',
            ]);
    }
}
