<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Services\RouteStop\RouteStopService;
use App\Models\RouteStop;
use App\Http\Requests\RouteStop\RouteStopStoreRequest;
use App\Http\Requests\RouteStop\RouteStopUpdateRequest;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;

class RouteStopController extends Controller
{
    public function __construct(
        private RouteStopService $routeStopService
    ) {}

    public function index()
    {
    Gate::authorize('viewAny', RouteStop::class);

    $routeStops = RouteStop::with('route:id,route_name')
        ->orderBy('route_id')
        ->orderBy('stop_order')
        ->paginate(10)
        ->through(fn ($stop) => [
            'id' => $stop->id,
            'stop_name' => $stop->stop_name,
            'order' => $stop->stop_order,
            'route_name' => $stop->route?->route_name,
        ])
        ->withQueryString();

            return Inertia::render('RouteStops/Index', [
                'routeStops' => $routeStops,
            ]);
    }

    public function show(RouteStop $routeStop)
    {
        Gate::authorize('view', $routeStop);

        return Inertia::render('RouteStops/Show', [
            'routeStop' => $routeStop->load('route'),
        ]);
    }


    public function store(RouteStopStoreRequest $request)
    {
        Gate::authorize('create', RouteStop::class);

        $this->routeStopService->createRouteStop(
            $request->validated()
        );

        return redirect()->back()->with('success', 'Route Stop created successfully.');
    }

    public function update(RouteStopUpdateRequest $request, RouteStop $routeStop)
    {
        Gate::authorize('update', $routeStop);

        $this->routeStopService->updateRouteStop(
            $routeStop,
            $request->validated()
        );

        return redirect()->back()->with('success', 'Route Stop updated successfully.');
    }

    public function destroy(RouteStop $routeStop)
    {
        Gate::authorize('delete', $routeStop);

        $this->routeStopService->deleteRouteStop($routeStop);

        return redirect()->back()->with('success', 'Route Stop deleted successfully.');
    }

    public function restore(RouteStop $routeStop)
    {
        Gate::authorize('restore', $routeStop);

        $this->routeStopService->restoreRouteStop($routeStop);

        return redirect()->back()->with('success', 'Route Stop restored successfully.');
    }

    public function forceDelete(RouteStop $routeStop)
    {
        Gate::authorize('forceDelete', $routeStop);

        $this->routeStopService->forceDeleteRouteStop($routeStop);

        return redirect()->back()->with('success', 'Route Stop permanently deleted successfully.');
    }
}
