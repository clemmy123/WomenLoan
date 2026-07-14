<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Concerns\HasDisplayName;
use App\Models\LoanGroup;
use App\Models\User;
use App\Support\IdentityNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ApplicantService
{
    public function __construct(private GeoHierarchyService $geo) {}

    public function paginated(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return Applicant::query()
            ->select([
                'id', 'first_name', 'middle_name', 'last_name', 'full_name',
                'nin', 'dob', 'phone', 'email', 'sex', 'marital_status', 'location_id',
            ])
            ->withCount(['loans', 'groups'])
            ->search($search, [
                'first_name', 'last_name', 'full_name', 'nin', 'phone', 'email',
            ])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function registrationFieldDefaults(User $user): array
    {
        $nameParts = HasDisplayName::splitFullName($user->name ?? '');

        if (filled($user->nin)) {
            $defaults = [
                'first_name' => $user->first_name ?: $nameParts['first_name'],
                'middle_name' => $user->middle_name ?: $nameParts['middle_name'],
                'last_name' => $user->last_name ?: $nameParts['last_name'],
                'email' => $user->email,
                'phone' => IdentityNormalizer::normalizePhone($user->phone),
                'nin' => IdentityNormalizer::normalizeNin($user->nin),
                'dob' => $user->dob?->format('Y-m-d'),
                'sex' => $user->sex ?: 'Female',
                'nationality' => $user->nationality ?: 'Tanzanian',
                'photo_path' => $user->nida_photo_path,
                'nida_verified' => $user->nida_verified_at !== null,
                'nida_verified_at' => $user->nida_verified_at,
            ];

            return $defaults;
        }

        return [
            'first_name' => $nameParts['first_name'] ?: $user->first_name,
            'middle_name' => $nameParts['middle_name'] ?: $user->middle_name,
            'last_name' => $nameParts['last_name'] ?: $user->last_name,
            'email' => $user->email,
            'phone' => IdentityNormalizer::normalizePhone($user->phone),
        ];
    }

    public function draftFromUser(User $user): Applicant
    {
        return new Applicant($this->registrationFieldDefaults($user));
    }

    public function create(array $validated, ?int $userId = null): Applicant
    {
        $validated['full_name'] = HasDisplayName::buildFullName(
            $validated['first_name'],
            $validated['middle_name'] ?? null,
            $validated['last_name']
        );
        $validated['user_id'] = $userId ?? auth()->id();
        $validated['nationality'] = $validated['nationality'] ?? 'Tanzanian';
        $validated['postal_code'] = filled($validated['postal_code'] ?? null) ? $validated['postal_code'] : null;
        $validated['po_box'] = filled($validated['po_box'] ?? null) ? $validated['po_box'] : null;
        $validated['has_disability'] = (bool) ($validated['has_disability'] ?? false);
        $validated['phone'] = IdentityNormalizer::normalizePhone($validated['phone'] ?? '');
        $validated['nin'] = IdentityNormalizer::normalizeNin($validated['nin'] ?? '');
        $validated['email'] = IdentityNormalizer::normalizeEmail($validated['email'] ?? '');

        $user = auth()->user();
        if ($user && filled($user->nin) && $user->nin === $validated['nin']) {
            $validated['photo_path'] = $validated['photo_path'] ?? $user->nida_photo_path;
            if ($user->nida_verified_at) {
                $validated['nida_verified'] = true;
                $validated['nida_verified_at'] = $user->nida_verified_at;
            }
        }

        return Applicant::create($validated);
    }

    public function update(Applicant $applicant, array $validated): Applicant
    {
        $validated['full_name'] = HasDisplayName::buildFullName(
            $validated['first_name'],
            $validated['middle_name'] ?? null,
            $validated['last_name']
        );
        $validated['phone'] = IdentityNormalizer::normalizePhone($validated['phone'] ?? '');
        $validated['nin'] = IdentityNormalizer::normalizeNin($validated['nin'] ?? '');
        $validated['email'] = IdentityNormalizer::normalizeEmail($validated['email'] ?? '');
        $validated['postal_code'] = filled($validated['postal_code'] ?? null) ? $validated['postal_code'] : null;
        $validated['po_box'] = filled($validated['po_box'] ?? null) ? $validated['po_box'] : null;
        $validated['has_disability'] = (bool) ($validated['has_disability'] ?? false);

        $applicant->update($validated);

        return $applicant;
    }

    public function locationContext(Applicant $applicant): array
    {
        return array_merge(
            ['applicant' => $applicant, 'regions' => $this->geo->regions()],
            $this->geo->resolveLocationChain($applicant)
        );
    }

    public function attachToGroup(Applicant $applicant, LoanGroup $group): void
    {
        if ($applicant->groups()->where('loan_groups.id', '!=', $group->id)->exists()) {
            throw new \RuntimeException('applicant_already_in_group');
        }

        $applicant->groups()->syncWithoutDetaching([$group->id]);
    }

    public function detachFromGroup(Applicant $applicant, LoanGroup $group): void
    {
        $applicant->groups()->detach($group->id);
    }
}
