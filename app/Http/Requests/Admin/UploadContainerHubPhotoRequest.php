<?php

namespace App\Http\Requests\Admin;

use App\Support\ContainerPhotoUploadRules;
use Illuminate\Foundation\Http\FormRequest;

class UploadContainerHubPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already restricted to admins via the role middleware.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ContainerPhotoUploadRules::hubPhotosRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photos.required' => 'Select at least one photo to upload.',
            'photos.*.image' => 'Each evidence file must be an image.',
        ];
    }
}
