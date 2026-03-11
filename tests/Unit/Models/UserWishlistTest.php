<?php

namespace Tests\Unit\Models;

use App\Models\Set;
use App\Models\User;
use App\Models\UserWishlist;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserWishlistTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_user()
    {
        $wishlist = new UserWishlist;

        $relation = $wishlist->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_set()
    {
        $wishlist = new UserWishlist;

        $relation = $wishlist->set();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Set::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_access_related_user()
    {
        $user = User::factory()->create();
        $set = Set::factory()->create();

        $wishlist = UserWishlist::factory()->forUser($user)->forSet($set)->create();

        $this->assertEquals($user->id, $wishlist->user->id);
        $this->assertEquals($user->email, $wishlist->user->email);
    }

    #[Test]
    public function it_can_access_related_set()
    {
        $user = User::factory()->create();
        $set = Set::factory()->create();

        $wishlist = UserWishlist::factory()->forUser($user)->forSet($set)->create();

        $this->assertEquals($set->id, $wishlist->set->id);
        $this->assertEquals($set->set_num, $wishlist->set->set_num);
    }

    #[Test]
    public function it_has_priority_attribute()
    {
        $user = User::factory()->create();
        $set = Set::factory()->create();

        $wishlist = UserWishlist::factory()->forUser($user)->forSet($set)->create([
            'priority' => 3,
        ]);

        $this->assertEquals(3, $wishlist->priority);
    }
}
