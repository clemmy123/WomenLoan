<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // One source of truth: every catalog permission is created individually
        // so Roles & Permissions UI can tick them one by one.
        PermissionCatalog::syncToDatabase();

        $allReportPermissions = [
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
        ];

        $rolePermissions = [
            'super_admin' => PermissionCatalog::allPermissionNames(),
            'admin' => [
                'view dashboard', 'manage applicants', 'register applicant', 'view all loans',
                'view repayments', 'rollback workflow step',
                ...$allReportPermissions,
                'view administration dashboard', 'manage users', 'reset user password',
                'activate users', 'deactivate users', 'manage roles', 'view audit logs',
                'manage loan groups', 'view loan by track id',
            ],
            'applicant' => [
                'view dashboard', 'view own profile', 'create loan application', 'view own loans',
                'edit pending loan', 'accept loan amount', 'view loan by track id', 'view repayments',
                'record repayment',
            ],
            'cdo_ward' => [
                'view dashboard', 'view ward loans', 'receive application', 'review ward application',
                'forward to council', 'rollback workflow step', 'view loan by track id', 'view repayments',
                ...$allReportPermissions,
            ],
            'cdo_council' => [
                'view dashboard', 'view council loans', 'review council application', 'forward to ministry',
                'rollback workflow step', 'view loan by track id', 'view repayments',
                ...$allReportPermissions,
            ],
            'cdo_region' => [
                'view dashboard', 'view region loans', 'view loan by track id', 'view repayments',
                ...$allReportPermissions,
            ],
            'cdo_ministry' => [
                'view dashboard', 'view all loans', 'review ministry application', 'propose loan amount',
                'send to applicant confirmation', 'forward to assistant director', 'rollback workflow step',
                'view loan by track id', 'view repayments',
                ...$allReportPermissions,
            ],
            'assistant_director' => [
                'view dashboard', 'view all loans', 'comment as assistant director', 'forward to director',
                'rollback workflow step', 'view loan by track id', 'view repayments',
                ...$allReportPermissions,
            ],
            'director' => [
                'view dashboard', 'view all loans', 'comment as director', 'forward to km',
                'rollback workflow step', 'view loan by track id', 'view repayments',
                ...$allReportPermissions,
            ],
            'km' => [
                'view dashboard', 'view all loans', 'approve as km', 'rollback workflow step',
                'view loan by track id', 'view repayments',
                ...$allReportPermissions,
            ],
            'chief' => [
                'view dashboard', 'assign accountant',
                'view loan by track id', 'view repayments',
                ...$allReportPermissions,
            ],
            'accountant' => [
                'view dashboard', 'disburse loan',
                'view loan by track id', 'view repayments',
                ...$allReportPermissions,
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
