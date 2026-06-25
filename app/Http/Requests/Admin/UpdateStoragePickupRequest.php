<?php

namespace App\Http\Requests\Admin;

use App\Enums\StoragePickupStatus;
use App\Models\StoragePickup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoragePickupRequest extends FormRequest
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
        $confirmedRules = ['nullable', 'date'];

        // The confirmed pickup must land on or after the date the student
        // requested — never before it.
        $pickup = $this->route('storagePickup');
        if ($pickup instanceof StoragePickup) {
            $confirmedRules[] = 'after_or_equal:' . $pickup->requested_pickup_date->format('Y-m-d');
        }

        return [
            'status' => ['required', 'string', Rule::in(StoragePickupStatus::all())],
            'confirmed_pickup_date' => $confirmedRules,
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirmed_pickup_date.after_or_equal' => "The confirmed pickup date must be on or after the student's requested pickup date.",
        ];
    }
}
