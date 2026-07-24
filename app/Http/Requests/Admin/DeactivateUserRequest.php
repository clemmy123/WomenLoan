<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class DeactivateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $target = $this->route('user');

        return $actor->can('manage users')
            && $actor->can('deactivate users')
            && $actor->id !== $target->id;
    }

    public function rules(): array
    {
        return [
            'deactivation_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'deactivation_reason' => __('admin.deactivation_reason'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $user = $this->route('user');

        session()->flash('deactivate_user', [
            'name' => $user->name,
            'url' => route('admin.users.deactivate', $user),
        ]);

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo(url()->previous());
    }
}
