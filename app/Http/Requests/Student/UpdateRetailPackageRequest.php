<?php

namespace App\Http\Requests\Student;

use App\Http\Requests\Concerns\ResolvesTrackingNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRetailPackageRequest extends FormRequest
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
            'retailer' => ['required', 'string', Rule::in($retailers)],
            'description' => ['required', 'string', 'max:255'],
            'tracking_number' => ['required', 'string', 'max:64'],
            'tracking_url' => ['nullable', 'url', 'max:2048'],
            'estimated_arrival' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
