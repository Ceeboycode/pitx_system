<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Gives every company that has a company code one operator, two drivers and two dispatchers.
 * Usernames follow the Employee module format (CODE-0001), the password is pitx@123, and the
 * accounts are verified and active. Re-running only tops up what a company is missing.
 */
class CompanyUserSeeder extends Seeder
{
    /**
     * @var array<string, array<int, string>>
     */
    private const ROLE_NAMES = [
        'operator' => ['Operator'],
        'driver' => ['Driver One', 'Driver Two'],
        'dispatcher' => ['Dispatcher One', 'Dispatcher Two'],
    ];

    public function run(): void
    {
        Company::query()->orderBy('id')->each(function (Company $company): void {
            if (blank($company->company_code)) {
                $this->command?->warn("Skipping {$company->company_name}: it has no company code.");

                return;
            }

            foreach (self::ROLE_NAMES as $role => $names) {
                $existing = User::query()
                    ->where('company_id', $company->id)
                    ->role($role)
                    ->get();

                $existing->each(fn (User $user) => $this->verifyAndActivate($user));

                foreach (array_slice($names, $existing->count()) as $name) {
                    $this->createUser($company, $role, $name);
                }
            }
        });
    }

    private function verifyAndActivate(User $user): void
    {
        $user->forceFill([
            'status' => 'active',
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
    }

    private function createUser(Company $company, string $role, string $name): void
    {
        $code = Str::upper($company->company_code);
        $username = $this->nextUsername($code);
        $number = (int) Str::afterLast($username, '-');

        $user = User::query()->create([
            'username' => $username,
            'name' => "{$company->company_name} {$name}",
            'email' => Str::lower($code).'.'.Str::slug($role).$number.'@example.test',
            'phone_number' => sprintf('09%04d%05d', $company->id % 10000, $number),
            'company_id' => $company->id,
            'status' => 'active',
            'email_verified_at' => now(),
            'password' => Hash::make('pitx@123'),
            'must_change_password' => false,
        ]);

        $user->assignRole($role);
    }

    /**
     * The next CODE-0001 style username. Usernames that don't end in digits (for example the hand-made
     * northstar-operator) are ignored so they can't skew the sequence.
     */
    private function nextUsername(string $code): string
    {
        $prefix = "{$code}-";

        $last = User::withTrashed()
            ->where('username', 'like', $prefix.'%')
            ->pluck('username')
            ->filter(fn (string $username) => str_starts_with($username, $prefix) && ctype_digit(substr($username, strlen($prefix))))
            ->map(fn (string $username) => (int) substr($username, strlen($prefix)))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }
}
