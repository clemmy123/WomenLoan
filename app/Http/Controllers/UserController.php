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
use App\Services\GeoHierarchyService;
use App\Services\UserProvisioningService;
use App\Support\StaffAdminScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    public function __construct(
        private UserProvisioningService $users,
        private GeoHierarchyService $geo,
    ) {}

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
        $filters = $this->listFilters($request, $this->forcedListStatus($request));
        $rows = $this->users->exportRows($filters);

        return Excel::download(
            new UsersExport($rows, [
                'search' => $filters['search'] ?? '',
                'role' => $filters['role'] ?? '',
                'status' => $filters['status'] ?? '',
            ]),
            $this->users->exportFilename('xlsx')
        );
    }

    public function exportPdf(Request $request)
    {
        $filters = $this->listFilters($request, $this->forcedListStatus($request));
        $rows = $this->users->exportRows($filters);

        return Pdf::loadView('admin.users.export-pdf', [
            'filters' => [
                'search' => $filters['search'] ?? '',
                'role' => $filters['role'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'rows' => $rows,
        ])->download($this->users->exportFilename('pdf'));
    }

    protected function renderUsersList(Request $request, string $listStatus)
    {
        $filters = $this->listFilters($request, $listStatus);
        $roleOptions = $filters['roleOptions'];
        unset($filters['roleOptions']);

        $users = $this->users->paginated($filters);
        $geoBounds = $this->geo->zoneBounds($request->user());

        return view('admin.users.index', [
            'users' => $users,
            'search' => $filters['search'] ?? '',
            'role' => $filters['role'] ?? '',
            'status' => $filters['status'],
            'listStatus' => $listStatus,
            'roleOptions' => $roleOptions,
            'filters' => $filters,
            'regions' => $this->geo->regionsForUser($request->user()),
            'geoBounds' => $geoBounds ?? [],
            'filtersApplied' => $this->filtersAreApplied($filters, $geoBounds['lock'] ?? []),
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
     * @return array{
     *     search: ?string,
     *     role: ?string,
     *     status: string,
     *     region_id: ?string,
     *     district_id: ?string,
     *     council_id: ?string,
     *     ward_id: ?string,
     *     roleOptions: array<string, string>
     * }
     */
    protected function listFilters(Request $request, ?string $forcedStatus = null): array
    {
        $search = $request->string('search')->trim()->toString() ?: null;
        $role = $request->string('role')->trim()->toString() ?: null;
        $status = $forcedStatus ?? $request->string('status')->toString() ?: null;

        $roles = $this->assignableRoles($request->user());
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
            $roleOptions[$item->name] = role_label($item->name);
        }

        $geo = $this->geo->clampGeoFilters([
            'region_id' => $request->input('region_id'),
            'district_id' => $request->input('district_id'),
            'council_id' => $request->input('council_id'),
            'ward_id' => $request->input('ward_id'),
        ], $request->user());

        return [
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'region_id' => filled($geo['region_id'] ?? null) ? (string) $geo['region_id'] : null,
            'district_id' => filled($geo['district_id'] ?? null) ? (string) $geo['district_id'] : null,
            'council_id' => filled($geo['council_id'] ?? null) ? (string) $geo['council_id'] : null,
            'ward_id' => filled($geo['ward_id'] ?? null) ? (string) $geo['ward_id'] : null,
            'roleOptions' => $roleOptions,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $locks
     */
    protected function filtersAreApplied(array $filters, array $locks = []): bool
    {
        $regionApplied = filled($filters['region_id'] ?? null) && empty($locks['region_id']);
        $districtApplied = filled($filters['district_id'] ?? null) && empty($locks['district_id']);
        $councilApplied = filled($filters['council_id'] ?? null) && empty($locks['council_id']);
        $wardApplied = filled($filters['ward_id'] ?? null) && empty($locks['ward_id']);

        return filled($filters['search'] ?? null)
            || filled($filters['role'] ?? null)
            || $regionApplied
            || $districtApplied
            || $councilApplied
            || $wardApplied;
    }

    public function create()
    {
        $roles = $this->assignableRoles(auth()->user());

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
        $this->ensureCanManage($user);
        $user->load(['roles', 'zoneable', 'deactivatedBy']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->ensureCanManage($user);
        $roles = $this->assignableRoles(auth()->user());
        $userRoles = $user->roles->pluck('name')->toArray();
        $user->loadMissing('zoneable');

        return view('admin.users.edit', array_merge(
            compact('user', 'roles', 'userRoles'),
            $this->users->formOptions()
        ));
    }

    public function assignRoles(User $user)
    {
        $this->ensureCanManage($user);
        $roles = $this->assignableRoles(auth()->user());
        $userRoles = $user->roles->pluck('name')->toArray();
        $user->loadMissing('zoneable');

        return view('admin.users.assign-roles', array_merge(
            compact('user', 'roles', 'userRoles'),
            $this->users->formOptions()
        ));
    }

    public function updateRoles(UpdateUserRolesRequest $request, User $user)
    {
        $this->ensureCanManage($user);
        $validated = $request->validated();
        $this->users->syncRolesAndZone($user, $validated['roles'] ?? [], $validated);

        return redirect()
            ->route('admin.users.assign-roles', $user)
            ->with('success', __('messages.user_roles_updated'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->ensureCanManage($user);
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
        $this->ensureCanManage($user);

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
        $this->ensureCanManage($user);

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
        $this->ensureCanManage($user);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => __('messages.cannot_delete_self')]);
        }

        $wasActive = (bool) $user->is_active;
        $user->delete();

        return redirect()
            ->route($wasActive ? 'admin.users.index' : 'admin.users.inactive')
            ->with('success', __('messages.user_deleted'));
    }

    protected function ensureCanManage(User $user): void
    {
        abort_unless(StaffAdminScope::canManage(auth()->user(), $user), 403);
    }

    /**
     * @return Collection<int, Role>
     */
    protected function assignableRoles(?User $actor): Collection
    {
        $query = Role::query()->orderBy('name');

        $assignable = StaffAdminScope::assignableRoleNames($actor);

        if ($assignable !== null) {
            return $query->whereIn('name', $assignable)->get();
        }

        $roles = $query->get();

        if (! $actor?->hasRole('super_admin')) {
            $roles = $roles->reject(fn (Role $role) => $role->name === 'super_admin')->values();
        }

        return $roles->reject(fn (Role $role) => $role->name === 'applicant')->values();
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
