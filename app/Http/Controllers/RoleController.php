<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\Web\PermissionResource as WebPermissionResource;
use App\Http\Resources\Web\RoleResource as WebRoleResource;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
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
        $role = Role::create([
            'name'       => $request->name,
            'guard_name' => config('auth.defaults.guard'),
        ]);

        $role->syncPermissions($request->permissions ?? []);

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
        $role->update([
            'name' => $request->name,
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return to_route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return back()->with('success', 'Role deleted successfully.');
    }
}
