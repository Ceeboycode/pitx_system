<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Company;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
public function definition(): array
{
    $company = $this->faker->unique()->company();

    // Build initials from words (handles hyphenated names too)
    $words = preg_split('/[\s\-]+/', $company);

    $initials = '';
    foreach ($words as $word) {
        $clean = preg_replace('/[^A-Za-z]/', '', $word);
        if ($clean !== '') {
            $initials .= strtoupper($clean[0]);
        }
    }

    // Ensure at least 3 letters by falling back to first letters of the cleaned company name
    $cleanName = strtoupper(preg_replace('/[^A-Za-z]/', '', $company));
    $base = substr($initials, 0, 3);
    if (strlen($base) < 3) {
        $base = substr($base . $cleanName, 0, 3);
    }
    if (strlen($base) < 3) {
        $base = str_pad($base, 3, 'X');
    }

    // Make it unique in DB by appending a number if needed
    $code = $base;
    $i = 1;
    while (Company::where('company_code', $code)->exists()) {
        $code = $base . str_pad((string)$i, 2, '0', STR_PAD_LEFT); // e.g. ABC01
        $i++;
    }

    return [
        'company_name' => $company,
        'company_code' => $code,
        'created_by' => 1,
        'updated_by' => 1,
    ];
}
}
