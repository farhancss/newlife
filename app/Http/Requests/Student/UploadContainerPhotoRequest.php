<?php

namespace App\Http\Requests\Student;

use App\Support\ContainerPhotoUploadRules;
use Illuminate\Foundation\Http\FormRequest;

class UploadContainerPhotoRequest extends FormRequest
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
            ...ContainerPhotoUploadRules::studentPhotosRules(),
            'acknowledge' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'acknowledge.accepted' => 'Please acknowledge the photo disclaimer before uploading.',
            'photos.required' => 'Select at least one photo to upload.',
        ];
    }
}
