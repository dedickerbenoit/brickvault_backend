<?php

namespace Tests\Feature;

use App\Models\Set;
use App\Models\Theme;
use App\Models\User;
use App\Models\UserSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserSetControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_requires_authentication_to_list()
    {
        $response = $this->json('GET', '/api/user-sets');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_returns_empty_list_for_new_user()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->json('GET', '/api/user-sets');

        $response->assertStatus(200)
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function it_returns_paginated_user_sets()
    {
        $user = User::factory()->create();
        $sets = Set::factory()->count(20)->create();

        foreach ($sets as $set) {
            UserSet::factory()->forUser($user)->forSet($set)->create();
        }

        Passport::actingAs($user);

        $response = $this->json('GET', '/api/user-sets?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'set', 'purchase_price', 'purchase_date', 'condition', 'notes', 'created_at'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    #[Test]
    public function it_includes_set_and_theme_in_response()
    {
        $user = User::factory()->create();
        $theme = Theme::factory()->create(['name' => 'Star Wars']);
        $set = Set::factory()->create(['theme_id' => $theme->id, 'name' => 'Millennium Falcon']);
        UserSet::factory()->forUser($user)->forSet($set)->create();

        Passport::actingAs($user);

        $response = $this->json('GET', '/api/user-sets');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.set.name', 'Millennium Falcon')
            ->assertJsonPath('data.0.set.theme.name', 'Star Wars');
    }

    #[Test]
    public function it_adds_a_set_to_collection()
    {
        $user = User::factory()->create();
        $set = Set::factory()->create(['set_num' => '75192-1']);

        Passport::actingAs($user);

        $response = $this->json('POST', '/api/user-sets', [
            'set_num' => '75192-1',
            'purchase_price' => 699.99,
            'purchase_date' => '2024-03-10',
            'condition' => 'new',
            'notes' => 'Sealed',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.set.set_num', '75192-1')
            ->assertJsonPath('data.purchase_price', 699.99)
            ->assertJsonPath('data.condition', 'new');

        $this->assertDatabaseHas('user_sets', [
            'user_id' => $user->id,
            'set_id' => $set->id,
        ]);
    }

    #[Test]
    public function it_returns_404_for_unknown_set_num()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->json('POST', '/api/user-sets', [
            'set_num' => '99999-1',
            'condition' => 'new',
        ]);

        $response->assertStatus(404);
    }

    #[Test]
    public function it_validates_store_request()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->json('POST', '/api/user-sets', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['set_num', 'condition']);
    }

    #[Test]
    public function it_shows_a_user_set()
    {
        $user = User::factory()->create();
        $set = Set::factory()->create();
        $userSet = UserSet::factory()->forUser($user)->forSet($set)->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/api/user-sets/{$userSet->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $userSet->id)
            ->assertJsonPath('data.set.set_num', $set->set_num);
    }

    #[Test]
    public function it_forbids_viewing_another_users_set()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $set = Set::factory()->create();
        $userSet = UserSet::factory()->forUser($user1)->forSet($set)->create();

        Passport::actingAs($user2);

        $response = $this->json('GET', "/api/user-sets/{$userSet->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function it_updates_a_user_set()
    {
        $user = User::factory()->create();
        $set = Set::factory()->create();
        $userSet = UserSet::factory()->forUser($user)->forSet($set)->create([
            'condition' => 'new',
            'purchase_price' => 100,
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/api/user-sets/{$userSet->id}", [
            'condition' => 'built',
            'purchase_price' => 89.99,
            'notes' => 'Great build',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.condition', 'built')
            ->assertJsonPath('data.purchase_price', 89.99)
            ->assertJsonPath('data.notes', 'Great build');
    }

    #[Test]
    public function it_forbids_updating_another_users_set()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $set = Set::factory()->create();
        $userSet = UserSet::factory()->forUser($user1)->forSet($set)->create();

        Passport::actingAs($user2);

        $response = $this->json('PUT', "/api/user-sets/{$userSet->id}", [
            'condition' => 'built',
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function it_deletes_a_user_set()
    {
        $user = User::factory()->create();
        $set = Set::factory()->create();
        $userSet = UserSet::factory()->forUser($user)->forSet($set)->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/api/user-sets/{$userSet->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('user_sets', ['id' => $userSet->id]);
    }

    #[Test]
    public function it_forbids_deleting_another_users_set()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $set = Set::factory()->create();
        $userSet = UserSet::factory()->forUser($user1)->forSet($set)->create();

        Passport::actingAs($user2);

        $response = $this->json('DELETE', "/api/user-sets/{$userSet->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function it_searches_sets_by_number()
    {
        $user = User::factory()->create();
        Set::factory()->create(['set_num' => '75192-1', 'name' => 'Millennium Falcon']);
        Set::factory()->create(['set_num' => '10497-1', 'name' => 'Galaxy Explorer']);
        Set::factory()->create(['set_num' => '75257-1', 'name' => 'Millennium Falcon Rise']);

        Passport::actingAs($user);

        $response = $this->json('GET', '/api/user-sets/search?q=7519');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('75192-1', $response->json('data.0.set_num'));
    }

    #[Test]
    public function it_searches_sets_by_name()
    {
        $user = User::factory()->create();
        Set::factory()->create(['set_num' => '75192-1', 'name' => 'Millennium Falcon']);
        Set::factory()->create(['set_num' => '10497-1', 'name' => 'Galaxy Explorer']);

        Passport::actingAs($user);

        $response = $this->json('GET', '/api/user-sets/search?q=falcon');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Millennium Falcon', $response->json('data.0.name'));
    }

    #[Test]
    public function it_validates_search_query()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $this->json('GET', '/api/user-sets/search')
            ->assertStatus(422);

        $this->json('GET', '/api/user-sets/search?q=a')
            ->assertStatus(422);
    }

    #[Test]
    public function it_only_lists_authenticated_users_sets()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $set = Set::factory()->create();

        UserSet::factory()->forUser($user1)->forSet($set)->create();
        UserSet::factory()->forUser($user2)->forSet($set)->create();

        Passport::actingAs($user1);

        $response = $this->json('GET', '/api/user-sets');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
