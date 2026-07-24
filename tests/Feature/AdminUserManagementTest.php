<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_admin_can_create_staff_user_with_required_identity_fields(): void
    {
        $response = $this->actingAsRole('admin@wdf.go.tz')
            ->post(route('admin.users.store'), [
                'check_number' => '1234567890',
                'first_name' => 'Asha',
                'middle_name' => 'Juma',
                'last_name' => 'Mushi',
                'email' => 'asha.mushi@wdf.go.tz',
                'phone' => '0712345999',
                'password' => $this->strongPassword(),
                'password_confirmation' => $this->strongPassword(),
                'roles' => ['accountant'],
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'asha.mushi@wdf.go.tz')->firstOrFail();

        $this->assertSame('1234567890', $user->check_number);
        $this->assertSame('Asha', $user->first_name);
        $this->assertSame('Juma', $user->middle_name);
        $this->assertSame('Mushi', $user->last_name);
        $this->assertSame('Asha Juma Mushi', $user->name);
        $this->assertSame('255712345999', $user->phone);
        $this->assertTrue($user->hasRole('accountant'));
        $this->assertTrue($user->must_change_password);
        $this->assertNull($user->temporary_password_expires_at);
    }

    public function test_staff_user_create_requires_check_number_and_strong_password(): void
    {
        $response = $this->actingAsRole('admin@wdf.go.tz')
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'first_name' => 'Asha',
                'last_name' => 'Mushi',
                'email' => 'asha2@wdf.go.tz',
                'phone' => '0712345888',
                'password' => 'weak',
                'password_confirmation' => 'weak',
                'roles' => ['accountant'],
            ]);

        $response->assertRedirect(route('admin.users.create'));
        $response->assertSessionHasErrors(['check_number', 'password']);
    }

    public function test_check_number_must_be_digits_only_max_ten(): void
    {
        $response = $this->actingAsRole('admin@wdf.go.tz')
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'check_number' => 'ABC1234567',
                'first_name' => 'Asha',
                'last_name' => 'Mushi',
                'email' => 'asha3@wdf.go.tz',
                'phone' => '0712345777',
                'password' => $this->strongPassword(),
                'password_confirmation' => $this->strongPassword(),
                'roles' => ['accountant'],
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'asha3@wdf.go.tz',
            'check_number' => '1234567',
        ]);

        $this->actingAsRole('admin@wdf.go.tz')
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'check_number' => '12345678901',
                'first_name' => 'Asha',
                'last_name' => 'Mushi',
                'email' => 'asha4@wdf.go.tz',
                'phone' => '0712345666',
                'password' => $this->strongPassword(),
                'password_confirmation' => $this->strongPassword(),
                'roles' => ['accountant'],
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'asha4@wdf.go.tz',
            'check_number' => '1234567890',
        ]);
    }

    public function test_create_form_shows_check_number_and_password_requirements(): void
    {
        $response = $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.users.create'));

        $response->assertOk();
        $response->assertSee(__('admin.check_number'), false);
        $response->assertSee(__('applicants.first_name'), false);
        $response->assertSee(__('applicants.middle_name'), false);
        $response->assertSee(__('applicants.last_name'), false);
        $response->assertSee(__('auth.password_requirements_title'), false);
        $response->assertSee('data-phone-field', false);
    }

    public function test_users_index_can_search_and_filter_by_role(): void
    {
        $response = $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.users.index', [
                'search' => 'Sarah',
                'role' => 'accountant',
            ]));

        $response->assertOk();
        $response->assertSee('accountant1@wdf.go.tz', false);
        $response->assertDontSee('ward.cdo@wdf.go.tz', false);
        $response->assertSee(__('admin.users_search_placeholder'), false);
        $response->assertSee(__('admin.sort_by_role'), false);
        $response->assertSee(e(role_label('accountant')), false);
        $response->assertSee(e(role_label('cdo_ward')), false);
        $response->assertSee(__('admin.export_excel'), false);
        $response->assertSee(__('admin.export_pdf'), false);
        $response->assertDontSee(__('admin.active_users'), false);
    }

    public function test_admin_can_export_users_excel_and_pdf(): void
    {
        $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.users.export.excel', [
                'role' => 'accountant',
                'list' => 'active',
            ]))
            ->assertOk()
            ->assertDownload();

        $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.users.export.pdf', [
                'role' => 'accountant',
                'list' => 'active',
            ]))
            ->assertOk()
            ->assertDownload();
    }

    public function test_users_export_excludes_applicants(): void
    {
        $rows = app(\App\Services\UserProvisioningService::class)->exportRows(status: 'active');

        $this->assertTrue($rows->contains(fn (array $row) => $row['email'] === 'ward.cdo@wdf.go.tz'));
        $this->assertFalse($rows->contains(fn (array $row) => $row['email'] === 'applicant2@wdf.go.tz'));
    }

    public function test_active_list_hides_deactivated_users_and_inactive_list_shows_them(): void
    {
        $target = User::where('email', 'accountant1@wdf.go.tz')->firstOrFail();
        $target->update(['is_active' => false]);

        $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('accountant1@wdf.go.tz', false);

        $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.users.inactive'))
            ->assertOk()
            ->assertSee(__('admin.deactivated_users'), false)
            ->assertSee('accountant1@wdf.go.tz', false);
    }

    public function test_admin_can_view_user_read_only(): void
    {
        $target = User::where('email', 'accountant1@wdf.go.tz')->firstOrFail();

        $response = $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.users.show', $target));

        $response->assertOk();
        $response->assertSee(__('admin.view_user'), false);
        $response->assertSee($target->email, false);
        $response->assertSee($target->name, false);
        $response->assertDontSee('name="email"', false);
        $response->assertDontSee('name="roles[]"', false);
    }

    public function test_admin_can_open_assign_roles_and_update_roles(): void
    {
        $target = User::where('email', 'accountant1@wdf.go.tz')->firstOrFail();

        $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.users.assign-roles', $target))
            ->assertOk()
            ->assertSee(__('admin.assign_roles'), false)
            ->assertSee('name="roles[]"', false);

        $this->actingAsRole('admin@wdf.go.tz')
            ->put(route('admin.users.assign-roles.update', $target), [
                'roles' => ['chief'],
            ])
            ->assertRedirect(route('admin.users.assign-roles', $target))
            ->assertSessionHas('success');

        $target->refresh();
        $this->assertTrue($target->hasRole('chief'));
        $this->assertFalse($target->hasRole('accountant'));
    }

    public function test_users_index_shows_row_action_menu_links(): void
    {
        $target = User::where('email', 'accountant1@wdf.go.tz')->firstOrFail();

        $response = $this->actingAsRole('admin@wdf.go.tz')
            ->get(route('admin.users.index', ['search' => 'accountant1@wdf.go.tz']));

        $response->assertOk();
        $response->assertSee(route('admin.users.show', $target), false);
        $response->assertSee(route('admin.users.edit', $target), false);
        $response->assertSee(route('admin.users.assign-roles', $target), false);
    }
}
