<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MapboxService
{
    private string $token;

    public function __construct()
    {
        $this->token = config('services.mapbox.secret_token', '');
    }

    /**
     * Call the Mapbox Directions API for the given ordered waypoints.
     *
     * Waypoints must be provided in final display order: origin first, destination last.
     * This method NEVER reorders them.
     *
     * @param  array<int, array{lat: string|float, lng: string|float}>  $waypoints
     * @return array{ geometry: array<mixed>, duration: int, distance: int }
     *
     * @throws RuntimeException on API failure or no-route response
     */
    public function getDirections(array $waypoints): array
    {
        if (empty($this->token)) {
            throw new RuntimeException('Mapbox secret token is not configured.');
        }

        $coordinates = implode(';', array_map(
            fn (array $wp) => number_format((float) $wp['lng'], 7, '.', '')
                . ','
                . number_format((float) $wp['lat'], 7, '.', ''),
            $waypoints
        ));

        $response = Http::timeout(10)->get(
            "https://api.mapbox.com/directions/v5/mapbox/driving/{$coordinates}",
            [
                'geometries'   => 'geojson',
                'overview'     => 'full',
                'steps'        => 'false',
                'access_token' => $this->token,
            ]
        );

        if (! $response->successful()) {
            throw new RuntimeException(
                'Mapbox Directions API request failed: HTTP ' . $response->status()
            );
        }

        $data = $response->json();

        if (empty($data['routes'])) {
            throw new RuntimeException('No route found between the provided stops.');
        }

        $route = $data['routes'][0];

        return [
            'geometry' => $route['geometry'],   // GeoJSON LineString object
            'duration' => (int) round($route['duration']),
            'distance' => (int) round($route['distance']),
        ];
    }
}
