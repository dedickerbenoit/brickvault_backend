<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatsResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sets_count' => $this->resource['sets_count'],
            'total_value' => $this->resource['total_value'],
            'collections_count' => $this->resource['collections_count'],
            'wishlist_count' => $this->resource['wishlist_count'],
        ];
    }
}
