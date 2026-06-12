<?php

namespace App\Http\Requests\Student;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRetailPackageRequest extends FormRequest
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
        /** @var array<int, string> $retailers */
        $retailers = config('portal.retail_packages.retailers', []);

        return [
            'retailer' => ['required', 'string', Rule::in($retailers)],
            'description' => ['required', 'string', 'max:255'],
            'tracking_number' => ['required', 'string', 'max:64'],
            'estimated_arrival' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'acknowledge' => $this->alreadyAcknowledged() ? ['nullable'] : ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'acknowledge.accepted' => 'You must acknowledge that only logged packages can be accepted.',
            'acknowledge.required' => 'You must acknowledge that only logged packages can be accepted.',
        ];
    }

    private function alreadyAcknowledged(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return StudentProfile::query()
            ->where('user_id', $user->id)
            ->whereNotNull('retail_packages_acknowledged_at')
            ->exists();
    }
}
