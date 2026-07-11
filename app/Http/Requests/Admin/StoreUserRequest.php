<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesIdentityFields;
use App\Rules\TanzaniaPhone;
use App\Rules\UniqueEmail;
use App\Rules\UniquePhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    use NormalizesIdentityFields;

    public function authorize(): bool
    {
        return $this->user()->can('manage users');
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeIdentityInput(['phone', 'email']);

        if ($this->filled('check_number')) {
            $digits = preg_replace('/\D+/', '', (string) $this->input('check_number')) ?? '';
            $this->merge([
                'check_number' => substr($digits, 0, 10),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'check_number' => ['required', 'digits_between:1,10', 'unique:users,check_number'],
            'first_name' => ['required', 'string', 'max:255', 'min:2'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255', 'min:2'],
            'email' => ['required', 'email', 'max:255', new UniqueEmail],
            'phone' => ['required', 'string', new TanzaniaPhone, new UniquePhone],
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
            'zone_type' => 'nullable|in:region,council,ward',
            'zone_id' => 'nullable|integer',
            'is_active' => 'boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $actor = $this->user();
            $canActivate = $actor->can('activate users');
            $canDeactivate = $actor->can('deactivate users');

            if (! $canActivate && ! $canDeactivate) {
                return;
            }

            if (! $this->has('is_active')) {
                return;
            }

            $desired = $this->boolean('is_active');

            if ($desired && ! $canActivate) {
                $validator->errors()->add('is_active', __('messages.cannot_activate_users'));
            }

            if (! $desired && ! $canDeactivate) {
                $validator->errors()->add('is_active', __('messages.cannot_deactivate_users'));
            }
        });
    }
}
