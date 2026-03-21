<?php

namespace App\Http\Controllers\Api\V1\Route;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Route\LocationSearchRequest;
use App\Http\Requests\Api\V1\Route\RouteSearchRequest;
use App\Models\Route;
use App\Models\RouteStop;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CommuterRouteController extends Controller
{
    public function locations(LocationSearchRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $search = Str::lower(trim((string) ($validated['search'] ?? '')));
        $limit = (int) ($validated['limit'] ?? 12);

        $locations = Route::query()
            ->where('status', 'active')
            ->with([
                'gate:id,gate_name',
                'stops:id,route_id,stop_name,stop_type,address,latitude,longitude,stop_order',
            ])
            ->orderBy('route_name')
            ->get()
            ->flatMap(fn (Route $route) => $this->extractLocationsFromRoute($route))
            ->filter(function (array $location) use ($search) {
                if ($search === '') {
                    return true;
                }

                return $this->matchesLocation($location, $search);
            })
            ->groupBy(fn (array $location) => $this->locationKey($location))
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'name' => $first['name'],
                    'address' => $first['address'],
                    'latitude' => $first['latitude'],
                    'longitude' => $first['longitude'],
                    'source' => $first['source'],
                    'route_names' => $items->pluck('route_name')->filter()->unique()->values(),
                    'gate_names' => $items->pluck('gate_name')->filter()->unique()->values(),
                    'match_count' => $items->count(),
                ];
            })
            ->sortBy([
                ['match_count', 'desc'],
                ['name', 'asc'],
            ])
            ->take($limit)
            ->values();

        return response()->json([
            'data' => $locations,
        ]);
    }

    public function search(RouteSearchRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $origin = trim((string) $validated['origin']);
        $destination = trim((string) $validated['destination']);
        $limit = (int) ($validated['limit'] ?? 10);

        $matches = Route::query()
            ->where('status', 'active')
            ->with([
                'gate:id,gate_name,bays',
                'stops:id,route_id,stop_name,stop_type,address,latitude,longitude,stop_order',
            ])
            ->orderBy('route_name')
            ->get()
            ->map(fn (Route $route) => $this->buildRouteMatch($route, $origin, $destination))
            ->filter()
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->map(fn (array $match) => $match['payload']);

        return response()->json([
            'data' => $matches,
            'meta' => [
                'origin' => $origin,
                'destination' => $destination,
                'count' => $matches->count(),
            ],
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function extractLocationsFromRoute(Route $route): Collection
    {
        $items = collect();

        if (filled($route->origin_name)) {
            $items->push([
                'name' => $route->origin_name,
                'address' => $route->route_name,
                'latitude' => $route->origin_lat !== null ? (float) $route->origin_lat : null,
                'longitude' => $route->origin_lng !== null ? (float) $route->origin_lng : null,
                'source' => 'route_origin',
                'route_name' => $route->route_name,
                'gate_name' => $route->gate?->gate_name,
            ]);
        }

        if (filled($route->destination_name)) {
            $items->push([
                'name' => $route->destination_name,
                'address' => $route->route_name,
                'latitude' => $route->destination_lat !== null ? (float) $route->destination_lat : null,
                'longitude' => $route->destination_lng !== null ? (float) $route->destination_lng : null,
                'source' => 'route_destination',
                'route_name' => $route->route_name,
                'gate_name' => $route->gate?->gate_name,
            ]);
        }

        foreach ($route->stops->sortBy('stop_order') as $stop) {
            $items->push([
                'name' => $stop->stop_name,
                'address' => $stop->address,
                'latitude' => $stop->latitude !== null ? (float) $stop->latitude : null,
                'longitude' => $stop->longitude !== null ? (float) $stop->longitude : null,
                'source' => 'route_stop',
                'route_name' => $route->route_name,
                'gate_name' => $route->gate?->gate_name,
            ]);
        }

        return $items;
    }

    /**
     * @return array{score:int,payload:array<string,mixed>}|null
     */
    private function buildRouteMatch(Route $route, string $origin, string $destination): ?array
    {
        $points = collect([
            [
                'label' => $route->origin_name,
                'address' => $route->route_name,
                'index' => 0,
                'type' => 'origin',
                'latitude' => $route->origin_lat !== null ? (float) $route->origin_lat : null,
                'longitude' => $route->origin_lng !== null ? (float) $route->origin_lng : null,
            ],
        ])->merge(
            $route->stops
                ->sortBy('stop_order')
                ->values()
                ->map(fn (RouteStop $stop, int $idx) => [
                    'label' => $stop->stop_name,
                    'address' => $stop->address,
                    'index' => $idx + 1,
                    'type' => $stop->stop_type ?: 'stop',
                    'latitude' => $stop->latitude !== null ? (float) $stop->latitude : null,
                    'longitude' => $stop->longitude !== null ? (float) $stop->longitude : null,
                ])
        )->push([
            'label' => $route->destination_name,
            'address' => $route->route_name,
            'index' => $route->stops->count() + 1,
            'type' => 'destination',
            'latitude' => $route->destination_lat !== null ? (float) $route->destination_lat : null,
            'longitude' => $route->destination_lng !== null ? (float) $route->destination_lng : null,
        ])->values();

        $originPoint = $this->findBestPointMatch($points, $origin);
        $destinationPoint = $this->findBestPointMatch($points, $destination);

        if (! $originPoint || ! $destinationPoint) {
            return null;
        }

        if (($originPoint['index'] ?? 0) >= ($destinationPoint['index'] ?? 0)) {
            return null;
        }

        $score = (int) $originPoint['score']
            + (int) $destinationPoint['score']
            + ($originPoint['type'] === 'origin' ? 25 : 0)
            + ($destinationPoint['type'] === 'destination' ? 25 : 0);

        return [
            'score' => $score,
            'payload' => [
                'id' => $route->id,
                'route_name' => $route->route_name,
                'origin_name' => $route->origin_name,
                'destination_name' => $route->destination_name,
                'distance_meters' => $route->distance_meters,
                'duration_seconds' => $route->duration_seconds,
                'route_geometry' => $route->route_geometry,
                'gate' => $route->gate ? [
                    'id' => $route->gate->id,
                    'gate_name' => $route->gate->gate_name,
                    'bays' => $route->gate->bays,
                ] : null,
                'match' => [
                    'origin_label' => $originPoint['label'],
                    'origin_type' => $originPoint['type'],
                    'destination_label' => $destinationPoint['label'],
                    'destination_type' => $destinationPoint['type'],
                    'score' => $score,
                ],
                'stops' => $route->stops
                    ->sortBy('stop_order')
                    ->values()
                    ->map(fn (RouteStop $stop) => [
                        'id' => $stop->id,
                        'stop_name' => $stop->stop_name,
                        'stop_type' => $stop->stop_type,
                        'address' => $stop->address,
                        'latitude' => $stop->latitude !== null ? (float) $stop->latitude : null,
                        'longitude' => $stop->longitude !== null ? (float) $stop->longitude : null,
                        'stop_order' => $stop->stop_order,
                    ]),
            ],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $points
     * @return array<string, mixed>|null
     */
    private function findBestPointMatch(Collection $points, string $term): ?array
    {
        $needle = Str::lower(trim($term));

        return $points
            ->map(function (array $point) use ($needle) {
                $label = Str::lower(trim((string) ($point['label'] ?? '')));
                $address = Str::lower(trim((string) ($point['address'] ?? '')));
                $haystack = trim($label . ' ' . $address);

                if ($needle === '' || $haystack === '') {
                    return null;
                }

                if (! Str::contains($haystack, $needle)) {
                    return null;
                }

                $score = 30;

                if ($label === $needle) {
                    $score = 120;
                } elseif ($label !== '' && Str::startsWith($label, $needle)) {
                    $score = 95;
                } elseif ($label !== '' && Str::contains($label, $needle)) {
                    $score = 80;
                } elseif ($address === $needle) {
                    $score = 100;
                } elseif ($address !== '' && Str::startsWith($address, $needle)) {
                    $score = 70;
                } elseif ($address !== '' && Str::contains($address, $needle)) {
                    $score = 55;
                }

                $point['score'] = $score;

                return $point;
            })
            ->filter()
            ->sortByDesc('score')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $location
     */
    private function matchesLocation(array $location, string $search): bool
    {
        $haystack = Str::lower(trim(
            implode(' ', array_filter([
                $location['name'] ?? null,
                $location['address'] ?? null,
                $location['route_name'] ?? null,
                $location['gate_name'] ?? null,
            ]))
        ));

        return $haystack !== '' && Str::contains($haystack, $search);
    }

    /**
     * @param  array<string, mixed>  $location
     */
    private function locationKey(array $location): string
    {
        return Str::lower(trim(($location['name'] ?? '') . '|' . ($location['address'] ?? '')));
    }
}
