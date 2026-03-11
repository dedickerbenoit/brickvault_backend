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

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_user_sets_relationship()
    {
        $user = new User;

        $relation = $user->userSets();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(UserSet::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_user_wishlists_relationship()
    {
        $user = new User;

        $relation = $user->userWishlists();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(UserWishlist::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_access_user_sets()
    {
        $user = User::factory()->create();
        $set = Set::factory()->create();

        UserSet::factory()->forUser($user)->forSet($set)->create();

        $this->assertCount(1, $user->userSets);
        $this->assertEquals($set->id, $user->userSets->first()->set_id);
    }

    #[Test]
    public function it_can_access_user_wishlists()
    {
        $user = User::factory()->create();
        $set = Set::factory()->create();

        UserWishlist::factory()->forUser($user)->forSet($set)->create();

        $this->assertCount(1, $user->userWishlists);
        $this->assertEquals($set->id, $user->userWishlists->first()->set_id);
    }
}
