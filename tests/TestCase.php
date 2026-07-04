<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function seedApplication(): void
    {
        $this->seed([
            \Database\Seeders\RolePermissionSeeder::class,
            \Database\Seeders\LocationSeeder::class,
            \Database\Seeders\BusinessSectorSeeder::class,
            \Database\Seeders\StaffUserSeeder::class,
            \Database\Seeders\DummyDataSeeder::class,
        ]);
    }

    protected function actingAsRole(string $email): static
    {
        $user = \App\Models\User::where('email', $email)->firstOrFail();

        return $this->actingAs($user);
    }

    protected function loanByTrack(string $trackId): \App\Models\Loan
    {
        return \App\Models\Loan::withoutGlobalScope(\App\Models\Scopes\ApprovalLevelScope::class)
            ->where('loan_track_id', $trackId)
            ->firstOrFail();
    }

    protected function applicantWithoutLoan(): \App\Models\User
    {
        return \App\Models\User::where('email', 'applicant2@wdf.go.tz')->firstOrFail();
    }
}
