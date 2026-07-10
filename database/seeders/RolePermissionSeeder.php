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

        $rolePermissions = [
            'super_admin' => PermissionCatalog::allPermissionNames(),
            'admin' => [
                'view dashboard', 'manage applicants', 'register applicant', 'view all loans',
                'view repayments', 'view reports', 'rollback workflow step',
                'view administration dashboard', 'manage users', 'manage roles', 'view audit logs',
                'manage loan groups', 'view loan by track id',
            ],
            'applicant' => [
                'view dashboard', 'view own profile', 'create loan application', 'view own loans',
                'edit pending loan', 'accept loan amount', 'view loan by track id', 'view repayments',
                'record repayment',
            ],
            'cdo_ward' => [
                'view dashboard', 'view ward loans', 'receive application', 'review ward application',
                'forward to ministry', 'rollback workflow step', 'view loan by track id', 'view repayments', 'view reports',
            ],
            'cdo_council' => [
                'view dashboard', 'view council loans', 'view loan by track id', 'view repayments', 'view reports',
            ],
            'cdo_region' => [
                'view dashboard', 'view region loans', 'view loan by track id', 'view repayments', 'view reports',
            ],
            'cdo_ministry' => [
                'view dashboard', 'view all loans', 'review ministry application', 'propose loan amount',
                'send to applicant confirmation', 'forward to assistant director', 'rollback workflow step',
                'view loan by track id', 'view repayments', 'view reports',
            ],
            'assistant_director' => [
                'view dashboard', 'view all loans', 'comment as assistant director', 'forward to director',
                'rollback workflow step', 'view loan by track id', 'view repayments', 'view reports',
            ],
            'director' => [
                'view dashboard', 'view all loans', 'comment as director', 'forward to km',
                'rollback workflow step', 'view loan by track id', 'view repayments', 'view reports',
            ],
            'km' => [
                'view dashboard', 'view all loans', 'approve as km', 'rollback workflow step',
                'view loan by track id', 'view repayments', 'view reports',
            ],
            'chief' => [
                'view dashboard', 'assign accountant',
                'view loan by track id', 'view repayments', 'view reports',
            ],
            'accountant' => [
                'view dashboard', 'disburse loan',
                'view loan by track id', 'view repayments', 'view reports',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
