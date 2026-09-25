<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Loan;
use App\Models\LoanGroup;
use App\Models\Scopes\ApprovalLevelScope;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\PurgeOperationalDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionEmptySeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_purge_leaves_only_super_admin_and_no_applications(): void
    {
        $this->assertGreaterThan(1, User::query()->count());
        $this->assertGreaterThan(0, Loan::withoutGlobalScope(ApprovalLevelScope::class)->count());

        $this->seed([
            PurgeOperationalDataSeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->assertSame(1, User::query()->count());
        $admin = User::query()->first();
        $this->assertSame('admin@wdf.go.tz', $admin->email);
        $this->assertTrue($admin->hasRole('super_admin'));
        $this->assertSame(0, Loan::withoutGlobalScope(ApprovalLevelScope::class)->count());
        $this->assertSame(0, Applicant::withoutGlobalScopes()->count());
        $this->assertSame(0, LoanGroup::query()->count());
    }
}
