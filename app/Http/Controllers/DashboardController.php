<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Dispatch;
use App\Models\Gate;
use App\Models\Route;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $today = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $next30Days = now()->addDays(30)->endOfDay();

        $totalCompanies = Company::count();
        $verifiedCompanies = Company::where('status', 'verified')->count();
        $pendingCompanies = Company::whereIn('status', [
            'draft',
            'docs_completed',
            'for_verification',
            'needs_revision',
        ])->count();

        $totalVehicles = Vehicle::count();
        $activeVehicles = Vehicle::where('status', 'active')->count();

        $totalRoutes = Route::count();
        $activeRoutes = Route::where('status', 'active')->count();

        $totalGates = Gate::count();
        $activeGates = Gate::where('status', 'active')->count();

        $dispatchesToday = Dispatch::whereBetween('created_at', [$today, $todayEnd])->count();
        $arrivedToday = Dispatch::whereBetween('arrived_at', [$today, $todayEnd])->count();
        $departedToday = Dispatch::whereBetween('departed_at', [$today, $todayEnd])->count();
        $dispatchedToday = Dispatch::whereBetween('dispatched_at', [$today, $todayEnd])->count();
        $totalPaxToday = (int) Dispatch::whereBetween('created_at', [$today, $todayEnd])->sum('pax_count');

        $expiringDocumentsCount = VehicleDocument::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', $today->toDateString())
            ->whereDate('expires_at', '<=', $next30Days->toDateString())
            ->count();

        $stats = [
            'total_companies' => $totalCompanies,
            'verified_companies' => $verifiedCompanies,
            'pending_companies' => $pendingCompanies,
            'total_vehicles' => $totalVehicles,
            'active_vehicles' => $activeVehicles,
            'total_routes' => $totalRoutes,
            'active_routes' => $activeRoutes,
            'total_gates' => $totalGates,
            'active_gates' => $activeGates,
            'dispatches_today' => $dispatchesToday,
            'arrived_today' => $arrivedToday,
            'departed_today' => $departedToday,
            'dispatched_today' => $dispatchedToday,
            'total_pax_today' => $totalPaxToday,
            'expiring_documents_count' => $expiringDocumentsCount,
        ];

        $companyStatus = Company::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'total' => (int) $row->total,
            ])
            ->values();

        $dispatchStatus = Dispatch::query()
            ->whereBetween('created_at', [$today, $todayEnd])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'total' => (int) $row->total,
            ])
            ->values();

        $gateActivity = Gate::query()
            ->withCount([
                'dispatches as dispatches_today_count' => fn ($query) => $query->whereBetween('created_at', [$today, $todayEnd]),
            ])
            ->orderBy('gate_name')
            ->get()
            ->map(fn ($gate) => [
                'id' => $gate->id,
                'gate_name' => $gate->gate_name,
                'status' => $gate->status,
                'bays' => (int) $gate->bays,
                'dispatches_today_count' => (int) $gate->dispatches_today_count,
            ])
            ->values();

        $topCompanies = Company::query()
            ->withCount([
                'dispatches as dispatches_today_count' => fn ($query) => $query->whereBetween('created_at', [$today, $todayEnd]),
                'vehicles',
            ])
            ->orderByDesc('dispatches_today_count')
            ->orderBy('company_name')
            ->limit(5)
            ->get()
            ->map(fn ($company) => [
                'id' => $company->id,
                'company_name' => $company->company_name,
                'company_code' => $company->company_code,
                'status' => $company->status,
                'vehicles_count' => (int) $company->vehicles_count,
                'dispatches_today_count' => (int) $company->dispatches_today_count,
            ])
            ->values();

        $recentDispatches = Dispatch::query()
            ->with([
                'company:id,company_name,company_code',
                'vehicle:id,plate_number,vehicle_type,route_id',
                'vehicle.route:id,route_name',
                'gate:id,gate_name',
            ])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($dispatch) => [
                'id' => $dispatch->id,
                'company' => $dispatch->company ? [
                    'name' => $dispatch->company->company_name,
                    'code' => $dispatch->company->company_code,
                ] : null,
                'vehicle' => $dispatch->vehicle ? [
                    'plate_number' => $dispatch->vehicle->plate_number,
                    'vehicle_type' => $dispatch->vehicle->vehicle_type,
                ] : null,
                'route' => $dispatch->vehicle?->route?->route_name,
                'gate' => $dispatch->gate?->gate_name,
                'bay_number' => $dispatch->bay_number,
                'pax_count' => (int) $dispatch->pax_count,
                'status' => $dispatch->status,
                'arrived_at' => optional($dispatch->arrived_at)?->format('M d, Y h:i A'),
                'departed_at' => optional($dispatch->departed_at)?->format('M d, Y h:i A'),
                'dispatched_at' => optional($dispatch->dispatched_at)?->format('M d, Y h:i A'),
                'created_at' => optional($dispatch->created_at)?->format('M d, Y h:i A'),
            ])
            ->values();

        $expiringVehicleDocuments = VehicleDocument::query()
            ->with([
                'vehicle:id,company_id,plate_number,vehicle_type',
                'vehicle.company:id,company_name,company_code',
            ])
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', $today->toDateString())
            ->whereDate('expires_at', '<=', $next30Days->toDateString())
            ->orderBy('expires_at')
            ->limit(8)
            ->get()
            ->map(function ($document) {
                $expiresAt = Carbon::parse($document->expires_at);
                $daysLeft = max(0, now()->startOfDay()->diffInDays($expiresAt, false));

                return [
                    'id' => $document->id,
                    'document_type' => $document->document_type,
                    'status' => $document->status,
                    'expires_at' => $expiresAt->format('M d, Y'),
                    'days_left' => $daysLeft,
                    'vehicle' => $document->vehicle ? [
                        'plate_number' => $document->vehicle->plate_number,
                        'vehicle_type' => $document->vehicle->vehicle_type,
                    ] : null,
                    'company' => $document->vehicle?->company ? [
                        'name' => $document->vehicle->company->company_name,
                        'code' => $document->vehicle->company->company_code,
                    ] : null,
                ];
            })
            ->values();

        $summaryReport = [
            'system_health' => [
                'label' => $activeRoutes === $totalRoutes && $activeGates === $totalGates ? 'Stable' : 'Needs Attention',
                'description' => $activeRoutes === $totalRoutes && $activeGates === $totalGates
                    ? 'All routes and gates are currently active.'
                    : 'Some routes or gates are inactive and may affect operations.',
            ],
            'compliance' => [
                'label' => $expiringDocumentsCount > 0 ? 'Watchlist Active' : 'Healthy',
                'description' => $expiringDocumentsCount > 0
                    ? "{$expiringDocumentsCount} vehicle document(s) will expire within 30 days."
                    : 'No vehicle documents are nearing expiry in the next 30 days.',
            ],
            'operations' => [
                'label' => $dispatchesToday > 0 ? 'Running' : 'No Activity',
                'description' => $dispatchesToday > 0
                    ? "{$dispatchesToday} dispatch record(s) logged today with {$totalPaxToday} passenger(s)."
                    : 'No dispatch activity has been recorded today yet.',
            ],
            'companies' => [
                'label' => $totalCompanies > 0 ? 'Tracked' : 'Empty',
                'description' => "{$verifiedCompanies} of {$totalCompanies} companies are verified.",
            ],
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'companyStatus' => $companyStatus,
            'dispatchStatus' => $dispatchStatus,
            'gateActivity' => $gateActivity,
            'topCompanies' => $topCompanies,
            'recentDispatches' => $recentDispatches,
            'expiringVehicleDocuments' => $expiringVehicleDocuments,
            'summaryReport' => $summaryReport,
        ]);
    }
}
