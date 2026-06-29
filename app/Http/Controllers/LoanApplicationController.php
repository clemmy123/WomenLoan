<?php

namespace App\Http\Controllers;

use App\Models\{Loan, DraftLoan, BusinessDetails, Gurantor, Region, District, Council, Ward, Street, LoanGroup};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth, Validator};

class LoanApplicationController extends Controller
{
    private function getUserId() { return Auth::id() ?? 1; }

    public function create(Request $request)
    {
        $regions = Region::orderBy('name')->get();
        $groups = LoanGroup::orderBy('name')->get();
        $trackId = $request->query('resume_track_id');

        return view('loan_applications.apply', compact('regions', 'groups', 'trackId'));
    }

    public function store(Request $request)
    {
        $action = $request->input('form_action');
        $trackId = $request->input('track_id') ?? $this->generateTrackId();

        if ($action === 'save_draft') {
            DraftLoan::updateOrCreate(
                ['track_id' => $trackId, 'user_id' => $this->getUserId()],
                ['form_data' => $request->except(['_token', 'form_action'])]
            );
            return redirect()->back()->with('success', 'Draft saved!')->with('track_id', $trackId);
        }

        // Validation - Kuhakikisha field zote muhimu zipo
        $validator = Validator::make($request->all(), [
            'loan_type'        => 'required',
            'requested_amount' => 'required|numeric',
            'business_name'    => 'required',
            'business_phone'   => 'required',
            'business_email'   => 'required|email',
            'business_proposal_document' => 'required|file|mimes:pdf,docx,doc|max:5120',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::transaction(function () use ($request, $trackId) {
            // 1. Create Loan
            $loan = Loan::create([
                'loan_track_id'    => $trackId,
                'loan_type'        => $request->loan_type,
                'requested_amount' => $request->requested_amount,
                'bank_name'        => $request->bank_name,
                'bank_number'      => $request->bank_number,
                'user_id'          => $this->getUserId(),
                'status'           => 'pending',
                'current_step'     => 1,
            ]);

            // 2. Create Business Details - Hapa ndipo nilipoongeza field zote
            $loan->businessDetails()->create([
                'region_id'        => $request->region_id,
                'district_id'      => $request->district_id,
                'council_id'       => $request->council_id,
                'ward_id'          => $request->ward_id,
                'street_id'        => $request->street_id,
                'business_name'    => $request->business_name,
                'business_phone'   => $request->business_phone,
                'business_email'   => $request->business_email,
                'business_sector'  => $request->business_sector,
                'business_type'    => $request->business_type,
                'tin_number'       => $request->tin_number,
                'business_proposal_document'       => $request->file('business_proposal_document')?->store('proposals', 'public'),
                'business_registration_attachment' => $request->file('business_registration_attachment')?->store('registrations', 'public'),
            ]);

            // 3. Create Guarantor
            if ($request->has('guarantor_name')) {
                $loan->guarantors()->create([
                    'name'  => $request->guarantor_name,
                    'phone' => $request->guarantor_phone,
                    'nin'   => $request->guarantor_nin,
                ]);
            }

            DraftLoan::where('track_id', $trackId)->delete();
        });

        return redirect()->route('loan-applications.index')->with('success', 'Application submitted successfully!');
    }

    private function generateTrackId() {
        $maxNum = DB::table('loans')->selectRaw('CAST(SUBSTR(loan_track_id, 3) AS UNSIGNED) as num')->orderByDesc('num')->first();
        return 'WL' . str_pad(($maxNum ? (int) $maxNum->num : 0) + 1, 7, '0', STR_PAD_LEFT);
    }

    public function getDistricts($id) { return response()->json(District::where('region_id', $id)->get(['id', 'name'])); }
    public function getCouncils($id) { return response()->json(Council::where('district_id', $id)->get(['id', 'name'])); }
    public function getWards($id) { return response()->json(Ward::where('council_id', $id)->get(['id', 'name'])); }
    public function getStreets($id) { return response()->json(Street::where('ward_id', $id)->get(['id', 'name'])); }
}