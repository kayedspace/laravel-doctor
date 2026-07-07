<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class EnvOutsideConfigController
{
    public function token(): string
    {
        return env('API_TOKEN');
    }
}
