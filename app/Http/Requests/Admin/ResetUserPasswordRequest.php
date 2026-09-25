<?php

namespace App\Http\Requests\Admin;

use App\Support\StaffAdminScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $target = $this->route('user');

        return $actor->can('manage users')
            && $actor->can('reset user password')
            && $actor->id !== $target->id
            && StaffAdminScope::canManage($actor, $target);
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
