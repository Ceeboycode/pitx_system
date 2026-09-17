<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Mail\TemporaryPasswordMail;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class UserController extends Controller
{
    private const DEFAULT_PASSWORD = 'pitx@123';

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $search = $request->input('search');
        $type = $request->input('type');
        $status = $request->input('status');

        $allowedSorts = ['name', 'status'];
        $sortBy = in_array($request->input('sort_by'), $allowedSorts, true) ? $request->input('sort_by') : null;
        $sortDir = $request->input('sort_dir') === 'desc' ? 'desc' : 'asc';

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
            ->when($sortBy, function ($query) use ($sortBy, $sortDir) {
                $query->orderBy($sortBy, $sortDir);
            }, function ($query) {
                $query->orderBy('name');
            })
            ->paginate(10)
            ->withQueryString()
            ->through(function (User $user) {
                $primaryRole = $user->roles->first();

                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'avatar' => $user->profile_photo_path ? Storage::url($user->profile_photo_path) : null,
                    'phone_number' => $user->phone_number,
                    'company_id' => $user->company_id,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                    'type' => $primaryRole?->type,
                    'avatar_url' => $user->profile_photo_path
                        ? Storage::disk('public')->url($user->profile_photo_path)
                        : null,
                    'roles' => $user->roles->map(fn ($role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'type' => $role->type,
                    ])->values(),
                    'company' => $user->company
                        ? [
                            'id' => $user->company->id,
                            'company_name' => $user->company->company_name,
                            'company_code' => $user->company->company_code,
                        ]
                        : null,
                ];
            });

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
            'currentUserId' => $request->user()->id,
            'statuses' => ['active', 'inactive'],
            'types' => ['internal', 'external'],
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
                ->where('name', '!=', Role::NAME_COMMUTER)
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

        $resolvedType = $role->type;
        $resolvedCompanyId = $resolvedType === 'external'
            ? (int) $data['company_id']
            : null;

        $user = DB::transaction(function () use ($data, $role, $resolvedType, $resolvedCompanyId) {
            $username = $this->generateUsername(
                type: $resolvedType,
                companyId: $resolvedCompanyId,
            );

            $user = User::create([
                'username' => $username,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'] ?? null,
                'company_id' => $resolvedCompanyId,
                'status' => 'active',
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'must_change_password' => true,
            ]);

            $user->assignRole($role->name);

            return $user;
        });

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                "User created successfully. Username: {$user->username}. Default password: ".self::DEFAULT_PASSWORD,
            );
    }

    /**
     * Single detail page for a user, serving both viewing and editing.
     * Gate::authorize('view', ...) only checks that the user is allowed to
     * open the page at all - Users/Edit.vue decides what's editable per
     * field via `can('users.update')`, and the update() action below still
     * independently authorizes the actual write.
     */
    public function show(User $user): Response
    {
        Gate::authorize('view', $user);

        $user->load([
            'roles:id,name,type',
            // Also pull each loaded role's permissions (id + name only) so
            // hasAllPermissionsInGroup() below doesn't need extra queries.
            'roles.permissions:id,name',
            'company:id,company_name,company_code',
        ]);

        $selectedRole = $user->roles->first();

        return Inertia::render('Users/Edit', [
            'currentUserId' => Auth::id(),
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'phone_number' => $user->phone_number,
                'status' => $user->status,
                'type' => $selectedRole?->type,
                'company_id' => $user->company_id,
                // Same conversion HandleInertiaRequests.php uses for the logged-in
                // user's own avatar: profile_photo_path is a storage-relative path,
                // Storage::url() turns it into a browser-usable URL.
                'avatar' => $user->profile_photo_path ? Storage::url($user->profile_photo_path) : null,
                'created_at' => $user->created_at,
                'updated_at_human' => $user->updated_at?->diffForHumans(),
            ],
            'roles' => Role::query()
                ->select('id', 'name', 'type')
                ->where('name', '!=', Role::NAME_COMMUTER)
                ->orderBy('type')
                ->orderBy('name')
                ->get(),
            'selectedRole' => $selectedRole?->name,
            // Drive which tabs Users/Edit.vue shows: the edited user's role
            // must hold every permission in the group, not just some of
            // them. `?->` short-circuits to null (then `?? false`) if the
            // user has no role at all.
            'canManageExternalUsers' => $selectedRole?->hasAllPermissionsInGroup('external_users') ?? false,
            'canManageExternalDispatches' => $selectedRole?->hasAllPermissionsInGroup('external_dispatches') ?? false,
            'companies' => Company::query()
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

        $nextType = $role->type;
        $nextCompanyId = $nextType === 'external'
            ? (isset($validated['company_id']) ? (int) $validated['company_id'] : null)
            : null;

        $currentRoleType = $user->roles()->first()?->type;
        $currentCompanyId = $currentRoleType === 'external'
            ? ($user->company_id ? (int) $user->company_id : null)
            : null;

        $typeChanged = $currentRoleType !== $nextType;
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
                'username' => $nextUsername,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'] ?? null,
                'company_id' => $nextCompanyId,
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

        $temporaryPassword = Str::password(16, true, true, true, false);
        $previousPassword = $user->password;
        $previousMustChangePassword = (bool) $user->must_change_password;

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ])->save();

        try {
            Mail::to($user->email)->send(new TemporaryPasswordMail($user, $temporaryPassword));
        } catch (Throwable $exception) {
            report($exception);

            $user->forceFill([
                'password' => $previousPassword,
                'must_change_password' => $previousMustChangePassword,
            ])->save();

            return back()->with(
                'error',
                'Password reset failed. The temporary password email could not be sent.',
            );
        }

        return back()->with(
            'success',
            "A temporary password has been emailed to {$user->email}.",
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
        $archivedWithin = $request->input('archived_within');

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
                $like = '%'.$search.'%';

                $query->where(function ($innerQuery) use ($like) {
                    $innerQuery->where('username', 'like', $like)
                        ->orWhere('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->when(
                in_array($archivedWithin, ['today', '7_days', '30_days'], true),
                function ($query) use ($archivedWithin) {
                    $query->where('deleted_at', '>=', match ($archivedWithin) {
                        'today' => now()->startOfDay(),
                        '7_days' => now()->subDays(7),
                        '30_days' => now()->subDays(30),
                    });
                },
            )
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString()
            ->through(function (User $user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'deleted_at_human' => $user->deleted_at?->diffForHumans(),
                    'deleter' => $user->deleter
                        ? [
                            'id' => $user->deleter->id,
                            'name' => $user->deleter->name,
                        ]
                        : null,
                ];
            });

        return Inertia::render('Users/Trash', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'archived_within' => $archivedWithin,
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

            $prefix = $company->company_code.'-';
        } else {
            $prefix = now()->year.'-';
        }

        $lastUsername = User::query()
            ->where('username', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('username')
            ->value('username');

        $nextNumber = 1;

        if ($lastUsername) {
            $lastDigits = substr($lastUsername, strlen($prefix));
            $nextNumber = ((int) $lastDigits) + 1;
        }

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
