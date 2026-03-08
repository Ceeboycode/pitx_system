<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CrmMessage;
use App\Models\CrmThread;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class CrmThreadSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->take(4)->get();

        if ($companies->isEmpty()) {
            $companies = Company::factory()->count(4)->create();
        }

        $companyUsers = $this->ensureCompanyUsers($companies);

        $internalUser = User::query()
            ->whereHas('roles', fn ($query) => $query->where('type', 'internal'))
            ->inRandomOrder()
            ->first()
            ?? User::query()->whereIn('username', ['superadmin', 'admin'])->first()
            ?? $companyUsers->first();

        CrmMessage::query()->delete();
        CrmThread::query()->delete();

        $subjects = [
            'Fare matrix clarification',
            'Terminal sticker compliance',
            'Dispatch schedule concern',
            'System login issue',
            'Permit upload follow-up',
            'Route assignment request',
        ];

        foreach ($companies as $companyIndex => $company) {
            $author = $companyUsers->firstWhere('company_id', $company->id) ?? $companyUsers->first();

            if (! $author) {
                continue;
            }

            $threadCount = fake()->numberBetween(2, 4);

            for ($i = 0; $i < $threadCount; $i++) {
                $isClosed = fake()->boolean(20);
                $category = fake()->randomElement(['compliance', 'system']);

                $thread = CrmThread::create([
                    'company_id' => $company->id,
                    'created_by_user_id' => $author->id,
                    'assigned_to_user_id' => $internalUser?->id,
                    'category' => $category,
                    'subject' => $subjects[array_rand($subjects)] . ' #' . ($companyIndex + 1) . '-' . ($i + 1),
                    'is_closed' => $isClosed,
                    'closed_at' => $isClosed ? now()->subDays(fake()->numberBetween(1, 10)) : null,
                    'details' => [
                        'priority' => fake()->randomElement(['low', 'medium', 'high']),
                        'channel' => 'portal',
                    ],
                ]);

                $messageCount = ($companyIndex === 0 && $i === 0)
                    ? 0
                    : fake()->numberBetween(2, 7);

                $lastMessageAt = null;

                for ($messageIndex = 0; $messageIndex < $messageCount; $messageIndex++) {
                    $fromInternal = $internalUser && $messageIndex % 2 === 1;
                    $sender = $fromInternal ? $internalUser : $author;

                    $message = CrmMessage::create([
                        'thread_id' => $thread->id,
                        'sender_user_id' => $sender->id,
                        'body' => fake()->paragraph(fake()->numberBetween(1, 3)),
                        'is_internal' => $fromInternal && fake()->boolean(35),
                    ]);

                    $lastMessageAt = $message->created_at;
                }

                if ($lastMessageAt) {
                    $thread->update([
                        'last_message_at' => $lastMessageAt,
                    ]);
                }
            }
        }
    }

    private function ensureCompanyUsers(Collection $companies): Collection
    {
        $users = collect();

        foreach ($companies as $company) {
            $user = User::query()->where('company_id', $company->id)->first();

            if (! $user) {
                $user = User::updateOrCreate(
                    ['username' => 'crm_company_' . $company->id],
                    [
                        'company_id' => $company->id,
                        'name' => 'CRM User ' . ($company->company_code ?? $company->id),
                        'email' => 'crm.company' . $company->id . '@example.test',
                        'phone_number' => null,
                        'email_verified_at' => now(),
                        'password' => Hash::make('password'),
                    ]
                );
            }

            if (! $user->hasAnyRole(['dispatcher', 'operator']) && $user->exists) {
                $user->assignRole('dispatcher');
            }

            $users->push($user);
        }

        return $users;
    }
}
