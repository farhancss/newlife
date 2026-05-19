<?php

namespace App\Enums;

final class UserRole
{
    public const STUDENT = 'student';
    public const ADMIN = 'admin';

    public static function values(): array
    {
        return [
            self::STUDENT,
            self::ADMIN,
        ];
    }
}
