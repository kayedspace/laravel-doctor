<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class EnvLocationController
{
    public function values(): array
    {
        return [
            env('FIRST_VALUE'),
            env('SECOND_VALUE'),
        ];
    }
}
