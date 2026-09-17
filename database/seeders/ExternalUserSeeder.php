<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ExternalUserSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->users() as $attributes) {
            $companyCode = $attributes['company_code'];
            $role = $attributes['role'];
            unset($attributes['company_code'], $attributes['role']);

            $companyId = $companyCode === null
                ? null
                : Company::query()->where('company_code', $companyCode)->value('id');

            if ($companyCode !== null && $companyId === null) {
                $this->command?->warn("Skipping {$attributes['username']}: company {$companyCode} was not seeded.");

                continue;
            }

            $user = User::withTrashed()->firstOrCreate(
                ['username' => $attributes['username']],
                [...$attributes, 'company_id' => $companyId],
            );

            if ($user->trashed()) {
                $user->restore();
            }

            $user->fill([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'phone_number' => $attributes['phone_number'],
                'status' => $attributes['status'],
                'email_verified_at' => $attributes['email_verified_at'],
                'must_change_password' => $attributes['must_change_password'],
                'company_id' => $companyId,
            ])->save();

            $user->syncRoles([$role]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function users(): array
    {
        return [
            $this->user('northstar-operator', 'Northstar Operator', 'northstar.operator@example.test', '+639170010001', 'NORTHSTAR', 'operator'),
            $this->user('northstar-dispatcher', 'Northstar Dispatcher', 'northstar.dispatcher@example.test', '+639170010002', 'NORTHSTAR', 'dispatcher'),
            $this->user('northstar-driver', 'Northstar Restricted Driver', 'northstar.driver@example.test', '+639170010003', 'NORTHSTAR', 'driver'),
            $this->user('pending-operator', 'Pending Company Operator', 'pending.operator@example.test', '+639170020001', 'PENDING', 'operator'),
            $this->user('incomplete-operator', 'Incomplete Company Operator', 'incomplete.operator@example.test', '+639170030001', 'INCOMPLETE', 'operator'),
            $this->user('issue-operator', 'Issue Company Operator', 'issue.operator@example.test', '+639170040001', 'ISSUE', 'operator'),
            $this->user('expired-operator', 'Expired Company Operator', 'expired.operator@example.test', '+639170050001', 'EXPIRED', 'operator'),
            $this->user('suspended-operator', 'Suspended Company Operator', 'suspended.operator@example.test', '+639170060001', 'SUSPENDED', 'operator'),
            $this->user('janrey_u', 'Development Commuter', 'commuter@example.test', '+639170090001', null, 'commuter'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function user(string $username, string $name, string $email, string $phone, ?string $companyCode, string $role): array
    {
        return [
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'phone_number' => $phone,
            'status' => 'active',
            'email_verified_at' => now()->subDay(),
            'password' => Hash::make('pitx@123'),
            'must_change_password' => false,
            'company_code' => $companyCode,
            'role' => $role,
        ];
    }
}
