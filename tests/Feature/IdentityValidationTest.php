<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\IdentityNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdentityValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        $existing = User::where('email', 'test@example.com')->firstOrFail();

        $this->post(route('register'), [
            'name' => 'Another User',
            'email' => $existing->email,
            'phone' => '255712399999',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_registration_rejects_duplicate_phone(): void
    {
        $existing = $this->applicantWithoutLoan();

        $this->post(route('register'), [
            'name' => 'Another User',
            'email' => 'new.user@example.com',
            'phone' => IdentityNormalizer::formatPhone($existing->phone),
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('phone');
    }

    public function test_applicant_profile_rejects_invalid_nin_format(): void
    {
        $user = $this->applicantWithoutLoan();

        $this->actingAs($user)->post(route('applicants.store'), $this->validApplicantPayload([
            'nin' => '12345',
        ]))->assertSessionHasErrors('nin');
    }

    public function test_applicant_profile_rejects_duplicate_nin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('applicant');
        $existingNin = User::where('email', 'test@example.com')->firstOrFail()->applicant->nin;

        $this->actingAs($user)->post(route('applicants.store'), $this->validApplicantPayload([
            'nin' => IdentityNormalizer::formatNin($existingNin),
        ]))->assertSessionHasErrors('nin');
    }

    public function test_identity_normalizer_formats_nin_and_phone(): void
    {
        $this->assertSame('19900515123450000001', IdentityNormalizer::normalizeNin('19900515-12345-00000-01'));
        $this->assertSame('19900515-12345-00000-01', IdentityNormalizer::formatNin('19900515123450000001'));
        $this->assertSame('255712345678', IdentityNormalizer::normalizePhone('+255 712345678'));
        $this->assertSame('712345678', IdentityNormalizer::phoneLocalPart('0712345678'));
    }

    private function validApplicantPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Asha',
            'middle_name' => 'J',
            'last_name' => 'Hassan',
            'nin' => '19920101123450000099',
            'dob' => '1992-01-01',
            'email' => 'asha.unique@example.com',
            'phone' => '255712399998',
            'sex' => 'Female',
            'marital_status' => 'Single',
            'nationality' => 'Tanzanian',
            'location_id' => 1,
        ], $overrides);
    }
}
