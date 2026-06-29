<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\District;
use App\Models\Council;
use App\Models\Ward;
use App\Models\Street;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==========================================
        // 1. DODOMA REGION
        // ==========================================
        $dodoma = Region::create(['name' => 'Dodoma']);
        
        $domDistrict = District::create(['region_id' => $dodoma->id, 'name' => 'Dodoma Mjini']);
        $domCouncil = Council::create(['district_id' => $domDistrict->id, 'name' => 'Dodoma Jiji', 'code' => 'CC']);
        
        // FIXED: Explicitly injected district_id to satisfy structural schema constraints
        $tambukareli = Ward::create([
            'council_id' => $domCouncil->id, 
            'district_id' => $domDistrict->id, 
            'name' => 'Tambukareli'
        ]);
        Street::create(['ward_id' => $tambukareli->id, 'name' => 'Uhuru']);
        Street::create(['ward_id' => $tambukareli->id, 'name' => 'Kupro']);

        // ==========================================
        // 2. DAR ES SALAAM REGION
        // ==========================================
        $dar = Region::create(['name' => 'Dar es Salaam']);
        
        $kinondoniDistrict = District::create(['region_id' => $dar->id, 'name' => 'Kinondoni']);
        $kinondoniCouncil = Council::create(['district_id' => $kinondoniDistrict->id, 'name' => 'Kinondoni Manispaa', 'code' => 'MC']);
        
        // FIXED: Explicitly injected district_id to satisfy structural schema constraints
        $hananasif = Ward::create([
            'council_id' => $kinondoniCouncil->id, 
            'district_id' => $kinondoniDistrict->id, 
            'name' => 'Hananasif'
        ]);
        Street::create(['ward_id' => $hananasif->id, 'name' => 'Kawawa']);
        Street::create(['ward_id' => $hananasif->id, 'name' => 'Mkunguni']);

        // ==========================================
        // 3. ARUSHA REGION
        // ==========================================
        $arusha = Region::create(['name' => 'Arusha']);
        
        $arushaDistrict = District::create(['region_id' => $arusha->id, 'name' => 'Arusha Mjini']);
        $arushaCouncil = Council::create(['district_id' => $arushaDistrict->id, 'name' => 'Arusha Jiji', 'code' => 'CC']);
        
        // FIXED: Explicitly injected district_id to satisfy structural schema constraints
        $sekerei = Ward::create([
            'council_id' => $arushaCouncil->id, 
            'district_id' => $arushaDistrict->id, 
            'name' => 'Sekerei'
        ]);
        Street::create(['ward_id' => $sekerei->id, 'name' => 'AICC']);

        // ==========================================
        // 4. MWANZA REGION
        // ==========================================
        $mwanza = Region::create(['name' => 'Mwanza']);
        
        $nyamaganaDistrict = District::create(['region_id' => $mwanza->id, 'name' => 'Nyamagana']);
        $mwanzaCouncil = Council::create(['district_id' => $nyamaganaDistrict->id, 'name' => 'Mwanza Jiji', 'code' => 'CC']);
        
        // FIXED: Explicitly injected district_id to satisfy structural schema constraints
        $nyanza = Ward::create([
            'council_id' => $mwanzaCouncil->id, 
            'district_id' => $nyamaganaDistrict->id, 
            'name' => 'Nyanza'
        ]);
        Street::create(['ward_id' => $nyanza->id, 'name' => 'Kenyatta Road']);

        // ==========================================
        // 5. MBEYA REGION
        // ==========================================
        $mbeya = Region::create(['name' => 'Mbeya']);
        
        $mbeyaDistrict = District::create(['region_id' => $mbeya->id, 'name' => 'Mbeya Mjini']);
        $mbeyaCouncil = Council::create(['district_id' => $mbeyaDistrict->id, 'name' => 'Mbeya Jiji', 'code' => 'CC']);
        
        // FIXED: Explicitly injected district_id to satisfy structural schema constraints
        $sisimba = Ward::create([
            'council_id' => $mbeyaCouncil->id, 
            'district_id' => $mbeyaDistrict->id, 
            'name' => 'Sisimba'
        ]);
        Street::create(['ward_id' => $sisimba->id, 'name' => 'Soko Matola']);
    }
}