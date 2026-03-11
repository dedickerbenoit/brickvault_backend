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
 * @property string $name
 * @property int|null $parent_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Theme|null $parent
 * @property-read Collection<int, Theme> $children
 * @property-read Collection<int, Set> $sets
 */
class Theme extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $guarded = [];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Theme::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Theme::class, 'parent_id');
    }

    public function sets(): HasMany
    {
        return $this->hasMany(Set::class, 'theme_id');
    }
}
