<?php

namespace Database\Seeders;

use App\Models\Council;
use App\Models\Region;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $users = [
            ['email' => 'admin@wdf.go.tz', 'name' => 'System Administrator', 'phone' => '255700000000', 'role' => 'super_admin'],
            ['email' => 'ward.cdo@wdf.go.tz', 'name' => 'Grace Mwangi (CDO Ward)', 'phone' => '255712345001', 'role' => 'cdo_ward', 'zone' => 'ward'],
            ['email' => 'council.cdo@wdf.go.tz', 'name' => 'John Massawe (CDO Council)', 'phone' => '255712345002', 'role' => 'cdo_council', 'zone' => 'council'],
            ['email' => 'region.cdo@wdf.go.tz', 'name' => 'Mary Lyimo (CDO Region)', 'phone' => '255712345003', 'role' => 'cdo_region', 'zone' => 'region'],
            ['email' => 'ministry@wdf.go.tz', 'name' => 'Dr. Amina Hassan (Ministry)', 'phone' => '255712345004', 'role' => 'cdo_ministry'],
            ['email' => 'assdir@wdf.go.tz', 'name' => 'Peter Kileo (Ass. Director)', 'phone' => '255712345005', 'role' => 'assistant_director'],
            ['email' => 'director@wdf.go.tz', 'name' => 'Elizabeth Mrema (Director)', 'phone' => '255712345006', 'role' => 'director'],
            ['email' => 'km@wdf.go.tz', 'name' => 'Prof. Neema Kapinga (KM)', 'phone' => '255712345007', 'role' => 'km'],
            ['email' => 'chief@wdf.go.tz', 'name' => 'James Mdoe (Chief)', 'phone' => '255712345008', 'role' => 'chief'],
            ['email' => 'accountant1@wdf.go.tz', 'name' => 'Sarah Ngowi (Accountant)', 'phone' => '255712345009', 'role' => 'accountant'],
            ['email' => 'accountant2@wdf.go.tz', 'name' => 'David Mushi (Accountant)', 'phone' => '255712345010', 'role' => 'accountant'],
        ];

        $dodomaRegion = Region::where('name', 'Dodoma')->first();
        $dodomaCouncil = Council::where('name', 'Dodoma Jiji')->first();
        $tambukareliWard = Ward::where('name', 'Tambukareli')->first();

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => $password,
                    'is_active' => true,
                ]
            );

            if (($data['zone'] ?? null) === 'ward' && $tambukareliWard) {
                $user->update(['zoneable_type' => Ward::class, 'zoneable_id' => $tambukareliWard->id]);
            } elseif (($data['zone'] ?? null) === 'council' && $dodomaCouncil) {
                $user->update(['zoneable_type' => Council::class, 'zoneable_id' => $dodomaCouncil->id]);
            } elseif (($data['zone'] ?? null) === 'region' && $dodomaRegion) {
                $user->update(['zoneable_type' => Region::class, 'zoneable_id' => $dodomaRegion->id]);
            }

            $user->syncRoles([$data['role']]);
        }
    }
}
