<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class InlineSuppressionController
{
    public function suppressed(): void
    {
        // @doctor-ignore development.debug-function
        dd('hidden');

        dump('hidden too'); // @doctor-ignore

        // @doctor-ignore security.command-injection
        var_dump('still visible');
    }
}
