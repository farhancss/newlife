<?php

namespace App\Http\Requests\Admin;

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
        $mimes = implode(',', (array) config('portal.container_photos.allowed_mimes', ['jpeg', 'png']));
        $maxKb = (int) config('portal.container_photos.max_size_kb', 5120);

        return [
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['file', 'image', "mimes:{$mimes}", "max:{$maxKb}"],
        ];
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
