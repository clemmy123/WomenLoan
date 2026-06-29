<?php

namespace App\Http\Controllers;

use App\Models\LoanPayment;

class LoanPaymentController extends Controller
{
    public function index()
    {
        $this->authorize('view repayments');

        $payments = LoanPayment::with('loan.applicant')->latest()->paginate(20);

        return view('repayments.index', compact('payments'));
    }
}
