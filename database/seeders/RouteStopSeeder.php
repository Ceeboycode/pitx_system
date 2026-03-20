<?php

namespace Database\Seeders;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\User;
use Illuminate\Database\Seeder;

class RouteStopSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::role(['admin', 'it', 'terminal manager'])->first()
            ?? User::query()->first();

        if (! $creator) {
            $this->command?->warn('No users found. Please seed users first before seeding route stops.');
            return;
        }

        Route::query()->get()->each(function (Route $route) use ($creator) {
            $midLat = round(((float) $route->origin_lat + (float) $route->destination_lat) / 2, 7);
            $midLng = round(((float) $route->origin_lng + (float) $route->destination_lng) / 2, 7);

            RouteStop::query()->updateOrCreate(
                [
                    'route_id' => $route->id,
                    'stop_order' => 1,
                ],
                [
                    'stop_name' => 'PITX',
                    'stop_type' => 'terminal',
                    'address' => 'PITX, Parañaque',
                    'latitude' => $route->origin_lat,
                    'longitude' => $route->origin_lng,
                    'mapbox_feature_id' => null,
                    'created_by' => $creator->id,
                    'updated_by' => $creator->id,
                ]
            );

            RouteStop::query()->updateOrCreate(
                [
                    'route_id' => $route->id,
                    'stop_order' => 2,
                ],
                [
                    'stop_name' => $route->destination_name . ' Midpoint',
                    'stop_type' => 'stop',
                    'address' => $route->destination_name . ' Corridor',
                    'latitude' => $midLat,
                    'longitude' => $midLng,
                    'mapbox_feature_id' => null,
                    'created_by' => $creator->id,
                    'updated_by' => $creator->id,
                ]
            );

            RouteStop::query()->updateOrCreate(
                [
                    'route_id' => $route->id,
                    'stop_order' => 3,
                ],
                [
                    'stop_name' => $route->destination_name,
                    'stop_type' => 'terminal',
                    'address' => $route->destination_name,
                    'latitude' => $route->destination_lat,
                    'longitude' => $route->destination_lng,
                    'mapbox_feature_id' => null,
                    'created_by' => $creator->id,
                    'updated_by' => $creator->id,
                ]
            );
        });
    }
}
