<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dispatch\DepartDispatchRequest;
use App\Http\Requests\Dispatch\StoreDispatchRequest;
use App\Http\Requests\Dispatch\UpdateDispatchRequest;
use App\Models\Dispatch;
use App\Models\DispatchChangeRequest;
use App\Models\Gate;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DriverAssignmentValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DispatchController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $company = $user->company;
        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status', 'all'));
        $date = trim((string) $request->string('date'));

        abort_unless($company, 403, 'No company is associated with this user.');

        $vehicles = Vehicle::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->select([
                'id',
                'route_id',
                'plate_number',
                'body_number',
                'vehicle_type',
                'make_model',
                'status',
            ])
            ->with([
                'route:id,gate_id,route_name,origin_name,destination_name,route_geometry,status',
            ])
            ->orderBy('plate_number')
            ->get()
            ->map(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'body_number' => $vehicle->body_number,
                'vehicle_type' => $vehicle->vehicle_type,
                'make_model' => $vehicle->make_model,
                'status' => $vehicle->status,
                'route' => $vehicle->route ? [
                    'id' => $vehicle->route->id,
                    'route_name' => $vehicle->route->route_name,
                    'origin_name' => $vehicle->route->origin_name,
                    'destination_name' => $vehicle->route->destination_name,
                    'status' => $vehicle->route->status,
                ] : null,
                'label' => trim(implode(' • ', array_filter([
                    $vehicle->plate_number,
                    $vehicle->body_number ? 'Body #' . $vehicle->body_number : null,
                    $vehicle->vehicle_type,
                ]))),
            ])
            ->values();

        $drivers = User::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->where('name', 'driver'))
            ->select(['id', 'name', 'username', 'email'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $driver) => [
                'id' => $driver->id,
                'name' => $driver->name,
                'username' => $driver->username,
                'email' => $driver->email,
                'label' => $driver->name . ($driver->username ? ' • ' . $driver->username : ''),
            ])
            ->values();

        $gates = Gate::query()
            ->where('status', 'active')
            ->select(['id', 'gate_name', 'bays', 'status'])
            ->orderBy('gate_name')
            ->get()
            ->map(function (Gate $gate) {
                $bayOptions = collect(range(1, max((int) $gate->bays, 0)))
                    ->map(fn (int $bay) => [
                        'value' => $bay,
                        'label' => 'Bay ' . $bay,
                    ])
                    ->values();

                return [
                    'id' => $gate->id,
                    'gate_name' => $gate->gate_name,
                    'bays' => (int) $gate->bays,
                    'status' => $gate->status,
                    'bay_options' => $bayOptions,
                    'label' => $gate->gate_name . ' (' . $gate->bays . ' bays)',
                ];
            })
            ->values();

        $dispatches = Dispatch::query()
            ->with([
                'vehicle:id,route_id,plate_number,body_number,vehicle_type,make_model',
                'vehicle.route:id,gate_id,route_name,origin_name,destination_name,route_geometry,status',
                'dispatcher:id,name,username',
                'driver:id,name,username',
                'gate:id,gate_name',
            ])
            ->where('company_id', $company->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('plate_number', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, [
                Dispatch::STATUS_ARRIVED,
                Dispatch::STATUS_DEPARTED,
            ], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($date !== '', function ($query) use ($date) {
                $driver = DB::connection()->getDriverName();

                if ($driver === 'sqlite') {
                    $query->whereRaw(
                        "date(datetime(arrived_at, '+8 hours')) = ?",
                        [$date],
                    );
                } else {
                    $query->whereRaw(
                        "DATE(DATE_ADD(arrived_at, INTERVAL 8 HOUR)) = ?",
                        [$date],
                    );
                }
            })
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Dispatch $dispatch) => [
                'id' => $dispatch->id,
                'plate_number' => $dispatch->plate_number,
                'pax_count' => $dispatch->pax_count,
                'bay_number' => $dispatch->bay_number,
                'remarks' => $dispatch->remarks,
                'status' => $dispatch->status,
                'arrived_at_formatted' => $dispatch->arrived_at_formatted,
                'departed_at_formatted' => $dispatch->departed_at_formatted,
                'dispatched_at_formatted' => $dispatch->dispatched_at
                    ? $dispatch->dispatched_at->timezone('Asia/Manila')->format('M d, Y h:i A')
                    : null,
                'vehicle' => $dispatch->vehicle ? [
                    'id' => $dispatch->vehicle->id,
                    'route_id' => $dispatch->vehicle->route_id,
                    'plate_number' => $dispatch->vehicle->plate_number,
                    'body_number' => $dispatch->vehicle->body_number,
                    'vehicle_type' => $dispatch->vehicle->vehicle_type,
                    'make_model' => $dispatch->vehicle->make_model,
                    'route' => $dispatch->vehicle->route ? [
                        'id' => $dispatch->vehicle->route->id,
                        'route_name' => $dispatch->vehicle->route->route_name,
                        'origin_name' => $dispatch->vehicle->route->origin_name,
                        'destination_name' => $dispatch->vehicle->route->destination_name,
                        'status' => $dispatch->vehicle->route->status,
                    ] : null,
                ] : null,
                'dispatcher' => $dispatch->dispatcher ? [
                    'id' => $dispatch->dispatcher->id,
                    'name' => $dispatch->dispatcher->name,
                    'username' => $dispatch->dispatcher->username,
                ] : null,
                'driver' => $dispatch->driver ? [
                    'id' => $dispatch->driver->id,
                    'name' => $dispatch->driver->name,
                    'username' => $dispatch->driver->username,
                ] : null,
                'gate' => $dispatch->gate ? [
                    'id' => $dispatch->gate->id,
                    'gate_name' => $dispatch->gate->gate_name,
                ] : null,
            ]);

        return Inertia::render('External/Dispatches/Index', [
            'company' => [
                'id' => $company->id,
                'company_name' => $company->company_name,
                'company_code' => $company->company_code,
                'status' => $company->status,
                'logo_url' => $company->logo_url,
            ],
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'gates' => $gates,
            'dispatches' => $dispatches,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'date' => $date ?: null,
            ],
            'changeRequests' => DispatchChangeRequest::whereHas('dispatch', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
                ->with(['dispatch:id,plate_number,status', 'requestedBy:id,name,email'])
                ->latest()
                ->get()
                ->map(fn ($req) => [
                    'id' => $req->id,
                    'dispatch_id' => $req->dispatch_id,
                    'dispatch' => [
                        'id' => $req->dispatch->id,
                        'plate_number' => $req->dispatch->plate_number,
                        'status' => $req->dispatch->status,
                    ],
                    'requested_by' => [
                        'id' => $req->requestedBy->id,
                        'name' => $req->requestedBy->name,
                        'email' => $req->requestedBy->email,
                    ],
                    'requested_field' => $req->requested_field,
                    'old_value' => $req->old_value,
                    'old_value_display' => $req->old_value_display,
                    'requested_value' => $req->requested_value,
                    'requested_value_display' => $req->requested_value_display,
                    'reason' => $req->reason,
                    'status' => $req->status,
                    'rejection_reason' => $req->rejection_reason,
                    'field_label' => $req->field_label,
                    'created_at' => $req->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function show(Request $request, Dispatch $dispatch): Response
    {
        $user = $request->user();
        $company = $user->company;

        abort_unless($company, 403, 'No company is associated with this user.');
        abort_unless($dispatch->company_id === $company->id, 403);

        $dispatch->load([
            'vehicle:id,company_id,route_id,plate_number,body_number,vehicle_type,make_model,status',
            'vehicle.route:id,gate_id,route_name,origin_name,destination_name,route_geometry,status',
            'vehicle.route.gate:id,gate_name',
            'vehicle.route.stops:id,route_id,stop_name,stop_order,stop_type,address,latitude,longitude',
            'dispatcher:id,name,username,email',
            'driver:id,name,username,email',
            'gate:id,gate_name,bays',
        ]);

        $routes = Route::query()
            ->with([
                'gate:id,gate_name',
                'stops:id,route_id,stop_name,stop_order,stop_type,address,latitude,longitude',
            ])
            ->select([
                'id',
                'gate_id',
                'route_name',
                'origin_name',
                'destination_name',
                'route_geometry',
                'status',
            ])
            ->orderBy('route_name')
            ->get()
            ->map(fn (Route $route) => [
                'id' => $route->id,
                'gate_id' => $route->gate_id,
                'route_name' => $route->route_name,
                'origin_name' => $route->origin_name,
                'destination_name' => $route->destination_name,
                'route_geometry' => $route->route_geometry,
                'gate' => $route->gate ? [
                    'id' => $route->gate->id,
                    'gate_name' => $route->gate->gate_name,
                ] : null,
                'stops' => $route->stops
                    ->sortBy('stop_order')
                    ->values()
                    ->map(fn ($stop) => [
                        'id' => $stop->id,
                        'route_id' => $stop->route_id,
                        'stop_name' => $stop->stop_name,
                        'stop_order' => $stop->stop_order,
                        'stop_type' => $stop->stop_type,
                        'address' => $stop->address,
                        'latitude' => $stop->latitude,
                        'longitude' => $stop->longitude,
                    ]),
            ])
            ->values();

        $gates = Gate::query()
            ->where('status', 'active')
            ->select(['id', 'gate_name', 'bays'])
            ->orderBy('gate_name')
            ->get()
            ->map(fn (Gate $gate) => [
                'id' => $gate->id,
                'gate_name' => $gate->gate_name,
                'bays' => $gate->bays,
            ])
            ->values();

        return Inertia::render('External/Dispatches/Show', [
            'dispatch' => [
                'id' => $dispatch->id,
                'plate_number' => $dispatch->plate_number,
                'pax_count' => $dispatch->pax_count,
                'bay_number' => $dispatch->bay_number,
                'remarks' => $dispatch->remarks,
                'status' => $dispatch->status,
                'arrived_at_formatted' => $dispatch->arrived_at_formatted,
                'departed_at_formatted' => $dispatch->departed_at_formatted,
                'dispatched_at_formatted' => $dispatch->dispatched_at
                    ? $dispatch->dispatched_at->timezone('Asia/Manila')->format('M d, Y h:i A')
                    : null,
                'vehicle' => $dispatch->vehicle ? [
                    'id' => $dispatch->vehicle->id,
                    'route_id' => $dispatch->vehicle->route_id,
                    'plate_number' => $dispatch->vehicle->plate_number,
                    'body_number' => $dispatch->vehicle->body_number,
                    'vehicle_type' => $dispatch->vehicle->vehicle_type,
                    'make_model' => $dispatch->vehicle->make_model,
                    'status' => $dispatch->vehicle->status,
                    'route' => $dispatch->vehicle->route ? [
                        'id' => $dispatch->vehicle->route->id,
                        'route_name' => $dispatch->vehicle->route->route_name,
                        'origin_name' => $dispatch->vehicle->route->origin_name,
                        'destination_name' => $dispatch->vehicle->route->destination_name,
                        'status' => $dispatch->vehicle->route->status,
                    ] : null,
                ] : null,
                'dispatcher' => $dispatch->dispatcher ? [
                    'id' => $dispatch->dispatcher->id,
                    'name' => $dispatch->dispatcher->name,
                    'username' => $dispatch->dispatcher->username,
                    'email' => $dispatch->dispatcher->email,
                ] : null,
                'driver' => $dispatch->driver ? [
                    'id' => $dispatch->driver->id,
                    'name' => $dispatch->driver->name,
                    'username' => $dispatch->driver->username,
                    'email' => $dispatch->driver->email,
                ] : null,
                'gate' => $dispatch->gate ? [
                    'id' => $dispatch->gate->id,
                    'gate_name' => $dispatch->gate->gate_name,
                    'bays' => $dispatch->gate->bays,
                ] : null,
            ],
            'routes' => $routes,
            'gates' => $gates,
            'mapConfig' => [
                'mapboxToken' => config('app.mapbox_public_token', env('VITE_MAPBOX_TOKEN')),
                'defaultCenter' => ['lng' => 120.9915, 'lat' => 14.5096],
                'defaultZoom' => 11,
            ],
        ]);
    }

    public function store(StoreDispatchRequest $request): RedirectResponse
    {
        $user = $request->user();
        $company = $user->company;

        abort_unless($company, 403, 'No company is associated with this user.');

        $vehicle = Vehicle::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->findOrFail($request->integer('vehicle_id'));

        $gate = Gate::query()
            ->where('status', 'active')
            ->findOrFail($request->integer('gate_id'));

        $driverId = $request->filled('driver_user_id')
            ? $request->integer('driver_user_id')
            : null;

        if ($driverId) {
            $driver = User::query()
                ->where('company_id', $company->id)
                ->where('status', 'active')
                ->whereHas('roles', fn ($query) => $query->where('name', 'driver'))
                ->findOrFail($driverId);

            // Validate driver availability
            $validator = new DriverAssignmentValidator();
            if (!$validator->canAssignToday($driver, now())) {
                return back()->with('error', $validator->getValidationMessage($driver, now()));
            }
        }

        Dispatch::create([
            'company_id' => $company->id,
            'vehicle_id' => $vehicle->id,
            'gate_id' => $gate->id,
            'plate_number' => $vehicle->plate_number,
            'pax_count' => 0,
            'bay_number' => $request->integer('bay_number'),
            'remarks' => $request->filled('remarks')
                ? $request->string('remarks')->toString()
                : null,
            'dispatcher_user_id' => $user->id,
            'driver_user_id' => $driverId,
            'arrived_at' => now(),
            'departed_at' => null,
            'dispatched_at' => now(),
            'status' => Dispatch::STATUS_ARRIVED,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return back()->with('success', 'Dispatch created successfully.');
    }

    public function update(UpdateDispatchRequest $request, Dispatch $dispatch): RedirectResponse
    {
        $user = $request->user();
        $company = $user->company;

        abort_unless($company, 403, 'No company is associated with this user.');
        abort_unless($dispatch->company_id === $company->id, 403);

        if ($dispatch->status === Dispatch::STATUS_DEPARTED) {
            return back()->with('error', 'Departed dispatches can no longer be edited.');
        }

        $vehicle = Vehicle::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->findOrFail($request->integer('vehicle_id'));

        $gate = Gate::query()
            ->where('status', 'active')
            ->findOrFail($request->integer('gate_id'));

        $driverId = $request->filled('driver_user_id')
            ? $request->integer('driver_user_id')
            : null;

        if ($driverId) {
            $driver = User::query()
                ->where('company_id', $company->id)
                ->where('status', 'active')
                ->whereHas('roles', fn ($query) => $query->where('name', 'driver'))
                ->findOrFail($driverId);

            // Validate driver availability (exclude current dispatch)
            $validator = new DriverAssignmentValidator();
            if (!$validator->canAssignToday($driver, now(), $dispatch)) {
                return back()->with('error', $validator->getValidationMessage($driver, now()));
            }
        }

        $dispatch->update([
            'vehicle_id' => $vehicle->id,
            'gate_id' => $gate->id,
            'plate_number' => $vehicle->plate_number,
            'driver_user_id' => $driverId,
            'bay_number' => $request->integer('bay_number'),
            'remarks' => $request->filled('remarks')
                ? $request->string('remarks')->toString()
                : null,
            'updated_by' => $user->id,
        ]);

        return back()->with('success', 'Dispatch updated successfully.');
    }

    public function depart(DepartDispatchRequest $request, Dispatch $dispatch): RedirectResponse
    {
        $user = $request->user();
        $company = $user->company;

        abort_unless($company, 403, 'No company is associated with this user.');
        abort_unless($dispatch->company_id === $company->id, 403);

        if ($dispatch->departed_at) {
            return back()->with('success', 'This dispatch has already been marked as departed.');
        }

        $dispatch->update([
            'pax_count' => $request->integer('pax_count'),
            'departed_at' => now(),
            'status' => Dispatch::STATUS_DEPARTED,
            'updated_by' => $user->id,
        ]);

        return back()->with('success', 'Dispatch marked as departed.');
    }
}
