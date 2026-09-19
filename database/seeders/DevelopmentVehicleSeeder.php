<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class DevelopmentVehicleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->vehicles() as $scenario) {
            $company = Company::query()->where('company_code', $scenario['company_code'])->first();
            $routeId = Route::query()->where('route_name', $scenario['route_name'])->value('id');
            $typeId = VehicleType::query()->where('type_name', $scenario['vehicle_type'])->value('id');
            $creatorId = User::query()->where('username', $scenario['owner_username'])->value('id');

            if ($company === null || $routeId === null || $typeId === null || $creatorId === null) {
                continue;
            }

            Vehicle::withTrashed()->updateOrCreate(
                ['plate_number' => $scenario['plate_number']],
                [
                    'company_id' => $company->id,
                    'route_id' => $routeId,
                    'vehicle_type_id' => $typeId,
                    'body_number' => $scenario['body_number'],
                    'capacity' => $scenario['capacity'],
                    'color' => 'Development Blue',
                    'engine_number' => 'DEV-ENG-'.$scenario['plate_number'],
                    'chassis_number' => 'DEV-CHS-'.$scenario['plate_number'],
                    'make_model' => $scenario['make_model'],
                    'status' => $scenario['status'],
                    'verification_status' => $scenario['verification_status'],
                    'verification_remark' => $scenario['verification_remark'],
                    'operator_remark' => $scenario['operator_remark'],
                    'suspension_remark' => $scenario['suspension_remark'],
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                ],
            );
        }
    }

    /**
     * @return array<int, array<string, int|string|null>>
     */
    private function vehicles(): array
    {
        return [
            $this->vehicle('DEV-NOR-100', 'NORTHSTAR', 'Development PITX to Trece Martires', 'Coach Bus', 'northstar-operator', 'active', 'draft'),
            $this->vehicle('DEV-PEN-100', 'PENDING', 'Development PITX to Dasmariñas', 'Mini Bus', 'pending-operator', 'inactive', 'draft'),
            $this->vehicle('DEV-ISS-100', 'ISSUE', 'Development PITX to Trece Martires', 'Modern PUV', 'issue-operator', 'inactive', 'draft'),
            $this->vehicle('DEV-EXP-100', 'EXPIRED', 'Development PITX to Dasmariñas', 'UV Express', 'expired-operator', 'inactive', 'draft'),
            $this->vehicle('DEV-SUS-100', 'SUSPENDED', 'Development Historical Route', 'Historic Coach', 'suspended-operator', 'inactive', 'draft', 'Historical vehicle retained for reporting.'),
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function vehicle(string $plate, string $company, string $route, string $type, string $owner, string $status, string $verificationStatus, ?string $operatorRemark = null): array
    {
        return [
            'plate_number' => $plate,
            'company_code' => $company,
            'route_name' => $route,
            'vehicle_type' => $type,
            'owner_username' => $owner,
            'body_number' => str_replace('DEV-', 'BODY-', $plate),
            'capacity' => 45,
            'make_model' => 'Development Transit Coach',
            'status' => $status,
            'verification_status' => $verificationStatus,
            'verification_remark' => null,
            'operator_remark' => $operatorRemark,
            'suspension_remark' => null,
        ];
    }
}
