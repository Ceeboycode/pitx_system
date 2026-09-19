<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->internalUsers() as $attributes) {
            $role = $attributes['role'];
            unset($attributes['role']);

            $user = User::withTrashed()->firstOrCreate(
                ['username' => $attributes['username']],
                $attributes,
            );

            if ($user->trashed()) {
                $user->restore();
            }

            $this->fillMissingProfileAttributes($user, $attributes);

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function internalUsers(): array
    {
        return [
            ['username' => '2026-0001', 'name' => 'Cedric Heyrosa', 'email' => 'cedric_heyrosa@gmail.com', 'phone_number' => '+639226789012', 'status' => 'active', 'email_verified_at' => now(), 'password' => Hash::make('admin123'), 'must_change_password' => false, 'company_id' => null, 'role' => 'super-admin'],
            ['username' => '2026-0002', 'name' => 'Pat Vicuna', 'email' => 'pat_vicuna@gmail.com', 'phone_number' => '+639237890123', 'status' => 'active', 'email_verified_at' => now(), 'password' => Hash::make('admin123'), 'must_change_password' => false, 'company_id' => null, 'role' => 'admin'],
            ['username' => '2026-0003', 'name' => 'Terminal Manager 1', 'email' => 'terminalmanager1@gmail.com', 'phone_number' => '+639171234567', 'status' => 'active', 'email_verified_at' => now(), 'password' => Hash::make('admin123'), 'must_change_password' => false, 'company_id' => null, 'role' => 'terminal manager'],
            ['username' => '2026-0004', 'name' => 'Terminal Manager 2', 'email' => 'terminalmanager2@gmail.com', 'phone_number' => '+639182345678', 'status' => 'active', 'email_verified_at' => now(), 'password' => Hash::make('admin123'), 'must_change_password' => false, 'company_id' => null, 'role' => 'terminal manager'],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function fillMissingProfileAttributes(User $user, array $attributes): void
    {
        $missing = collect($attributes)
            ->except(['username', 'password', 'company_id'])
            ->filter(fn (mixed $value, string $attribute): bool => blank($user->getAttribute($attribute)) && filled($value))
            ->all();

        if ($missing !== []) {
            $user->fill($missing)->save();
        }
    }
}
