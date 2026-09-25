<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => PurgeOperationalDataSeeder::SUPER_ADMIN_EMAIL],
            [
                'check_number' => '1000000001',
                'first_name' => 'System',
                'middle_name' => null,
                'last_name' => 'Administrator',
                'name' => 'System Administrator',
                'phone' => '255700000000',
                'password' => 'password',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        $admin->forceFill([
            'is_active' => true,
            'must_change_password' => false,
        ])->save();

        $admin->syncRoles(['super_admin']);
    }
}
