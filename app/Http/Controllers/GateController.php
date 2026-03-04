<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Gate\GateService;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\Gate\GateStoreRequest;
use App\Http\Requests\Gate\GateUpdateRequest;
use App\Models\Gate as GateModel;
use Inertia\Inertia;

class GateController extends Controller
{
    public function __construct(
        private GateService $gateService
    ) {}

    public function index()
    {
        Gate::authorize('viewAny', GateModel::class);

        $gates = GateModel::select('id', 'gate_name', 'created_by')
            ->with('creator:id,name')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Gates/Index', [
            'gates' => $gates,
        ]);
    }

    public function show(GateModel $gate)
    {
        Gate::authorize('view', GateModel::class);

        return Inertia::render('Gates/Show', [
            'gate' => $gate->load(['creator', 'updater']),
        ]);
    }

    public function store(GateStoreRequest $request)
    {
        Gate::authorize('create', GateModel::class);

        $this->gateService->createGate(
            $request->validated(),
        );

        return to_route('gates.index')->with('success', 'Gate created successfully.');
    }

    public function update(GateUpdateRequest $request, GateModel $gate)
    {
        Gate::authorize('update', GateModel::class);

        $this->gateService->updateGate(
            $gate,
            $request->validated(),
        );

        return redirect()->back()->with('success', 'Gate updated successfully.');
    }

    public function trash()
    {
        Gate::authorize('viewTrash', GateModel::class);

        $gates = GateModel::onlyTrashed()
            ->select('id', 'gate_name', 'deleted_at')
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Gates/Trash', [
            'gates' => $gates,
        ]);
    }

    public function destroy(GateModel $gate)
    {
        Gate::authorize('delete', GateModel::class);

        $this->gateService->deleteGate($gate);

        return redirect()->back()->with('success', 'Gate deleted successfully.');
    }

    public function restore(int $id)
    {
        $gate = GateModel::onlyTrashed()->findOrFail($id);
        Gate::authorize('restore', GateModel::class);

        $this->gateService->restoreGate($gate);

        return redirect()->back()->with('success', 'Gate restored successfully.');
    }

    public function forceDelete(int $id)
    {
        $gate = GateModel::onlyTrashed()->findOrFail($id);
        Gate::authorize('forceDelete', GateModel::class);

        $this->gateService->forceDeleteGate($gate);

        return redirect()->back()->with('success', 'Gate permanently deleted successfully.');
    }

}
