<?php

namespace Tests\Unit\Models;

use App\Models\Set;
use App\Models\User;
use App\Models\UserSet;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserSetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_user()
    {
        $userSet = new UserSet;

        $relation = $userSet->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_set()
    {
        $userSet = new UserSet;

        $relation = $userSet->set();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Set::class, $relation->getRelated());
    }

    #[Test]
    public function it_casts_purchase_price_to_decimal()
    {
        $user = User::factory()->create();
        $set = Set::factory()->create();

        $userSet = UserSet::factory()->forUser($user)->forSet($set)->create([
            'purchase_price' => 123.45,
        ]);

        $this->assertIsString($userSet->purchase_price);
        $this->assertEquals('123.45', $userSet->purchase_price);
    }

    #[Test]
    public function it_casts_purchase_date_to_date()
    {
        $user = User::factory()->create();
        $set = Set::factory()->create();

        $userSet = UserSet::factory()->forUser($user)->forSet($set)->create([
            'purchase_date' => '2024-01-15',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $userSet->purchase_date);
        $this->assertEquals('2024-01-15', $userSet->purchase_date->format('Y-m-d'));
    }

    #[Test]
    public function it_can_access_related_user()
    {
        $user = User::factory()->create();
        $set = Set::factory()->create();

        $userSet = UserSet::factory()->forUser($user)->forSet($set)->create();

        $this->assertEquals($user->id, $userSet->user->id);
        $this->assertEquals($user->email, $userSet->user->email);
    }

    #[Test]
    public function it_can_access_related_set()
    {
        $user = User::factory()->create();
        $set = Set::factory()->create();

        $userSet = UserSet::factory()->forUser($user)->forSet($set)->create();

        $this->assertEquals($set->id, $userSet->set->id);
        $this->assertEquals($set->set_number, $userSet->set->set_number);
    }
}
