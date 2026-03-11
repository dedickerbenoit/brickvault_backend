<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Set extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function userSets(): HasMany
    {
        return $this->hasMany(UserSet::class);
    }

    public function userWishlists(): HasMany
    {
        return $this->hasMany(UserWishlist::class);
    }
}
