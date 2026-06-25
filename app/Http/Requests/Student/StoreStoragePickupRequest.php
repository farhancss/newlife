<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreStoragePickupRequest extends FormRequest
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
            'requested_pickup_date' => ['required', 'date', 'after:today'],
            'pickup_location' => ['required', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'container_count' => ['nullable', 'integer', 'min:1', 'max:99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'requested_pickup_date' => 'pickup date',
            'pickup_location' => 'pickup location',
            'contact_phone' => 'contact phone',
            'container_count' => 'number of containers',
        ];
    }
}
