<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Role;
use App\Services\RoleService;
use App\Support\PermissionCatalog;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    public function __construct(private RoleService $roles) {}

    public function index()
    {
        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissionGroups = PermissionCatalog::groups();
        $menuHints = PermissionCatalog::menuHints();

        return view('admin.roles.create', compact('permissionGroups', 'menuHints'));
    }

    public function store(StoreRoleRequest $request)
    {
        $this->roles->create($request->validated());

        return redirect()->route('admin.roles.index')
            ->with('success', __('messages.role_created'));
    }

    public function edit(Role $role)
    {
        $permissionGroups = PermissionCatalog::groups();
        $menuHints = PermissionCatalog::menuHints();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('admin.roles.edit', compact('role', 'permissionGroups', 'menuHints', 'rolePermissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        try {
            $this->roles->update($role, $request->validated());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.roles.index')
            ->with('success', __('messages.role_updated'));
    }

    public function destroy(Role $role)
    {
        try {
            $this->roles->delete($role);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.roles.index')
            ->with('success', __('messages.role_deleted'));
    }
}
