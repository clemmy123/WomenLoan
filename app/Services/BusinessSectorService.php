<?php

namespace App\Services;

use App\Models\BusinessSector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BusinessSectorService
{
    private const TTL = 3600;

    public function sectors(): Collection
    {
        return Cache::remember('business_sectors.all', self::TTL, function () {
            return BusinessSector::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'sort_order']);
        });
    }

    /**
     * @return list<array{id: int, name: string, types: list<array{id: int, name: string}>}>
     */
    public function wizardCatalog(): array
    {
        return Cache::remember('business_sectors.wizard_catalog', self::TTL, function () {
            return BusinessSector::query()
                ->with(['businessTypes' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (BusinessSector $sector) => [
                    'id' => $sector->id,
                    'name' => $sector->name,
                    'types' => $sector->businessTypes
                        ->map(fn ($type) => ['id' => $type->id, 'name' => $type->name])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all();
        });
    }
}
