<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurgeOperationalDataSeeder extends Seeder
{
    public const SUPER_ADMIN_EMAIL = 'admin@wdf.go.tz';

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'loan_payments',
            'approval_levels',
            'gurantors',
            'business_details',
            'draft_loans',
            'loans',
            'loan_group_members',
            'applicant_loan_group',
            'loan_groups',
            'applicants',
            'jumuishi_processed_events',
            'activity_log',
            'sessions',
            'password_reset_tokens',
            'jobs',
            'job_batches',
            'failed_jobs',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        $keepId = User::query()->where('email', self::SUPER_ADMIN_EMAIL)->value('id');

        if (Schema::hasColumn('users', 'deactivated_by')) {
            DB::table('users')->update(['deactivated_by' => null]);
        }

        if (Schema::hasTable('model_has_roles')) {
            $roleQuery = DB::table('model_has_roles')->where('model_type', User::class);
            if ($keepId) {
                $roleQuery->where('model_id', '!=', $keepId);
            }
            $roleQuery->delete();
        }

        if (Schema::hasTable('model_has_permissions')) {
            $permQuery = DB::table('model_has_permissions')->where('model_type', User::class);
            if ($keepId) {
                $permQuery->where('model_id', '!=', $keepId);
            }
            $permQuery->delete();
        }

        User::query()->when(
            $keepId,
            fn ($query) => $query->where('id', '!=', $keepId),
            fn ($query) => $query
        )->delete();

        Schema::enableForeignKeyConstraints();

        $this->ensureSuperAdmin();
    }

    protected function ensureSuperAdmin(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => self::SUPER_ADMIN_EMAIL],
            [
                'check_number' => '1000000001',
                'first_name' => 'System',
                'middle_name' => null,
                'last_name' => 'Administrator',
                'name' => 'System Administrator',
                'phone' => '255700000000',
                'password' => 'password',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        $admin->forceFill([
            'is_active' => true,
            'must_change_password' => false,
            'zoneable_type' => null,
            'zoneable_id' => null,
        ])->save();

        $admin->syncRoles(['super_admin']);
    }
}
