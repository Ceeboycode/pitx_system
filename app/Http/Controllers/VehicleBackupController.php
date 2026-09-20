<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VehicleBackupController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | EXPORT
    |
    | GET /vehicles/export
    |
    | Streams a CSV with human-readable vehicle data.
    |--------------------------------------------------------------------------
    */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Vehicle::class);

        $vehicles = Vehicle::with([
            'company:id,company_name',
            'route:id,route_name',
            'vehicleType:id,type_name',
        ])->orderBy('plate_number')->get();

        $filename = 'vehicles-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($vehicles) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens it correctly
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'ID', 'Plate Number', 'Body Number', 'Vehicle Type',
                'Make / Model', 'Capacity', 'Color',
                'Engine Number', 'Chassis Number',
                'Status', 'Verification Status', 'Company', 'Route',
                'Operator Remark', 'Suspension Remark', 'Verification Remark', 'Created At',
            ]);

            foreach ($vehicles as $v) {
                fputcsv($out, [
                    $v->id,
                    $v->plate_number ?? '',
                    $v->body_number ?? '',
                    $v->vehicleType?->type_name ?? '',
                    $v->make_model ?? '',
                    $v->capacity ?? '',
                    $v->color ?? '',
                    $v->engine_number ?? '',
                    $v->chassis_number ?? '',
                    $v->status ?? '',
                    $v->verification_status ?? '',
                    $v->company?->company_name ?? '',
                    $v->route?->route_name ?? '',
                    $v->operator_remark ?? '',
                    $v->suspension_remark ?? '',
                    $v->verification_remark ?? '',
                    $v->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A') ?? '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
