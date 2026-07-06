<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizesIdentityFields;
use App\Rules\TanzaniaPhone;
use App\Rules\UniqueEmail;
use App\Rules\UniquePhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    use NormalizesIdentityFields;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeIdentityInput(['phone', 'email']);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255', 'min:2'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255', 'min:2'],
            'email' => ['required', 'email', 'max:255', new UniqueEmail],
            'phone' => ['required', 'string', new TanzaniaPhone, new UniquePhone],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
