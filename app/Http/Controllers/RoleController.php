<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\Web\PermissionResource as WebPermissionResource;
use App\Http\Resources\Web\RoleResource as WebRoleResource;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:users.viewAny', only: ['index']),
            new Middleware('permission:users.view', only: ['show']),
            new Middleware('permission:roles.create', only: ['create', 'store']),
            new Middleware('permission:roles.edit', only: ['edit', 'update']),
            new Middleware('permission:roles.delete', only: ['destroy']),
        ];
    }

    public function index()
    {
        $roles = Role::with('permissions:id,name')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Roles/Index', [
            'roles' => WebRoleResource::collection($roles),
        ]);
    }

    public function create()
    {
        return Inertia::render('Roles/Create', [
            'permissions' => WebPermissionResource::collection(
                Permission::orderBy('name')->get(['id', 'name'])
            ),
        ]);
    }

    public function store(StoreRoleRequest $request)
    {
        $validated = $request->validated();

        $role = Role::create([
            'name'       => $validated['name'],
            'guard_name' => config('auth.defaults.guard'),
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return to_route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function show(Role $role)
    {
        $role->load('permissions:id,name');

        return Inertia::render('Roles/Show', [
            'role' => new WebRoleResource($role),
        ]);
    }

    public function edit(Role $role)
    {
        $role->load('permissions:id,name');

        return Inertia::render('Roles/Edit', [
            'role' => new WebRoleResource($role),
            'permissions' => WebPermissionResource::collection(
                Permission::orderBy('name')->get(['id', 'name'])
            ),
            'selectedPermissions' => $role->permissions->pluck('id'),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $validated = $request->validated();

        $role->update([
            'name' => $validated['name'],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return to_route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return back()->with('success', 'Role deleted successfully.');
    }
}
