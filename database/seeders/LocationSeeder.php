<?php

namespace Database\Seeders;

use App\Models\Council;
use App\Models\District;
use App\Models\Region;
use App\Models\Street;
use App\Models\Ward;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            'Dodoma' => [
                'district' => 'Dodoma Mjini',
                'council' => ['name' => 'Dodoma Jiji', 'code' => 'CC'],
                'ward' => 'Tambukareli',
                'streets' => ['Uhuru', 'Kupro'],
            ],
            'Dar es Salaam' => [
                'district' => 'Kinondoni',
                'council' => ['name' => 'Kinondoni Manispaa', 'code' => 'MC'],
                'ward' => 'Hananasif',
                'streets' => ['Kawawa', 'Mkunguni'],
            ],
            'Arusha' => [
                'district' => 'Arusha Mjini',
                'council' => ['name' => 'Arusha Jiji', 'code' => 'CC'],
                'ward' => 'Sekerei',
                'streets' => ['AICC', 'Kaloleni'],
            ],
            'Mwanza' => [
                'district' => 'Nyamagana',
                'council' => ['name' => 'Mwanza Jiji', 'code' => 'CC'],
                'ward' => 'Nyanza',
                'streets' => ['Kenyatta Road', 'Pamba Road'],
            ],
            'Mbeya' => [
                'district' => 'Mbeya Mjini',
                'council' => ['name' => 'Mbeya Jiji', 'code' => 'CC'],
                'ward' => 'Sisimba',
                'streets' => ['Soko Matola', 'Igawilo'],
            ],
        ];

        foreach ($locations as $regionName => $data) {
            $region = Region::firstOrCreate(['name' => $regionName]);

            $district = District::firstOrCreate(
                ['region_id' => $region->id, 'name' => $data['district']]
            );

            $council = Council::firstOrCreate(
                ['district_id' => $district->id, 'name' => $data['council']['name']],
                ['code' => $data['council']['code']]
            );

            $ward = Ward::firstOrCreate(
                ['council_id' => $council->id, 'name' => $data['ward']],
                ['district_id' => $district->id]
            );

            foreach ($data['streets'] as $streetName) {
                Street::firstOrCreate(
                    ['ward_id' => $ward->id, 'name' => $streetName]
                );
            }
        }
    }
}
