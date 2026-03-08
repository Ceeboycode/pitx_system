<?php

namespace App\Imports;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CompaniesImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function __construct(
        protected ?int $importedBy = null
    ) {}

    public array $summary = [
        'imported' => [],
        'skipped' => [],
        'errors' => [],
    ];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $companyCode = trim((string) ($row['company_code'] ?? ''));

            if ($companyCode === '') {
                $this->summary['errors'][] = 'Skipped one row: missing company_code.';
                continue;
            }

            if (Company::where('company_code', $companyCode)->withTrashed()->exists()) {
                $this->summary['skipped'][] = "{$companyCode} — already exists, skipped.";
                continue;
            }

            try {
                DB::transaction(function () use ($row, $companyCode) {
                    $company = Company::create([
                        'company_name' => $this->nullable($row['company_name'] ?? null),
                        'company_code' => $companyCode,
                        'company_email' => $this->nullable($row['company_email'] ?? null),
                        'company_phone' => $this->nullable($row['company_phone'] ?? null),
                        'company_address' => $this->nullable($row['company_address'] ?? null),
                        'business_type' => $this->nullable($row['business_type'] ?? null),
                        'registration_number' => $this->nullable($row['registration_number'] ?? null),
                        'authorized_representative_name' => $this->nullable($row['authorized_representative_name'] ?? null),
                        'authorized_representative_position' => $this->nullable($row['authorized_representative_position'] ?? null),
                        'authorized_representative_contact' => $this->nullable($row['authorized_representative_contact'] ?? null),
                        'status' => $this->nullable($row['status'] ?? null) ?? 'for_verification',
                        'created_by' => $this->importedBy,
                        'updated_by' => $this->importedBy,
                    ]);

                    $emailList = $this->explodePipe($row['user_emails'] ?? null);
                    $nameList = $this->explodePipe($row['user_names'] ?? null);
                    $usernameList = $this->explodePipe($row['user_usernames'] ?? null);
                    $phoneList = $this->explodePipe($row['user_phone_numbers'] ?? null);
                    $passwordHashList = $this->explodePipe($row['user_password_hash'] ?? null);

                    foreach ($emailList as $index => $email) {
                        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            continue;
                        }

                        if (User::where('email', $email)->exists()) {
                            continue;
                        }

                        $username = $usernameList[$index] ?? $this->nextUsernameForCode($companyCode);
                        $passwordHash = $passwordHashList[$index] ?? null;

                        $user = User::create([
                            'name' => $nameList[$index] ?? 'Imported User',
                            'email' => $email,
                            'username' => $username,
                            'phone_number' => $phoneList[$index] ?? null,
                            'company_id' => $company->id,
                            'password' => $passwordHash ?: Hash::make(Str::random(24)),
                        ]);

                        if (method_exists($user, 'assignRole')) {
                            $user->assignRole('dispatcher');
                        }
                    }

                    $this->summary['imported'][] = "{$company->company_code} — {$company->company_name}";
                });
            } catch (\Throwable $e) {
                $this->summary['errors'][] = "{$companyCode} — {$e->getMessage()}";
            }
        }
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_code' => ['required', 'string', 'max:50'],
            'company_email' => ['nullable', 'email'],
            'status' => ['nullable', 'string'],
        ];
    }

    private function explodePipe(mixed $value): array
    {
        $text = trim((string) $value);

        if ($text === '') {
            return [];
        }

        return collect(explode('|', $text))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function nullable(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function nextUsernameForCode(string $code): string
    {
        $last = DB::table('users')
            ->where('username', 'like', $code . '-%')
            ->orderByDesc('username')
            ->value('username');

        $next = 1;

        if (is_string($last) && preg_match('/-(\d{4})$/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return $code . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
