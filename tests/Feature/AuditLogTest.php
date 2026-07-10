<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_admin_can_view_audit_logs_index(): void
    {
        $admin = User::where('email', 'admin@wdf.go.tz')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.audit.index'))
            ->assertOk()
            ->assertSee(__('nav.audit_logs'), false);
    }

    public function test_non_admin_cannot_view_audit_logs(): void
    {
        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('admin.audit.index'))
            ->assertForbidden();
    }

    public function test_model_changes_are_logged_with_causer(): void
    {
        $admin = User::where('email', 'admin@wdf.go.tz')->firstOrFail();
        $this->actingAs($admin);

        $target = User::where('email', 'ministry@wdf.go.tz')->firstOrFail();
        $target->update(['name' => 'Ministry Officer Updated']);

        $activity = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $target->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($admin->id, $activity->causer_id);
        $this->assertSame(User::class, $activity->causer_type);

        $this->get(route('admin.audit.show', $activity))
            ->assertOk()
            ->assertSee(__('audit.who'), false)
            ->assertSee('Ministry Officer Updated', false);
    }

    public function test_audit_logs_can_filter_by_date_range(): void
    {
        $admin = User::where('email', 'admin@wdf.go.tz')->firstOrFail();
        $this->actingAs($admin);

        $old = Activity::query()->create([
            'log_name' => 'audit',
            'description' => 'Old activity',
            'event' => 'updated',
            'subject_type' => User::class,
            'subject_id' => $admin->id,
            'causer_type' => User::class,
            'causer_id' => $admin->id,
            'properties' => [],
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $recent = Activity::query()->create([
            'log_name' => 'audit',
            'description' => 'Recent activity',
            'event' => 'updated',
            'subject_type' => User::class,
            'subject_id' => $admin->id,
            'causer_type' => User::class,
            'causer_id' => $admin->id,
            'properties' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('admin.audit.index', [
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
            ->assertOk()
            ->assertSee('Recent activity', false)
            ->assertDontSee('Old activity', false);

        $this->assertTrue(
            app(\App\Services\AuditLogService::class)
                ->exportRows([
                    'search' => '',
                    'event' => '',
                    'date_from' => now()->subDay()->toDateString(),
                    'date_to' => now()->toDateString(),
                ])
                ->contains(fn (array $row) => str_contains($row['description'], 'Recent activity'))
        );

        $this->assertFalse(
            app(\App\Services\AuditLogService::class)
                ->exportRows([
                    'search' => '',
                    'event' => '',
                    'date_from' => now()->subDay()->toDateString(),
                    'date_to' => now()->toDateString(),
                ])
                ->contains(fn (array $row) => str_contains($row['description'], 'Old activity'))
        );

        unset($old, $recent);
    }

    public function test_admin_can_export_audit_logs_excel_and_pdf(): void
    {
        $admin = User::where('email', 'admin@wdf.go.tz')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.audit.export.excel'))
            ->assertOk()
            ->assertDownload();

        $this->actingAs($admin)
            ->get(route('admin.audit.export.pdf'))
            ->assertOk()
            ->assertDownload();
    }
}
