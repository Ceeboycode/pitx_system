<?php

namespace App\Http\Controllers;

use App\Http\Requests\Gate\GateStoreRequest;
use App\Http\Requests\Gate\GateUpdateRequest;
use App\Models\Dispatch;
use App\Models\Gate as GateModel;
use App\Notifications\External\GateStatusChangedNotification;
use App\Services\Gate\GateService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GateController extends Controller
{
    public function __construct(
        private GateService $gateService,
        private NotificationService $notificationService,
    ) {}

    public function index(Request $request)
{
    Gate::authorize('viewAny', GateModel::class);

    $gates = GateModel::query()
        ->select('id', 'gate_name', 'status', 'bays', 'location', 'picture_path', 'created_by')
        ->with([
            'creator:id,name',
            'routes:id,gate_id,route_name,status',
            'dispatches' => fn ($query) => $query
                ->select('id', 'company_id', 'vehicle_id', 'gate_id', 'plate_number', 'bay_number', 'status')
                ->where('status', '!=', Dispatch::STATUS_DEPARTED)
                ->with([
                    'company:id,company_name',
                    'vehicle:id,company_id,plate_number,body_number',
                    'vehicle.company:id,company_name',
                ])
                ->latest('updated_at'),
        ])

        // ✅ Search
        ->when($request->search, fn ($q, $s) =>
            $q->where('gate_name', 'like', "%{$s}%")
        )

        // ✅ Status filter (FIXED)
        ->when($request->filled('status'), fn ($q) =>
            $q->where('status', $request->status)
        )

        // ✅ Bays filter (optional but you already have it in UI)
        ->when($request->filled('bays'), fn ($q) =>
            $q->where('bays', $request->bays)
        )

        ->latest()
        ->paginate(10)
        ->withQueryString()
        ->through(fn (GateModel $gate) => $this->gateIndexPayload($gate));

    return Inertia::render('Gates/Index', [
        'gates' => $gates,

        // ✅ IMPORTANT: include ALL filters
        'filters' => $request->only('search', 'status', 'bays'),
    ]);
}

    private function gateIndexPayload(GateModel $gate): array
    {
        $occupiedByBay = collect();

        foreach ($gate->dispatches as $dispatch) {
            $bayNumber = $this->bayNumberFromDispatch($dispatch->bay_number);

            if ($bayNumber !== null && ! $occupiedByBay->has($bayNumber)) {
                $occupiedByBay->put($bayNumber, $dispatch);
            }
        }

        $bayStatuses = [];

        for ($bayNumber = 1; $bayNumber <= (int) $gate->bays; $bayNumber++) {
            $dispatch = $occupiedByBay->get($bayNumber);
            $vehicle = $dispatch?->vehicle;
            $company = $vehicle?->company ?? $dispatch?->company;

            $bayStatuses[] = [
                'bay_number' => $bayNumber,
                'status' => $dispatch ? 'occupied' : 'empty',
                'vehicle' => $dispatch ? [
                    'plate_number' => $vehicle?->plate_number ?? $dispatch->plate_number,
                    'body_number' => $vehicle?->body_number,
                ] : null,
                'company' => $dispatch ? [
                    'company_name' => $company?->company_name ?? 'Unknown company',
                ] : null,
            ];
        }

        return [
            'id' => $gate->id,
            'gate_name' => $gate->gate_name,
            'status' => $gate->status,
            'bays' => $gate->bays,
            'creator' => $gate->creator ? [
                'id' => $gate->creator->id,
                'name' => $gate->creator->name,
            ] : null,
            'location' => [
                'label' => $gate->location ?? 'Location not configured',
                'is_placeholder' => $gate->location === null,
            ],
            'picture_path' => $gate->picture_path,
            'picture_url' => $gate->picture_path ? Storage::disk('public')->url($gate->picture_path) : null,
            'assigned_routes' => $gate->routes
                ->map(fn ($route) => [
                    'id' => $route->id,
                    'route_name' => $route->route_name,
                    'status' => $route->status instanceof \BackedEnum
                        ? $route->status->value
                        : (string) $route->status,
                ])
                ->values(),
            'bay_statuses' => $bayStatuses,
        ];
    }

    private function bayNumberFromDispatch(?string $bayNumber): ?int
    {
        if ($bayNumber === null || $bayNumber === '') {
            return null;
        }

        if (preg_match('/\d+/', $bayNumber, $matches) !== 1) {
            return null;
        }

        return (int) $matches[0];
    }

    public function show(GateModel $gate)
    {
        Gate::authorize('view', $gate);

        return Inertia::render('Gates/Show', [
            'gate' => $gate->load(['creator', 'updater']),
        ]);
    }

    public function store(GateStoreRequest $request)
    {
        Gate::authorize('create', GateModel::class);

        $data = $request->safe()->except('picture');
        if ($request->hasFile('picture')) {
            $data['picture_path'] = $request->file('picture')->store('gates', 'public');
        }
        $this->gateService->createGate($data);

        return to_route('gates.index')->with('success', 'Gate created successfully.');
    }

    public function update(GateUpdateRequest $request, GateModel $gate)
    {
        Gate::authorize('update', $gate);

        $oldStatus = (string) $gate->status;

        $data = $request->safe()->except('picture');
        if ($request->hasFile('picture')) {
            if ($gate->picture_path) Storage::disk('public')->delete($gate->picture_path);
            $data['picture_path'] = $request->file('picture')->store('gates', 'public');
        }
        $this->gateService->updateGate($gate, $data);

        $gate->refresh();

        if ($oldStatus !== (string) $gate->status) {
            $notification = new GateStatusChangedNotification($gate, (string) $gate->status);

            $this->notificationService->notifyAffectedCompaniesByGate($gate, $notification);
        }

        return redirect()->back()->with('success', 'Gate updated successfully.');
    }

    public function toggleStatus(GateModel $gate)
    {
        Gate::authorize('update', $gate);

        $gate->update([
            'status' => $gate->status === 'active' ? 'inactive' : 'active',
            'updated_by' => auth()->id(),
        ]);
        $gate->refresh();

        $notification = new GateStatusChangedNotification($gate, (string) $gate->status);
        $this->notificationService->notifyAffectedCompaniesByGate($gate, $notification);

        return back()->with('success', "Gate marked as {$gate->status}.");
    }

    public function trash(Request $request)
    {
        Gate::authorize('viewTrash', GateModel::class);

        $gates = GateModel::onlyTrashed()
            ->select('id', 'gate_name', 'status', 'bays', 'deleted_at')
            ->when($request->search, fn ($q, $s) => $q->where('gate_name', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->filled('bays'), fn ($q) => $q->where('bays', (int) $request->bays))
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Gates/Trash', [
            'gates'   => $gates,
            'filters' => $request->only('search', 'status', 'bays'),
        ]);
    }

    public function destroy(GateModel $gate)
    {
        Gate::authorize('delete', $gate);

        $this->gateService->deleteGate($gate);

        return to_route('gates.index')->with('success', 'Gate archived successfully.');
    }

    public function restore(int $id)
    {
        $gate = GateModel::onlyTrashed()->findOrFail($id);

        Gate::authorize('restore', $gate);

        $this->gateService->restoreGate($gate);

        return redirect()->back()->with('success', 'Gate restored successfully.');
    }

    public function forceDelete(int $id)
    {
        $gate = GateModel::onlyTrashed()->findOrFail($id);

        Gate::authorize('forceDelete', $gate);

        $this->gateService->forceDeleteGate($gate);

        return redirect()->back()->with('success', 'Gate permanently deleted.');
    }
}
