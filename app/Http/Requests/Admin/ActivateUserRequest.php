<?php

namespace App\Http\Requests\Admin;

use App\Support\StaffAdminScope;
use Illuminate\Foundation\Http\FormRequest;

class ActivateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $this->user()->can('manage users')
            && $this->user()->can('activate users')
            && StaffAdminScope::canManage($this->user(), $target);
    }

    public function rules(): array
    {
        return [];
    }
}
