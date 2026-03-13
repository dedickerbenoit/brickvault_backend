<?php

namespace Tests\Feature;

use App\Models\Set;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchMultilingualTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_searches_by_english_name()
    {
        $user = User::factory()->create();
        Set::factory()->create([
            'set_num' => '75192-1',
            'name' => ['en' => 'Millennium Falcon', 'fr' => 'Faucon Millenium'],
        ]);
        Set::factory()->create([
            'set_num' => '10497-1',
            'name' => ['en' => 'Galaxy Explorer', 'fr' => 'Explorateur galactique'],
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', '/api/user-sets/search?q=falcon');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('75192-1', $response->json('data.0.set_num'));
    }

    #[Test]
    public function it_searches_by_french_name()
    {
        $user = User::factory()->create();
        Set::factory()->create([
            'set_num' => '75192-1',
            'name' => ['en' => 'Millennium Falcon', 'fr' => 'Faucon Millenium'],
        ]);
        Set::factory()->create([
            'set_num' => '10497-1',
            'name' => ['en' => 'Galaxy Explorer', 'fr' => 'Explorateur galactique'],
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', '/api/user-sets/search?q=faucon');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('75192-1', $response->json('data.0.set_num'));
    }

    #[Test]
    public function it_still_searches_by_set_num()
    {
        $user = User::factory()->create();
        Set::factory()->create([
            'set_num' => '75192-1',
            'name' => ['en' => 'Millennium Falcon', 'fr' => 'Faucon Millenium'],
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', '/api/user-sets/search?q=7519');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('75192-1', $response->json('data.0.set_num'));
    }
}
