<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Route\RouteService;
use App\Http\Requests\Route\RouteStoreRequest;
use App\Http\Requests\Route\RouteUpdateRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Route;
use App\Models\Gate as GateModel;
use Inertia\Inertia;

class RouteController extends Controller
{
    public function __construct(
        private RouteService $routeService
    ) {}

    public function index()
    {
        Gate::authorize('viewAny', Route::class);

        $routes = Route::select('id', 'route_name', 'gate_id', 'created_at')
            ->with('gate:id,gate_name')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Route/Index', [
            'routes' => $routes,
        ]);
    }

    public function show(Route $route)
    {
        Gate::authorize('view', Route::class);

        $route->load([
            'gate:id,gate_name',
            'stops:id,route_id,stop_name,stop_order',
            'creator:id,name',
            'updater:id,name',
        ]);

        return Inertia::render('Route/Show', [
            'route' => $route,
        ]);
    }

    public function create()
    {
        Gate::authorize('create', Route::class);

        $gates = GateModel::select('id', 'gate_name')
            ->orderBy('gate_name')
            ->get();

        return Inertia::render('Route/Create', [
            'gates' => $gates,
        ]);
    }

    public function edit(Route $route)
    {
        Gate::authorize('update', Route::class);

        $route->load('gate:id,gate_name');

        $gates = GateModel::select('id', 'gate_name')
            ->orderBy('gate_name')
            ->get();

        return Inertia::render('Route/Edit', [
            'route' => $route,
            'gates' => $gates,
        ]);
    }

    public function store(RouteStoreRequest $request)
    {
        Gate::authorize('create', Route::class);

        $this->routeService->createRoute(
            $request->validated()
        );

        return to_route('routes.create')->with('success', 'Route created successfully.');
    }

    public function update(RouteUpdateRequest $request, Route $route)
    {
        Gate::authorize('update', Route::class);

        $this->routeService->updateRoute(
            $route,
            $request->validated()
        );

        return to_route('routes.index')->with('success', 'Route updated successfully.');
    }

    public function trash()
    {
        Gate::authorize('viewTrash', Route::class);

        $routes = Route::onlyTrashed()
            ->select('id', 'route_name', 'gate_id', 'deleted_at')
            ->with('gate:id,gate_name')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Route/Trash', [
            'routes' => $routes,
        ]);
    }

    public function destroy(Route $route)
    {
        Gate::authorize('delete', Route::class);

        $this->routeService->deleteRoute($route);

        return to_route('routes.index')->with('success', 'Route archived successfully.');
    }

    public function restore(Route $route)
    {
        Gate::authorize('restore', Route::class);

        $this->routeService->restoreRoute($route);

        return to_route('routes.trash')->with('success', 'Route restored successfully.');
    }

    public function forceDelete(Route $route)
    {
        Gate::authorize('forceDelete', Route::class);

        $this->routeService->forceDeleteRoute($route);

        return to_route('routes.trash')->with('success', 'Route permanently deleted successfully.');
    }

}
