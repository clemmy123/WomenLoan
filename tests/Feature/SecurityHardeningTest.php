<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\Scopes\ApprovalLevelScope;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_applicant_cannot_accept_another_users_loan_amount(): void
    {
        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000004')
            ->firstOrFail();

        $this->actingAsRole('applicant2@wdf.go.tz')
            ->post(route('loans.workflow', $loan->hashid), [
                'action' => 'accept_amount',
                'comments' => 'Trying to accept someone else loan.',
            ])
            ->assertForbidden();
    }

    public function test_nida_api_requires_registration_session(): void
    {
        $this->postJson(route('nida.api.start'), ['nin' => '19920515123456789012'])
            ->assertForbidden();
    }

    public function test_nida_api_rejects_invalid_nin_format(): void
    {
        $this->withSession(['nida_registration_allowed' => true])
            ->postJson(route('nida.api.start'), ['nin' => 'not-a-valid-nin'])
            ->assertUnprocessable();
    }

    public function test_applicant_lookup_api_requires_permission_and_valid_nin(): void
    {
        $this->actingAsRole('applicant9@wdf.go.tz')
            ->getJson(route('loans.api.applicant', ['nin' => 'invalid-nin']))
            ->assertUnprocessable();

        $response = $this->actingAsRole('applicant9@wdf.go.tz')
            ->getJson(route('loans.api.applicant', ['nin' => '19980404123450000009']))
            ->assertOk();

        $payload = $response->json();
        $this->assertArrayHasKey('full_name', $payload);
        $this->assertArrayNotHasKey('email', $payload);
        $this->assertArrayNotHasKey('phone', $payload);
        $this->assertArrayNotHasKey('dob', $payload);
    }

    public function test_applicant_destroy_requires_manage_applicants_permission(): void
    {
        $applicant = User::where('email', 'applicant9@wdf.go.tz')->firstOrFail()->applicant;

        $this->actingAsRole('applicant9@wdf.go.tz')
            ->delete(route('applicants.destroy', $applicant))
            ->assertForbidden();
    }

    public function test_secure_file_route_blocks_unrelated_user(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bank-statements/secret.pdf', 'private');

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000011')
            ->firstOrFail();

        $loan->businessDetails()->update(['bank_statement' => 'bank-statements/secret.pdf']);

        $encoded = \App\Support\SecureFileUrl::encodePath('bank-statements/secret.pdf');

        $this->actingAsRole('applicant9@wdf.go.tz')
            ->get(route('secure-files.show', ['path' => $encoded]))
            ->assertForbidden();
    }

    public function test_secure_file_route_allows_authorized_loan_viewer(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bank-statements/secret.pdf', 'private');

        $loan = Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', 'WL000011')
            ->firstOrFail();

        $loan->businessDetails()->update(['bank_statement' => 'bank-statements/secret.pdf']);

        $encoded = \App\Support\SecureFileUrl::encodePath('bank-statements/secret.pdf');

        $this->actingAsRole('ministry@wdf.go.tz')
            ->get(route('secure-files.show', ['path' => $encoded]))
            ->assertOk();
    }
}
