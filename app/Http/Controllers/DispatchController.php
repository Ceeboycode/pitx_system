<?php

namespace App\Http\Controllers;

// use App\Http\Requests\Dispatch\StoreDispatchRequest;
// use App\Http\Requests\Dispatch\UpdateDispatchRequest;

use App\Http\Requests\Dispatch\StoreDispatchRequest;
use App\Models\Company;
use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DispatchController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        abort_if(!$user->company_id, 403);

        $dispatches = Dispatch::query()
            ->with([
                'dispatcher:id,name',
                'vehicle:id,plate_number,route_id,vehicle_type_id',
                'vehicle.route:id,route_name',
                'vehicle.vehicleType:id,type_name',
            ])
            ->where('company_id', $user->company_id)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Dispatches/Index', [
            'dispatches' => $dispatches,
            'company' => [
                'id' => $user->company_id,
                'name' => $user->company?->company_name,
                'code' => $user->company?->company_code,
            ],
        ]);
    }

    public function create(Company $company)
    {
        $vehicles = Vehicle::query()
            ->where('company_id', $company->id)
            ->orderBy('plate_number')
            ->get(['id', 'plate_number', 'body_number']);

        return Inertia::render('Dispatches/Create', [
            'company' => [
                'id' => $company->id,
                'name' => $company->company_name,
                'code' => $company->company_code,
            ],
            'vehicles' => $vehicles,
        ]);
    }

    public function store(StoreDispatchRequest $request, Company $company)
    {
        $user = $request->user();

        // ✅ optional safety: only allow dispatchers to create for their own company
        // If admin can create for any company, remove this block.
        if ($user->company_id !== null && (int) $user->company_id !== (int) $company->id) {
            abort(403, 'You are not allowed to create dispatches for this company.');
        }

        $data = $request->validated();

        // ✅ Ensure vehicle belongs to same company
        $vehicle = Vehicle::query()
            ->whereKey($data['vehicle_id'])
            ->where('company_id', $company->id)
            ->firstOrFail();

        DB::transaction(function () use ($company, $user, $vehicle, $data) {
            Dispatch::create([
                'company_id'          => $company->id,
                'vehicle_id'          => $vehicle->id,

                // keep in sync with vehicle
                'plate_number'        => $vehicle->plate_number,

                'pax_count'           => $data['pax_count'],
                'bay_number'          => $data['bay_number'] ?? null,
                'remarks'             => $data['remarks'] ?? null,

                'dispatcher_user_id'  => $user->id,

                // ✅ your rule: created dispatch = arrived
                'arrived_at'          => now(),
                'status'              => 'arrived',

                'created_by'          => $user->id,
            ]);
        });

        return to_route('dispatches.index')
            ->with('success', 'Dispatch created successfully.');
    }
}
