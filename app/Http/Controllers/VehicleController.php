<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vehicle\VehicleStoreRequest;
use App\Http\Requests\Vehicle\VehicleUpdateRequest;
use App\Models\Company;
use App\Models\Route;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehicleType;
use App\Services\Vehicle\VehicleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function __construct(
        private readonly VehicleService $vehicleService
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Vehicle::class);

        $search = $request->input('search');

        $vehicles = Vehicle::query()
            ->with([
                'company:id,company_name',
                'route:id,route_name',
            ])
            ->select([
                'id',
                'vehicle_type',
                'plate_number',
                'body_number',
                'capacity',
                'company_id',
                'route_id',
                'status',
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

    public function create(Request $request): Response
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

    public function show(Request $request, Vehicle $vehicle): Response
    {
        Gate::authorize('view', $vehicle);

        $vehicle->load([
            'company:id,company_name,company_code,company_email,company_phone,company_address',
            'route:id,route_name,gate_id,origin_name,origin_lat,origin_lng,destination_name,destination_lat,destination_lng,distance_meters,duration_seconds,route_geometry,status,created_by,updated_by,created_at,updated_at',
            'route.gate:id,gate_name',
            'route.stops:id,route_id,stop_name,stop_type,full_address,latitude,longitude,stop_order',
            'documents:id,vehicle_id,document_type,file_path,file_name,file_mime_type,file_size,status,issued_at,expires_at,remarks,created_at,updated_at',
            'creator:id,name',
            'updater:id,name',
            'deleter:id,name',
        ]);

        return Inertia::render('Vehicles/Show', [
            'vehicle' => [
                'id' => $vehicle->id,
                'vehicle_type' => $vehicle->vehicle_type,
                'plate_number' => $vehicle->plate_number,
                'body_number' => $vehicle->body_number,
                'capacity' => $vehicle->capacity,
                'color' => $vehicle->color,
                'engine_number' => $vehicle->engine_number,
                'chassis_number' => $vehicle->chassis_number,
                'make_model' => $vehicle->make_model,
                'status' => $vehicle->status,
                'docs_status' => $vehicle->docs_status,
                'remarks' => $vehicle->remarks,
                'created_at' => $vehicle->created_at,
                'updated_at' => $vehicle->updated_at,
                'deleted_at' => $vehicle->deleted_at,

                'company' => $vehicle->company ? [
                    'id' => $vehicle->company->id,
                    'company_name' => $vehicle->company->company_name,
                    'company_code' => $vehicle->company->company_code,
                    'company_email' => $vehicle->company->company_email,
                    'company_phone' => $vehicle->company->company_phone,
                    'company_address' => $vehicle->company->company_address,
                ] : null,

                'route' => $vehicle->route ? [
                    'id' => $vehicle->route->id,
                    'route_name' => $vehicle->route->route_name,
                    'origin_name' => $vehicle->route->origin_name,
                    'origin_lat' => $vehicle->route->origin_lat,
                    'origin_lng' => $vehicle->route->origin_lng,
                    'destination_name' => $vehicle->route->destination_name,
                    'destination_lat' => $vehicle->route->destination_lat,
                    'destination_lng' => $vehicle->route->destination_lng,
                    'distance_meters' => $vehicle->route->distance_meters,
                    'duration_seconds' => $vehicle->route->duration_seconds,
                    'route_geometry' => $vehicle->route->route_geometry,
                    'status' => $vehicle->route->status?->value ?? $vehicle->route->status,
                    'gate' => $vehicle->route->gate ? [
                        'id' => $vehicle->route->gate->id,
                        'gate_name' => $vehicle->route->gate->gate_name,
                    ] : null,
                    'stops' => $vehicle->route->stops
                        ->sortBy('stop_order')
                        ->values()
                        ->map(fn ($stop) => [
                            'id' => $stop->id,
                            'stop_name' => $stop->stop_name,
                            'stop_type' => $stop->stop_type,
                            'address' => $stop->full_address,
                            'latitude' => (float) $stop->latitude,
                            'longitude' => (float) $stop->longitude,
                            'stop_order' => $stop->stop_order,
                        ]),
                ] : null,

                'documents' => $vehicle->documents
                    ->sortBy('document_type')
                    ->values()
                    ->map(fn ($document) => [
                        'id' => $document->id,
                        'document_type' => $document->document_type,
                        'file_name' => $document->file_name,
                        'file_mime_type' => $document->file_mime_type,
                        'file_size' => $document->file_size,
                        'status' => $document->status,
                        'issued_at' => $document->issued_at?->toDateString(),
                        'expires_at' => $document->expires_at?->toDateString(),
                        'remarks' => $document->remarks,
                        'created_at' => $document->created_at,
                        'updated_at' => $document->updated_at,
                        'file_url' => Storage::url($document->file_path),
                    ]),

                'creator' => $vehicle->creator ? [
                    'id' => $vehicle->creator->id,
                    'name' => $vehicle->creator->name,
                ] : null,

                'updater' => $vehicle->updater ? [
                    'id' => $vehicle->updater->id,
                    'name' => $vehicle->updater->name,
                ] : null,

                'deleter' => $vehicle->deleter ? [
                    'id' => $vehicle->deleter->id,
                    'name' => $vehicle->deleter->name,
                ] : null,
            ],

            'mapConfig' => [
                'mapboxToken' => config('app.mapbox_public_token', env('VITE_MAPBOX_TOKEN')),
            ],
        ]);
    }

    public function edit(Request $request, Vehicle $vehicle): Response
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
            'vehicle' => $vehicle->load([
                'company:id,company_name',
                'route:id,route_name',
            ]),
            'companies' => $companies,
            'routes' => $routes,
            'vehicleTypes' => $vehicleTypes,
        ]);
    }

    public function store(VehicleStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', Vehicle::class);

        $this->vehicleService->createVehicle($request->validated(), $request->user()->id);

        return back()->with('success', 'Vehicle created successfully.');
    }

    public function update(VehicleUpdateRequest $request, Vehicle $vehicle): RedirectResponse
    {
        Gate::authorize('update', $vehicle);

        $this->vehicleService->updateVehicle($vehicle, $request->validated(), $request->user()->id);

        return to_route('vehicles.index')->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        Gate::authorize('delete', $vehicle);

        $this->vehicleService->deleteVehicle($vehicle, request()->user()->id);

        return back()->with('success', 'Vehicle deleted successfully.');
    }

    public function trash(Request $request): Response
    {
        Gate::authorize('viewAny', Vehicle::class);

        $search = $request->input('search');

        $vehicles = Vehicle::onlyTrashed()
            ->with([
                'company:id,company_name',
                'route:id,route_name',
                'deleter:id,name',
            ])
            ->select([
                'id',
                'vehicle_type',
                'plate_number',
                'body_number',
                'capacity',
                'company_id',
                'route_id',
                'status',
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

    public function restore(Vehicle $vehicle): RedirectResponse
    {
        Gate::authorize('restore', $vehicle);

        $this->vehicleService->restoreVehicle($vehicle);

        return back()->with('success', 'Vehicle restored successfully.');
    }

    public function forceDelete(Vehicle $vehicle): RedirectResponse
    {
        Gate::authorize('forceDelete', $vehicle);

        $this->vehicleService->forceDeleteVehicle($vehicle);

        return back()->with('success', 'Vehicle permanently deleted successfully.');
    }

    public function verifyDocument(Request $request, Vehicle $vehicle, VehicleDocument $document): RedirectResponse
    {
        Gate::authorize('update', $vehicle);

        abort_unless($document->vehicle_id === $vehicle->id, 404);

        $this->vehicleService->verifyDocument($vehicle, $document, $request->user()->id);

        return back()->with('success', 'Vehicle document verified successfully.');
    }

    public function invalidateDocument(Request $request, Vehicle $vehicle, VehicleDocument $document): RedirectResponse
    {
        Gate::authorize('update', $vehicle);

        abort_unless($document->vehicle_id === $vehicle->id, 404);

        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:1000'],
        ]);

        $this->vehicleService->invalidateDocument(
            $vehicle,
            $document,
            $validated['remarks'],
            $request->user()->id,
        );

        return back()->with('success', 'Vehicle document marked as invalid.');
    }

    public function unverifyDocument(Request $request, Vehicle $vehicle, VehicleDocument $document): RedirectResponse
    {
        Gate::authorize('update', $vehicle);

        abort_unless($document->vehicle_id === $vehicle->id, 404);

        $this->vehicleService->unverifyDocument($vehicle, $document, $request->user()->id);

        return back()->with('success', 'Vehicle document moved back to pending.');
    }

    public function toggleStatus(Request $request, Vehicle $vehicle): RedirectResponse
    {
        Gate::authorize('update', $vehicle);

        $nextStatus = $vehicle->status === 'suspended' ? 'active' : 'suspended';

        $vehicle->update([
            'status' => $nextStatus,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Vehicle status updated successfully.');
    }
}
