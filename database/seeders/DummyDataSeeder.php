<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\ApprovalLevel;
use App\Models\BusinessDetails;
use App\Models\Council;
use App\Models\District;
use App\Models\DraftLoan;
use App\Models\Gurantor;
use App\Models\Loan;
use App\Models\LoanGroup;
use App\Models\LoanPayment;
use App\Models\Region;
use App\Models\Street;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DummyDataSeeder extends Seeder
{
    protected Region $region;

    protected District $district;

    protected Council $council;

    protected Ward $ward;

    protected Street $street;

    protected array $staff = [];

    protected array $applicants = [];

    public function run(): void
    {
        $this->loadGeography();
        $this->loadStaff();

        DB::transaction(function () {
            $this->seedApplicants();
            $this->seedLoanGroups();
            $this->seedWorkflowLoans();
            $this->seedDraftLoans();
        });

        $this->command?->info('Dummy data seeded: applicants, groups, loans (steps 1-10), repayments.');
    }

    protected function loadGeography(): void
    {
        $this->region = Region::where('name', 'Dodoma')->firstOrFail();
        $this->district = District::where('region_id', $this->region->id)->where('name', 'Dodoma Mjini')->firstOrFail();
        $this->council = Council::where('district_id', $this->district->id)->where('name', 'Dodoma Jiji')->firstOrFail();
        $this->ward = Ward::where('council_id', $this->council->id)->where('name', 'Tambukareli')->firstOrFail();
        $this->street = Street::where('ward_id', $this->ward->id)->where('name', 'Uhuru')->firstOrFail();
    }

    protected function loadStaff(): void
    {
        $this->staff = [
            'ward' => User::where('email', 'ward.cdo@wdf.go.tz')->first(),
            'council' => User::where('email', 'council.cdo@wdf.go.tz')->first(),
            'ministry' => User::where('email', 'ministry@wdf.go.tz')->first(),
            'ass_dir' => User::where('email', 'assdir@wdf.go.tz')->first(),
            'director' => User::where('email', 'director@wdf.go.tz')->first(),
            'km' => User::where('email', 'km@wdf.go.tz')->first(),
            'chief' => User::where('email', 'chief@wdf.go.tz')->first(),
            'accountant' => User::where('email', 'accountant1@wdf.go.tz')->first(),
        ];
    }

    protected function seedApplicants(): void
    {
        $profiles = [
            ['email' => 'test@example.com', 'name' => 'Test Applicant', 'first' => 'Neema', 'middle' => 'Juma', 'last' => 'Mrosso', 'nin' => '19900515123450000001', 'phone' => '255712200001'],
            ['email' => 'applicant2@wdf.go.tz', 'name' => 'Fatuma Saidi', 'first' => 'Fatuma', 'middle' => 'Hassan', 'last' => 'Saidi', 'nin' => '19910220123450000002', 'phone' => '255712200002'],
            ['email' => 'applicant3@wdf.go.tz', 'name' => 'Asha Mwakyusa', 'first' => 'Asha', 'middle' => 'Peter', 'last' => 'Mwakyusa', 'nin' => '19880303123450000003', 'phone' => '255712200003'],
            ['email' => 'applicant4@wdf.go.tz', 'name' => 'Rehema Kavishe', 'first' => 'Rehema', 'middle' => 'Joseph', 'last' => 'Kavishe', 'nin' => '19920707123450000004', 'phone' => '255712200004'],
            ['email' => 'applicant5@wdf.go.tz', 'name' => 'Zainabu Omary', 'first' => 'Zainabu', 'middle' => 'Omary', 'last' => 'Rajabu', 'nin' => '19941111123450000005', 'phone' => '255712200005'],
            ['email' => 'applicant6@wdf.go.tz', 'name' => 'Mariam Juma', 'first' => 'Mariam', 'middle' => 'Juma', 'last' => 'Hassan', 'nin' => '19950101123450000006', 'phone' => '255712200006'],
            ['email' => 'applicant7@wdf.go.tz', 'name' => 'Halima Mbwana', 'first' => 'Halima', 'middle' => 'Ali', 'last' => 'Mbwana', 'nin' => '19960202123450000007', 'phone' => '255712200007'],
            ['email' => 'applicant8@wdf.go.tz', 'name' => 'Grace Mushi', 'first' => 'Grace', 'middle' => 'Paul', 'last' => 'Mushi', 'nin' => '19970303123450000008', 'phone' => '255712200008'],
            ['email' => 'applicant9@wdf.go.tz', 'name' => 'Salma Kassim', 'first' => 'Salma', 'middle' => 'Kassim', 'last' => 'Omar', 'nin' => '19980404123450000009', 'phone' => '255712200009'],
            ['email' => 'applicant10@wdf.go.tz', 'name' => 'Amina Yusuf', 'first' => 'Amina', 'middle' => 'Yusuf', 'last' => 'Bakari', 'nin' => '19990505123450000010', 'phone' => '255712200010'],
            ['email' => 'applicant11@wdf.go.tz', 'name' => 'Hawa Mgeni', 'first' => 'Hawa', 'middle' => 'Mgeni', 'last' => 'Said', 'nin' => '19900606123450000011', 'phone' => '255712200011'],
            ['email' => 'applicant12@wdf.go.tz', 'name' => 'Pendo Mcharo', 'first' => 'Pendo', 'middle' => 'Joseph', 'last' => 'Mcharo', 'nin' => '19910707123450000012', 'phone' => '255712200012'],
            ['email' => 'applicant13@wdf.go.tz', 'name' => 'Neema Kileo', 'first' => 'Neema', 'middle' => 'Peter', 'last' => 'Kileo', 'nin' => '19920808123450000013', 'phone' => '255712200013'],
            ['email' => 'applicant14@wdf.go.tz', 'name' => 'Tabia Mwanga', 'first' => 'Tabia', 'middle' => 'Hassan', 'last' => 'Mwanga', 'nin' => '19930909123450000014', 'phone' => '255712200014'],
            ['email' => 'applicant15@wdf.go.tz', 'name' => 'Rukia Ally', 'first' => 'Rukia', 'middle' => 'Ally', 'last' => 'Hamisi', 'nin' => '19941010123450000015', 'phone' => '255712200015'],
        ];

        foreach ($profiles as $p) {
            $user = User::updateOrCreate(
                ['email' => $p['email']],
                [
                    'name' => $p['name'],
                    'phone' => $p['phone'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
            );
            $user->syncRoles(['applicant']);

            $fullName = trim("{$p['first']} {$p['middle']} {$p['last']}");

            $applicant = Applicant::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nin' => $p['nin'],
                    'first_name' => $p['first'],
                    'middle_name' => $p['middle'],
                    'last_name' => $p['last'],
                    'full_name' => $fullName,
                    'dob' => '1990-05-15',
                    'sex' => 'Female',
                    'marital_status' => 'Married',
                    'preferred_loan_type' => 'individual',
                    'has_disability' => false,
                    'nationality' => 'Tanzanian',
                    'phone' => $p['phone'],
                    'email' => $p['email'],
                    'location_id' => $this->street->id,
                    'nida_verified' => true,
                    'nida_verified_at' => now(),
                ]
            );

            $this->applicants[] = $applicant;
        }
    }

    protected function seedLoanGroups(): void
    {
        $group1 = LoanGroup::firstOrCreate(
            ['name' => 'Tambukareli Women Entrepreneurs'],
            [
                'registration_number' => 'WDF-GROUP-001',
                'phone' => '255712300001',
                'email' => 'tambukareli.women@wdf.go.tz',
            ]
        );

        $group2 = LoanGroup::firstOrCreate(
            ['name' => 'Uhuru Street VICOBA'],
            [
                'registration_number' => 'WDF-GROUP-002',
                'phone' => '255712300002',
                'email' => 'uhuru.vicoba@wdf.go.tz',
            ]
        );

        $group1->applicants()->syncWithoutDetaching([
            $this->applicants[0]->id,
            $this->applicants[1]->id,
            $this->applicants[2]->id,
        ]);

        $group2->applicants()->syncWithoutDetaching([
            $this->applicants[3]->id,
            $this->applicants[4]->id,
        ]);
    }

    protected function seedWorkflowLoans(): void
    {
        $scenarios = [
            // Step 1 — submitted, pending ward (demo applicant, not login test accounts)
            ['track' => 'WL000001', 'step' => 1, 'status' => 'pending', 'acceptance' => 'pending', 'requested' => 5000000, 'proposed' => 0, 'disbursed' => 0, 'applicant' => 5, 'history' => []],
            // Step 1 — received by ward
            ['track' => 'WL000002', 'step' => 1, 'status' => 'received', 'acceptance' => 'pending', 'requested' => 3500000, 'proposed' => 0, 'disbursed' => 0, 'applicant' => 6, 'history' => [
                ['step' => 1, 'action' => 'received', 'user' => 'ward', 'comments' => 'Application received at ward office.'],
            ]],
            // Step 2 — council review
            ['track' => 'WL000003', 'step' => 2, 'status' => 'in_review', 'acceptance' => 'pending', 'requested' => 8000000, 'proposed' => 0, 'disbursed' => 0, 'applicant' => 7, 'history' => [
                ['step' => 1, 'action' => 'received', 'user' => 'ward', 'comments' => 'Received.'],
                ['step' => 1, 'action' => 'forwarded_to_council', 'user' => 'ward', 'comments' => 'Forwarded to council for review.'],
            ]],
            // Step 3 — ministry review
            ['track' => 'WL000004', 'step' => 3, 'status' => 'in_review', 'acceptance' => 'pending', 'requested' => 6000000, 'proposed' => 0, 'disbursed' => 0, 'applicant' => 7, 'history' => [
                ['step' => 1, 'action' => 'received', 'user' => 'ward'],
                ['step' => 1, 'action' => 'forwarded_to_council', 'user' => 'ward'],
                ['step' => 2, 'action' => 'forwarded_to_ministry', 'user' => 'council', 'comments' => 'Council endorsed. Forward to ministry.'],
            ]],
            // Step 4 — awaiting applicant confirmation
            ['track' => 'WL000005', 'step' => 4, 'status' => 'awaiting_applicant', 'acceptance' => 'pending', 'requested' => 4500000, 'proposed' => 4000000, 'disbursed' => 0, 'applicant' => 8, 'history' => [
                ['step' => 1, 'action' => 'received', 'user' => 'ward'],
                ['step' => 1, 'action' => 'forwarded_to_council', 'user' => 'ward'],
                ['step' => 2, 'action' => 'forwarded_to_ministry', 'user' => 'council'],
                ['step' => 3, 'action' => 'proposed_amount', 'user' => 'ministry', 'comments' => 'Proposed TZS 4,000,000', 'amount' => 4000000],
            ]],
            // Step 5 — ministry after applicant accepted
            ['track' => 'WL000006', 'step' => 5, 'status' => 'in_review', 'acceptance' => 'accepted', 'requested' => 7000000, 'proposed' => 6500000, 'disbursed' => 0, 'applicant' => 9, 'history' => [
                ['step' => 1, 'action' => 'received', 'user' => 'ward'],
                ['step' => 1, 'action' => 'forwarded_to_council', 'user' => 'ward'],
                ['step' => 2, 'action' => 'forwarded_to_ministry', 'user' => 'council'],
                ['step' => 3, 'action' => 'proposed_amount', 'user' => 'ministry', 'amount' => 6500000],
                ['step' => 4, 'action' => 'accepted', 'user' => 'applicant', 'comments' => 'Applicant accepted proposed amount.'],
            ]],
            // Step 6 — assistant director
            ['track' => 'WL000007', 'step' => 6, 'status' => 'in_review', 'acceptance' => 'accepted', 'requested' => 9000000, 'proposed' => 8500000, 'disbursed' => 0, 'applicant' => 10, 'history' => [
                ['step' => 5, 'action' => 'forwarded_to_ass_dir', 'user' => 'ministry', 'comments' => 'Sent to Assistant Director.'],
            ]],
            // Step 7 — director
            ['track' => 'WL000008', 'step' => 7, 'status' => 'in_review', 'acceptance' => 'accepted', 'requested' => 10000000, 'proposed' => 9500000, 'disbursed' => 0, 'applicant' => 11, 'history' => [
                ['step' => 6, 'action' => 'forwarded_to_director', 'user' => 'ass_dir', 'comments' => 'Recommended for director review.'],
            ]],
            // Step 8 — Permanent Secretary
            ['track' => 'WL000009', 'step' => 8, 'status' => 'in_review', 'acceptance' => 'accepted', 'requested' => 5500000, 'proposed' => 5000000, 'disbursed' => 0, 'applicant' => 12, 'history' => [
                ['step' => 7, 'action' => 'forwarded_to_km', 'user' => 'director', 'comments' => 'Director endorsed. Forward to Permanent Secretary.'],
            ]],
            // Step 9 — chief assigns accountant
            ['track' => 'WL000010', 'step' => 9, 'status' => 'approved', 'acceptance' => 'accepted', 'requested' => 4000000, 'proposed' => 3800000, 'disbursed' => 0, 'applicant' => 13, 'approved_by' => 'Prof. Neema Kapinga', 'history' => [
                ['step' => 8, 'action' => 'approved', 'user' => 'km', 'comments' => 'Final approval granted by Permanent Secretary.'],
            ]],
            // Step 10 — ready for disbursement
            ['track' => 'WL000011', 'step' => 10, 'status' => 'ready_for_disbursement', 'acceptance' => 'accepted', 'requested' => 4000000, 'proposed' => 3800000, 'disbursed' => 0, 'applicant' => 14, 'officer' => 'accountant', 'approved_by' => 'Prof. Neema Kapinga', 'history' => [
                ['step' => 8, 'action' => 'approved', 'user' => 'km', 'comments' => 'Final approval granted by Permanent Secretary.'],
                ['step' => 9, 'action' => 'assigned_accountant', 'user' => 'chief', 'comments' => 'Assigned to accountant for disbursement.'],
            ]],
            // Step 10 — disbursed with repayment (terminal history on test account)
            ['track' => 'WL000012', 'step' => 10, 'status' => 'disbursed', 'acceptance' => 'accepted', 'requested' => 3000000, 'proposed' => 2800000, 'disbursed' => 2800000, 'applicant' => 0, 'officer' => 'accountant', 'with_payment' => true, 'approved_by' => 'Prof. Neema Kapinga', 'history' => [
                ['step' => 8, 'action' => 'approved', 'user' => 'km', 'comments' => 'Final approval granted by Permanent Secretary.'],
                ['step' => 9, 'action' => 'assigned_accountant', 'user' => 'chief', 'comments' => 'Assigned to accountant for disbursement.'],
                ['step' => 10, 'action' => 'disbursed', 'user' => 'accountant', 'comments' => 'Funds disbursed to applicant bank account.'],
            ]],
        ];

        $group = LoanGroup::where('name', 'Tambukareli Women Entrepreneurs')->first();

        foreach ($scenarios as $i => $s) {
            $applicant = $this->applicants[$s['applicant']];

            $loan = Loan::withoutEvents(function () use ($s, $applicant, $group, $i) {
                return Loan::updateOrCreate(
                    ['loan_track_id' => $s['track']],
                    [
                        'user_id' => $applicant->user_id,
                        'applicant_id' => $applicant->id,
                        'loan_group_id' => $group?->id,
                        'loan_type' => $i % 2 === 0 ? 'individual' : 'group',
                        'requested_amount' => $s['requested'],
                        'proposed_amount' => $s['proposed'],
                        'disbursed_amount' => $s['disbursed'],
                        'status' => $s['status'],
                        'current_step' => $s['step'],
                        'applicant_acceptance' => $s['acceptance'],
                        'approved_by' => $s['approved_by'] ?? null,
                        'officer_id' => isset($s['officer']) ? $this->staff[$s['officer']]?->id : null,
                        'bank_name' => 'CRDB Bank',
                        'bank_number' => '01' . str_pad((string) ($i + 1), 8, '0', STR_PAD_LEFT),
                        'date_issued' => $s['status'] === 'disbursed' ? now()->subDays(30)->toDateString() : null,
                        'approval_history' => collect($s['history'])->map(fn ($h) => [
                            'step' => $h['step'],
                            'action' => $h['action'],
                            'user' => $this->staff[$h['user'] ?? 'ward']?->name ?? $applicant->full_name,
                            'at' => now()->subDays(20 - $h['step'])->toIso8601String(),
                            'comments' => $h['comments'] ?? null,
                        ])->toArray(),
                    ]
                );
            });

            BusinessDetails::updateOrCreate(
                ['loan_id' => $loan->id],
                [
                    'region_id' => $this->region->id,
                    'district_id' => $this->district->id,
                    'council_id' => $this->council->id,
                    'ward_id' => $this->ward->id,
                    'street_id' => $this->street->id,
                    'business_name' => $applicant->first_name . ' ' . ['Shop', 'Salon', 'Tailoring', 'Food Stall', 'Agribusiness'][$i % 5],
                    'business_phone' => $applicant->phone,
                    'business_email' => $applicant->email,
                    'business_sector' => ['Trade', 'Services', 'Agriculture', 'Manufacturing', 'Food'][$i % 5],
                    'business_type' => 'Sole Proprietor',
                    'tin_number' => '100' . str_pad((string) ($i + 1), 7, '0', STR_PAD_LEFT),
                    'business_proposal_document' => 'proposals/demo-proposal.pdf',
                    'business_registration_attachment' => 'registrations/demo-registration.pdf',
                    'proof_address_attachment' => 'proof-of-address/demo-proof.pdf',
                    'application_letter' => 'application-letters/demo-letter.pdf',
                    'bank_statement' => 'bank-statements/demo-statement.pdf',
                ]
            );

            Gurantor::updateOrCreate(
                ['loan_id' => $loan->id, 'id_number' => '1985010112345000' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'applicant_id' => $applicant->id,
                    'first_name' => 'Guarantor',
                    'middle_name' => null,
                    'last_name' => (string) ($i + 1),
                    'name' => 'Guarantor ' . ($i + 1),
                    'phone' => '255713' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                    'relationship' => 'Spouse',
                    'occupation' => 'Business Owner',
                    'guarantor_region_id' => $this->region->id,
                    'guarantor_district_id' => $this->district->id,
                    'guarantor_council_id' => $this->council->id,
                    'guarantor_ward_id' => $this->ward->id,
                    'guarantor_street_id' => $this->street->id,
                    'guarantor_letter' => 'guarantor-letters/demo-guarantor.pdf',
                ]
            );

            foreach ($s['history'] as $h) {
                $actor = $h['user'] === 'applicant'
                    ? User::find($applicant->user_id)
                    : ($this->staff[$h['user']] ?? null);

                if ($actor) {
                    ApprovalLevel::updateOrCreate(
                        [
                            'loan_id' => $loan->id,
                            'step_number' => $h['step'],
                            'action_taken' => $h['action'],
                        ],
                        [
                            'user_id' => $actor->id,
                            'proposed_amount' => $h['amount'] ?? 0,
                            'comments' => $h['comments'] ?? null,
                        ]
                    );
                }
            }

            if (! empty($s['with_payment'])) {
                $disbursed = (float) $s['disbursed'];
                $interest = $disbursed * 0.16;
                $totalPayable = $disbursed + $interest;
                $monthlyAmount = round($totalPayable / 12, 2);
                $startDate = now()->subMonths(4)->startOfDay();
                $repaymentStart = $startDate->copy()->addMonths(3);
                $installments = [];
                $allocated = 0.0;

                for ($i = 1; $i <= 12; $i++) {
                    $due = $i === 12
                        ? round($totalPayable - $allocated, 2)
                        : $monthlyAmount;
                    $allocated += $due;
                    $installments[] = [
                        'installment' => $i,
                        'due_date' => $repaymentStart->copy()->addMonths($i - 1)->toDateString(),
                        'amount_due' => $due,
                        'amount_paid' => $i === 1 ? round($disbursed * 0.25, 2) : 0,
                        'status' => $i === 1 ? 'partial' : 'pending',
                    ];
                }

                LoanPayment::updateOrCreate(
                    ['loan_id' => $loan->id],
                    [
                        'amount_requested' => $s['requested'],
                        'amount_disbursed' => $disbursed,
                        'interest_amount' => $interest,
                        'amount_paid' => $disbursed * 0.25,
                        'outstanding_debt' => $disbursed + $interest - ($disbursed * 0.25),
                        'grace_period_days' => $startDate->diffInDays($repaymentStart),
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $repaymentStart->copy()->addMonths(11)->toDateString(),
                        'payment_interval' => 'monthly',
                        'notes' => 'Sample repayment schedule — 16% interest applied.',
                        'payment_history' => [
                            'installments' => $installments,
                            'transactions' => [
                                ['date' => now()->subDays(15)->toDateTimeString(), 'amount' => $disbursed * 0.15, 'method' => 'Bank Transfer', 'reference' => 'WDF-'.$s['track'].'-001', 'receipt_number' => 'RCP-'.$s['track'].'-001'],
                                ['date' => now()->subDays(5)->toDateTimeString(), 'amount' => $disbursed * 0.10, 'method' => 'Bank Transfer', 'reference' => 'WDF-'.$s['track'].'-002', 'receipt_number' => 'RCP-'.$s['track'].'-002'],
                            ],
                        ],
                    ]
                );
            }
        }
    }

    protected function seedDraftLoans(): void
    {
        $user = User::where('email', 'applicant2@wdf.go.tz')->first();

        if ($user) {
            DraftLoan::updateOrCreate(
                ['track_id' => 'WL000099'],
                [
                    'user_id' => $user->id,
                    'form_data' => [
                        'loan_type' => 'individual',
                        'requested_amount' => 2500000,
                        'business_name' => 'Fatuma Catering Services',
                        'region_id' => $this->region->id,
                        'ward_id' => $this->ward->id,
                        'step' => 1,
                    ],
                ]
            );
        }
    }
}
