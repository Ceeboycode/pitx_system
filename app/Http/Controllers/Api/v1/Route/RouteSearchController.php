<?php

namespace App\Http\Controllers\Api\V1\Route;

use App\Http\Controllers\Controller;
use App\Models\RouteStop;
use App\Services\MapboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class RouteSearchController extends Controller
{
    public function __construct(private MapboxService $mapboxService) {}

    /**
     * GET /api/v1/routes/search
     *
     * Accepts origin_id, destination_id, and optional ordered stop_ids.
     * Calls Mapbox Directions API and returns route geometry + ETA + distance.
     *
     * Stop order is ALWAYS preserved as provided. No reordering is performed.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_id'      => ['required', 'integer', Rule::exists('route_stops', 'id')->whereNull('deleted_at')],
            'destination_id' => ['required', 'integer', 'different:origin_id', Rule::exists('route_stops', 'id')->whereNull('deleted_at')],
            'stop_ids'       => ['nullable', 'array', 'max:8'],
            'stop_ids.*'     => ['integer', Rule::exists('route_stops', 'id')->whereNull('deleted_at')],
        ]);

        $originId      = (int) $validated['origin_id'];
        $destinationId = (int) $validated['destination_id'];
        $stopIds       = array_map('intval', $validated['stop_ids'] ?? []);

        // Validate no duplicates across all provided IDs
        $allIds = array_merge([$originId], $stopIds, [$destinationId]);

        if (count($allIds) !== count(array_unique($allIds))) {
            return response()->json([
                'message' => 'Duplicate stops are not allowed.',
            ], 422);
        }

        // Fetch stops — preserve client-supplied order for middle stops
        $origin      = RouteStop::findOrFail($originId);
        $destination = RouteStop::findOrFail($destinationId);

        $middleStops = collect();
        if (! empty($stopIds)) {
            $stopsById   = RouteStop::findMany($stopIds)->keyBy('id');
            $middleStops = collect($stopIds)
                ->map(fn (int $id) => $stopsById->get($id))
                ->filter()
                ->values();

            if ($middleStops->count() !== count($stopIds)) {
                return response()->json([
                    'message' => 'One or more stops could not be found.',
                ], 422);
            }
        }

        // Build ordered waypoint list: origin → middle stops → destination
        $orderedStops = collect([$origin])
            ->concat($middleStops)
            ->push($destination);

        $waypoints = $orderedStops
            ->map(fn (RouteStop $stop) => [
                'lat' => $stop->latitude,
                'lng' => $stop->longitude,
            ])
            ->values()
            ->all();

        try {
            $directions = $this->mapboxService->getDirections($waypoints);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'origin'          => $this->serializeStop($origin),
                'destination'     => $this->serializeStop($destination),
                'stops'           => $middleStops->map(fn ($s) => $this->serializeStop($s))->values(),
                'route_geometry'  => $directions['geometry'],
                'eta_seconds'     => $directions['duration'],
                'distance_meters' => $directions['distance'],
            ],
        ]);
    }

    private function serializeStop(RouteStop $stop): array
    {
        return [
            'id'        => $stop->id,
            'stop_name' => $stop->stop_name,
            'stop_type' => $stop->stop_type,
            'address'   => $stop->address,
            'latitude'  => $stop->latitude,
            'longitude' => $stop->longitude,
        ];
    }
}
