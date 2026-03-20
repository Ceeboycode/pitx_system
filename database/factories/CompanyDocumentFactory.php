<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompanyDocument>
 */
class CompanyDocumentFactory extends Factory
{
    protected $model = CompanyDocument::class;

    public function definition(): array
    {
        $companyId = Company::query()->inRandomOrder()->value('id');

        $uploadedBy = User::query()
            ->where('company_id', $companyId)
            ->role('operator')
            ->value('id')
            ?? User::role(['admin', 'it', 'terminal manager'])->value('id')
            ?? User::query()->value('id');

        $verifiedBy = User::role(['admin', 'it', 'terminal manager'])->value('id')
            ?? User::query()->value('id');

        $issuedAt = fake()->dateTimeBetween('-1 year', '-1 month');
        $expiresAt = fake()->dateTimeBetween('+1 month', '+2 years');

        return [
            'company_id' => $companyId,
            'doc_type' => fake()->randomElement([
                'business_permit',
                'bir_certificate',
                'dti_sec_registration',
                'mayors_permit',
                'company_insurance',
            ]),
            'file_path' => 'company-documents/' . fake()->uuid() . '.pdf',
            'original_name' => fake()->slug() . '.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(100000, 3000000),
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
            'status' => fake()->randomElement(['pending', 'verified', 'invalid', 'expired']),
            'remarks' => fake()->optional()->sentence(),
            'uploaded_by' => $uploadedBy,
            'verified_by' => fake()->boolean(70) ? $verifiedBy : null,
            'verified_at' => fake()->boolean(70) ? now()->subDays(rand(1, 60)) : null,
        ];
    }
}
