<?php

namespace App\Http\Controllers\Api\V1\RouteFinding;

use App\Http\Controllers\Controller;
use App\Models\RouteStop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CommuterRouteFindingController extends Controller
{
    public function nearestStops(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_lat' => ['required', 'numeric', 'between:-90,90'],
            'origin_lng' => ['required', 'numeric', 'between:-180,180'],
            'destination_lat' => ['required', 'numeric', 'between:-90,90'],
            'destination_lng' => ['required', 'numeric', 'between:-180,180'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $limit = (int) ($validated['limit'] ?? 3);
        $originLat = (float) $validated['origin_lat'];
        $originLng = (float) $validated['origin_lng'];
        $destinationLat = (float) $validated['destination_lat'];
        $destinationLng = (float) $validated['destination_lng'];

        $stops = RouteStop::query()
            ->select([
                'id',
                'route_id',
                'stop_name',
                'stop_type',
                'address',
                'latitude',
                'longitude',
                'stop_order',
            ])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereHas('route', fn ($query) => $query->where('status', 'active'))
            ->with(['route:id,route_name,status'])
            ->get();

        if ($stops->isEmpty()) {
            return response()->json([
                'message' => 'No active route stops are currently available.',
                'data' => [
                    'origin' => [
                        'lat' => $originLat,
                        'lng' => $originLng,
                    ],
                    'destination' => [
                        'lat' => $destinationLat,
                        'lng' => $destinationLng,
                    ],
                    'origin_nearest_stops' => [],
                    'destination_nearest_stops' => [],
                    'matching_routes' => [],
                ],
            ]);
        }

        $originNearest = $this->nearestForPoint(
            $stops,
            $originLat,
            $originLng,
            $limit,
        );

        $destinationNearest = $this->nearestForPoint(
            $stops,
            $destinationLat,
            $destinationLng,
            $limit,
        );

        $originRouteIds = $originNearest
            ->pluck('route.id')
            ->filter()
            ->unique()
            ->values();

        $destinationRouteIds = $destinationNearest
            ->pluck('route.id')
            ->filter()
            ->unique()
            ->values();

        $matchingRouteIds = $originRouteIds->intersect($destinationRouteIds)->values();

        $routeLookup = $originNearest
            ->merge($destinationNearest)
            ->pluck('route')
            ->filter()
            ->unique('id')
            ->keyBy('id');

        $matchingRoutes = $matchingRouteIds
            ->map(fn ($routeId) => $routeLookup->get($routeId))
            ->filter()
            ->values();

        return response()->json([
            'data' => [
                'origin' => [
                    'lat' => $originLat,
                    'lng' => $originLng,
                ],
                'destination' => [
                    'lat' => $destinationLat,
                    'lng' => $destinationLng,
                ],
                'origin_nearest_stops' => $originNearest->values(),
                'destination_nearest_stops' => $destinationNearest->values(),
                'matching_routes' => $matchingRoutes,
            ],
        ]);
    }

    private function nearestForPoint(
        Collection $stops,
        float $lat,
        float $lng,
        int $limit
    ): Collection {
        return $stops
            ->map(function (RouteStop $stop) use ($lat, $lng) {
                $distance = $this->distanceMeters(
                    $lat,
                    $lng,
                    (float) $stop->latitude,
                    (float) $stop->longitude,
                );

                return [
                    'id' => $stop->id,
                    'stop_name' => $stop->stop_name,
                    'stop_type' => $stop->stop_type,
                    'address' => $stop->address,
                    'latitude' => (float) $stop->latitude,
                    'longitude' => (float) $stop->longitude,
                    'stop_order' => $stop->stop_order,
                    'distance_meters' => round($distance, 2),
                    'route' => [
                        'id' => $stop->route?->id,
                        'route_name' => $stop->route?->route_name,
                    ],
                ];
            })
            ->sortBy('distance_meters')
            ->take($limit)
            ->values();
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0;

        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
