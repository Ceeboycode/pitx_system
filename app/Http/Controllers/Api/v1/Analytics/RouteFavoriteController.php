<?php

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Http\Controllers\Controller;
use App\Models\RouteFavorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteFavoriteController extends Controller
{
    // GET v1/analytics/route-favorites
    public function index(Request $request): JsonResponse
    {
        $favorites = RouteFavorite::where('user_id', $request->user()->id)
            ->latest()
            ->get(['id', 'origin', 'destination', 'created_at']);

        return response()->json(['data' => $favorites]);
    }

    // POST v1/analytics/route-favorites
    // Idempotent: returns existing record with 200 if already saved, 201 if newly created
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin'      => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
        ]);

        $favorite = RouteFavorite::firstOrCreate([
            'user_id'     => $request->user()->id,
            'origin'      => $validated['origin'],
            'destination' => $validated['destination'],
        ]);

        return response()->json(
            ['data' => $favorite->only(['id', 'origin', 'destination', 'created_at'])],
            $favorite->wasRecentlyCreated ? 201 : 200
        );
    }

    // DELETE v1/analytics/route-favorites/{favorite}
    public function destroy(Request $request, RouteFavorite $favorite): JsonResponse
    {
        // Only the owning user may delete their own favorite
        abort_unless((int) $favorite->user_id === (int) $request->user()->id, 403);

        $favorite->delete();

        return response()->json(['message' => 'Favorite removed.']);
    }
}
