<?php

namespace Database\Seeders;

use App\Models\Council;
use App\Models\District;
use App\Models\Region;
use App\Models\Street;
use App\Models\Ward;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/Data/ADMIN_AREAS.csv');

        if (! is_readable($path)) {
            throw new RuntimeException("Admin areas CSV not found or unreadable: {$path}");
        }

        [$regions, $districts, $councils, $wards, $streets] = $this->parseCsv($path);

        DB::transaction(function () use ($regions, $districts, $councils, $wards, $streets) {
            $this->seedRegions($regions);
            $regionIds = Region::query()->pluck('id', 'code');

            $this->seedDistricts($districts, $regionIds);
            $districtIds = District::query()->pluck('id', 'code');

            $this->seedCouncils($councils, $districtIds);
            $councilIds = Council::query()->pluck('id', 'code');

            $this->seedWards($wards, $councilIds, $districtIds);
            $wardIds = Ward::query()->pluck('id', 'code');

            $this->seedStreets($streets, $wardIds);
        });

        $this->command?->info(sprintf(
            'Locations seeded from CSV: %d regions, %d districts, %d councils, %d wards, %d streets/villages.',
            count($regions),
            count($districts),
            count($councils),
            count($wards),
            count($streets),
        ));
    }

    /**
     * @return array{0: array<string, array{name: string, code: string}>, 1: array<string, array{name: string, code: string, region_code: string}>, 2: array<string, array{name: string, code: string, district_code: string}>, 3: array<string, array{name: string, code: string, council_code: string, district_code: string}>, 4: array<string, array{name: string, code: string, ward_code: string}>}
     */
    protected function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Unable to open admin areas CSV: {$path}");
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);
            throw new RuntimeException('Admin areas CSV is empty.');
        }

        $header = array_map(static fn ($column) => trim((string) $column), $header);
        $index = array_flip($header);

        foreach ([
            'region_code', 'region_name',
            'district_code', 'district_name',
            'council_code', 'council_name',
            'ward_code', 'ward_name',
            'village_mtaa_code', 'village_mtaa_name',
        ] as $required) {
            if (! isset($index[$required])) {
                fclose($handle);
                throw new RuntimeException("Admin areas CSV missing required column: {$required}");
            }
        }

        $regions = [];
        $districts = [];
        $councils = [];
        $wards = [];
        $streets = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->rowIsBlank($row)) {
                continue;
            }

            $regionCode = trim((string) ($row[$index['region_code']] ?? ''));
            $regionName = trim((string) ($row[$index['region_name']] ?? ''));
            $districtCode = trim((string) ($row[$index['district_code']] ?? ''));
            $districtName = trim((string) ($row[$index['district_name']] ?? ''));
            $councilCode = trim((string) ($row[$index['council_code']] ?? ''));
            $councilName = trim((string) ($row[$index['council_name']] ?? ''));
            $wardCode = trim((string) ($row[$index['ward_code']] ?? ''));
            $wardName = trim((string) ($row[$index['ward_name']] ?? ''));
            $streetCode = trim((string) ($row[$index['village_mtaa_code']] ?? ''));
            $streetName = trim((string) ($row[$index['village_mtaa_name']] ?? ''));

            if ($regionCode === '' || $regionName === ''
                || $districtCode === '' || $districtName === ''
                || $councilCode === '' || $councilName === ''
                || $wardCode === '' || $wardName === ''
                || $streetCode === '' || $streetName === '') {
                continue;
            }

            $regions[$regionCode] = [
                'name' => $regionName,
                'code' => $regionCode,
            ];

            $districts[$districtCode] = [
                'name' => $districtName,
                'code' => $districtCode,
                'region_code' => $regionCode,
            ];

            $councils[$councilCode] = [
                'name' => $councilName,
                'code' => $councilCode,
                'district_code' => $districtCode,
            ];

            $wards[$wardCode] = [
                'name' => $wardName,
                'code' => $wardCode,
                'council_code' => $councilCode,
                'district_code' => $districtCode,
            ];

            $streets[$streetCode] = [
                'name' => $streetName,
                'code' => $streetCode,
                'ward_code' => $wardCode,
            ];
        }

        fclose($handle);

        return [$regions, $districts, $councils, $wards, $streets];
    }

    /**
     * @param  array<int, string|null>  $row
     */
    protected function rowIsBlank(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, array{name: string, code: string}>  $regions
     */
    protected function seedRegions(array $regions): void
    {
        $existingByCode = Region::query()->whereNotNull('code')->pluck('id', 'code');
        $existingByName = Region::query()->pluck('id', 'name');
        $now = now();
        $rows = [];

        foreach ($regions as $region) {
            if ($existingByCode->has($region['code'])) {
                continue;
            }

            if ($existingByName->has($region['name'])) {
                Region::query()->whereKey($existingByName[$region['name']])->update([
                    'code' => $region['code'],
                    'updated_at' => $now,
                ]);

                continue;
            }

            $rows[] = [
                'name' => $region['name'],
                'code' => $region['code'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->insertChunks(Region::class, $rows);
    }

    /**
     * @param  array<string, array{name: string, code: string, region_code: string}>  $districts
     * @param  \Illuminate\Support\Collection<string, int|string>  $regionIds
     */
    protected function seedDistricts(array $districts, $regionIds): void
    {
        $existingByCode = District::query()->whereNotNull('code')->pluck('id', 'code');
        $existingByParentName = District::query()
            ->get(['id', 'region_id', 'name'])
            ->mapWithKeys(fn (District $district) => [
                $district->region_id.'|'.$district->name => $district->id,
            ]);
        $now = now();
        $rows = [];

        foreach ($districts as $district) {
            $regionId = $regionIds[$district['region_code']] ?? null;

            if ($regionId === null) {
                continue;
            }

            if ($existingByCode->has($district['code'])) {
                continue;
            }

            $parentKey = $regionId.'|'.$district['name'];

            if ($existingByParentName->has($parentKey)) {
                District::query()->whereKey($existingByParentName[$parentKey])->update([
                    'code' => $district['code'],
                    'updated_at' => $now,
                ]);

                continue;
            }

            $rows[] = [
                'region_id' => $regionId,
                'name' => $district['name'],
                'code' => $district['code'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->insertChunks(District::class, $rows);
    }

    /**
     * @param  array<string, array{name: string, code: string, district_code: string}>  $councils
     * @param  \Illuminate\Support\Collection<string, int|string>  $districtIds
     */
    protected function seedCouncils(array $councils, $districtIds): void
    {
        $existingByCode = Council::query()->whereNotNull('code')->pluck('id', 'code');
        $existingByParentName = Council::query()
            ->get(['id', 'district_id', 'name'])
            ->mapWithKeys(fn (Council $council) => [
                $council->district_id.'|'.$council->name => $council->id,
            ]);
        $now = now();
        $rows = [];

        foreach ($councils as $council) {
            $districtId = $districtIds[$council['district_code']] ?? null;

            if ($districtId === null) {
                continue;
            }

            if ($existingByCode->has($council['code'])) {
                continue;
            }

            $parentKey = $districtId.'|'.$council['name'];

            if ($existingByParentName->has($parentKey)) {
                Council::query()->whereKey($existingByParentName[$parentKey])->update([
                    'code' => $council['code'],
                    'updated_at' => $now,
                ]);

                continue;
            }

            $rows[] = [
                'district_id' => $districtId,
                'name' => $council['name'],
                'code' => $council['code'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->insertChunks(Council::class, $rows);
    }

    /**
     * @param  array<string, array{name: string, code: string, council_code: string, district_code: string}>  $wards
     * @param  \Illuminate\Support\Collection<string, int|string>  $councilIds
     * @param  \Illuminate\Support\Collection<string, int|string>  $districtIds
     */
    protected function seedWards(array $wards, $councilIds, $districtIds): void
    {
        $existingByCode = Ward::query()->whereNotNull('code')->pluck('id', 'code');
        $existingByParentName = Ward::query()
            ->get(['id', 'council_id', 'name'])
            ->mapWithKeys(fn (Ward $ward) => [
                $ward->council_id.'|'.$ward->name => $ward->id,
            ]);
        $now = now();
        $rows = [];

        foreach ($wards as $ward) {
            $councilId = $councilIds[$ward['council_code']] ?? null;
            $districtId = $districtIds[$ward['district_code']] ?? null;

            if ($councilId === null || $districtId === null) {
                continue;
            }

            if ($existingByCode->has($ward['code'])) {
                continue;
            }

            $parentKey = $councilId.'|'.$ward['name'];

            if ($existingByParentName->has($parentKey)) {
                Ward::query()->whereKey($existingByParentName[$parentKey])->update([
                    'code' => $ward['code'],
                    'district_id' => $districtId,
                    'updated_at' => $now,
                ]);

                continue;
            }

            $rows[] = [
                'council_id' => $councilId,
                'district_id' => $districtId,
                'name' => $ward['name'],
                'code' => $ward['code'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->insertChunks(Ward::class, $rows);
    }

    /**
     * @param  array<string, array{name: string, code: string, ward_code: string}>  $streets
     * @param  \Illuminate\Support\Collection<string, int|string>  $wardIds
     */
    protected function seedStreets(array $streets, $wardIds): void
    {
        $existingByCode = Street::query()->whereNotNull('code')->pluck('id', 'code');
        $existingByParentName = Street::query()
            ->get(['id', 'ward_id', 'name'])
            ->mapWithKeys(fn (Street $street) => [
                $street->ward_id.'|'.$street->name => $street->id,
            ]);
        $now = now();
        $rows = [];

        foreach ($streets as $street) {
            $wardId = $wardIds[$street['ward_code']] ?? null;

            if ($wardId === null) {
                continue;
            }

            if ($existingByCode->has($street['code'])) {
                continue;
            }

            $parentKey = $wardId.'|'.$street['name'];

            if ($existingByParentName->has($parentKey)) {
                Street::query()->whereKey($existingByParentName[$parentKey])->update([
                    'code' => $street['code'],
                    'updated_at' => $now,
                ]);

                continue;
            }

            $rows[] = [
                'ward_id' => $wardId,
                'name' => $street['name'],
                'code' => $street['code'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->insertChunks(Street::class, $rows);
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function insertChunks(string $model, array $rows): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            $model::query()->insert($chunk);
        }
    }
}
