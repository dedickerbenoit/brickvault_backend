<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardStatsResource;
use Illuminate\Http\Request;
use App\Models\User;

class DashboardController extends Controller
{
    public function stats(Request $request): DashboardStatsResource
    {
        /** @var User $user */
        $user = $request->user();

        $setsCount = $user->userSets()->count();
        $totalValue = $user->userSets()->sum('purchase_price');
        $wishlistCount = $user->userWishlists()->count();

        return new DashboardStatsResource([
            'sets_count' => $setsCount,
            'total_value' => number_format((float) $totalValue, 2, '.', ''),
            'collections_count' => 0,
            'wishlist_count' => $wishlistCount,
        ]);
    }
}
