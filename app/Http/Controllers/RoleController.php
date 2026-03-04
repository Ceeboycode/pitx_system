<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use App\Models\Role;



class RoleController extends Controller
{

    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');

        $roles = Role::query()
            ->select('id', 'name', 'type')
            ->with(['permissions:id,name'])
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'type' => $type,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Roles/Create', [
            'permissions' => Permission::select('id', 'name')->orderBy('name')->get(),
            'roleTypes' => ['internal', 'external'],
        ]);
    }

    public function store(StoreRoleRequest $request)
    {
        $role = Role::create([
            'name' => $request->string('name')->toString(),
            'type' => $request->string('type')->toString(),
            'guard_name' => config('auth.defaults.guard'),
        ]);

        $role->syncPermissions($request->input('permissions', []));

        return to_route('roles.index')->with('success', 'Role created successfully.');
    }

    // public function show(Role $role)
    // {
    //     $role->load('permissions:id,name');

    //     return Inertia::render('Roles/Show', [
    //         'role' => new WebRoleResource($role),
    //     ]);
    // }

    public function edit(Role $role)
    {
        $role->load('permissions:id');

        return Inertia::render('Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'type' => $role->type,
            ],
            'permissions' => Permission::select('id', 'name')->orderBy('name')->get(),
            'rolePermissionIds' => $role->permissions->pluck('id')->values(),
            'roleTypes' => ['internal', 'external'],
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $validated = $request->validated();

        $role->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return back()->with('success', 'Role deleted successfully.');
    }
}
