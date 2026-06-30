<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $action = $this->input('action');

        return [
            'action' => 'required|string',
            'comments' => [
                Rule::requiredIf(in_array($action, ['forward_director', 'forward_km', 'decline_amount'], true)),
                'nullable',
                'string',
                'max:2000',
            ],
            'proposed_amount' => [
                Rule::requiredIf($action === 'propose_amount'),
                'nullable',
                'numeric',
                'min:1',
            ],
            'disbursed_amount' => [
                Rule::requiredIf($action === 'disburse'),
                'nullable',
                'numeric',
                'min:1',
            ],
            'accountant_id' => [
                Rule::requiredIf($action === 'assign_accountant'),
                'nullable',
                'exists:users,id',
            ],
            'attachment' => 'nullable|file|max:5120',
        ];
    }
}
