<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserRolesRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\UserProvisioningService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserProvisioningService $users) {}

    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->toString() ?: null;
        $role = $request->string('role')->trim()->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $roleNames = $roles->pluck('name')->all();

        if ($role !== null && ! in_array($role, $roleNames, true)) {
            $role = null;
        }

        if (! in_array($status, ['active', 'inactive'], true)) {
            $status = null;
        }

        $users = $this->users->paginated($search, $role, $status);

        $roleOptions = ['' => __('admin.role_all')];
        foreach ($roles as $item) {
            if ($item->name === 'super_admin' && ! $request->user()?->hasRole('super_admin')) {
                continue;
            }
            $roleOptions[$item->name] = role_label($item->name);
        }

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search ?? '',
            'role' => $role ?? '',
            'status' => $status ?? '',
            'roleOptions' => $roleOptions,
        ]);
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.users.create', array_merge(
            compact('roles'),
            $this->users->formOptions()
        ));
    }

    public function store(StoreUserRequest $request)
    {
        $this->users->create(
            $request->validated(),
            $this->resolveIsActiveForCreate($request)
        );

        return redirect()->route('admin.users.index')
            ->with('success', __('messages.user_created'));
    }

    public function show(User $user)
    {
        $user->load(['roles', 'zoneable']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $userRoles = $user->roles->pluck('name')->toArray();

        return view('admin.users.edit', array_merge(
            compact('user', 'roles', 'userRoles'),
            $this->users->formOptions()
        ));
    }

    public function assignRoles(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $userRoles = $user->roles->pluck('name')->toArray();

        return view('admin.users.assign-roles', compact('user', 'roles', 'userRoles'));
    }

    public function updateRoles(UpdateUserRolesRequest $request, User $user)
    {
        $this->users->syncRolesOnly($user, $request->validated('roles') ?? []);

        return redirect()
            ->route('admin.users.assign-roles', $user)
            ->with('success', __('messages.user_roles_updated'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $unlockLogin = $request->boolean('unlock_login');

        $this->users->update(
            $user,
            $request->validated(),
            $this->resolveIsActiveForUpdate($request, $user),
            $unlockLogin
        );

        $message = __('messages.user_updated');
        if ($unlockLogin) {
            $message = __('messages.user_updated_and_unlocked');
        }

        return redirect()->route('admin.users.index')
            ->with('success', $message);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => __('messages.cannot_delete_self')]);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', __('messages.user_deleted'));
    }

    private function resolveIsActiveForCreate(Request $request): bool
    {
        $actor = $request->user();
        $canActivate = $actor->can('activate users');
        $canDeactivate = $actor->can('deactivate users');

        if (! $canActivate && ! $canDeactivate) {
            return true;
        }

        $desired = $request->boolean('is_active');

        if ($desired && ! $canActivate) {
            return false;
        }

        if (! $desired && ! $canDeactivate) {
            return true;
        }

        return $desired;
    }

    private function resolveIsActiveForUpdate(Request $request, User $user): bool
    {
        $actor = $request->user();
        $canActivate = $actor->can('activate users');
        $canDeactivate = $actor->can('deactivate users');

        if (! $canActivate && ! $canDeactivate) {
            return (bool) $user->is_active;
        }

        if (! $request->has('is_active')) {
            return (bool) $user->is_active;
        }

        $desired = $request->boolean('is_active');

        if ($desired === (bool) $user->is_active) {
            return (bool) $user->is_active;
        }

        if ($desired && ! $canActivate) {
            return (bool) $user->is_active;
        }

        if (! $desired && ! $canDeactivate) {
            return (bool) $user->is_active;
        }

        return $desired;
    }
}
