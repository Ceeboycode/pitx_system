<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompanyDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $company = $user?->company()
            ->with([
                'documents:id,company_id,doc_type,status,issued_at,expires_at,created_at,updated_at',
            ])
            ->first();

        if (! $company) {
            return Inertia::render('RegistrationStatus', [
                'company' => null,
                'meta' => [
                    'title' => 'No Company Found',
                    'description' => 'Your account is missing a company record. Please register again.',
                    'icon' => 'warning',
                    'color' => 'destructive',
                ],
            ]);
        }

        $logoUrl = filled($company->logo)
            ? Storage::disk('public')->url($company->logo)
            : null;

        $dispatchesQuery = $company->dispatches();
        $vehiclesQuery = $company->vehicles();
        $documentsQuery = $company->documents();

        $totalDispatches = (clone $dispatchesQuery)->count();
        $pendingDispatches = (clone $dispatchesQuery)->where('status', 'pending')->count();
        $arrivedDispatches = (clone $dispatchesQuery)->where('status', 'arrived')->count();
        $departedDispatches = (clone $dispatchesQuery)->where('status', 'departed')->count();

        $totalDocuments = (clone $documentsQuery)->count();
        $verifiedDocuments = (clone $documentsQuery)->where('status', 'verified')->count();
        $pendingDocuments = (clone $documentsQuery)->where('status', 'pending')->count();
        $invalidDocuments = (clone $documentsQuery)->where('status', 'invalid')->count();
        $expiredDocuments = (clone $documentsQuery)->where('status', 'expired')->count();

        $totalVehicles = (clone $vehiclesQuery)->count();
        $activeVehicles = (clone $vehiclesQuery)->where('status', 'active')->count();
        $inactiveVehicles = (clone $vehiclesQuery)->where('status', 'inactive')->count();
        $forVerificationVehicles = (clone $vehiclesQuery)->where('status', 'for_verification')->count();

        $assignedVehicles = (clone $vehiclesQuery)->whereNotNull('route_id')->count();
        $unassignedVehicles = (clone $vehiclesQuery)->whereNull('route_id')->count();

        $routesBaseQuery = $company->vehicles()
            ->whereNotNull('route_id')
            ->with([
                'route:id,gate_id,route_name,origin_name,destination_name,status',
                'route.gate:id,gate_name',
            ]);

        $distinctRouteIds = (clone $routesBaseQuery)
            ->pluck('route_id')
            ->filter()
            ->unique()
            ->values();

        $totalRoutes = $distinctRouteIds->count();

        $activeRoutes = $company->vehicles()
            ->whereNotNull('route_id')
            ->whereHas('route', fn ($query) => $query->where('status', 'active'))
            ->distinct('route_id')
            ->count('route_id');

        $inactiveRoutes = $company->vehicles()
            ->whereNotNull('route_id')
            ->whereHas('route', fn ($query) => $query->where('status', 'inactive'))
            ->distinct('route_id')
            ->count('route_id');

        $routesWithVehicles = $company->vehicles()
            ->selectRaw('route_id, count(*) as vehicles_count')
            ->whereNotNull('route_id')
            ->groupBy('route_id')
            ->with([
                'route:id,gate_id,route_name,origin_name,destination_name,status',
                'route.gate:id,gate_name',
            ])
            ->orderByDesc('vehicles_count')
            ->take(5)
            ->get()
            ->map(function ($vehicleGroup) {
                $route = $vehicleGroup->route;

                return [
                    'route_id' => $vehicleGroup->route_id,
                    'route_name' => $route?->route_name,
                    'origin_name' => $route?->origin_name,
                    'destination_name' => $route?->destination_name,
                    'status' => $route?->status,
                    'gate_name' => $route?->gate?->gate_name,
                    'vehicles_count' => (int) $vehicleGroup->vehicles_count,
                ];
            })
            ->values();

        $recentDispatches = $company->dispatches()
            ->with([
                'vehicle:id,route_id,plate_number,body_number,vehicle_type',
                'vehicle.route:id,gate_id,route_name,origin_name,destination_name,status',
                'vehicle.route.gate:id,gate_name',
                'gate:id,gate_name',
            ])
            ->latest('dispatched_at')
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function ($dispatch) {
                $route = $dispatch->vehicle?->route;

                return [
                    'id' => $dispatch->id,
                    'plate_number' => $dispatch->plate_number,
                    'status' => $dispatch->status,
                    'bay_number' => $dispatch->bay_number,
                    'pax_count' => $dispatch->pax_count,
                    'remarks' => $dispatch->remarks,
                    'dispatched_at' => $dispatch->dispatched_at,
                    'arrived_at' => $dispatch->arrived_at,
                    'departed_at' => $dispatch->departed_at,
                    'vehicle' => $dispatch->vehicle ? [
                        'id' => $dispatch->vehicle->id,
                        'plate_number' => $dispatch->vehicle->plate_number,
                        'body_number' => $dispatch->vehicle->body_number,
                        'vehicle_type' => $dispatch->vehicle->vehicle_type,
                    ] : null,
                    'gate' => $dispatch->gate ? [
                        'id' => $dispatch->gate->id,
                        'gate_name' => $dispatch->gate->gate_name,
                    ] : null,
                    'route' => $route ? [
                        'id' => $route->id,
                        'route_name' => $route->route_name,
                        'origin_name' => $route->origin_name,
                        'destination_name' => $route->destination_name,
                        'status' => $route->status,
                        'gate_name' => $route->gate?->gate_name,
                    ] : null,
                ];
            })
            ->values();

        $recentDocuments = $company->documents()
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(fn ($document) => [
                'id' => $document->id,
                'doc_type' => $document->doc_type,
                'status' => $document->status,
                'issued_at' => $document->issued_at,
                'expires_at' => $document->expires_at,
                'updated_at' => $document->updated_at,
            ])
            ->values();

        $complianceRate = $totalDocuments > 0
            ? (int) round(($verifiedDocuments / $totalDocuments) * 100)
            : 0;

        $dispatchCompletionRate = $totalDispatches > 0
            ? (int) round(($arrivedDispatches / $totalDispatches) * 100)
            : 0;

        $fleetReadinessRate = $totalVehicles > 0
            ? (int) round(($activeVehicles / $totalVehicles) * 100)
            : 0;

        $routeCoverageRate = $totalVehicles > 0
            ? (int) round(($assignedVehicles / $totalVehicles) * 100)
            : 0;

        $attentionCount =
            $pendingDispatches +
            $pendingDocuments +
            $invalidDocuments +
            $expiredDocuments +
            $forVerificationVehicles +
            $unassignedVehicles;

        return Inertia::render('External/Dashboard', [
            'company' => [
                'id' => $company->id,
                'company_name' => $company->company_name,
                'company_code' => $company->company_code,
                'company_email' => $company->company_email,
                'company_phone' => $company->company_phone,
                'status' => $company->status,
                'business_type' => $company->business_type,
                'authorized_representative_name' => $company->authorized_representative_name,
                'logo_url' => $logoUrl,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'stats' => [
                'total_dispatches' => $totalDispatches,
                'pending_dispatches' => $pendingDispatches,
                'arrived_dispatches' => $arrivedDispatches,
                'departed_dispatches' => $departedDispatches,

                'total_documents' => $totalDocuments,
                'verified_documents' => $verifiedDocuments,
                'pending_documents' => $pendingDocuments,
                'invalid_documents' => $invalidDocuments,
                'expired_documents' => $expiredDocuments,

                'total_vehicles' => $totalVehicles,
                'active_vehicles' => $activeVehicles,
                'inactive_vehicles' => $inactiveVehicles,
                'for_verification_vehicles' => $forVerificationVehicles,
                'assigned_vehicles' => $assignedVehicles,
                'unassigned_vehicles' => $unassignedVehicles,

                'total_routes' => $totalRoutes,
                'active_routes' => $activeRoutes,
                'inactive_routes' => $inactiveRoutes,

                'compliance_rate' => $complianceRate,
                'dispatch_completion_rate' => $dispatchCompletionRate,
                'fleet_readiness_rate' => $fleetReadinessRate,
                'route_coverage_rate' => $routeCoverageRate,

                'attention_count' => $attentionCount,
            ],
            'recentDispatches' => $recentDispatches,
            'recentDocuments' => $recentDocuments,
            'topRoutes' => $routesWithVehicles,
        ]);
    }
}
