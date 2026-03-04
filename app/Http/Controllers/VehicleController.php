<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Inertia\Inertia;
use App\Models\Company;
use App\Models\Route;
use App\Models\VehicleType;
use App\Http\Requests\Vehicle\VehicleStoreRequest;
use App\Http\Requests\Vehicle\VehicleUpdateRequest;
use App\Services\Vehicle\VehicleService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function __construct(
        private readonly VehicleService $vehicleService
    )
    {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Vehicle::class);

        $search = $request->input('search');

        $vehicles = Vehicle::query()
            ->with([
                'company:id,company_name',
                'route:id,route_name',
                'vehicleType:id,type_name',
            ])
            ->select([
                'id',
                'plate_number',
                'body_number',
                'capacity',
                'company_id',
                'route_id',
                'vehicle_type_id',
                'created_at',
            ])
            ->search($search)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Vehicle::class);

        $companies = Company::select('id', 'company_name')
            ->orderBy('company_name')
            ->get();

        $routes = Route::select('id', 'route_name')
            ->orderBy('route_name')
            ->get();

        $vehicleTypes = VehicleType::select('id', 'type_name')
            ->orderBy('type_name')
            ->get();

        return Inertia::render('Vehicles/Create', [
            'companies' => $companies,
            'routes' => $routes,
            'vehicleTypes' => $vehicleTypes,
        ]);
    }

    public function edit(Request $request, Vehicle $vehicle)
    {
        Gate::authorize('update', $vehicle);

        $companies = Company::select('id', 'company_name')
            ->orderBy('company_name')
            ->get();

        $routes = Route::select('id', 'route_name')
            ->orderBy('route_name')
            ->get();

        $vehicleTypes = VehicleType::select('id', 'type_name')
            ->orderBy('type_name')
            ->get();

        return Inertia::render('Vehicles/Edit', [
            'vehicle' => $vehicle->load(['company:id,company_name', 'route:id,route_name', 'vehicleType:id,type_name']),
            'companies' => $companies,
            'routes' => $routes,
            'vehicleTypes' => $vehicleTypes,
        ]);
    }

    public function store(VehicleStoreRequest $request)
    {
        Gate::authorize('create', Vehicle::class);

        $this->vehicleService->createVehicle($request->validated(), auth()->user()->id);

        return back()->with('success', 'Vehicle created successfully.');
    }

    public function update(VehicleUpdateRequest $request, Vehicle $vehicle)
    {
        Gate::authorize('update', $vehicle);

        $this->vehicleService->updateVehicle($vehicle, $request->validated(), auth()->user()->id);

        return to_route('vehicles.index')->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle)
    {
        Gate::authorize('delete', $vehicle);

        $this->vehicleService->deleteVehicle($vehicle, auth()->user()->id);

        return back()->with('success', 'Vehicle deleted successfully.');
    }

    public function trash(Request $request)
    {
        Gate::authorize('viewAny', Vehicle::class);

        $search = $request->input('search');

        $vehicles = Vehicle::onlyTrashed()
            ->with([
                'company:id,company_name',
                'route:id,route_name',
                'vehicleType:id,type_name',
                'deleter:id,name',
            ])
            ->select([
                'id',
                'plate_number',
                'body_number',
                'capacity',
                'company_id',
                'route_id',
                'vehicle_type_id',
                'deleted_at',
                'deleted_by',
            ])
            ->search($search)
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Vehicles/Trash', [
            'vehicles' => $vehicles,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function restore(Vehicle $vehicle)
    {
        Gate::authorize('restore', $vehicle);

        $this->vehicleService->restoreVehicle($vehicle);

        return back()->with('success', 'Vehicle restored successfully.');
    }

    public function forceDelete(Vehicle $vehicle)
    {
        Gate::authorize('forceDelete', $vehicle);

        $this->vehicleService->forceDeleteVehicle($vehicle);

        return back()->with('success', 'Vehicle permanently deleted successfully.');
    }
}
