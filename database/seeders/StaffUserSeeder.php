<?php

namespace Database\Seeders;

use App\Models\Concerns\HasDisplayName;
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
            ['email' => 'admin@wdf.go.tz', 'check' => '1000000001', 'first' => 'System', 'middle' => null, 'last' => 'Administrator', 'phone' => '255700000000', 'role' => 'super_admin'],
            ['email' => 'ward.cdo@wdf.go.tz', 'check' => '1000000002', 'first' => 'Grace', 'middle' => null, 'last' => 'Mwangi', 'phone' => '255712345001', 'role' => 'cdo_ward', 'zone' => 'ward'],
            ['email' => 'council.cdo@wdf.go.tz', 'check' => '1000000003', 'first' => 'John', 'middle' => null, 'last' => 'Massawe', 'phone' => '255712345002', 'role' => 'cdo_council', 'zone' => 'council'],
            ['email' => 'region.cdo@wdf.go.tz', 'check' => '1000000004', 'first' => 'Mary', 'middle' => null, 'last' => 'Lyimo', 'phone' => '255712345003', 'role' => 'cdo_region', 'zone' => 'region'],
            ['email' => 'ministry@wdf.go.tz', 'check' => '1000000005', 'first' => 'Amina', 'middle' => null, 'last' => 'Hassan', 'phone' => '255712345004', 'role' => 'cdo_ministry'],
            ['email' => 'assdir@wdf.go.tz', 'check' => '1000000006', 'first' => 'Peter', 'middle' => null, 'last' => 'Kileo', 'phone' => '255712345005', 'role' => 'assistant_director'],
            ['email' => 'director@wdf.go.tz', 'check' => '1000000007', 'first' => 'Elizabeth', 'middle' => null, 'last' => 'Mrema', 'phone' => '255712345006', 'role' => 'director'],
            ['email' => 'km@wdf.go.tz', 'check' => '1000000008', 'first' => 'Neema', 'middle' => null, 'last' => 'Kapinga', 'phone' => '255712345007', 'role' => 'km'],
            ['email' => 'chief@wdf.go.tz', 'check' => '1000000009', 'first' => 'James', 'middle' => null, 'last' => 'Mdoe', 'phone' => '255712345008', 'role' => 'chief'],
            ['email' => 'accountant1@wdf.go.tz', 'check' => '1000000010', 'first' => 'Sarah', 'middle' => null, 'last' => 'Ngowi', 'phone' => '255712345009', 'role' => 'accountant'],
            ['email' => 'accountant2@wdf.go.tz', 'check' => '1000000011', 'first' => 'David', 'middle' => null, 'last' => 'Mushi', 'phone' => '255712345010', 'role' => 'accountant'],
        ];

        $dodomaRegion = Region::where('name', 'Dodoma')->first();
        $dodomaCouncil = Council::where('name', 'Dodoma Jiji')->first();
        $tambukareliWard = Ward::where('name', 'Tambukareli')->first();

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'check_number' => $data['check'],
                    'first_name' => $data['first'],
                    'middle_name' => $data['middle'],
                    'last_name' => $data['last'],
                    'name' => HasDisplayName::buildFullName($data['first'], $data['middle'], $data['last']),
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
