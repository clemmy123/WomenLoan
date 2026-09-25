<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Production seed: roles, geography, sectors, then empty operational data.
     * Super admin: admin@wdf.go.tz only. Demo staff/loans are test-only
     * (StaffUserSeeder + DummyDataSeeder).
     *
     * Run: php artisan db:seed
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            LocationSeeder::class,
            BusinessSectorSeeder::class,
            PurgeOperationalDataSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
