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
       $company = Company::all();
       $route = Route::all();
       $vehicleType = VehicleType::all();

        return Inertia::render('Vehicles/Create',[
            'companies' => $company,
            'routes' => $route,
            'vehicleTypes' => $vehicleType,
        ]);
    }

public function store(VehicleStoreRequest $request)
{
    Vehicle::create([
        ...$request->validated(),
        'created_by' => auth()->id(), // or Auth::id()
    ]);

    return redirect()->route('vehicles.index');
}
}
