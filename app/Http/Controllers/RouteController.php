<?php

namespace App\Http\Controllers;

use App\Http\Requests\Route\RouteStoreRequest;
use App\Http\Requests\Route\RouteUpdateRequest;
use App\Models\Gate as GateModel;
use App\Models\Route;
use App\Services\Route\RouteService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RouteController extends Controller
{
    public function __construct(
        private RouteService $routeService
    ) {}

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(): Response
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

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(Route $route): Response
    {
        Gate::authorize('view', Route::class);

        $route->load([
            'gate:id,gate_name',
            'stops',
            'creator:id,name',
            'updater:id,name',
        ]);

        return Inertia::render('Route/Show', [
            'route'     => $route,
            'mapConfig' => [
                'mapboxToken' => config('app.mapbox_public_token', env('VITE_MAPBOX_TOKEN')),
            ],
        ]);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(): Response
    {
        Gate::authorize('create', Route::class);

        return Inertia::render('Route/Create', [
            'gates'     => GateModel::select('id', 'gate_name')->orderBy('gate_name')->get(),
            'mapConfig' => [
                'mapboxToken' => config('app.mapbox_public_token', env('VITE_MAPBOX_TOKEN')),
                'pitx'        => [
                    'name' => 'PITX',
                    'lat'  => 14.5096,
                    'lng'  => 120.9915,
                ],
            ],
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(RouteStoreRequest $request)
    {
        Gate::authorize('create', Route::class);

        $this->routeService->createRoute($request->validated());

        return to_route('routes.index')->with('success', 'Route created successfully.');
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function edit(Route $route): Response
    {
        Gate::authorize('update', Route::class);

        $route->load([
            'gate:id,gate_name',
            'stops',
            'creator:id,name',
            'updater:id,name',
        ]);

        return Inertia::render('Route/Edit', [
            'route'     => $route,
            'gates'     => GateModel::select('id', 'gate_name')->orderBy('gate_name')->get(),
            'mapConfig' => [
                'mapboxToken' => config('app.mapbox_public_token', env('VITE_MAPBOX_TOKEN')),
                'pitx'        => [
                    'name' => 'PITX',
                    'lat'  => 14.5096,
                    'lng'  => 120.9915,
                ],
            ],
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(RouteUpdateRequest $request, Route $route)
    {
        Gate::authorize('update', Route::class);

        $this->routeService->updateRoute($route, $request->validated());

        return to_route('routes.index')->with('success', 'Route updated successfully.');
    }

    // ── Trash ─────────────────────────────────────────────────────────────────

    public function trash(): Response
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

    // ── Destroy (soft) ────────────────────────────────────────────────────────

    public function destroy(Route $route)
    {
        Gate::authorize('delete', Route::class);

        $this->routeService->deleteRoute($route);

        return to_route('routes.index')->with('success', 'Route archived successfully.');
    }

    // ── Restore ───────────────────────────────────────────────────────────────

    public function restore(Route $route)
    {
        Gate::authorize('restore', Route::class);

        $this->routeService->restoreRoute($route);

        return to_route('routes.trash')->with('success', 'Route restored successfully.');
    }

    // ── Force delete ──────────────────────────────────────────────────────────

    public function forceDelete(Route $route)
    {
        Gate::authorize('forceDelete', Route::class);

        $this->routeService->forceDeleteRoute($route);

        return to_route('routes.trash')->with('success', 'Route permanently deleted successfully.');
    }
}
