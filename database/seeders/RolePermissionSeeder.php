<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'manage applicants',
            'register applicant',
            'view own profile',
            'create loan application',
            'view own loans',
            'edit pending loan',
            'accept loan amount',
            'view loan by track id',
            'view ward loans',
            'receive application',
            'review ward application',
            'forward to ministry',
            'view council loans',
            'view region loans',
            'view all loans',
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
            'record repayment',
            'view repayments',
            'view reports',
            'manage users',
            'manage roles',
            'manage loan groups',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $rolePermissions = [
            'super_admin' => Permission::all()->pluck('name')->toArray(),
            'admin' => [
                'view dashboard', 'manage applicants', 'register applicant', 'view all loans',
                'view repayments', 'record repayment', 'view reports', 'rollback workflow step',
                'manage users', 'manage roles', 'manage loan groups',
                'view loan by track id',
            ],
            'applicant' => [
                'view dashboard', 'view own profile', 'create loan application', 'view own loans',
                'edit pending loan', 'accept loan amount', 'view loan by track id', 'view repayments',
                'record repayment',
            ],
            'cdo_ward' => [
                'view dashboard', 'view ward loans', 'receive application', 'review ward application',
                'forward to ministry', 'view loan by track id', 'view repayments', 'view reports',
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
                'view dashboard', 'view all loans', 'assign accountant', 'rollback workflow step',
                'view loan by track id', 'view repayments', 'view reports',
            ],
            'accountant' => [
                'view dashboard', 'view all loans', 'disburse loan', 'record repayment',
                'rollback workflow step', 'view loan by track id', 'view repayments', 'view reports',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
