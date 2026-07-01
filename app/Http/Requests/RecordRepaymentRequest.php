<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordRepaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user?->can('record repayment')) {
            return false;
        }

        if ($user->hasRole(['admin', 'super_admin', 'accountant'])) {
            return true;
        }

        $payment = $this->route('payment');

        return $payment && (int) $payment->loan?->user_id === (int) $user->id;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'reference' => ['nullable', 'string', 'max:100'],
            'method' => ['nullable', 'string', 'max:50'],
        ];
    }
}
