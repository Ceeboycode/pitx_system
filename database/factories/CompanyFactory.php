<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $company = fake()->unique()->company();

        $words = preg_split('/[\s\-&,.]+/', $company);

        $initials = '';
        foreach ($words as $word) {
            $clean = preg_replace('/[^A-Za-z]/', '', $word);
            if ($clean !== '') {
                $initials .= strtoupper($clean[0]);
            }
        }

        $cleanName = strtoupper((string) preg_replace('/[^A-Za-z]/', '', $company));
        $base = substr($initials, 0, 3);

        if (strlen($base) < 3) {
            $base = substr($base . $cleanName, 0, 3);
        }

        if (strlen($base) < 3) {
            $base = str_pad($base, 3, 'X');
        }

        $code = $base;
        $i = 1;

        while (Company::query()->where('company_code', $code)->exists()) {
            $code = $base . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $i++;
        }

        return [
            'company_name' => $company,
            'logo' => null,
            'company_code' => $code,
            'company_email' => fake()->unique()->companyEmail(),
            'company_email_verified_at' => fake()->boolean(70) ? now() : null,
            'company_phone' => '09' . fake()->numerify('#########'),
            'company_address' => fake()->address(),
            'business_type' => fake()->randomElement(['corporate', 'sole_proprietorship']),
            'registration_number' => strtoupper(fake()->bothify('REG-####-???')),
            'authorized_representative_name' => fake()->name(),
            'authorized_representative_position' => fake()->jobTitle(),
            'authorized_representative_contact' => '09' . fake()->numerify('#########'),
            'status' => fake()->randomElement([
                'draft',
                'docs_completed',
                'for_verification',
                'verified',
                'needs_revision',
                'rejected',
            ]),
            'created_by' => User::query()->value('id'),
            'updated_by' => User::query()->value('id'),
            'deleted_by' => null,
            'deleted_at' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => 'verified',
            'company_email_verified_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => 'draft',
            'company_email_verified_at' => null,
        ]);
    }
}
