<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class DebugFunctionLocationsController
{
    public function inspect(): void
    {
        dd('first');

        dump('second');

        var_dump('third');

        print_r('fourth');
    }
}
