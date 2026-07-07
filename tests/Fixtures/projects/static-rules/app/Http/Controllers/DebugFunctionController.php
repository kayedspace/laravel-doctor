<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class DebugFunctionController
{
    public function show(): void
    {
        dd('stop');
        dump(['payload' => true]);
        var_dump('value');
        print_r(['debug' => true]);
    }
}
