<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@wdf.go.tz'],
            [
                'check_number' => '1000000001',
                'first_name' => 'System',
                'middle_name' => null,
                'last_name' => 'Administrator',
                'name' => 'System Administrator',
                'phone' => '255700000000',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $admin->syncRoles(['super_admin']);

        $applicant = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'first_name' => 'Test',
                'middle_name' => null,
                'last_name' => 'Applicant',
                'name' => 'Test Applicant',
                'phone' => '255711111111',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $applicant->syncRoles(['applicant']);
    }
}
