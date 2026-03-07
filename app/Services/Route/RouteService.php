<?php

namespace App\Services\Route;

use App\Models\Route;
use Illuminate\Support\Facades\DB;

class RouteService
{
    public function createRoute(array $data): Route
    {
        return DB::transaction(function () use ($data) {
            $stops = $data['stops'] ?? [];
            unset($data['stops']);

            $data['created_by'] = auth()->id();

            $route = Route::create($data);

            foreach ($stops as $index => $stop) {
                $route->stops()->create([
                    'stop_name'         => $stop['stop_name'],
                    'stop_type'         => $stop['stop_type'] ?? 'stop',
                    'address'           => $stop['address'] ?? null,
                    'latitude'          => $stop['latitude'],
                    'longitude'         => $stop['longitude'],
                    'mapbox_feature_id' => $stop['mapbox_feature_id'] ?? null,
                    'stop_order'        => $stop['stop_order'] ?? ($index + 1),
                    'created_by'        => auth()->id(),
                ]);
            }

            return $route->load('stops');
        });
    }

    public function updateRoute(Route $route, array $data): Route
    {
        return DB::transaction(function () use ($route, $data) {
            $stops = $data['stops'] ?? [];
            unset($data['stops']);

            $data['updated_by'] = auth()->id();

            $route->update($data);

            // Hard delete existing stops then re-insert in new order.
            $route->stops()->forceDelete();

            foreach ($stops as $index => $stop) {
                $route->stops()->create([
                    'stop_name'         => $stop['stop_name'],
                    'stop_type'         => $stop['stop_type'] ?? 'stop',
                    'address'           => $stop['address'] ?? null,
                    'latitude'          => $stop['latitude'],
                    'longitude'         => $stop['longitude'],
                    'mapbox_feature_id' => $stop['mapbox_feature_id'] ?? null,
                    'stop_order'        => $stop['stop_order'] ?? ($index + 1),
                    'created_by'        => $route->created_by,
                    'updated_by'        => auth()->id(),
                ]);
            }

            return $route->fresh('stops');
        });
    }

    public function deleteRoute(Route $route): void
    {
        $route->delete();
    }

    public function restoreRoute(Route $route): void
    {
        $route->restore();
    }

    public function forceDeleteRoute(Route $route): void
    {
        DB::transaction(function () use ($route) {
            $route->stops()->forceDelete();
            $route->forceDelete();
        });
    }
}
