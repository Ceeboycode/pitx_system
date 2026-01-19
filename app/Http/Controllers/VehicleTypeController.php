<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Vehicle\VehicleTypeService;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\VehicleType\VehicleTypeStoreRequest;
use App\Http\Requests\VehicleType\VehicleTypeUpdateRequest;
use App\Models\VehicleType;
use Inertia\Inertia;


class VehicleTypeController extends Controller
{
    public function __construct(
        private VehicleTypeService $vehicleTypeService
    ) {}

    //
    public function index()
    {
        Gate::authorize('viewAny', VehicleType::class);

        $vehicleTypes = VehicleType::select('id', 'type_name', 'is_active')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('VehicleType/Index', [
            'vehicleTypes' => $vehicleTypes,
        ]);
    }

    public function store(VehicleTypeStoreRequest $request)
    {
        Gate::authorize('create', VehicleType::class);

        $this->vehicleTypeService->createVehicleType(
            $request->validated()
        );

        return redirect()->back()->with('success', 'Vehicle type created successfully.');
    }

    public function update(VehicleTypeUpdateRequest $request, VehicleType $vehicleType)
    {
        Gate::authorize('update', $vehicleType);

        $this->vehicleTypeService->updateVehicleType(
            $vehicleType,
            $request->validated()
        );

        return redirect()->back()->with('success', 'Vehicle type updated successfully.');
    }

    public function destroy(VehicleType $vehicleType)
    {
        Gate::authorize('delete', $vehicleType);

        $this->vehicleTypeService->deleteVehicleType($vehicleType);

        return redirect()->back()->with('success', 'Vehicle type deleted successfully.');
    }

    public function show(VehicleType $vehicleType)
    {
        Gate::authorize('view', $vehicleType);

        return Inertia::render('VehicleType/Show', [
            'vehicleType' => $vehicleType->load(['creator', 'updater']),
        ]);
    }

}
