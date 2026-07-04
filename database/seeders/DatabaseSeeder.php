<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed order: permissions → geography → staff → sample data.
     * Run: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            LocationSeeder::class,
            BusinessSectorSeeder::class,
            StaffUserSeeder::class,
            DummyDataSeeder::class,
        ]);
    }
}
