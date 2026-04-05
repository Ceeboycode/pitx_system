<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    private const DEFAULT_PASSWORD = 'pitx@123';

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $search = $request->input('search');
        $type   = $request->input('type');
        $status = $request->input('status');

        $users = User::query()
            ->select([
                'id',
                'username',
                'name',
                'email',
                'email_verified_at',
                'profile_photo_path',
                'phone_number',
                'company_id',
                'status',
                'created_at',
                'profile_photo_path',
            ])
            ->with([
                'roles:id,name,type',
                'company:id,company_name,company_code',
            ])
            ->when($type, function ($query) use ($type) {
                $query->whereHas('roles', fn ($q) => $q->where('type', $type));
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->search($search)
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(function (User $user) {
                $primaryRole = $user->roles->first();

                return [
                    'id'                => $user->id,
                    'username'          => $user->username,
                    'name'              => $user->name,
                    'email'             => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'avatar'            => $user->profile_photo_path ? Storage::url($user->profile_photo_path) : null,
                    'phone_number'      => $user->phone_number,
                    'company_id'        => $user->company_id,
                    'status'            => $user->status,
                    'created_at'        => $user->created_at,
                    'type'              => $primaryRole?->type,
                    'avatar_url'        => $user->profile_photo_path
                        ? Storage::disk('public')->url($user->profile_photo_path)
                        : null,
                    'roles'             => $user->roles->map(fn ($role) => [
                        'id'   => $role->id,
                        'name' => $role->name,
                        'type' => $role->type,
                    ])->values(),
                    'company' => $user->company
                        ? [
                            'id'           => $user->company->id,
                            'company_name' => $user->company->company_name,
                            'company_code' => $user->company->company_code,
                        ]
                        : null,
                ];
            });

        return Inertia::render('Users/Index', [
            'users'   => $users,
            'filters' => [
                'search' => $search,
                'type'   => $type,
                'status' => $status,
            ],
            'currentUserId' => $request->user()->id,
            'statuses' => ['active', 'inactive'],
            'types'    => ['internal', 'external'],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('Users/Create', [
            'companies' => Company::query()
                ->orderBy('company_name')
                ->get(['id', 'company_name', 'company_code']),
            'roles' => Role::query()
                ->select('id', 'name', 'type')
                ->orderBy('type')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $data = $request->validated();

        $role = Role::query()
            ->where('name', $data['role'])
            ->firstOrFail(['id', 'name', 'type']);

        $resolvedType      = $role->type;
        $resolvedCompanyId = $resolvedType === 'external'
            ? (int) $data['company_id']
            : null;

        $user = DB::transaction(function () use ($data, $role, $resolvedType, $resolvedCompanyId) {
            $username = $this->generateUsername(
                type: $resolvedType,
                companyId: $resolvedCompanyId,
            );

            $user = User::create([
                'username'             => $username,
                'name'                 => $data['name'],
                'email'                => $data['email'],
                'phone_number'         => $data['phone_number'] ?? null,
                'company_id'           => $resolvedCompanyId,
                'status'               => 'active',
                'password'             => Hash::make(self::DEFAULT_PASSWORD),
                'must_change_password' => true,
            ]);

            $user->assignRole($role->name);

            return $user;
        });

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                "User created successfully. Username: {$user->username}. Default password: " . self::DEFAULT_PASSWORD,
            );
    }

    public function show(User $user): Response
    {
        Gate::authorize('view', $user);

        $user->load([
            'roles:id,name,type',
            'company:id,company_name,company_code',
        ]);

        $primaryRole  = $user->roles->first();
        $resolvedType = $primaryRole?->type;

        return Inertia::render('Users/Show', [
            'currentUserId' => Auth::id(),
            'user' => [
                'id'                => $user->id,
                'username'          => $user->username,
                'name'              => $user->name,
                'email'             => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'avatar'            => $user->profile_photo_path ? Storage::url($user->profile_photo_path) : null,
                'phone_number'      => $user->phone_number,
                'status'            => $user->status,
                'created_at'        => $user->created_at,
                'type'              => $resolvedType,
                'company'           => $resolvedType === 'external' && $user->company
                    ? [
                        'id'           => $user->company->id,
                        'company_name' => $user->company->company_name,
                        'company_code' => $user->company->company_code,
                    ]
                    : null,
                'roles' => $user->roles->map(fn ($role) => [
                    'id'   => $role->id,
                    'name' => $role->name,
                    'type' => $role->type,
                ])->values(),
                'internal_roles' => $user->roles
                    ->filter(fn ($role) => $role->type === 'internal')
                    ->map(fn ($role) => [
                        'id'   => $role->id,
                        'name' => $role->name,
                        'type' => $role->type,
                    ])
                    ->values(),
                'external_roles' => $user->roles
                    ->filter(fn ($role) => $role->type === 'external')
                    ->map(fn ($role) => [
                        'id'   => $role->id,
                        'name' => $role->name,
                        'type' => $role->type,
                    ])
                    ->values(),
            ],
        ]);
    }

    public function edit(User $user): Response
    {
        Gate::authorize('update', $user);

        $user->load([
            'roles:id,name,type',
            'company:id,company_name,company_code',
        ]);

        $selectedRole = $user->roles->first();

        return Inertia::render('Users/Edit', [
            'user' => [
                'id'           => $user->id,
                'username'     => $user->username,
                'name'         => $user->name,
                'email'        => $user->email,
                'phone_number' => $user->phone_number,
                'type'         => $selectedRole?->type,
                'company_id'   => $user->company_id,
            ],
            'roles' => Role::query()
                ->select('id', 'name', 'type')
                ->orderBy('type')
                ->orderBy('name')
                ->get(),
            'selectedRole' => $selectedRole?->name,
            'companies'    => Company::query()
                ->orderBy('company_name')
                ->get(['id', 'company_name', 'company_code']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validated();

        $role = Role::query()
            ->where('name', $validated['role'])
            ->firstOrFail(['id', 'name', 'type']);

        $nextType      = $role->type;
        $nextCompanyId = $nextType === 'external'
            ? (isset($validated['company_id']) ? (int) $validated['company_id'] : null)
            : null;

        $currentRoleType  = $user->roles()->first()?->type;
        $currentCompanyId = $currentRoleType === 'external'
            ? ($user->company_id ? (int) $user->company_id : null)
            : null;

        $typeChanged    = $currentRoleType !== $nextType;
        $companyChanged = $currentCompanyId !== $nextCompanyId;

        DB::transaction(function () use ($user, $validated, $role, $nextType, $nextCompanyId, $typeChanged, $companyChanged) {
            $nextUsername = $user->username;

            if ($typeChanged || $companyChanged) {
                $nextUsername = $this->generateUsername(
                    type: $nextType,
                    companyId: $nextCompanyId,
                );
            }

            $user->update([
                'username'     => $nextUsername,
                'name'         => $validated['name'],
                'email'        => $validated['email'],
                'phone_number' => $validated['phone_number'] ?? null,
                'company_id'   => $nextCompanyId,
            ]);

            $user->syncRoles([$role->name]);
        });

        return to_route('users.index')->with('success', 'User updated successfully.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        Gate::authorize('toggleStatus', $user);

        $nextStatus = $user->status === 'active' ? 'inactive' : 'active';

        $user->update(['status' => $nextStatus]);

        return back()->with('success', "{$user->name} is now {$nextStatus}.");
    }

    public function resetPassword(User $user): RedirectResponse
    {
        Gate::authorize('resetPassword', $user);

        $user->update([
            'password'             => Hash::make(self::DEFAULT_PASSWORD),
            'must_change_password' => true,
        ]);

        return back()->with(
            'success',
            "{$user->name}'s password has been reset to the default password: " . self::DEFAULT_PASSWORD,
        );
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $user->deleted_by = $request->user()?->id;
        $user->save();
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User archived successfully.');
    }

    public function trash(Request $request): Response
    {
        Gate::authorize('viewTrash', User::class);

        $search = $request->input('search');

        $users = User::onlyTrashed()
            ->select([
                'id',
                'username',
                'name',
                'email',
                'deleted_at',
                'deleted_by',
            ])
            ->with(['deleter:id,name'])
            ->when($search, function ($query) use ($search) {
                $like = '%' . $search . '%';

                $query->where(function ($innerQuery) use ($like) {
                    $innerQuery->where('username', 'like', $like)
                        ->orWhere('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString()
            ->through(function (User $user) {
                return [
                    'id'              => $user->id,
                    'username'        => $user->username,
                    'name'            => $user->name,
                    'email'           => $user->email,
                    'deleted_at_human' => $user->deleted_at?->diffForHumans(),
                    'deleter'         => $user->deleter
                        ? [
                            'id'   => $user->deleter->id,
                            'name' => $user->deleter->name,
                        ]
                        : null,
                ];
            });

        return Inertia::render('Users/Trash', [
            'users'    => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function restore(User $user): RedirectResponse
    {
        Gate::authorize('restore', $user);

        $user->restore();

        return back()->with('success', 'User restored successfully.');
    }

    private function generateUsername(string $type, ?int $companyId = null): string
    {
        if ($type === 'external') {
            $company = Company::query()
                ->whereKey($companyId)
                ->lockForUpdate()
                ->firstOrFail();

            $prefix = $company->company_code . '-';
        } else {
            $prefix = now()->year . '-';
        }

        $lastUsername = User::query()
            ->where('username', 'like', $prefix . '%')
            ->lockForUpdate()
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
