<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CompanyUserController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->user()->company;

        abort_if(!$company, 403, 'No company assigned.');

        $search = $request->input('search');
        $role = $request->input('role');
        $status = $request->input('status');

        $users = User::query()
            ->with(['roles', 'company'])
            ->where('company_id', $company->id)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['driver', 'dispatcher']);
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
            'company' => $company,
            'user' => $request->user(),
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $role,
                'status' => $status,
            ],
            'roles' => ['driver', 'dispatcher'],
            'statuses' => ['pending', 'active', 'inactive', 'suspended'],
        ]);
    }

    public function create(Request $request)
    {
        $company = $request->user()->company;

        abort_if(!$company, 403, 'No company assigned.');

        return Inertia::render('External/Employee/Create', [
            'company' => $company,
            'user' => $request->user(),
            'roles' => ['driver', 'dispatcher'],
            'statuses' => ['pending', 'active', 'inactive', 'suspended'],
            'defaultStatus' => 'pending',
            'nextUsernamePreview' => $this->generateNextUsername($company->company_code),
        ]);
    }

    public function store(Request $request)
    {
        $company = $request->user()->company;

        abort_if(!$company, 403, 'No company assigned.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:driver,dispatcher'],
        ]);

        $user = DB::transaction(function () use ($company, $validated) {
            $username = $this->generateNextUsername($company->company_code, true);

            $user = User::create([
                'username' => $username,
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'company_id' => $company->id,
                'status' => 'active',
                'password' => 'pitx@123',
                'must_change_password' => true,
            ]);

            $user->assignRole($validated['role']);

            return $user;
        });

        return redirect()
            ->route('employee-users.index')
            ->with('success', "User created successfully. Username: {$user->username}");
    }

    public function show(Request $request, User $employeeUser)
    {
        $this->ensureCompanyUser($request, $employeeUser);

        $employeeUser->load(['roles', 'company']);

        return Inertia::render('External/Employee/Show', [
            'company' => $request->user()->company,
            'user' => $request->user(),
            'employee' => $employeeUser,
        ]);
    }

    public function edit(Request $request, User $employeeUser)
    {
        $this->ensureCompanyUser($request, $employeeUser);

        $employeeUser->load(['roles', 'company']);

        return Inertia::render('External/Employee/Edit', [
            'company' => $request->user()->company,
            'user' => $request->user(),
            'employee' => $employeeUser,
            'roles' => ['driver', 'dispatcher'],
            'statuses' => ['pending', 'active', 'inactive', 'suspended'],
            'selectedRole' => $employeeUser->getRoleNames()->first(),
        ]);
    }

    public function update(Request $request, User $employeeUser)
    {
        $this->ensureCompanyUser($request, $employeeUser);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $employeeUser->id],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:driver,dispatcher'],
        ]);

        $employeeUser->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
        ]);

        $employeeUser->syncRoles([$validated['role']]);

        return redirect()
            ->route('employee-users.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function toggleStatus(Request $request, User $employeeUser)
    {
        $this->ensureCompanyUser($request, $employeeUser);

        $nextStatus = match ($employeeUser->status) {
            'active' => 'inactive',
            'inactive' => 'active',
            'pending' => 'active',
            'suspended' => 'active',
            default => 'active',
        };

        $employeeUser->update([
            'status' => $nextStatus,
        ]);

        return back()->with('success', 'Employee status updated successfully.');
    }

    public function resetPassword(Request $request, User $employeeUser)
    {
        $this->ensureCompanyUser($request, $employeeUser);

        $employeeUser->update([
            'password' => 'pitx@123',
            'must_change_password' => true,
        ]);

        return back()->with('success', 'Employee password has been reset to pitx@123.');
    }

    public function destroy(Request $request, User $employeeUser)
    {
        $this->ensureCompanyUser($request, $employeeUser);

        $employeeUser->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    private function ensureCompanyUser(Request $request, User $user): void
    {
        $company = $request->user()->company;

        abort_if(!$company, 403, 'No company assigned.');

        abort_unless($user->company_id === $company->id, 403, 'Unauthorized access.');

        abort_unless(
            $user->hasAnyRole(['driver', 'dispatcher']),
            403,
            'This user is not a driver or dispatcher.'
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
