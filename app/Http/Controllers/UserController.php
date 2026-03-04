<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;


class UserController extends Controller
{

    public function index(Request $request)
    {
        $search = $request->input('search');
        $type   = $request->input('type');

        $users = User::query()
            ->select(['id', 'username', 'name', 'email', 'phone_number', 'company_id'])
            ->with(['roles:id,name,type', 'company:id,company_name,company_code'])
            ->when($type, function ($query) use ($type) {
                $query->whereHas('roles', fn ($q) => $q->where('type', $type));
            })
            ->search($search)
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'type'   => $type,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Create', [
            'companies' => Company::orderBy('company_name')
                ->get(['id', 'company_name', 'company_code']),

            // IMPORTANT: include type
            'roles' => Role::orderBy('name')
                ->get(['id', 'name', 'type']),
        ]);
    }

    public function store(StoreUserRequest $request)
{
    $data = $request->validated();

    $user = DB::transaction(function () use ($data) {
        $type = $data['type'];

        // Determine prefix
        if ($type === 'external') {
            $company = Company::query()
                ->whereKey($data['company_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $prefix = $company->company_code . '-'; // e.g. SAS-
        } else {
            $prefix = now()->year . '-'; // e.g. 2026-
        }

        // Find latest username for this prefix (lock users rows to avoid duplicates)
        $lastUsername = User::query()
            ->where('username', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('username') // works because 0001 padding keeps lexical order
            ->value('username');

        $nextNumber = 1;
        if ($lastUsername) {
            // username format: PREFIX + 4 digits (e.g. SAS-0007)
            $lastDigits = substr($lastUsername, strlen($prefix)); // "0007"
            $nextNumber = ((int) $lastDigits) + 1;
        }

        $username = $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

        // Create user
        $user = User::create([
            'username' => $username,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'type' => $type,
            'company_id' => $type === 'external' ? $data['company_id'] : null,

            // default password = phone number
            'password' => Hash::make($data['phone_number']),
        ]);

        // Assign role (Spatie)
        $user->assignRole($data['role']);

        return $user;
    });

    return redirect()
        ->route('users.index')
        ->with('success', "User created successfully. Username: {$user->username}");
}

    public function edit(User $user)
    {
        $user->load('roles:id,name,type');

        $selectedRole = $user->roles->first()?->name;

        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
            ],
            'roles' => Role::query()
                ->select('id', 'name', 'type')
                ->orderBy('type')
                ->orderBy('name')
                ->get(),
            'selectedRole' => $selectedRole, // ✅ single value
            'roleTypes' => ['internal', 'external'], // UI-only
            'initialRoleType' => $user->user_type, // internal|external|null|mixed
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        // Optional (if Edit.vue submits role_type)
        $this->assertRoleMatchesType(
            $validated['role'],
            $validated['role_type'] ?? null
        );

        $user->update([
            'username' => $validated['username'] ?? $user->username,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'password' => !empty($validated['password'])
                ? Hash::make($validated['password'])
                : $user->password,
        ]);

        // ✅ single role
        $user->syncRoles([$validated['role']]);

        return to_route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
