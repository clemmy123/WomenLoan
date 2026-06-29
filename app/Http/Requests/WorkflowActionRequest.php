<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkflowActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|string',
            'comments' => 'nullable|string|max:2000',
            'proposed_amount' => 'nullable|numeric|min:0',
            'disbursed_amount' => 'nullable|numeric|min:0',
            'accountant_id' => 'nullable|exists:users,id',
            'attachment' => 'nullable|file|max:5120',
        ];
    }
}
