<?php

namespace App\Http\Controllers\Api\V1\Route;

use App\Http\Controllers\Controller;
use App\Models\RouteStop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * GET /api/v1/locations?q=&lat=&lng=&origin_stop_id=&destination_stop_id=
     *
     * Returns up to 15 matching stops from non-deleted routes.
     *
     * Search modes:
     * - Text search:      q (min 2 chars) — full-text across stop name and route labels
     * - Nearby search:    lat + lng without q — nearest stops sorted by Haversine distance
     * - Combined:         q + lat/lng — text matches sorted by distance
     *
     * Route filtering (mutually exclusive; use one or the other, not both):
     * - origin_stop_id:      restricts results to stops on the same route as the given origin
     * - destination_stop_id: restricts results to stops on the same route as the given destination
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q'                    => ['nullable', 'string', 'min:2', 'max:100'],
            'lat'                  => ['nullable', 'numeric', 'between:-90,90'],
            'lng'                  => ['nullable', 'numeric', 'between:-180,180'],
            'origin_stop_id'       => ['nullable', 'integer', 'exists:route_stops,id'],
            'destination_stop_id'  => ['nullable', 'integer', 'exists:route_stops,id'],
        ]);

        $hasQuery       = isset($validated['q']) && strlen($validated['q']) >= 2;
        $hasCoords      = isset($validated['lat'], $validated['lng']);
        $originStopId   = $validated['origin_stop_id'] ?? null;
        $destStopId     = $validated['destination_stop_id'] ?? null;

        // Require at least one search criterion
        if (!$hasQuery && !$hasCoords) {
            return response()->json(['data' => []]);
        }

        $stops = RouteStop::query()
            ->join('routes', 'route_stops.route_id', '=', 'routes.id')
            ->whereNull('routes.deleted_at')
            ->whereNull('route_stops.deleted_at')

            // ── Text filter ────────────────────────────────────────────────
            ->when($hasQuery, function ($query) use ($validated) {
                $like = '%' . $validated['q'] . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('route_stops.stop_name', 'like', $like)
                      ->orWhere(function ($inner) use ($like) {
                          $inner->where('route_stops.stop_type', 'origin')
                                ->where('routes.origin_name', 'like', $like);
                      })
                      ->orWhere(function ($inner) use ($like) {
                          $inner->where('route_stops.stop_type', 'destination')
                                ->where('routes.destination_name', 'like', $like);
                      });
                });
            })

            // ── Route-aware filter: same route as origin ───────────────────
            ->when($originStopId !== null, function ($query) use ($originStopId) {
                $query->whereIn('route_stops.route_id', function ($sub) use ($originStopId) {
                    $sub->select('route_id')
                        ->from('route_stops')
                        ->where('id', $originStopId);
                });
            })

            // ── Route-aware filter: same route as destination ──────────────
            ->when($destStopId !== null, function ($query) use ($destStopId) {
                $query->whereIn('route_stops.route_id', function ($sub) use ($destStopId) {
                    $sub->select('route_id')
                        ->from('route_stops')
                        ->where('id', $destStopId);
                });
            })

            // ── Nearby-only: skip stops without coordinates ────────────────
            ->when($hasCoords && !$hasQuery, function ($query) {
                $query->whereNotNull('route_stops.latitude')
                      ->whereNotNull('route_stops.longitude');
            })

            ->select(
                'route_stops.id',
                'route_stops.stop_name',
                'route_stops.address',
                'route_stops.latitude',
                'route_stops.longitude'
            )
            ->distinct()

            // ── Distance sort (primary when coords provided) ───────────────
            ->when(
                $hasCoords,
                function ($query) use ($validated) {
                    $lat = (float) $validated['lat'];
                    $lng = (float) $validated['lng'];
                    $query->orderByRaw(
                        '(6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(route_stops.latitude)) *
                          COS(RADIANS(route_stops.longitude) - RADIANS(?)) +
                          SIN(RADIANS(?)) * SIN(RADIANS(route_stops.latitude))))',
                        [$lat, $lng, $lat]
                    );
                }
            )
            ->orderBy('route_stops.stop_name')
            ->limit(15)
            ->get();

        return response()->json(['data' => $stops]);
    }
}
