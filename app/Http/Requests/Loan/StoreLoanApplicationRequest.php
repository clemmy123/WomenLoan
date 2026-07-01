<?php

namespace App\Http\Requests\Loan;

use App\Http\Requests\Loan\Concerns\ValidatesLoanApplication;
use Illuminate\Foundation\Http\FormRequest;

class StoreLoanApplicationRequest extends FormRequest
{
    use ValidatesLoanApplication;

    public function authorize(): bool
    {
        return $this->user()->can('create loan application');
    }

    public function rules(): array
    {
        return $this->loanApplicationRules();
    }
}
