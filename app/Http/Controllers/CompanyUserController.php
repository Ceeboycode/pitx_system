<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CompanyUserController extends Controller
{
    // ── Shared: fetch all external roles ──────────────────────────────────────
    private function getExternalRoles(): \Illuminate\Support\Collection
    {
        return Role::query()
            ->where('type', 'external')
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['name', 'guard_name', 'type']);
    }

    // ── Index ──────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        Gate::authorize('external_users.viewAny');

        $company = $request->user()->company;

        abort_if(! $company, 403, 'No company assigned.');

        $search = $request->input('search');
        $role   = $request->input('role');
        $status = $request->input('status');

        $externalRoleNames = $this->getExternalRoles()->pluck('name')->toArray();

        $users = User::query()
            ->with(['roles', 'company'])
            ->where('company_id', $company->id)
            ->whereHas('roles', function ($query) use ($externalRoleNames) {
                $query->whereIn('name', $externalRoleNames);
            })
            ->when($role, function ($query) use ($role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->search($search)
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(function (User $employeeUser) {
                return [
                    'id'           => $employeeUser->id,
                    'username'     => $employeeUser->username,
                    'name'         => $employeeUser->name,
                    'email'        => $employeeUser->email,
                    'phone_number' => $employeeUser->phone_number,
                    'status'       => $employeeUser->status,
                    'created_at'   => $employeeUser->created_at,
                    'avatar'       => $employeeUser->profile_photo_path
                        ? Storage::url($employeeUser->profile_photo_path)
                        : null,
                    'roles' => $employeeUser->roles->map(fn ($role) => [
                        'id'   => $role->id,
                        'name' => $role->name,
                    ])->values(),
                ];
            });

        return Inertia::render('External/Employee/Index', [
            'company'  => $company,
            'user'     => $request->user(),
            'users'    => $users,
            'filters'  => [
                'search' => $search,
                'role'   => $role,
                'status' => $status,
            ],
            'roles'    => $this->getExternalRoles()->values(),
            'statuses' => ['active', 'inactive', 'suspended'],
        ]);
    }

    // ── Create ─────────────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        Gate::authorize('external_users.create');

        $company = $request->user()->company;

        abort_if(! $company, 403, 'No company assigned.');

        return Inertia::render('External/Employee/Create', [
            'company'             => $company,
            'user'                => $request->user(),
            'roles'               => $this->getExternalRoles()->values(),
            // FIX: default status is now 'active' — removed 'pending'
            'defaultStatus'       => 'active',
            'nextUsernamePreview' => $this->generateNextUsername($company->company_code),
        ]);
    }

    // ── Store ──────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        Gate::authorize('external_users.create');

        $company = $request->user()->company;

        abort_if(! $company, 403, 'No company assigned.');

        $externalRoleNames = $this->getExternalRoles()->pluck('name')->toArray();

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255', "regex:/^[A-Za-z][A-Za-z\s.'-]*$/"],
            'email'        => ['required', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')],
            'phone_number' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-()\s]{7,20}$/', Rule::unique('users', 'phone_number')],
            'role'         => ['required', 'string', Rule::in($externalRoleNames)],
        ], [
            'name.regex' => 'Name may only contain letters, spaces, apostrophes, periods, and hyphens.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address (e.g., name@example.com).',
            'email.unique' => 'This email is already in use.',
            'phone_number.required' => 'Phone number is required.',
            'phone_number.regex' => 'Phone number must be a valid number (digits, spaces, +, -, and parentheses only).',
            'phone_number.unique' => 'This phone number is already in use.',
            'role.in' => 'Please select a valid external role.',
        ]);

        $user = DB::transaction(function () use ($company, $validated) {
            $username = $this->generateNextUsername($company->company_code, true);

            $user = User::create([
                'username'             => $username,
                'name'                 => $validated['name'],
                'email'                => $validated['email'],
                'phone_number'         => $validated['phone_number'],
                'company_id'           => $company->id,
                // FIX: create as 'active', not 'pending'
                'status'               => 'active',
                'password'             => Hash::make('pitx@123'),
                'must_change_password' => true,
            ]);

            $user->assignRole($validated['role']);

            return $user;
        });

        return redirect()
            ->route('employee-users.index')
            ->with('success', "Employee created successfully. Username: {$user->username}");
    }

    // ── Show ───────────────────────────────────────────────────────────────────

    public function show(Request $request, User $employeeUser)
    {
        Gate::authorize('external_users.view');

        $this->ensureCompanyUser($request, $employeeUser);

        $employeeUser->load(['roles', 'company']);

        return Inertia::render('External/Employee/Show', [
            'company'  => $request->user()->company,
            'user'     => $request->user(),
            'employee' => [
                'id'           => $employeeUser->id,
                'username'     => $employeeUser->username,
                'name'         => $employeeUser->name,
                'email'        => $employeeUser->email,
                'phone_number' => $employeeUser->phone_number,
                'status'       => $employeeUser->status,
                'created_at'   => $employeeUser->created_at,
                'avatar'       => $employeeUser->profile_photo_path
                    ? Storage::url($employeeUser->profile_photo_path)
                    : null,
                'roles' => $employeeUser->roles->map(fn ($role) => [
                    'id'   => $role->id,
                    'name' => $role->name,
                ])->values(),
                'company' => $employeeUser->company,
            ],
        ]);
    }

    // ── Edit ───────────────────────────────────────────────────────────────────

    public function edit(Request $request, User $employeeUser)
    {
        Gate::authorize('external_users.update');

        $this->ensureCompanyUser($request, $employeeUser);
        $this->ensureNotActingOnSelf($request, $employeeUser);

        $employeeUser->load(['roles', 'company']);

        return Inertia::render('External/Employee/Edit', [
            'company'      => $request->user()->company,
            'user'         => $request->user(),
            'employee'     => [
                'id'           => $employeeUser->id,
                'username'     => $employeeUser->username,
                'name'         => $employeeUser->name,
                'email'        => $employeeUser->email,
                'phone_number' => $employeeUser->phone_number,
                'status'       => $employeeUser->status,
                'created_at'   => $employeeUser->created_at,
                'avatar'       => $employeeUser->profile_photo_path
                    ? Storage::url($employeeUser->profile_photo_path)
                    : null,
                'roles' => $employeeUser->roles->map(fn ($role) => [
                    'id'   => $role->id,
                    'name' => $role->name,
                ])->values(),
                'company' => $employeeUser->company,
            ],
            'roles'        => $this->getExternalRoles()->values(),
            // FIX: statuses on edit do not include 'pending' — only real operational states
            'statuses'     => ['active', 'inactive', 'suspended'],
            // selectedRole is the current role name string (or null if none)
            'selectedRole' => $employeeUser->getRoleNames()->first() ?? null,
        ]);
    }

    // ── Update ─────────────────────────────────────────────────────────────────

    public function update(Request $request, User $employeeUser)
    {
        Gate::authorize('external_users.update');

        $this->ensureCompanyUser($request, $employeeUser);
        $this->ensureNotActingOnSelf($request, $employeeUser);

        $externalRoleNames = $this->getExternalRoles()->pluck('name')->toArray();

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255', "regex:/^[A-Za-z][A-Za-z\s.'-]*$/"],
            'email'        => ['required', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')->ignore($employeeUser->id)],
            'phone_number' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-()\s]{7,20}$/', Rule::unique('users', 'phone_number')->ignore($employeeUser->id)],
            'role'         => ['required', 'string', Rule::in($externalRoleNames)],
        ], [
            'name.regex' => 'Name may only contain letters, spaces, apostrophes, periods, and hyphens.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address (e.g., name@example.com).',
            'email.unique' => 'This email is already in use.',
            'phone_number.required' => 'Phone number is required.',
            'phone_number.regex' => 'Phone number must be a valid number (digits, spaces, +, -, and parentheses only).',
            'phone_number.unique' => 'This phone number is already in use.',
            'role.in' => 'Please select a valid external role.',
        ]);

        $employeeUser->update([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'phone_number' => $validated['phone_number'],
        ]);

        $employeeUser->syncRoles([$validated['role']]);

        return redirect()
            ->route('employee-users.index')
            ->with('success', 'Employee updated successfully.');
    }

    // ── Toggle Status ──────────────────────────────────────────────────────────

    public function toggleStatus(Request $request, User $employeeUser)
    {
        Gate::authorize('external_users.toggleStatus');

        $this->ensureCompanyUser($request, $employeeUser);
        $this->ensureNotActingOnSelf($request, $employeeUser);

        $nextStatus = match ($employeeUser->status) {
            'active'    => 'inactive',
            'inactive'  => 'active',
            'pending'   => 'active',   // legacy — migrate any pending users to active
            'suspended' => 'active',
            default     => 'active',
        };

        $employeeUser->update(['status' => $nextStatus]);

        return back()->with('success', 'Employee status updated successfully.');
    }

    // ── Reset Password ─────────────────────────────────────────────────────────

    public function resetPassword(Request $request, User $employeeUser)
    {
        Gate::authorize('external_users.resetPassword');

        $this->ensureCompanyUser($request, $employeeUser);
        $this->ensureNotActingOnSelf($request, $employeeUser);

        $employeeUser->update([
            'password'             => Hash::make('pitx@123'),
            'must_change_password' => true,
        ]);

        return back()->with('success', 'Password reset to pitx@123.');
    }

    // ── Destroy ────────────────────────────────────────────────────────────────

    public function destroy(Request $request, User $employeeUser)
    {
        Gate::authorize('external_users.archive');

        $this->ensureCompanyUser($request, $employeeUser);
        $this->ensureNotActingOnSelf($request, $employeeUser);

        if ($employeeUser->profile_photo_path) {
            Storage::disk('public')->delete($employeeUser->profile_photo_path);
        }

        $employeeUser->delete();

        return back()->with('success', 'Employee deleted successfully.');
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function ensureCompanyUser(Request $request, User $user): void
    {
        $company = $request->user()->company;

        abort_if(! $company, 403, 'No company assigned.');

        abort_unless(
            $user->company_id === $company->id,
            403,
            'Unauthorized access.'
        );

        $externalRoleNames = $this->getExternalRoles()->pluck('name')->toArray();

        abort_unless(
            $user->hasAnyRole($externalRoleNames),
            403,
            'This user does not have an external role.'
        );
    }

    private function ensureNotActingOnSelf(Request $request, User $user): void
    {
        abort_if(
            $request->user()->id === $user->id,
            403,
            'You cannot perform this action on your own account in Employee module.'
        );
    }

    private function generateNextUsername(?string $companyCode, bool $lock = false): string
    {
        $prefix = Str::upper($companyCode ?: 'COMP') . '-';

        $query = User::query()->where('username', 'like', $prefix . '%');

        if ($lock) {
            $query->lockForUpdate();
        }

        $lastUsername = $query
            ->orderByDesc('username')
            ->value('username');

        $nextNumber = 1;

        if ($lastUsername) {
            $lastDigits = substr($lastUsername, strlen($prefix));
            $nextNumber = ((int) $lastDigits) + 1;
        }

        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
