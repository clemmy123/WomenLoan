<?php

namespace App\Http\Requests;

use App\Rules\AccountantUser;
use App\Support\IdentityNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['proposed_amount', 'disbursed_amount'] as $field) {
            if ($this->has($field)) {
                $merge[$field] = IdentityNormalizer::normalizeAmount($this->input($field));
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $action = $this->input('action');

        return [
            'action' => 'required|string',
            'comments' => ['required', 'string', 'max:2000'],
            'proposed_amount' => [
                Rule::requiredIf($action === 'propose_amount'),
                'nullable',
                'numeric',
                'min:1',
            ],
            'disbursed_amount' => [
                'prohibited',
            ],
            'accountant_id' => [
                Rule::requiredIf($action === 'assign_accountant'),
                'nullable',
                'integer',
                new AccountantUser,
            ],
            'grace_period_months' => [
                Rule::requiredIf($action === 'disburse'),
                'nullable',
                'integer',
                'min:0',
                'max:12',
            ],
            'attachment' => [
                Rule::requiredIf(in_array($action, ['forward_ministry', 'forward_ass_dir'], true)),
                'nullable',
                'file',
                'mimes:pdf',
                'max:1024',
            ],
        ];
    }
}
