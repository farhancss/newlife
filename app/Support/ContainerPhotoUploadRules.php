<?php

namespace App\Support;

final class ContainerPhotoUploadRules
{
    /** Hard ceiling for a single photo upload (KB). */
    public const MAX_FILE_SIZE_KB = 5120;

    /** Hard ceiling for student container photos per request. */
    public const MAX_PHOTOS_PER_CONTAINER = 5;

    /** Hard ceiling for admin hub evidence photos per request. */
    public const MAX_HUB_PHOTOS_PER_CONTAINER = 5;

    /** @var list<string> */
    public const ALLOWED_MIME_TYPES = ['jpeg', 'jpg', 'png'];

    /**
     * @return array<string, list<string>>
     */
    public static function studentPhotosRules(): array
    {
        return [
            'photos' => ['required', 'array', 'min:1', 'max:'.self::MAX_PHOTOS_PER_CONTAINER],
            'photos.*' => ['file', 'image', 'mimes:'.implode(',', self::ALLOWED_MIME_TYPES), 'max:'.self::MAX_FILE_SIZE_KB],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function hubPhotosRules(): array
    {
        return [
            'photos' => ['required', 'array', 'min:1', 'max:'.self::MAX_HUB_PHOTOS_PER_CONTAINER],
            'photos.*' => ['file', 'image', 'mimes:'.implode(',', self::ALLOWED_MIME_TYPES), 'max:'.self::MAX_FILE_SIZE_KB],
        ];
    }
}
