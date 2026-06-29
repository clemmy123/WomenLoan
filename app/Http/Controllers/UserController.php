<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\UserProvisioningService;
use App\Models\Role;

class UserController extends Controller
{
    public function __construct(private UserProvisioningService $users) {}

    public function index()
    {
        $users = User::with('roles')->latest()->paginate(15);

        return view('admin.users.index', compact('users'));
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
            $request->boolean('is_active', true)
        );

        return redirect()->route('admin.users.index')
            ->with('success', __('messages.user_created'));
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

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->users->update(
            $user,
            $request->validated(),
            $request->boolean('is_active', true)
        );

        return redirect()->route('admin.users.index')
            ->with('success', __('messages.user_updated'));
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
}
