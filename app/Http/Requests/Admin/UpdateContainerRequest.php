<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContainerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContainerRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(ContainerStatus::ordered())],
            'location' => ['nullable', 'string', 'max:120'],
            'outbound_tracking' => ['nullable', 'string', 'max:64'],
            'return_tracking' => ['nullable', 'string', 'max:64'],
            'ship_by_date' => ['nullable', 'date'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'status_note' => ['nullable', 'string', 'max:255'],
            'force_status' => ['sometimes', 'boolean'],
        ];
    }
}
