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
                'name' => 'Test Applicant',
                'phone' => '255711111111',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $applicant->syncRoles(['applicant']);
    }
}
