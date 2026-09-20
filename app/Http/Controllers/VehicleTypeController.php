<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleType\VehicleTypeStoreRequest;
use App\Http\Requests\VehicleType\VehicleTypeUpdateRequest;
use App\Models\AuditLog;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Services\Vehicle\VehicleTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        ]);
    }

    public function store(VehicleTypeStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', VehicleType::class);

        $data = $request->safe()->except('picture');
        if ($request->hasFile('picture')) {
            $data['picture_path'] = $request->file('picture')->store('vehicle-types', 'public');
        }

        $this->vehicleTypeService->createVehicleType($data);

        return to_route('vehicle-types.index')->with('success', 'Vehicle type created successfully.');
    }

    public function update(VehicleTypeUpdateRequest $request, VehicleType $vehicleType): RedirectResponse
    {
        Gate::authorize('update', $vehicleType);

        $data = $request->safe()->except(['picture', 'remove_picture']);
        if ($request->hasFile('picture')) {
            if ($vehicleType->picture_path) {
                Storage::disk('public')->delete($vehicleType->picture_path);
            }
            $data['picture_path'] = $request->file('picture')->store('vehicle-types', 'public');
        } elseif ($request->boolean('remove_picture') && $vehicleType->picture_path) {
            Storage::disk('public')->delete($vehicleType->picture_path);
            $data['picture_path'] = null;
        }

        $this->vehicleTypeService->updateVehicleType($vehicleType, $data);

        return redirect()->back()->with('success', 'Vehicle type updated successfully.');
    }

    public function edit(VehicleType $vehicleType)
    {
        // Page access only needs 'view' - VehicleType/Edit.vue decides what's
        // actually editable via can('vehicle_types.update'), and update()
        // below still independently authorizes the real write.
        Gate::authorize('view', $vehicleType);

        // Archived vehicle types open read-only, and only for whoever may open the Archives page.
        if ($vehicleType->trashed()) {
            Gate::authorize('viewTrash', VehicleType::class);
        }

        $vehicleType->load(['creator:id,name', 'updater:id,name']);

        return Inertia::render('VehicleType/Edit', [
            'isArchived' => $vehicleType->trashed(),
            'vehicleType' => [
                'id' => $vehicleType->id,
                'type_name' => $vehicleType->type_name,
                'description' => $vehicleType->description,
                'picture_url' => $vehicleType->picture_path ? Storage::disk('public')->url($vehicleType->picture_path) : null,
                'is_active' => $vehicleType->is_active,
                'created_at_human' => $vehicleType->created_at_human,
                'updated_at_human' => $vehicleType->updated_at_human,
                'creator' => $vehicleType->creator ? ['name' => $vehicleType->creator->name] : null,
                'updater' => $vehicleType->updater ? ['name' => $vehicleType->updater->name] : null,
            ],
            'vehicleStats' => $this->vehicleStats($vehicleType),
            'recentVehicles' => $this->recentVehicles($vehicleType),
            'auditLogs' => $this->auditHistory($vehicleType),
        ]);
    }

    private function vehicleStats(VehicleType $vehicleType): array
    {
        $vehicles = $vehicleType->vehicles();

        return [
            'total' => (clone $vehicles)->count(),
            'active' => (clone $vehicles)->where('status', Vehicle::STATUS_ACTIVE)->count(),
            'inactive' => (clone $vehicles)->where('status', Vehicle::STATUS_INACTIVE)->count(),
            'suspended' => (clone $vehicles)->where('status', Vehicle::STATUS_SUSPENDED)->count(),
        ];
    }

    private function recentVehicles(VehicleType $vehicleType): array
    {
        return $vehicleType->vehicles()
            ->select('id', 'company_id', 'plate_number', 'body_number', 'status')
            ->with('company:id,company_name')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'body_number' => $vehicle->body_number,
                'status' => $vehicle->status,
                'company_name' => $vehicle->company?->company_name,
            ])
            ->all();
    }

    private function auditHistory(VehicleType $vehicleType): array
    {
        // Actor is already surfaced via `user_name`, so the audit-trail
        // fields below are hidden from the diff to avoid redundant noise.
        $hiddenFields = ['created_by', 'updated_by', 'deleted_by'];

        return AuditLog::query()
            ->where('auditable_type', VehicleType::class)
            ->where('auditable_id', $vehicleType->id)
            ->with('user:id,name')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => Str::headline($log->action),
                'user_name' => $log->user?->name,
                'created_at_human' => $log->created_at?->diffForHumans(),
                'changes' => collect($log->changed_fields ?? [])
                    ->reject(fn ($value, $field) => in_array($field, $hiddenFields, true))
                    ->map(fn ($value, $field) => [
                        'field' => $field,
                        'label' => Str::headline((string) $field),
                        'old' => $this->formatAuditValue($field, $value['old'] ?? null),
                        'new' => $this->formatAuditValue($field, $value['new'] ?? null),
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    private function formatAuditValue(string $field, mixed $value): mixed
    {
        if ($field === 'picture_path') {
            return $value ? 'Picture updated' : null;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return $value;
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

        $this->vehicleTypeService->archiveVehicleType($vehicleType);

        return to_route('vehicle-types.index')->with('success', 'Vehicle type archived successfully.');
    }

    public function trash(Request $request)
    {
        Gate::authorize('viewTrash', VehicleType::class);

        $vehicleTypes = VehicleType::onlyTrashed()
            ->select('id', 'type_name', 'is_active', 'deleted_at', 'deleted_by')
            ->with('deleter:id,name')
            ->when($request->search, fn ($query, $search) => $query->where('type_name', 'like', "%{$search}%"))
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('VehicleType/Trash', [
            'vehicleTypes' => $vehicleTypes,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function restore(VehicleType $vehicleType): RedirectResponse
    {
        Gate::authorize('restore', $vehicleType);

        $this->vehicleTypeService->restoreVehicleType($vehicleType);

        return redirect()->back()->with('success', 'Vehicle type restored successfully.');
    }

    public function forceDelete(VehicleType $vehicleType): RedirectResponse
    {
        Gate::authorize('forceDelete', $vehicleType);

        $this->vehicleTypeService->forceDeleteVehicleType($vehicleType);

        return redirect()->back()->with('success', 'Vehicle type permanently deleted.');
    }
}
