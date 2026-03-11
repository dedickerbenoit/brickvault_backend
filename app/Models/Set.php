<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $set_num
 * @property string $name
 * @property int|null $theme_id
 * @property int|null $year
 * @property int|null $num_parts
 * @property string|null $img_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Theme|null $theme
 * @property-read Collection<int, UserSet> $userSets
 * @property-read Collection<int, UserWishlist> $userWishlists
 */
class Set extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class, 'theme_id');
    }

    public function userSets(): HasMany
    {
        return $this->hasMany(UserSet::class);
    }

    public function userWishlists(): HasMany
    {
        return $this->hasMany(UserWishlist::class);
    }
}
