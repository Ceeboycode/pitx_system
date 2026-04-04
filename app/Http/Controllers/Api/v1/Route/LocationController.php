<?php

namespace App\Http\Controllers\Api\V1\Route;

use App\Enums\RouteStatus;
use App\Http\Controllers\Controller;
use App\Models\RouteStop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * GET /api/v1/locations?q=
     *
     * Returns up to 15 stops matching the query, from active routes only.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $stops = RouteStop::query()
            ->join('routes', 'route_stops.route_id', '=', 'routes.id')
            ->where('routes.status', RouteStatus::Active)
            ->whereNull('routes.deleted_at')
            ->whereNull('route_stops.deleted_at')
            ->where('route_stops.stop_name', 'like', '%' . $validated['q'] . '%')
            ->select(
                'route_stops.id',
                'route_stops.stop_name',
                'route_stops.address',
                'route_stops.latitude',
                'route_stops.longitude'
            )
            ->orderBy('route_stops.stop_name')
            ->limit(15)
            ->get();

        return response()->json(['data' => $stops]);
    }
}
