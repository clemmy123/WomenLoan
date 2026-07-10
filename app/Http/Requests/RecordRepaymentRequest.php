<?php

namespace App\Http\Requests;

use App\Support\IdentityNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordRepaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user?->can('record repayment') || ! $user->hasRole('applicant')) {
            return false;
        }

        $payment = $this->route('payment');

        return $payment && (int) $payment->loan?->user_id === (int) $user->id;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge([
                'amount' => IdentityNormalizer::normalizeAmount($this->input('amount')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'string', Rule::in(config('wdf.payment_methods', []))],
        ];
    }
}
