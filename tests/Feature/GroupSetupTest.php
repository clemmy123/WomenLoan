<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\LoanGroup;
use App\Models\LoanGroupMember;
use App\Models\Scopes\ApplicantAccess;
use App\Models\User;
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

    public function test_applicant_can_register_group_with_members_once(): void
    {
        $user = $this->applicantWithoutLoan();
        $applicant = Applicant::withoutGlobalScope(ApplicantAccess::class)
            ->where('user_id', $user->id)
            ->firstOrFail();
        $applicant->groups()->detach();

        $response = $this->actingAs($user)->post(route('my-group.store'), [
            'name' => 'Tambukareli Women Group',
            'registration_number' => 'GRP-2026-001',
            'phone' => '0712345678',
            'email' => 'group@test.com',
            'leader' => ['age' => 35, 'sex' => 'Female'],
            'members' => [
                [
                    'first_name' => 'Asha',
                    'middle_name' => 'J',
                    'last_name' => 'Hassan',
                    'nin' => '19920101123450000011',
                    'age' => 32,
                    'phone' => '0755111222',
                    'email' => 'asha@test.com',
                    'sex' => 'Female',
                    'marital_status' => 'Married',
                ],
            ],
        ]);

        $response->assertRedirect(route('my-group.show'));
        $response->assertSessionHas('success');

        $group = LoanGroup::where('name', 'Tambukareli Women Group')->firstOrFail();
        $this->assertSame($user->id, $group->created_by_user_id);
        $this->assertCount(2, LoanGroupMember::where('loan_group_id', $group->id)->get());
        $this->assertTrue($user->applicant->groups()->where('loan_groups.id', $group->id)->exists());
    }

    public function test_create_group_option_hidden_after_registration(): void
    {
        $user = $this->applicantWithoutLoan();
        Applicant::withoutGlobalScope(ApplicantAccess::class)
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->groups()
            ->detach();

        $this->actingAs($user)->post(route('my-group.store'), [
            'name' => 'One Time Group',
            'leader' => ['age' => 30, 'sex' => 'Female'],
            'members' => [
                [
                    'first_name' => 'Mary',
                    'last_name' => 'Kidole',
                    'nin' => '19930101123450000012',
                    'age' => 28,
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
        $applicant = Applicant::withoutGlobalScope(ApplicantAccess::class)
            ->where('user_id', $user->id)
            ->firstOrFail();
        $applicant->groups()->detach();

        $this->actingAs($user)->post(route('my-group.store'), [
            'name' => 'Manageable Group',
            'leader' => ['age' => 35, 'sex' => 'Female'],
            'members' => [
                [
                    'first_name' => 'Asha',
                    'last_name' => 'Hassan',
                    'nin' => '19920101123450000011',
                    'age' => 32,
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
                'age' => 33,
                'phone' => '0755999888',
                'sex' => 'Female',
                'marital_status' => 'Married',
            ])
            ->assertRedirect(route('my-group.show'))
            ->assertSessionHas('success');

        $member->refresh();
        $this->assertSame(33, $member->age);
        $this->assertSame('255755999888', $member->phone);

        $this->actingAs($user)
            ->delete(route('my-group.members.destroy', $member))
            ->assertRedirect(route('my-group.show'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('loan_group_members', ['id' => $member->id]);
    }
}
