<?php

namespace App\Http\Requests\Admin;

use App\Enums\RetailPackageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRetailPackageStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(RetailPackageStatus::ordered())],
            'status_note' => ['nullable', 'string', 'max:255'],
            'force_status' => ['sometimes', 'boolean'],
        ];
    }
}
