<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizesIdentityFields;
use App\Rules\TanzanianNin;
use App\Rules\TanzaniaPhone;
use App\Rules\UniqueEmail;
use App\Rules\UniqueNin;
use App\Rules\UniquePhone;
use App\Services\Nida\NidaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    use NormalizesIdentityFields;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeIdentityInput(['phone', 'email', 'nin']);

        if ((bool) config('services.nida.enabled')) {
            $identity = app(NidaService::class)->pullVerified((string) $this->input('nin', ''));

            if ($identity !== null) {
                $this->merge([
                    'nin' => $identity->nin,
                    'first_name' => $identity->firstName,
                    'middle_name' => $identity->middleName,
                    'last_name' => $identity->lastName,
                    'dob' => $identity->dateOfBirth->format('Y-m-d'),
                    'sex' => 'Female',
                    'nationality' => $identity->nationality,
                ]);
            }

            return;
        }

        $this->merge([
            'sex' => 'Female',
            'nationality' => filled($this->input('nationality')) ? trim((string) $this->input('nationality')) : 'Tanzanian',
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:255', 'min:2'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255', 'min:2'],
            'email' => ['required', 'email', 'max:255', new UniqueEmail],
            'phone' => ['required', 'string', new TanzaniaPhone, new UniquePhone],
            'password' => ['required', 'confirmed', Password::defaults()],
            'nin' => ['required', 'string', new TanzanianNin, new UniqueNin],
            'dob' => ['required', 'date', 'before:today'],
            'sex' => ['required', 'string', 'in:Female'],
            'nationality' => ['nullable', 'string', 'max:255'],
        ];

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! (bool) config('services.nida.enabled')) {
                return;
            }

            $identity = app(NidaService::class)->pullVerified((string) $this->input('nin', ''));

            if ($identity === null) {
                $validator->errors()->add('nin', __('nida.must_verify'));
            }
        });
    }
}
