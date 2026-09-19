<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleType\VehicleTypeStoreRequest;
use App\Http\Requests\VehicleType\VehicleTypeUpdateRequest;
use App\Models\VehicleType;
use App\Services\Vehicle\VehicleTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class VehicleTypeController extends Controller
{
    public function __construct(
        private VehicleTypeService $vehicleTypeService
    ) {}

    //
    public function index(Request $request)
    {
        Gate::authorize('viewAny', VehicleType::class);

        $vehicleTypes = VehicleType::query()
            ->select('id', 'type_name', 'is_active', 'created_at')
            ->when($request->search, function ($query, $search) {
                $query->where('type_name', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->status !== 'all', function ($query) use ($request) {
                $query->where('is_active', $request->status === 'active');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('VehicleType/Index', [
            'vehicleTypes' => $vehicleTypes,
            'filters' => [
                'search' => $request->search,
                'status' => $request->input('status'),
            ],
            'canDelete' => $request->user()->can('vehicle_types.delete'),
        ]);
    }

    public function store(VehicleTypeStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', VehicleType::class);

        $this->vehicleTypeService->createVehicleType(
            $request->validated()
        );

        return to_route('vehicle-types.index')->with('success', 'Vehicle type created successfully.');
    }

    public function update(VehicleTypeUpdateRequest $request, VehicleType $vehicleType): RedirectResponse
    {
        Gate::authorize('update', $vehicleType);

        $this->vehicleTypeService->updateVehicleType(
            $vehicleType,
            $request->validated()
        );

        return redirect()->back()->with('success', 'Vehicle type updated successfully.');
    }

    public function edit(VehicleType $vehicleType)
    {
        Gate::authorize('update', $vehicleType);

        return Inertia::render('VehicleType/Edit', [
            'vehicleType' => $vehicleType->load(['creator:id,name', 'updater:id,name']),
        ]);
    }

    public function toggleStatus(VehicleType $vehicleType): RedirectResponse
    {
        Gate::authorize('update', $vehicleType);

        $vehicleType->update([
            'is_active' => ! $vehicleType->is_active,
            'updated_by' => request()->user()->id,
        ]);

        return redirect()->back()->with('success', 'Vehicle type status updated successfully.');
    }

    public function destroy(VehicleType $vehicleType): RedirectResponse
    {
        Gate::authorize('delete', $vehicleType);

        $this->vehicleTypeService->deleteVehicleType($vehicleType);

        return to_route('vehicle-types.index')->with('success', 'Vehicle type deleted successfully.');
    }

    public function show(VehicleType $vehicleType)
    {
        Gate::authorize('view', $vehicleType);

        return Inertia::render('VehicleType/Show', [
            'vehicleType' => $vehicleType->load(['creator', 'updater']),
        ]);
    }
}
