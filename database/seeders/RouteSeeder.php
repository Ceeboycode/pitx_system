<?php

namespace Database\Seeders;

use App\Models\Gate;
use App\Models\Route;
use App\Models\RouteStop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RouteSeeder extends Seeder
{
    public function run(): void
    {
        $places = [
            'pitx' => ['PITX', 14.5104, 120.9910],
            'bacoor' => ['SM City Bacoor', 14.4453, 120.9503],
            'imus' => ['District Imus', 14.4065, 120.9382],
            'dasma' => ['SM City Dasmariñas', 14.3019, 120.9573],
            'gentri' => ['Manggahan, General Trias', 14.2902, 120.9074],
            'trece' => ['Trece Martires City Hall', 14.2822, 120.8673],
            'molino' => ['SM City Molino', 14.3835, 120.9780],
            'paliparan' => ['Paliparan, Dasmariñas', 14.3220, 120.9990],
            'gma' => ['GMA Central Terminal', 14.3000, 121.0060],
            'silang' => ['Silang Junction', 14.2305, 120.9750],
            'tagaytay' => ['Tagaytay Rotonda', 14.1153, 120.9621],
            'mendez' => ['Mendez Crossing', 14.1294, 120.9051],
            'alfonso' => ['Alfonso Town Plaza', 14.1408, 120.8530],
            'long_haul' => ['Davao City Overland Transport Terminal', 7.05544, 125.60087],
            'moa' => ['SM Mall of Asia', 14.5352, 120.9822],
            'buendia' => ['Buendia Bus Terminal', 14.5545, 120.9973],
            'lawton' => ['Lawton, Manila', 14.5915, 120.9740],
            'blumentritt' => ['Blumentritt, Manila', 14.6228, 120.9912],
            'quezon_ave' => ['Quezon Avenue Station', 14.6429, 121.0385],
            'nlet' => ['North Luzon Express Terminal', 14.7930, 120.9480],
            'monumento' => ['Monumento', 14.6570, 120.9842],
            'ayala' => ['One Ayala', 14.5492, 121.0279],
            'ortigas' => ['Ortigas Busway Station', 14.5868, 121.0567],
            'cubao' => ['Main Avenue Busway Station', 14.6204, 121.0530],
            'alabang' => ['Starmall Alabang', 14.4174, 121.0473],
            'bgc' => ['Market! Market!', 14.5496, 121.0550],
            'turbina' => ['Turbina Bus Terminal', 14.2112, 121.1654],
            'lucena' => ['Lucena Grand Central Terminal', 13.9414, 121.6236],
            'naga' => ['Naga City Central Bus Terminal', 13.6218, 123.1948],
            'dau' => ['Dau Bus Terminal', 15.1775, 120.5885],
            'tarlac' => ['Tarlac City', 15.4869, 120.5900],
            'baguio' => ['Baguio City Bus Terminal', 16.4023, 120.5960],
            'lipa' => ['Lipa City Bus Terminal', 13.9411, 121.1622],
            'batangas' => ['Batangas Grand Terminal', 13.7565, 121.0584],
            'calamba' => ['Calamba Central Terminal', 14.2117, 121.1653],
            'sta_cruz' => ['Santa Cruz, Laguna', 14.2814, 121.4161],
            'san_pablo' => ['San Pablo Central Terminal', 14.0690, 121.3250],
            'kawit' => ['Aguinaldo Shrine, Kawit', 14.4440, 120.9060],
            'noveleta' => ['Noveleta Town Plaza', 14.4280, 120.8790],
            'tanza' => ['Tanza Public Market', 14.3940, 120.8530],
            'naic' => ['Naic Grand Terminal', 14.3180, 120.7680],
            'ternate' => ['Ternate Public Market', 14.2860, 120.7160],
            'cavite_city' => ['Cavite City Public Market', 14.4837, 120.8988],
            'baclaran' => ['Baclaran', 14.5321, 120.9984],
            'taft' => ['Vito Cruz, Taft Avenue', 14.5636, 120.9948],
            'nichols' => ['Nichols, Pasay', 14.5205, 121.0190],
            'sucat' => ['SM City Sucat', 14.4840, 120.9940],
            'zapote' => ['Zapote, Las Piñas', 14.4500, 120.9780],
            'southmall' => ['SM Southmall', 14.4331, 121.0114],
        ];

        $routes = [
            ['Gate 1', 'PITX - Trece Martires', 'trece', ['bacoor', 'dasma', 'gentri', 'trece']],
            ['Gate 1', 'PITX - Dasmariñas', 'dasma', ['bacoor', 'imus', 'dasma']],
            ['Gate 1', 'PITX - GMA', 'gma', ['molino', 'paliparan', 'gma']],
            ['Gate 2', 'PITX - Tagaytay-Mendez (Erjohn, San Agustin)', 'mendez', ['dasma', 'tagaytay', 'mendez']],
            ['Gate 2', 'PITX - Tagaytay-Amadeo/Alfonso (Regal Starliner, CBTSC)', 'alfonso', ['silang', 'tagaytay', 'alfonso']],
            ['Gate 2', 'PITX - Long-Haul (Visayas, Mindanao, Mindoro)', 'long_haul', ['batangas', 'long_haul']],
            ['Gate 3', 'Route 6: PITX - Quezon Avenue', 'quezon_ave', ['moa', 'buendia', 'quezon_ave']],
            ['Gate 3', 'Route 39: PITX - NLET', 'nlet', ['buendia', 'lawton', 'blumentritt', 'nlet']],
            ['Gate 3', 'Route 24: PITX - Alabang', 'alabang', ['baclaran', 'southmall', 'alabang']],
            ['Gate 4', 'PITX - Bicol', 'naga', ['turbina', 'lucena', 'naga']],
            ['Gate 4', 'PITX - Baguio City', 'baguio', ['dau', 'tarlac', 'baguio']],
            ['Gate 5', 'PITX - Batangas', 'batangas', ['turbina', 'lipa', 'batangas']],
            ['Gate 5', 'PITX - Laguna', 'sta_cruz', ['alabang', 'calamba', 'sta_cruz']],
            ['Gate 5', 'PITX - Quezon', 'lucena', ['turbina', 'san_pablo', 'lucena']],
            ['Gate 6', 'PITX - Ternate', 'ternate', ['kawit', 'naic', 'ternate']],
            ['Gate 6', 'PITX - Naic', 'naic', ['kawit', 'tanza', 'naic']],
            ['Gate 6', 'PITX - Cavite City', 'cavite_city', ['kawit', 'noveleta', 'cavite_city']],
            ['Gate 7', 'PITX - Tanza (Mex)', 'tanza', ['kawit', 'noveleta', 'tanza']],
            ['Gate 7', 'PITX - Dasma (KB)', 'dasma', ['bacoor', 'imus', 'dasma']],
            ['Gate 7', 'PITX - Buendia (Beep)', 'buendia', ['moa', 'taft', 'buendia']],
            ['Gate 10', 'PITX - EDSA Busway', 'monumento', ['moa', 'ayala', 'ortigas', 'cubao', 'monumento']],
        ];

        DB::transaction(function () use ($places, $routes) {
            foreach ($routes as [$gateName, $routeName, $destinationKey, $stopKeys]) {
                $gate = Gate::where('gate_name', $gateName)->firstOrFail();
                [$destinationName, $destinationLat, $destinationLng] = $places[$destinationKey];

                $route = Route::updateOrCreate(
                    ['route_name' => $routeName],
                    [
                        'gate_id' => $gate->id,
                        'origin_name' => $places['pitx'][0],
                        'origin_lat' => $places['pitx'][1],
                        'origin_lng' => $places['pitx'][2],
                        'destination_name' => $destinationName,
                        'destination_lat' => $destinationLat,
                        'destination_lng' => $destinationLng,
                        'status' => 'active',
                        'created_by' => 1,
                        'updated_by' => 1,
                    ],
                );

                $route->stops()->forceDelete();

                foreach ($stopKeys as $index => $stopKey) {
                    [$stopName, $latitude, $longitude] = $places[$stopKey];
                    RouteStop::create([
                        'route_id' => $route->id,
                        'stop_name' => $stopName,
                        'stop_type' => $index === count($stopKeys) - 1 ? 'destination' : 'stop',
                        'address' => $stopName,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'stop_order' => $index + 1,
                        'created_by' => 1,
                        'updated_by' => 1,
                    ]);
                }
            }
        });
    }
}
