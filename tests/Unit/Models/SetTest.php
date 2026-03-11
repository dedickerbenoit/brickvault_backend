<?php

namespace Tests\Unit\Models;

use App\Models\Set;
use App\Models\User;
use App\Models\UserSet;
use App\Models\UserWishlist;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_user_sets_relationship()
    {
        $set = new Set;

        $relation = $set->userSets();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(UserSet::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_user_wishlists_relationship()
    {
        $set = new Set;

        $relation = $set->userWishlists();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(UserWishlist::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_access_user_sets()
    {
        $set = Set::factory()->create();
        $user = User::factory()->create();

        UserSet::factory()->forUser($user)->forSet($set)->create();

        $this->assertCount(1, $set->userSets);
        $this->assertEquals($user->id, $set->userSets->first()->user_id);
    }

    #[Test]
    public function it_can_access_user_wishlists()
    {
        $set = Set::factory()->create();
        $user = User::factory()->create();

        UserWishlist::factory()->forUser($user)->forSet($set)->create();

        $this->assertCount(1, $set->userWishlists);
        $this->assertEquals($user->id, $set->userWishlists->first()->user_id);
    }
}
