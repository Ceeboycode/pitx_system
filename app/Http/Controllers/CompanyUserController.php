<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            ->withQueryString();

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
        $company = $request->user()->company;

        abort_if(! $company, 403, 'No company assigned.');

        $externalRoleNames = $this->getExternalRoles()->pluck('name')->toArray();

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'role'         => ['required', 'string', 'in:' . implode(',', $externalRoleNames)],
        ]);

        $user = DB::transaction(function () use ($company, $validated) {
            $username = $this->generateNextUsername($company->company_code, true);

            $user = User::create([
                'username'             => $username,
                'name'                 => $validated['name'],
                'email'                => $validated['email'] ?? null,
                'phone_number'         => $validated['phone_number'] ?? null,
                'company_id'           => $company->id,
                // FIX: create as 'active', not 'pending'
                'status'               => 'active',
                'password'             => 'pitx@123',
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
        $this->ensureCompanyUser($request, $employeeUser);

        $employeeUser->load(['roles', 'company']);

        return Inertia::render('External/Employee/Show', [
            'company'  => $request->user()->company,
            'user'     => $request->user(),
            'employee' => $employeeUser,
        ]);
    }

    // ── Edit ───────────────────────────────────────────────────────────────────

    public function edit(Request $request, User $employeeUser)
    {
        $this->ensureCompanyUser($request, $employeeUser);

        $employeeUser->load(['roles', 'company']);

        return Inertia::render('External/Employee/Edit', [
            'company'      => $request->user()->company,
            'user'         => $request->user(),
            'employee'     => $employeeUser,
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
        $this->ensureCompanyUser($request, $employeeUser);

        $externalRoleNames = $this->getExternalRoles()->pluck('name')->toArray();

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['nullable', 'email', 'max:255', 'unique:users,email,' . $employeeUser->id],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'role'         => ['required', 'string', 'in:' . implode(',', $externalRoleNames)],
        ]);

        $employeeUser->update([
            'name'         => $validated['name'],
            'email'        => $validated['email'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
        ]);

        $employeeUser->syncRoles([$validated['role']]);

        return redirect()
            ->route('employee-users.index')
            ->with('success', 'Employee updated successfully.');
    }

    // ── Toggle Status ──────────────────────────────────────────────────────────

    public function toggleStatus(Request $request, User $employeeUser)
    {
        $this->ensureCompanyUser($request, $employeeUser);

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
        $this->ensureCompanyUser($request, $employeeUser);

        $employeeUser->update([
            'password'             => 'pitx@123',
            'must_change_password' => true,
        ]);

        return back()->with('success', 'Password reset to pitx@123.');
    }

    // ── Destroy ────────────────────────────────────────────────────────────────

    public function destroy(Request $request, User $employeeUser)
    {
        $this->ensureCompanyUser($request, $employeeUser);

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
