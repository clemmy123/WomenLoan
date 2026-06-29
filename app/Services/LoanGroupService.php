<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\LoanGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LoanGroupService
{
    public function paginated(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return LoanGroup::query()
            ->withCount(['applicants', 'loans'])
            ->search($search, ['name', 'registration_number'])
            ->latest()
            ->paginate($perPage);
    }

    public function eligibleApplicants(?int $regionId = null): Collection
    {
        return Applicant::query()
            ->when($regionId, fn ($q) => $q->inRegion($regionId))
            ->whereDoesntHave('groups')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'nin', 'first_name', 'middle_name', 'last_name']);
    }

    public function editableApplicants(LoanGroup $group, ?int $regionId = null): Collection
    {
        return Applicant::query()
            ->when($regionId, fn ($q) => $q->inRegion($regionId))
            ->where(function ($query) use ($group) {
                $query->whereDoesntHave('groups')
                    ->orWhereHas('groups', fn ($q) => $q->where('loan_groups.id', $group->id));
            })
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'nin', 'first_name', 'middle_name', 'last_name']);
    }

    public function create(array $validated, array $memberIds = []): LoanGroup
    {
        $group = LoanGroup::create(collect($validated)->only([
            'name', 'registration_number', 'phone', 'email',
        ])->all());

        if ($memberIds) {
            $group->applicants()->sync($memberIds);
        }

        return $group;
    }

    public function update(LoanGroup $group, array $validated, ?array $memberIds = null): LoanGroup
    {
        $group->update(collect($validated)->only([
            'name', 'registration_number', 'phone', 'email',
        ])->all());

        if ($memberIds !== null) {
            $group->applicants()->sync($memberIds);
        }

        return $group;
    }

    public function delete(LoanGroup $group): void
    {
        $group->applicants()->detach();
        $group->delete();
    }
}
