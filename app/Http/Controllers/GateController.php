<?php

namespace App\Http\Controllers;

use App\Http\Requests\Gate\GateStoreRequest;
use App\Http\Requests\Gate\GateUpdateRequest;
use App\Models\Gate as GateModel;
use App\Notifications\External\GateStatusChangedNotification;
use App\Services\Gate\GateService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
            ->select('id', 'gate_name', 'status', 'bays', 'created_by')
            ->with('creator:id,name')
            ->when($request->search, fn ($q, $s) => $q->where('gate_name', 'like', "%{$s}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Gates/Index', [
            'gates'   => $gates,
            'filters' => $request->only('search'),
        ]);
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

        $this->gateService->createGate($request->validated());

        return to_route('gates.index')->with('success', 'Gate created successfully.');
    }

    public function update(GateUpdateRequest $request, GateModel $gate)
    {
        Gate::authorize('update', $gate);

        $oldStatus = (string) $gate->status;

        $this->gateService->updateGate($gate, $request->validated());

        $gate->refresh();

        if ($oldStatus !== (string) $gate->status) {
            $notification = new GateStatusChangedNotification($gate, (string) $gate->status);

            $this->notificationService->notifyAffectedCompaniesByGate($gate, $notification);
        }

        return redirect()->back()->with('success', 'Gate updated successfully.');
    }

    public function trash(Request $request)
    {
        Gate::authorize('viewTrash', GateModel::class);

        $gates = GateModel::onlyTrashed()
            ->select('id', 'gate_name', 'status', 'bays', 'deleted_at')
            ->when($request->search, fn ($q, $s) => $q->where('gate_name', 'like', "%{$s}%"))
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Gates/Trash', [
            'gates'   => $gates,
            'filters' => $request->only('search'),
        ]);
    }

    public function destroy(GateModel $gate)
    {
        Gate::authorize('delete', $gate);

        $this->gateService->deleteGate($gate);

        return redirect()->back()->with('success', 'Gate archived successfully.');
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
