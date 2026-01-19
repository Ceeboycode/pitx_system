<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Route\RouteService;
use App\Http\Requests\Route\RouteStoreRequest;
use App\Http\Requests\Route\RouteUpdateRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Route;
use Inertia\Inertia;

class RouteController extends Controller
{
    public function __construct(
        private RouteService $routeService
    ) {}

    public function index()
    {
        Gate::authorize('viewAny', Route::class);

        $routes = Route::with('gate')
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

        return Inertia::render('Route/Show', [
            'route' => $route->load(['gate', 'creator', 'updater']),
        ]);
    }

    public function store(RouteStoreRequest $request)
    {
        Gate::authorize('create', Route::class);

        $this->routeService->createRoute(
            $request->validated()
        );

        return redirect()->back()->with('success', 'Route created successfully.');
    }

    public function update(RouteUpdateRequest $request, Route $route)
    {
        Gate::authorize('update', Route::class);
        $this->routeService->updateRoute(
            $route,
            $request->validated()
        );
        return redirect()->back()->with('success', 'Route updated successfully.');
    }

    public function destroy(Route $route)
    {
        Gate::authorize('delete', Route::class);

        $this->routeService->deleteRoute($route);
        
        return redirect()->back()->with('success', 'Route deleted successfully.');
    }

    public function restore(Route $route)
    {
        Gate::authorize('restore', Route::class);

        $this->routeService->restoreRoute($route);

        return redirect()->back()->with('success', 'Route restored successfully.');
    }

    public function forceDelete(Route $route)
    {
        Gate::authorize('forceDelete', Route::class);

        $this->routeService->forceDeleteRoute($route);

        return redirect()->back()->with('success', 'Route permanently deleted successfully.');
    }

}
