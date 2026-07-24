<?php

namespace App\Http\Controllers;

use App\Exports\UsersExport;
use App\Http\Requests\Admin\ActivateUserRequest;
use App\Http\Requests\Admin\DeactivateUserRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserRolesRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\UserProvisioningService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    public function __construct(private UserProvisioningService $users) {}

    public function index(Request $request)
    {
        return $this->renderUsersList($request, 'active');
    }

    public function inactive(Request $request)
    {
        return $this->renderUsersList($request, 'inactive');
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        [$search, $role, $status] = $this->listFilters($request, $this->forcedListStatus($request));
        $rows = $this->users->exportRows($search, $role, $status);

        return Excel::download(
            new UsersExport($rows, [
                'search' => $search ?? '',
                'role' => $role ?? '',
                'status' => $status ?? '',
            ]),
            $this->users->exportFilename('xlsx')
        );
    }

    public function exportPdf(Request $request)
    {
        [$search, $role, $status] = $this->listFilters($request, $this->forcedListStatus($request));
        $rows = $this->users->exportRows($search, $role, $status);
        $filters = [
            'search' => $search ?? '',
            'role' => $role ?? '',
            'status' => $status ?? '',
        ];

        return Pdf::loadView('admin.users.export-pdf', compact('filters', 'rows'))
            ->download($this->users->exportFilename('pdf'));
    }

    protected function renderUsersList(Request $request, string $listStatus)
    {
        [$search, $role, $status, $roleOptions] = $this->listFilters($request, $listStatus);

        $users = $this->users->paginated($search, $role, $status);

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search ?? '',
            'role' => $role ?? '',
            'status' => $status,
            'listStatus' => $listStatus,
            'roleOptions' => $roleOptions,
        ]);
    }

    protected function forcedListStatus(Request $request): string
    {
        $fromQuery = $request->string('list')->toString();

        if (in_array($fromQuery, ['active', 'inactive'], true)) {
            return $fromQuery;
        }

        return $request->routeIs('admin.users.inactive') ? 'inactive' : 'active';
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: string, 3: array<string, string>}
     */
    protected function listFilters(Request $request, ?string $forcedStatus = null): array
    {
        $search = $request->string('search')->trim()->toString() ?: null;
        $role = $request->string('role')->trim()->toString() ?: null;
        $status = $forcedStatus ?? $request->string('status')->toString() ?: null;

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $roleNames = $roles->pluck('name')->all();

        if ($role !== null && ! in_array($role, $roleNames, true)) {
            $role = null;
        }

        if ($role === 'applicant') {
            $role = null;
        }

        if (! in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $roleOptions = ['' => __('admin.role_all')];
        foreach ($roles as $item) {
            if ($item->name === 'applicant') {
                continue;
            }
            if ($item->name === 'super_admin' && ! $request->user()?->hasRole('super_admin')) {
                continue;
            }
            $roleOptions[$item->name] = role_label($item->name);
        }

        return [$search, $role, $status, $roleOptions];
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
        $user->load(['roles', 'zoneable', 'deactivatedBy']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $userRoles = $user->roles->pluck('name')->toArray();
        $user->loadMissing('zoneable');

        return view('admin.users.edit', array_merge(
            compact('user', 'roles', 'userRoles'),
            $this->users->formOptions()
        ));
    }

    public function assignRoles(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $userRoles = $user->roles->pluck('name')->toArray();
        $user->loadMissing('zoneable');

        return view('admin.users.assign-roles', array_merge(
            compact('user', 'roles', 'userRoles'),
            $this->users->formOptions()
        ));
    }

    public function updateRoles(UpdateUserRolesRequest $request, User $user)
    {
        $validated = $request->validated();
        $this->users->syncRolesAndZone($user, $validated['roles'] ?? [], $validated);

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

        $user->refresh();

        $message = __('messages.user_updated');
        if ($unlockLogin) {
            $message = __('messages.user_updated_and_unlocked');
        }

        return redirect()
            ->route($user->is_active ? 'admin.users.index' : 'admin.users.inactive')
            ->with('success', $message);
    }

    public function deactivate(DeactivateUserRequest $request, User $user)
    {
        if (! $user->is_active) {
            return redirect()
                ->route('admin.users.inactive')
                ->with('success', __('messages.user_already_deactivated'));
        }

        $this->users->deactivate($user, $request->validated('deactivation_reason'), $request->user());

        return redirect()
            ->route('admin.users.inactive')
            ->with('success', __('messages.user_deactivated'));
    }

    public function activate(ActivateUserRequest $request, User $user)
    {
        if ($user->is_active) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', __('messages.user_already_active'));
        }

        $this->users->activate($user);

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('messages.user_activated'));
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => __('messages.cannot_delete_self')]);
        }

        $wasActive = (bool) $user->is_active;
        $user->delete();

        return redirect()
            ->route($wasActive ? 'admin.users.index' : 'admin.users.inactive')
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
