<?php

namespace App\Http\Controllers;

use App\Http\Requests\Route\RouteStoreRequest;
use App\Http\Requests\Route\RouteUpdateRequest;
use App\Models\Gate as GateModel;
use App\Models\Route;
use App\Notifications\External\RouteStatusChangedNotification;
use App\Services\NotificationService;
use App\Services\Route\RouteService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RouteController extends Controller
{
    public function __construct(
        private RouteService $routeService,
        private NotificationService $notificationService,
    ) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', Route::class);

        $search = request('search');
        $status = request('status');
        $sortBy = request('sort_by');
        $sortDir = request('sort_dir', 'asc');

        $allowedSorts = ['route_name', 'gate_name', 'status', 'created_at'];
        $sortDir = in_array($sortDir, ['asc', 'desc']) ? $sortDir : 'asc';

        $routes = Route::query()
            ->select('routes.id', 'routes.route_name', 'routes.gate_id', 'routes.status', 'routes.created_at')
            ->with('gate:id,gate_name')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('route_name', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('gate', fn ($g) =>
                            $g->where('gate_name', 'like', "%{$search}%")
                        );
                });
            })
            ->when($status && $status !== 'all', function ($query) use ($status) {
                $query->where('routes.status', $status);
            })
            ->when($sortBy && in_array($sortBy, $allowedSorts), function ($query) use ($sortBy, $sortDir) {
                if ($sortBy === 'gate_name') {
                    $query->leftJoin('gates', 'routes.gate_id', '=', 'gates.id')
                        ->orderBy('gates.gate_name', $sortDir)
                        ->select('routes.id', 'routes.route_name', 'routes.gate_id', 'routes.status', 'routes.created_at');
                } else {
                    $query->orderBy("routes.{$sortBy}", $sortDir);
                }
            }, function ($query) {
                $query->latest('routes.created_at');
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Route/Index', [
            'routes' => $routes,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort_by' => in_array($sortBy, $allowedSorts) ? $sortBy : null,
                'sort_dir' => $sortDir,
            ],
        ]);
    }

    public function show(Route $route): Response
    {
        Gate::authorize('view', $route);

        $route->load([
            'gate:id,gate_name',
            'stops',
            'creator:id,name',
            'updater:id,name',
        ]);

        return Inertia::render('Route/Show', [
            'route' => $route,
            'mapConfig' => [
                'mapboxToken' => config('app.mapbox_public_token', env('VITE_MAPBOX_TOKEN')),
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Route::class);

        return Inertia::render('Route/Create', [
            'gates' => GateModel::select('id', 'gate_name')->orderBy('gate_name')->get(),
            'mapConfig' => [
                'mapboxToken' => config('app.mapbox_public_token', env('VITE_MAPBOX_TOKEN')),
                'pitx' => [
                    'name' => 'PITX',
                    'lat' => 14.5096,
                    'lng' => 120.9915,
                ],
            ],
        ]);
    }

    public function store(RouteStoreRequest $request)
    {
        Gate::authorize('create', Route::class);

        $this->routeService->createRoute($request->validated());

        return to_route('routes.index')->with('success', 'Route created successfully.');
    }

    public function edit(Route $route): Response
    {
        Gate::authorize('update', $route);

        $route->load([
            'gate:id,gate_name',
            'stops',
            'creator:id,name',
            'updater:id,name',
        ]);

        return Inertia::render('Route/Edit', [
            'route' => $route,
            'gates' => GateModel::select('id', 'gate_name')->orderBy('gate_name')->get(),
            'mapConfig' => [
                'mapboxToken' => config('app.mapbox_public_token', env('VITE_MAPBOX_TOKEN')),
                'pitx' => [
                    'name' => 'PITX',
                    'lat' => 14.5096,
                    'lng' => 120.9915,
                ],
            ],
        ]);
    }

    public function update(RouteUpdateRequest $request, Route $route)
    {
        Gate::authorize('update', $route);

        $this->routeService->updateRoute($route, $request->validated());

        return to_route('routes.index')->with('success', 'Route updated successfully.');
    }

    public function toggleStatus(Route $route)
    {
        Gate::authorize('toggleStatus', $route);

        $route->toggleStatus();
        $route->refresh();

        $label = $route->status->label();

        $notification = new RouteStatusChangedNotification($route, $label);

        $this->notificationService->notifyAffectedCompaniesByRoute($route, $notification);

        return back()->with('success', "Route marked as {$label}.");
    }

    public function trash(): Response
    {
        Gate::authorize('viewTrash', Route::class);

        $search = request('search');

        $routes = Route::onlyTrashed()
            ->select('id', 'route_name', 'gate_id', 'status', 'deleted_at')
            ->with('gate:id,gate_name')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('route_name', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('gate', fn ($g) =>
                            $g->where('gate_name', 'like', "%{$search}%")
                        );
                });
            })
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Route/Trash', [
            'routes' => $routes,
            'filters' => ['search' => $search],
        ]);
    }

    public function destroy(Route $route)
    {
        Gate::authorize('delete', $route);

        $this->routeService->deleteRoute($route);

        return to_route('routes.index')->with('success', 'Route archived successfully.');
    }

    public function restore(Route $route)
    {
        Gate::authorize('restore', $route);

        $this->routeService->restoreRoute($route);

        return to_route('routes.trash')->with('success', 'Route restored successfully.');
    }

    public function forceDelete(Route $route)
    {
        Gate::authorize('forceDelete', $route);

        $this->routeService->forceDeleteRoute($route);

        return to_route('routes.trash')->with('success', 'Route permanently deleted successfully.');
    }
}