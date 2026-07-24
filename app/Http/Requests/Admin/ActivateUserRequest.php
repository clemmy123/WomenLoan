<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ActivateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage users')
            && $this->user()->can('activate users');
    }

    public function rules(): array
    {
        return [];
    }
}
