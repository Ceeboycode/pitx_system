<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicle;
use Inertia\Inertia;
use App\Models\Company;
use App\Models\Route;
use App\Models\VehicleType;
use App\Http\Requests\Vehicle\VehicleStoreRequest;


class VehicleController extends Controller
{
    //
    public function index()
    {
        $vehicles = Vehicle::with(['vehicleType', 'company', 'route'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
        ]);
    }

    public function create()
    {
        return Inertia::render('Vehicles/Create', [
            'companies' => Company::query()
                ->select('id', 'company_name')
                ->orderBy('company_name')
                ->get(),

            'routes' => Route::query()
                ->select('id', 'route_name')
                ->orderBy('route_name')
                ->get(),

            'vehicleTypes' => VehicleType::query()
                ->select('id', 'type_name')
                ->where('is_active', 1)
                ->orderBy('type_name')
                ->get(),
        ]);
    }

    public function store(VehicleStoreRequest $request)
    {
        Vehicle::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return to_route('vehicles.create')->with('success', 'Vehicle created successfully.');
    }
}
