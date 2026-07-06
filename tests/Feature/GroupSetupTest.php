<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\LoanGroup;
use App\Models\LoanGroupMember;
use App\Models\Scopes\ApplicantAccess;
use App\Models\User;
use App\Support\GroupLeadershipRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    private function prepareGroupApplicant(User $user): Applicant
    {
        $applicant = Applicant::withoutGlobalScope(ApplicantAccess::class)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $applicant->groups()->detach();
        $applicant->update(['preferred_loan_type' => 'group']);

        return $applicant->fresh();
    }

    public function test_applicant_can_register_group_with_members_once(): void
    {
        $user = $this->applicantWithoutLoan();
        $this->prepareGroupApplicant($user);

        $applicant = $this->prepareGroupApplicant($user);

        $response = $this->actingAs($user)->post(route('my-group.store'), [
            'name' => 'Tambukareli Women Group',
            'registration_number' => 'GRP-2026-001',
            'phone' => '0712345678',
            'email' => 'group@test.com',
            'leader' => ['dob' => $applicant->dob->format('Y-m-d'), 'sex' => 'Female', 'leadership_role' => GroupLeadershipRole::CHAIRPERSON],
            'members' => [
                [
                    'first_name' => 'Asha',
                    'middle_name' => 'J',
                    'last_name' => 'Hassan',
                    'nin' => '19920101123450000011',
                    'dob' => '1992-01-01',
                    'phone' => '0755111222',
                    'email' => 'asha@test.com',
                    'sex' => 'Female',
                    'marital_status' => 'Married',
                    'leadership_role' => GroupLeadershipRole::SECRETARY,
                ],
            ],
        ]);

        $response->assertRedirect(route('my-group.show'));
        $response->assertSessionHas('success');

        $group = LoanGroup::where('name', 'Tambukareli Women Group')->firstOrFail();
        $this->assertSame($user->id, $group->created_by_user_id);
        $this->assertCount(2, LoanGroupMember::where('loan_group_id', $group->id)->get());
        $this->assertSame(GroupLeadershipRole::CHAIRPERSON, $group->members->firstWhere('is_group_leader', true)->leadership_role);
        $this->assertSame(GroupLeadershipRole::SECRETARY, $group->members->firstWhere('nin', '19920101123450000011')->leadership_role);
        $this->assertTrue($user->applicant->groups()->where('loan_groups.id', $group->id)->exists());
    }

    public function test_create_group_option_hidden_after_registration(): void
    {
        $user = $this->applicantWithoutLoan();
        $applicant = $this->prepareGroupApplicant($user);

        $this->actingAs($user)->post(route('my-group.store'), [
            'name' => 'One Time Group',
            'leader' => ['dob' => $applicant->dob->format('Y-m-d'), 'sex' => 'Female'],
            'members' => [
                [
                    'first_name' => 'Mary',
                    'last_name' => 'Kidole',
                    'nin' => '19930101123450000012',
                    'dob' => '1993-01-01',
                    'phone' => '0755333444',
                    'sex' => 'Female',
                    'marital_status' => 'Single',
                ],
            ],
        ])->assertRedirect();

        $this->actingAs($user)
            ->get(route('my-group.create'))
            ->assertRedirect(route('my-group.show'));

        $this->actingAs($user)
            ->get(route('loan-applications.index'))
            ->assertOk()
            ->assertDontSee(__('groups.setup_title'), false)
            ->assertSee(__('groups.my_group'), false);
    }

    public function test_group_creator_can_update_and_remove_member(): void
    {
        $user = $this->applicantWithoutLoan();
        $applicant = $this->prepareGroupApplicant($user);

        $this->actingAs($user)->post(route('my-group.store'), [
            'name' => 'Manageable Group',
            'leader' => ['dob' => $applicant->dob->format('Y-m-d'), 'sex' => 'Female'],
            'members' => [
                [
                    'first_name' => 'Asha',
                    'last_name' => 'Hassan',
                    'nin' => '19920101123450000011',
                    'dob' => '1992-01-01',
                    'phone' => '0755111222',
                    'sex' => 'Female',
                    'marital_status' => 'Single',
                ],
            ],
        ])->assertRedirect();

        $member = LoanGroupMember::where('nin', '19920101123450000011')->firstOrFail();

        $this->actingAs($user)
            ->put(route('my-group.members.update', $member), [
                'first_name' => 'Asha',
                'last_name' => 'Hassan',
                'nin' => '19920101123450000011',
                'dob' => '1991-06-15',
                'phone' => '0755999888',
                'sex' => 'Female',
                'marital_status' => 'Married',
            ])
            ->assertRedirect(route('my-group.show'))
            ->assertSessionHas('success');

        $member->refresh();
        $this->assertSame('1991-06-15', $member->dob->format('Y-m-d'));
        $this->assertSame('255755999888', $member->phone);

        $this->actingAs($user)
            ->delete(route('my-group.members.destroy', $member))
            ->assertRedirect(route('my-group.show'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('loan_group_members', ['id' => $member->id]);
    }

    public function test_multiple_members_can_have_member_leadership_role(): void
    {
        $user = $this->applicantWithoutLoan();
        $applicant = $this->prepareGroupApplicant($user);

        $this->actingAs($user)->post(route('my-group.store'), [
            'name' => 'Member Role Group',
            'leader' => ['dob' => $applicant->dob->format('Y-m-d'), 'sex' => 'Female'],
            'members' => [
                [
                    'first_name' => 'Asha',
                    'last_name' => 'Hassan',
                    'nin' => '19920101123450000011',
                    'dob' => '1992-01-01',
                    'phone' => '0755111222',
                    'sex' => 'Female',
                    'marital_status' => 'Single',
                    'leadership_role' => GroupLeadershipRole::MEMBER,
                ],
            ],
        ])->assertRedirect();

        $this->actingAs($user)
            ->post(route('my-group.members.store'), [
                'first_name' => 'Mary',
                'last_name' => 'Kidole',
                'nin' => '19930101123450000012',
                'dob' => '1993-01-01',
                'phone' => '0755333444',
                'sex' => 'Female',
                'marital_status' => 'Single',
                'leadership_role' => GroupLeadershipRole::MEMBER,
            ])
            ->assertRedirect(route('my-group.show'))
            ->assertSessionHas('success');

        $this->assertSame(2, LoanGroupMember::where('leadership_role', GroupLeadershipRole::MEMBER)->count());
    }

    public function test_group_creator_cannot_assign_same_leadership_role_twice(): void
    {
        $user = $this->applicantWithoutLoan();
        $applicant = $this->prepareGroupApplicant($user);

        $this->actingAs($user)->post(route('my-group.store'), [
            'name' => 'Leadership Group',
            'leader' => [
                'dob' => $applicant->dob->format('Y-m-d'),
                'sex' => 'Female',
                'leadership_role' => GroupLeadershipRole::CHAIRPERSON,
            ],
            'members' => [
                [
                    'first_name' => 'Asha',
                    'last_name' => 'Hassan',
                    'nin' => '19920101123450000011',
                    'dob' => '1992-01-01',
                    'phone' => '0755111222',
                    'sex' => 'Female',
                    'marital_status' => 'Single',
                ],
            ],
        ])->assertRedirect();

        $this->actingAs($user)
            ->post(route('my-group.members.store'), [
                'first_name' => 'Mary',
                'last_name' => 'Kidole',
                'nin' => '19930101123450000012',
                'dob' => '1993-01-01',
                'phone' => '0755333444',
                'sex' => 'Female',
                'marital_status' => 'Single',
                'leadership_role' => GroupLeadershipRole::CHAIRPERSON,
            ])
            ->assertSessionHasErrors('leadership_role');
    }

    public function test_group_leader_can_update_their_leadership_role(): void
    {
        $user = $this->applicantWithoutLoan();
        $applicant = $this->prepareGroupApplicant($user);

        $this->actingAs($user)->post(route('my-group.store'), [
            'name' => 'Leader Role Group',
            'leader' => ['dob' => $applicant->dob->format('Y-m-d'), 'sex' => 'Female'],
            'members' => [
                [
                    'first_name' => 'Asha',
                    'last_name' => 'Hassan',
                    'nin' => '19920101123450000011',
                    'dob' => '1992-01-01',
                    'phone' => '0755111222',
                    'sex' => 'Female',
                    'marital_status' => 'Single',
                ],
            ],
        ])->assertRedirect();

        $leader = LoanGroupMember::where('is_group_leader', true)->firstOrFail();

        $this->actingAs($user)
            ->put(route('my-group.members.update', $leader), [
                'dob' => $applicant->dob->format('Y-m-d'),
                'sex' => 'Female',
                'leadership_role' => GroupLeadershipRole::TREASURER,
            ])
            ->assertRedirect(route('my-group.show'))
            ->assertSessionHas('success');

        $this->assertSame(GroupLeadershipRole::TREASURER, $leader->fresh()->leadership_role);
    }

    public function test_individual_applicant_cannot_open_group_setup(): void
    {
        $user = $this->applicantWithoutLoan();

        $this->actingAs($user)
            ->get(route('my-group.create'))
            ->assertRedirect(route('loan-applications.index'));

        $this->actingAs($user)
            ->get(route('loan-applications.index'))
            ->assertOk()
            ->assertDontSee(__('groups.setup_title'), false)
            ->assertSee(__('loans.continue_as_individual'), false);
    }
}
