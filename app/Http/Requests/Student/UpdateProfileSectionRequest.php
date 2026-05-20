<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'section' => $this->input('section') !== null ? (int) $this->input('section') : null,
            'action' => in_array($this->input('action'), ['save', 'next'], true)
                ? $this->input('action')
                : 'save',
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return array_merge(
            [
                'section' => ['required', 'integer', 'between:1,4'],
                'action' => ['required', 'in:save,next'],
            ],
            $this->sectionRules($this->resolvedSection())
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid phone number.',
            'parent_phone.regex' => 'Please enter a valid phone number for your parent or guardian.',
            'incoming_year.digits' => 'Please enter a 4-digit incoming year (e.g. 2026).',
            'country_code.size' => 'Please use a 2-letter country code (e.g. US).',
            'country_code.alpha' => 'Please use letters only for the country code.',
            'move_in_date.date_format' => 'Please pick a valid move-in date.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'incoming_year' => 'incoming year',
            'parent_name' => 'parent or guardian name',
            'parent_email' => 'parent or guardian email',
            'parent_phone' => 'parent or guardian phone',
            'parent_relationship' => 'relationship',
            'line1' => 'street address',
            'line2' => 'apartment, suite, or unit',
            'region' => 'state',
            'postal_code' => 'ZIP code',
            'country_code' => 'country',
            'residence_hall' => 'dorm / residence hall',
            'move_in_date' => 'move-in date',
            'move_in_window' => 'move-in time window',
            'delivery_notes' => 'dorm delivery notes',
            'shipping_notes' => 'home pickup notes',
        ];
    }

    public function resolvedSection(): int
    {
        $section = (int) $this->input('section', 1);

        return $section >= 1 && $section <= 4 ? $section : 1;
    }

    public function resolvedAction(): string
    {
        $action = (string) $this->input('action', 'save');

        return in_array($action, ['save', 'next'], true) ? $action : 'save';
    }

    /**
     * Validated payload for the active section (excludes routing flags).
     *
     * @return array<string, mixed>
     */
    public function sectionData(): array
    {
        $data = $this->validated();
        unset($data['section'], $data['action']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return route('student.profile', ['section' => $this->resolvedSection()]);
    }

    /**
     * @return array<string, list<string>>
     */
    private function sectionRules(int $section): array
    {
        return match ($section) {
            1 => [
                'first_name' => ['bail', 'required', 'string', 'min:2', 'max:100'],
                'last_name' => ['bail', 'required', 'string', 'min:2', 'max:100'],
                'phone' => ['bail', 'required', 'string', 'max:30', 'regex:/^[0-9+\-\s().]+$/'],
                'school' => ['bail', 'required', 'string', 'min:2', 'max:150'],
                'incoming_year' => ['bail', 'required', 'digits:4'],
            ],
            2 => [
                'parent_name' => ['bail', 'required', 'string', 'min:2', 'max:150'],
                'parent_email' => ['bail', 'required', 'email:rfc,dns', 'max:255'],
                'parent_phone' => ['bail', 'required', 'string', 'max:30', 'regex:/^[0-9+\-\s().]+$/'],
                'parent_relationship' => ['bail', 'required', 'string', 'min:2', 'max:50'],
            ],
            3 => [
                'line1' => ['bail', 'required', 'string', 'min:5', 'max:255'],
                'line2' => ['nullable', 'string', 'max:255'],
                'city' => ['bail', 'required', 'string', 'min:2', 'max:100'],
                'region' => ['bail', 'required', 'string', 'min:2', 'max:100'],
                'postal_code' => ['bail', 'required', 'string', 'min:3', 'max:20'],
                'country_code' => ['bail', 'required', 'string', 'size:2', 'alpha'],
                'shipping_notes' => ['nullable', 'string', 'max:1000'],
            ],
            4 => [
                'university' => ['bail', 'required', 'string', 'min:2', 'max:150'],
                'residence_hall' => ['bail', 'required', 'string', 'min:2', 'max:150'],
                'building' => ['nullable', 'string', 'max:100'],
                'room' => ['nullable', 'string', 'max:50'],
                'move_in_date' => ['bail', 'required', 'date_format:Y-m-d'],
                'move_in_window' => ['nullable', 'string', 'max:100'],
                'delivery_notes' => ['nullable', 'string', 'max:1000'],
            ],
            default => [],
        };
    }
}
