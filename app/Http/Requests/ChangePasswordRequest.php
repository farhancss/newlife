<?php

namespace App\Http\Requests;

use App\Support\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $mustReset = (bool) $this->user()?->must_reset_password;

        $rules = [
            'password' => ['required', 'string', 'confirmed', PasswordPolicy::rule()],
        ];

        if (!$mustReset) {
            $rules['current_password'] = ['required', 'string'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'The password confirmation does not match.',
            'current_password.required' => 'Please enter your current password.',
        ];
    }

    public function attributes(): array
    {
        return [
            'password' => 'new password',
            'password_confirmation' => 'password confirmation',
            'current_password' => 'current password',
        ];
    }

    public function mustResetPassword(): bool
    {
        return (bool) $this->user()?->must_reset_password;
    }
}
