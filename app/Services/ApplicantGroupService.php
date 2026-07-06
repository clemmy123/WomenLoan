<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Concerns\HasDisplayName;
use App\Models\LoanGroup;
use App\Models\LoanGroupMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApplicantGroupService
{
    public function __construct(private LoanQueryService $loans) {}

    public function groupForUser(User $user): ?LoanGroup
    {
        $applicantId = $user->applicant?->id;

        if (! $applicantId) {
            return null;
        }

        return LoanGroup::query()
            ->with(['members'])
            ->whereHas('applicants', fn ($q) => $q->where('applicants.id', $applicantId))
            ->first();
    }

    public function canSetupGroup(User $user): bool
    {
        if (! $user->applicant || $this->loans->userHasLoanApplication($user)) {
            return false;
        }

        if (! $user->applicant->prefersGroupLoan()) {
            return false;
        }

        return ! $this->groupForUser($user);
    }

    public function setup(User $user, array $groupData, array $memberRows): LoanGroup
    {
        $applicant = $user->applicant;

        if (! $applicant) {
            throw new \RuntimeException('applicant_profile_required');
        }

        if (! $this->canSetupGroup($user)) {
            throw new \RuntimeException('group_already_exists');
        }

        return DB::transaction(function () use ($user, $applicant, $groupData, $memberRows) {
            $group = LoanGroup::create([
                'name' => $groupData['name'],
                'registration_number' => $groupData['registration_number'] ?? null,
                'phone' => $groupData['phone'] ?? null,
                'email' => $groupData['email'] ?? null,
                'created_by_user_id' => $user->id,
                'setup_completed_at' => now(),
            ]);

            $this->createLeaderMember($group, $applicant, $groupData['leader'] ?? []);

            foreach ($memberRows as $row) {
                if ($this->isLeaderRow($row, $applicant)) {
                    continue;
                }

                $this->createMember($group, $row);
            }

            $applicant->groups()->attach($group->id);

            return $group->load('members');
        });
    }

    public function userCanAccessGroup(User $user, LoanGroup $group): bool
    {
        $applicantId = $user->applicant?->id;

        if (! $applicantId) {
            return false;
        }

        return $group->applicants()->where('applicants.id', $applicantId)->exists();
    }

    public function userCanManageGroup(User $user, LoanGroup $group): bool
    {
        return $this->userCanAccessGroup($user, $group)
            && (int) $group->created_by_user_id === (int) $user->id;
    }

    public function updateMember(User $user, LoanGroupMember $member, array $data): LoanGroupMember
    {
        $group = $member->group;

        if (! $this->userCanManageGroup($user, $group)) {
            abort(403);
        }

        if ($member->is_group_leader) {
            $member->update([
                'dob' => $data['dob'],
                'sex' => $data['sex'],
                'leadership_role' => $this->leadershipRoleFromRow($data),
            ]);

            return $member->fresh();
        }

        $member->update([
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'full_name' => HasDisplayName::buildFullName(
                $data['first_name'],
                $data['middle_name'] ?? null,
                $data['last_name'],
            ),
            'nin' => $data['nin'],
            'dob' => $data['dob'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'sex' => $data['sex'],
            'marital_status' => $data['marital_status'],
            'leadership_role' => $this->leadershipRoleFromRow($data),
        ]);

        return $member->fresh();
    }

    public function addMember(User $user, array $row): LoanGroupMember
    {
        $group = $this->groupForUser($user);

        if (! $group || ! $this->userCanManageGroup($user, $group)) {
            abort(403);
        }

        return $this->createMember($group, $row);
    }

    public function removeMember(User $user, LoanGroupMember $member): void
    {
        if ($member->is_group_leader) {
            throw new \RuntimeException('cannot_remove_leader');
        }

        if (! $this->userCanManageGroup($user, $member->group)) {
            abort(403);
        }

        $member->delete();
    }

    public function assertMemberBelongsToUserGroup(User $user, LoanGroupMember $member): void
    {
        $group = $this->groupForUser($user);

        if (! $group || (int) $member->loan_group_id !== (int) $group->id) {
            abort(404);
        }
    }

    protected function createLeaderMember(LoanGroup $group, Applicant $applicant, array $leader): LoanGroupMember
    {
        return LoanGroupMember::create([
            'loan_group_id' => $group->id,
            'applicant_id' => $applicant->id,
            'first_name' => $applicant->first_name,
            'middle_name' => $applicant->middle_name,
            'last_name' => $applicant->last_name,
            'full_name' => $applicant->full_name,
            'nin' => $applicant->nin,
            'dob' => $leader['dob'] ?? $applicant->dob,
            'email' => $applicant->email,
            'phone' => $applicant->phone,
            'sex' => $leader['sex'] ?? $applicant->sex,
            'marital_status' => $applicant->marital_status,
            'is_group_leader' => true,
            'leadership_role' => $this->leadershipRoleFromRow($leader),
        ]);
    }

    protected function createMember(LoanGroup $group, array $row): LoanGroupMember
    {
        return LoanGroupMember::create([
            'loan_group_id' => $group->id,
            'first_name' => $row['first_name'],
            'middle_name' => $row['middle_name'] ?? null,
            'last_name' => $row['last_name'],
            'full_name' => HasDisplayName::buildFullName(
                $row['first_name'],
                $row['middle_name'] ?? null,
                $row['last_name'],
            ),
            'nin' => $row['nin'],
            'dob' => $row['dob'] ?? null,
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'],
            'sex' => $row['sex'] ?? null,
            'marital_status' => $row['marital_status'] ?? null,
            'is_group_leader' => false,
            'leadership_role' => $this->leadershipRoleFromRow($row),
        ]);
    }

    protected function leadershipRoleFromRow(array $row): ?string
    {
        $role = $row['leadership_role'] ?? null;

        return filled($role) ? $role : null;
    }

    protected function isLeaderRow(array $row, Applicant $applicant): bool
    {
        return isset($row['nin']) && $row['nin'] === $applicant->nin;
    }
}
