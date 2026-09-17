<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentCompanySeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = User::query()->where('username', '2026-0002')->value('id');

        foreach ($this->companies() as $company) {
            Company::withTrashed()->updateOrCreate(
                ['company_code' => $company['company_code']],
                [...$company, 'created_by' => $creatorId, 'updated_by' => $creatorId],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function companies(): array
    {
        return [
            $this->company('NORTHSTAR', 'Northstar Provincial Transit Corporation', true),
            $this->company('PENDING', 'Pending Harbor Transport Cooperative', false),
            $this->company('INCOMPLETE', 'Incomplete Route Services', false),
            $this->company('ISSUE', 'Issue Resolution Transit Incorporated', false),
            $this->company('EXPIRED', 'Expired Permit Transport Cooperative', false),
            $this->company('SUSPENDED', 'Suspended Historical Transit Corporation', false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function company(string $code, string $name, bool $isActive): array
    {
        return [
            'company_name' => $name,
            'company_code' => $code,
            'company_email' => strtolower($code).'.operations@example.test',
            'company_phone' => '+639170000'.str_pad((string) (100 + strlen($code)), 3, '0', STR_PAD_LEFT),
            'company_address' => 'PITX Development Fixture, Parañaque City',
            'business_type' => 'corporate',
            'registration_number' => 'DEV-'.$code.'-2026',
            'authorized_representative_name' => 'Development Representative',
            'authorized_representative_position' => 'Operations Manager',
            'authorized_representative_contact' => '+639179999999',
            'company_email_verified_at' => now()->subDay(),
            'status' => Company::STATUS_DRAFT,
            'is_active' => $isActive,
        ];
    }
}
