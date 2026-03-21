<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        $name = fake()->name();

        return [
            'name' => $name,
            'username' => Str::lower(Str::slug($name)) . fake()->unique()->numberBetween(100, 999),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
            'phone_number' => '09' . fake()->numerify('#########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'must_change_password' => false,
            'remember_token' => Str::random(10),
            'company_id' => null,
        ];
    }

    public function internal(): static
    {
        return $this->state(fn () => [
            'company_id' => null,
            'status' => 'active',
        ]);
    }

    public function external(?int $companyId = null): static
    {
        return $this->state(fn () => [
            'company_id' => $companyId ?? Company::query()->inRandomOrder()->value('id'),
            'status' => 'active',
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn () => [
            'must_change_password' => true,
        ]);
    }
}
