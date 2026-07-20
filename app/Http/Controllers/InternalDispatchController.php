<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InternalDispatchController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('dispatches.viewAny');

        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $minimumDispatches = max(0, $request->integer('minimum_dispatches'));

        $allowedSortFields = ['company_name', 'company_code', 'status', 'dispatches_count'];
        $sortBy  = in_array($request->input('sort_by'), $allowedSortFields, true) ? $request->input('sort_by') : 'company_name';
        $sortDir = $request->input('sort_dir') === 'desc' ? 'desc' : 'asc';

        $companies = Company::query()
            ->select(['id', 'company_name', 'company_code', 'company_email', 'company_phone', 'status'])
            ->withCount('dispatches')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('company_name', 'like', "%{$search}%")
                        ->orWhere('company_code', 'like', "%{$search}%")
                        ->orWhere('company_email', 'like', "%{$search}%")
                        ->orWhere('company_phone', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($minimumDispatches > 0, fn ($query) => $query->has('dispatches', '>=', $minimumDispatches))
            ->orderBy($sortBy, $sortDir)
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Company $company) => [
                'id'               => $company->id,
                'company_name'     => $company->company_name,
                'company_code'     => $company->company_code,
                'company_email'    => $company->company_email,
                'company_phone'    => $company->company_phone,
                'status'           => $company->status,
                'dispatches_count' => $company->dispatches_count,
            ]);

        return Inertia::render('Dispatches/Index', [
            'filters'   => [
                'search' => $search,
                'status' => $status,
                'minimum_dispatches' => $minimumDispatches ?: null,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
            'companies' => $companies,
        ]);
    }

    public function show(Request $request, int $company): Response
    {
        Gate::authorize('dispatches.view');

        $selectedDate = trim((string) $request->string('date'));
        $search       = trim((string) $request->string('search'));
        $status       = trim((string) $request->string('status', 'all'));

        $allowedStatuses = ['all', 'arrived', 'departed'];
        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $company = Company::query()
            ->select(['id', 'company_name', 'company_code', 'company_email', 'company_phone', 'status', 'logo'])
            ->findOrFail($company);

        $baseQuery = $company->dispatches()
            ->select([
                'id',
                'company_id',
                'vehicle_id',
                'gate_id',
                'plate_number',
                'pax_count',
                'bay_number',
                'remarks',
                'dispatcher_user_id',
                'driver_user_id',
                'arrived_at',
                'departed_at',
                'dispatched_at',
                'status',
            ])
            ->with([
                'vehicle:id,route_id,plate_number,vehicle_type,make_model',
                'vehicle.route:id,route_name,origin_name,destination_name,route_geometry',
                'vehicle.route.stops:id,route_id,stop_name,stop_order,stop_type,address,latitude,longitude',
                'gate:id,gate_name',
                'dispatcher:id,name',
                'driver:id,name',
            ])
            ->when($selectedDate, fn ($q) => $q->whereDate('dispatched_at', $selectedDate))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('plate_number', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhere('bay_number', 'like', "%{$search}%")
                        ->orWhereHas('vehicle', fn ($v) =>
                            $v->where('plate_number', 'like', "%{$search}%")
                                ->orWhere('vehicle_type', 'like', "%{$search}%")
                                ->orWhere('make_model', 'like', "%{$search}%")
                        )
                        ->orWhereHas('vehicle.route', fn ($r) =>
                            $r->where('route_name', 'like', "%{$search}%")
                                ->orWhere('origin_name', 'like', "%{$search}%")
                                ->orWhere('destination_name', 'like', "%{$search}%")
                        )
                        ->orWhereHas('gate', fn ($g) =>
                            $g->where('gate_name', 'like', "%{$search}%")
                        )
                        ->orWhereHas('dispatcher', fn ($d) =>
                            $d->where('name', 'like', "%{$search}%")
                        )
                        ->orWhereHas('driver', fn ($d) =>
                            $d->where('name', 'like', "%{$search}%")
                        );
                });
            });

        /*
        |--------------------------------------------------------------------------
        | Summary (FULL filtered result, not paginated)
        |--------------------------------------------------------------------------
        */
        $summaryDispatches = (clone $baseQuery)->get();

        $filteredTotal    = $summaryDispatches->count();
        $totalPax         = (int) $summaryDispatches->sum(fn ($d) => (int) ($d->pax_count ?? 0));
        $withPaxCount     = $summaryDispatches->filter(fn ($d) => $d->pax_count !== null)->count();
        $avgPax           = $withPaxCount > 0 ? (int) round($totalPax / $withPaxCount) : 0;

        $dispatchWithGateCount  = $summaryDispatches->filter(fn ($d) => filled($d->gate?->gate_name))->count();
        $dispatchWithRouteCount = $summaryDispatches->filter(fn ($d) => filled($d->vehicle?->route?->route_name))->count();

        $routeCoveragePercent = $filteredTotal > 0
            ? (int) round(($dispatchWithRouteCount / $filteredTotal) * 100)
            : 0;

        $gateCoveragePercent = $filteredTotal > 0
            ? (int) round(($dispatchWithGateCount / $filteredTotal) * 100)
            : 0;

        $statusBreakdown = $summaryDispatches
            ->groupBy(fn ($d) => $d->status ?: 'unknown')
            ->map(function ($items, $statusKey) use ($filteredTotal) {
                $count = $items->count();

                return [
                    'status' => $statusKey,
                    'count'  => $count,
                    'pct'    => $filteredTotal > 0 ? (int) round(($count / $filteredTotal) * 100) : 0,
                ];
            })
            ->sortByDesc('count')
            ->values();

        $routeSummary = $summaryDispatches
            ->groupBy(fn ($d) => $d->vehicle?->route?->route_name ?: 'No Route')
            ->map(fn ($items, $label) => [
                'label' => $label,
                'count' => $items->count(),
                'pax'   => (int) $items->sum(fn ($d) => (int) ($d->pax_count ?? 0)),
            ])
            ->sortByDesc('count')
            ->take(5)
            ->values();

        $gateSummary = $summaryDispatches
            ->groupBy(fn ($d) => $d->gate?->gate_name ?: 'No Gate')
            ->map(fn ($items, $label) => [
                'label' => $label,
                'count' => $items->count(),
                'pax'   => (int) $items->sum(fn ($d) => (int) ($d->pax_count ?? 0)),
            ])
            ->sortByDesc('count')
            ->values();

        $baySummary = $summaryDispatches
            ->groupBy(fn ($d) => $d->bay_number !== null && $d->bay_number !== ''
                ? 'Bay ' . $d->bay_number
                : 'No Bay')
            ->map(fn ($items, $label) => [
                'label' => $label,
                'count' => $items->count(),
                'pax'   => (int) $items->sum(fn ($d) => (int) ($d->pax_count ?? 0)),
            ])
            ->sortByDesc('count')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Paginated records
        |--------------------------------------------------------------------------
        */
        $dispatches = (clone $baseQuery)
            ->latest('dispatched_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($dispatch) {
                $route = $dispatch->vehicle?->route;

                return [
                    'id'            => $dispatch->id,
                    'plate_number'  => $dispatch->plate_number,
                    'pax_count'     => $dispatch->pax_count,
                    'bay_number'    => $dispatch->bay_number,
                    'remarks'       => $dispatch->remarks,
                    'status'        => $dispatch->status,
                    'dispatched_at' => $dispatch->dispatched_at?->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'arrived_at'    => $dispatch->arrived_at?->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'departed_at'   => $dispatch->departed_at?->timezone('Asia/Manila')->format('M d, Y h:i A'),

                    'vehicle' => $dispatch->vehicle ? [
                        'plate_number' => $dispatch->vehicle->plate_number,
                        'vehicle_type' => $dispatch->vehicle->vehicle_type,
                        'make_model'   => $dispatch->vehicle->make_model,
                        'route'        => $route ? [
                            'route_name'       => $route->route_name,
                            'origin_name'      => $route->origin_name,
                            'destination_name' => $route->destination_name,
                            'route_geometry'   => $route->route_geometry,
                            'stops'            => $route->stops
                                ->sortBy('stop_order')
                                ->values()
                                ->map(fn ($stop) => [
                                    'id'         => $stop->id,
                                    'stop_name'  => $stop->stop_name,
                                    'stop_order' => $stop->stop_order,
                                    'stop_type'  => $stop->stop_type,
                                    'address'    => $stop->address,
                                    'latitude'   => $stop->latitude,
                                    'longitude'  => $stop->longitude,
                                ]),
                        ] : null,
                    ] : null,

                    'gate' => $dispatch->gate
                        ? ['gate_name' => $dispatch->gate->gate_name]
                        : null,

                    'dispatcher' => $dispatch->dispatcher
                        ? ['name' => $dispatch->dispatcher->name]
                        : null,

                    'driver' => $dispatch->driver
                        ? ['name' => $dispatch->driver->name]
                        : null,
                ];
            });

        return Inertia::render('Dispatches/Show', [
            'filters' => [
                'date'   => $selectedDate,
                'search' => $search,
                'status' => $status,
            ],
            'company' => [
                'id'            => $company->id,
                'company_name'  => $company->company_name,
                'company_code'  => $company->company_code,
                'company_email' => $company->company_email,
                'company_phone' => $company->company_phone,
                'status'        => $company->status,
                'company_logo'  => $company->logo ? asset('storage/' . $company->logo) : null,
            ],
            'dispatches' => $dispatches,
            'summary'    => [
                'filtered_total'         => $filteredTotal,
                'total_pax'              => $totalPax,
                'avg_pax'                => $avgPax,
                'route_coverage_percent' => $routeCoveragePercent,
                'gate_coverage_percent'  => $gateCoveragePercent,
                'status_breakdown'       => $statusBreakdown,
                'route_summary'          => $routeSummary,
                'gate_summary'           => $gateSummary,
                'bay_summary'            => $baySummary,
            ],
            'mapConfig' => [
                'mapboxToken'   => config('app.mapbox_public_token', env('VITE_MAPBOX_TOKEN')),
                'defaultCenter' => ['lng' => 120.9842, 'lat' => 14.5995],
                'defaultZoom'   => 11,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT
    |
    | GET /dispatches/{dispatch}/export
    |
    | Streams a CSV of all dispatches for a company matching the current
    | filters (date, status, search). Exports all rows — not paginated.
    |--------------------------------------------------------------------------
    */
    public function export(Request $request, int $dispatch): StreamedResponse
    {
        Gate::authorize('dispatches.view');

        $selectedDate = trim((string) $request->string('date'));
        $search       = trim((string) $request->string('search'));
        $status       = trim((string) $request->string('status', 'all'));

        $allowedStatuses = ['all', 'arrived', 'departed'];
        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $company = Company::select(['id', 'company_name'])->findOrFail($dispatch);

        $dispatches = $company->dispatches()
            ->with([
                'vehicle:id,route_id,plate_number,vehicle_type,make_model',
                'vehicle.route:id,route_name,origin_name,destination_name',
                'gate:id,gate_name',
                'dispatcher:id,name',
                'driver:id,name',
            ])
            ->when($selectedDate, fn ($q) => $q->whereDate('dispatched_at', $selectedDate))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('plate_number', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhere('bay_number', 'like', "%{$search}%")
                        ->orWhereHas('vehicle', fn ($v) =>
                            $v->where('plate_number', 'like', "%{$search}%")
                                ->orWhere('vehicle_type', 'like', "%{$search}%")
                                ->orWhere('make_model', 'like', "%{$search}%")
                        )
                        ->orWhereHas('vehicle.route', fn ($r) =>
                            $r->where('route_name', 'like', "%{$search}%")
                                ->orWhere('origin_name', 'like', "%{$search}%")
                                ->orWhere('destination_name', 'like', "%{$search}%")
                        )
                        ->orWhereHas('gate', fn ($g) =>
                            $g->where('gate_name', 'like', "%{$search}%")
                        )
                        ->orWhereHas('dispatcher', fn ($d) =>
                            $d->where('name', 'like', "%{$search}%")
                        )
                        ->orWhereHas('driver', fn ($d) =>
                            $d->where('name', 'like', "%{$search}%")
                        );
                });
            })
            ->latest('dispatched_at')
            ->latest('id')
            ->get();

        $label = collect([
            $company->company_name,
            $selectedDate ?: null,
            ($status !== 'all') ? $status : null,
        ])->filter()->map(fn ($s) => str_replace(' ', '-', $s))->implode('_');

        $filename = 'dispatches_' . $label . '_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($dispatches) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'ID', 'Plate Number', 'Vehicle Type', 'Make / Model',
                'Route', 'Bay', 'PAX', 'Status',
                'Driver', 'Dispatcher', 'Gate',
                'Arrived At', 'Departed At', 'Dispatched At', 'Remarks',
            ]);

            foreach ($dispatches as $d) {
                $route = $d->vehicle?->route;
                fputcsv($out, [
                    $d->id,
                    $d->vehicle?->plate_number ?? $d->plate_number ?? '',
                    $d->vehicle?->vehicle_type ?? '',
                    $d->vehicle?->make_model   ?? '',
                    $route
                        ? trim(($route->origin_name ?? '') . ' to ' . ($route->destination_name ?? ''))
                        : '',
                    $d->bay_number  ?? '',
                    $d->pax_count   ?? '',
                    $d->status      ?? '',
                    $d->driver?->name     ?? '',
                    $d->dispatcher?->name ?? '',
                    $d->gate?->gate_name  ?? '',
                    $d->arrived_at?->timezone('Asia/Manila')->format('M d, Y h:i A')   ?? '',
                    $d->departed_at?->timezone('Asia/Manila')->format('M d, Y h:i A')  ?? '',
                    $d->dispatched_at?->timezone('Asia/Manila')->format('M d, Y h:i A') ?? '',
                    $d->remarks ?? '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
