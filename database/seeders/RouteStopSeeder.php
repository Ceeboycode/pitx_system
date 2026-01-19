<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Route;
use App\Models\RouteStop;

class RouteStopSeeder extends Seeder
{
    public function run(): void
    {
        Route::all()->each(function ($route) {
            $order = 1;

            RouteStop::factory()
                ->count(3)
                ->create([
                    'route_id' => $route->id,
                    'stop_order' => fn () => $order++,
                ]);
        });
    }
}
