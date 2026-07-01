<?php

namespace App\Http\Requests\Loan;

use App\Http\Requests\Loan\Concerns\ValidatesLoanApplication;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanApplicationRequest extends FormRequest
{
    use ValidatesLoanApplication;

    public function authorize(): bool
    {
        $loan = $this->route('loan');

        return $loan && $loan->isEditableByApplicant($this->user());
    }

    public function rules(): array
    {
        return array_merge($this->loanApplicationRules(updating: true), [
            'declaration' => 'accepted',
        ]);
    }
}
