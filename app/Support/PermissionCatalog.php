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
                'permissions' => ['view reports'],
            ],
            'administration' => [
                'label' => __('permissions.groups.administration'),
                'permissions' => [
                    'view administration dashboard',
                    'manage users',
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
            'view reports' => __('nav.reports'),
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

        return $names;
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
