<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;

class PermissionCatalog
{
    public static function groups(): array
    {
        return [
            'dashboard' => [
                'label' => __('permissions.groups.dashboard'),
                'permissions' => ['view dashboard'],
            ],
            'applicants' => [
                'label' => __('permissions.groups.applicants'),
                'permissions' => [
                    'manage applicants',
                    'register applicant',
                    'view own profile',
                ],
            ],
            'loans' => [
                'label' => __('permissions.groups.loans'),
                'permissions' => [
                    'create loan application',
                    'view own loans',
                    'edit pending loan',
                    'accept loan amount',
                    'view loan by track id',
                    'view ward loans',
                    'view council loans',
                    'view region loans',
                    'view all loans',
                ],
            ],
            'workflow' => [
                'label' => __('permissions.groups.workflow'),
                'permissions' => [
                    'receive application',
                    'review ward application',
                    'forward to council',
                    'review council application',
                    'forward to ministry',
                    'review ministry application',
                    'propose loan amount',
                    'send to applicant confirmation',
                    'forward to assistant director',
                    'comment as assistant director',
                    'forward to director',
                    'comment as director',
                    'forward to km',
                    'approve as km',
                    'assign accountant',
                    'disburse loan',
                    'rollback workflow step',
                ],
            ],
            'groups' => [
                'label' => __('permissions.groups.groups'),
                'permissions' => ['manage loan groups'],
            ],
            'finance' => [
                'label' => __('permissions.groups.finance'),
                'permissions' => ['view repayments', 'record repayment'],
            ],
            'reports' => [
                'label' => __('permissions.groups.reports'),
                'permissions' => [
                    'view reports overview',
                    'view application reports',
                    'view payment reports',
                    'view outstanding reports',
                    'view overdue reports',
                    'view by region reports',
                    'view by type reports',
                    'view by sector reports',
                    'view by bank reports',
                    'view by monthly reports',
                    'view by age reports',
                ],
            ],
            'administration' => [
                'label' => __('permissions.groups.administration'),
                'permissions' => [
                    'view administration dashboard',
                    'manage users',
                    'reset user password',
                    'activate users',
                    'deactivate users',
                    'manage roles',
                    'view audit logs',
                ],
            ],
        ];
    }

    /** @return array<string, string> permission name => sidebar menu label (if any) */
    public static function menuHints(): array
    {
        return [
            'view dashboard' => __('nav.dashboard'),
            'manage applicants' => __('nav.applicants'),
            'register applicant' => __('nav.register_applicant'),
            'view own profile' => __('nav.my_profile'),
            'create loan application' => __('nav.new_application'),
            'view own loans' => __('nav.my_loans'),
            'view loan by track id' => __('nav.track_loan'),
            'view ward loans' => __('nav.loan_applications'),
            'view council loans' => __('nav.loan_applications'),
            'view region loans' => __('nav.loan_applications'),
            'view all loans' => __('nav.loan_applications'),
            'assign accountant' => __('nav.assign_accountant_queue'),
            'disburse loan' => __('nav.my_disbursements'),
            'manage loan groups' => __('nav.loan_groups'),
            'view repayments' => __('nav.repayments'),
            'view reports overview' => __('nav.reports_overview'),
            'view application reports' => __('nav.application_reports'),
            'view payment reports' => __('nav.analytical_overview'),
            'view outstanding reports' => __('nav.analytical_outstanding'),
            'view overdue reports' => __('nav.analytical_overdue'),
            'view by region reports' => __('nav.by_region'),
            'view by type reports' => __('nav.by_types'),
            'view by sector reports' => __('nav.by_sectors'),
            'view by bank reports' => __('nav.by_banks'),
            'view by monthly reports' => __('nav.by_monthly'),
            'view by age reports' => __('nav.by_age'),
            'view administration dashboard' => __('nav.admin_dashboard'),
            'manage users' => __('nav.users'),
            'manage roles' => __('nav.roles'),
            'view audit logs' => __('nav.audit_logs'),
        ];
    }

    public static function allPermissionNames(): array
    {
        return collect(self::groups())
            ->pluck('permissions')
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Ensure every catalog permission exists in the database so Roles UI
     * can show each one as its own checkbox.
     *
     * @return list<string>
     */
    public static function syncToDatabase(): array
    {
        $names = self::allPermissionNames();

        foreach ($names as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        self::migrateLegacyReportPermissions();

        return $names;
    }

    /**
     * Map old broad report permissions onto the new submenu permissions.
     */
    public static function migrateLegacyReportPermissions(): void
    {
        $map = [
            'view reports' => [
                'view reports overview',
                'view application reports',
                'view by region reports',
                'view by type reports',
                'view by sector reports',
                'view by bank reports',
                'view by monthly reports',
                'view by age reports',
            ],
            'view analytical reports' => [
                'view payment reports',
                'view outstanding reports',
                'view overdue reports',
            ],
        ];

        foreach ($map as $legacyName => $replacements) {
            $legacy = Permission::query()
                ->where('name', $legacyName)
                ->where('guard_name', 'web')
                ->first();

            if (! $legacy) {
                continue;
            }

            foreach ($replacements as $name) {
                Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);
            }

            $roleIds = \Illuminate\Support\Facades\DB::table('role_has_permissions')
                ->where('permission_id', $legacy->id)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                $role = \App\Models\Role::query()->find($roleId);
                $role?->givePermissionTo($replacements);
            }

            $modelRows = \Illuminate\Support\Facades\DB::table('model_has_permissions')
                ->where('permission_id', $legacy->id)
                ->get();

            foreach ($modelRows as $row) {
                $model = $row->model_type::query()->find($row->model_id);
                if ($model && method_exists($model, 'givePermissionTo')) {
                    $model->givePermissionTo($replacements);
                }
            }

            $legacy->delete();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public static function orderedPermissions()
    {
        $names = self::syncToDatabase();

        return Permission::query()
            ->whereIn('name', $names)
            ->orderBy('name')
            ->get()
            ->sortBy(fn ($p) => array_search($p->name, $names, true))
            ->values();
    }
}
