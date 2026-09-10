<?php

namespace Database\Seeders;

use App\Models\BusinessSector;
use App\Models\BusinessType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class BusinessSectorSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = $this->catalog();

        foreach ($catalog as $sortOrder => [$sectorName, $types]) {
            $sector = BusinessSector::updateOrCreate(
                ['name' => $sectorName],
                ['sort_order' => $sortOrder + 1],
            );

            foreach ($types as $typeOrder => $typeName) {
                BusinessType::updateOrCreate(
                    [
                        'business_sector_id' => $sector->id,
                        'name' => $typeName,
                    ],
                    ['sort_order' => $typeOrder + 1],
                );
            }
        }

        Cache::forget('business_sectors.all');
        Cache::forget('business_sectors.wizard_catalog');
    }

    /**
     * @return list<array{0: string, 1: list<string>}>
     */
    private function catalog(): array
    {
        $path = database_path('seeders/Data/sectors.php');

        if (! is_readable($path)) {
            throw new RuntimeException("Sectors catalog not found or unreadable: {$path}");
        }

        $catalog = require $path;

        if (! is_array($catalog) || $catalog === []) {
            throw new RuntimeException("Sectors catalog is empty: {$path}");
        }

        return $catalog;
    }
}
