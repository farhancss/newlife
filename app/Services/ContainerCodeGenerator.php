<?php

namespace App\Services;

use App\Models\Container;

class ContainerCodeGenerator
{
    public function generate(): string
    {
        do {
            $code = 'CTN-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Container::query()->where('code', $code)->exists());

        return $code;
    }
}
