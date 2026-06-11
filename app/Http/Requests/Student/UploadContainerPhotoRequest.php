<?php

namespace App\Http\Requests\Student;

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
        $mimes = implode(',', (array) config('portal.container_photos.allowed_mimes', ['jpeg', 'png']));
        $maxKb = (int) config('portal.container_photos.max_size_kb', 5120);

        return [
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['file', 'image', "mimes:{$mimes}", "max:{$maxKb}"],
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
