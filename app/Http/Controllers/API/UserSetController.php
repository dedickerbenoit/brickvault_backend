<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserSetRequest;
use App\Http\Requests\UpdateUserSetRequest;
use App\Http\Resources\SetResource;
use App\Http\Resources\UserSetResource;
use App\Models\Set;
use App\Models\User;
use App\Models\UserSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class UserSetController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $userSets = $user->userSets()
            ->with('set.theme')
            ->orderByDesc('created_at')
            ->paginate(min($request->integer('per_page', 15), 100));

        return UserSetResource::collection($userSets);
    }

    public function store(StoreUserSetRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $set = Set::where('set_num', $request->set_num)->first();

        if (! $set) {
            return response()->json([
                'message' => 'Set not found in catalog.',
            ], 404);
        }

        $userSet = $user->userSets()->create([
            'set_id' => $set->id,
            'purchase_price' => $request->purchase_price,
            'purchase_date' => $request->purchase_date,
            'condition' => $request->condition,
            'notes' => $request->notes,
        ]);

        return (new UserSetResource($userSet->load('set.theme')))
            ->additional(['message' => 'Set added to collection.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(UserSet $userSet): UserSetResource
    {
        Gate::authorize('view', $userSet);

        return new UserSetResource($userSet->load('set.theme'));
    }

    public function update(UpdateUserSetRequest $request, UserSet $userSet): UserSetResource
    {
        Gate::authorize('update', $userSet);

        $userSet->update($request->validated());

        return (new UserSetResource($userSet->load('set.theme')))
            ->additional(['message' => 'Set updated.']);
    }

    public function destroy(UserSet $userSet): JsonResponse
    {
        Gate::authorize('delete', $userSet);

        $userSet->delete();

        return response()->json(null, 204);
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $query = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->string('q'));

        $sets = Set::with('theme')
            ->where(function ($q) use ($query) {
                $q->where('set_num', 'like', "%{$query}%")
                    ->orWhere('name->en', 'like', "%{$query}%")
                    ->orWhere('name->fr', 'like', "%{$query}%");
            })
            ->limit(15)
            ->get();

        return SetResource::collection($sets);
    }
}
