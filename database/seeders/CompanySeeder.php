<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::role(['admin', 'terminal manager'])->first()
            ?? User::query()->first();

        if (! $creator) {
            $this->command?->warn('No users found. Please seed users first before seeding companies.');
            return;
        }

        $companies = [
            [
                'company_name' => 'Northstar Provincial Transit Corporation',
                'company_code' => 'NOR',
                'company_email' => 'operations@nptc.example.test',
                'company_phone' => '+639178423156',
                'company_address' => "142 Governor's Drive, Barangay San Agustin, Trece Martires City, Cavite",
                'business_type' => 'corporate',
                'registration_number' => 'CS2024017842',
                'authorized_representative_name' => 'Marissa L. Villanueva',
                'authorized_representative_position' => 'Operations Manager',
                'authorized_representative_contact' => '+639183647291',
                'operator' => [
                    'username' => 'NOR-0001',
                    'name' => 'Marissa Villanueva',
                    'email' => 'marissa.villanueva@nptc.example.test',
                    'phone_number' => '+639183647291',
                ],
                'dispatchers' => [
                    [
                        'username' => 'NOR-0002',
                        'name' => 'Carlo M. Reyes',
                        'email' => 'carlo.reyes@nptc.example.test',
                        'phone_number' => '+639176382041',
                    ],
                    [
                        'username' => 'NOR-0003',
                        'name' => 'Angela P. Santos',
                        'email' => 'angela.santos@nptc.example.test',
                        'phone_number' => '+639187254913',
                    ],
                    [
                        'username' => 'NOR-0004',
                        'name' => 'Roberto L. de Guzman',
                        'email' => 'roberto.deguzman@nptc.example.test',
                        'phone_number' => '+639165907324',
                    ],
                ],
                'drivers' => [
                    [
                        'username' => 'NOR-0005',
                        'name' => 'Ernesto B. Flores',
                        'email' => 'ernesto.flores@nptc.example.test',
                        'phone_number' => '+639278401653',
                    ],
                    [
                        'username' => 'NOR-0006',
                        'name' => 'Jun M. Castillo',
                        'email' => 'jun.castillo@nptc.example.test',
                        'phone_number' => '+639359162740',
                    ],
                    [
                        'username' => 'NOR-0007',
                        'name' => 'Noel D. Ramos',
                        'email' => 'noel.ramos@nptc.example.test',
                        'phone_number' => '+639466308125',
                    ],
                ],
            ],
            [
                'company_name' => 'Southbay Commuter Transport',
                'company_code' => 'SOU',
                'company_email' => 'dispatch@sbct.example.test',
                'company_phone' => '+639175268043',
                'company_address' => '58 Aguinaldo Highway, Barangay San Agustin II, Dasmarinas City, Cavite',
                'business_type' => 'sole_proprietorship',
                'registration_number' => 'BN-2025-0938417',
                'authorized_representative_name' => 'Daniel R. Mendoza',
                'authorized_representative_position' => 'Owner',
                'authorized_representative_contact' => '+639194752186',
                'operator' => [
                    'username' => 'SOU-0001',
                    'name' => 'Daniel Mendoza',
                    'email' => 'daniel.mendoza@sbct.example.test',
                    'phone_number' => '+639194752186',
                ],
                'dispatchers' => [
                    [
                        'username' => 'SOU-0002',
                        'name' => 'Paulo R. Navarro',
                        'email' => 'paulo.navarro@sbct.example.test',
                        'phone_number' => '+639193846275',
                    ],
                    [
                        'username' => 'SOU-0003',
                        'name' => 'Michelle A. Cruz',
                        'email' => 'michelle.cruz@sbct.example.test',
                        'phone_number' => '+639172538064',
                    ],
                    [
                        'username' => 'SOU-0004',
                        'name' => 'Adrian L. Bautista',
                        'email' => 'adrian.bautista@sbct.example.test',
                        'phone_number' => '+639285174306',
                    ],
                ],
                'drivers' => [
                    [
                        'username' => 'SOU-0005',
                        'name' => 'Ramon G. Dela Cruz',
                        'email' => 'ramon.delacruz@sbct.example.test',
                        'phone_number' => '+639376420518',
                    ],
                    [
                        'username' => 'SOU-0006',
                        'name' => 'Joel P. Aquino',
                        'email' => 'joel.aquino@sbct.example.test',
                        'phone_number' => '+639487250631',
                    ],
                    [
                        'username' => 'SOU-0007',
                        'name' => 'Mark S. Villareal',
                        'email' => 'mark.villareal@sbct.example.test',
                        'phone_number' => '+639598136247',
                    ],
                ],
            ],
        ];

        foreach ($companies as $data) {
            $employeesByRole = [
                'operator' => [$data['operator']],
                'dispatcher' => $data['dispatchers'],
                'driver' => $data['drivers'],
            ];
            unset($data['operator'], $data['dispatchers'], $data['drivers']);

            $company = Company::query()->updateOrCreate(
                ['company_code' => $data['company_code']],
                [
                    ...$data,
                    'company_email_verified_at' => now(),
                    'status' => Company::STATUS_VERIFIED,
                    'created_by' => $creator->id,
                    'updated_by' => $creator->id,
                ],
            );

            foreach ($employeesByRole as $role => $employees) {
                foreach ($employees as $employeeData) {
                    $employee = User::query()->updateOrCreate(
                        ['username' => $employeeData['username']],
                        [
                            ...$employeeData,
                            'company_id' => $company->id,
                            'status' => 'active',
                            'email_verified_at' => now(),
                            'password' => Hash::make('admin123'),
                            'must_change_password' => false,
                        ],
                    );

                    $employee->syncRoles([$role]);
                }
            }
        }
    }
}
