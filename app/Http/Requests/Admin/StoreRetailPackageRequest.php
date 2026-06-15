<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ResolvesTrackingNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRetailPackageRequest extends FormRequest
{
    use ResolvesTrackingNumber;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->resolveTrackingNumber();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var array<int, string> $retailers */
        $retailers = config('portal.retail_packages.retailers', []);

        return [
            'student_profile_id' => ['required', 'integer', 'exists:student_profiles,id'],
            'retailer' => ['required', 'string', Rule::in($retailers)],
            'description' => ['required', 'string', 'max:255'],
            'tracking_number' => ['required', 'string', 'max:64'],
            'tracking_url' => ['nullable', 'url', 'max:2048'],
            'estimated_arrival' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
