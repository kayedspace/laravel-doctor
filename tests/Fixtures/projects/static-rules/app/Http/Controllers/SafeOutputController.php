<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class SafeOutputController
{
    public function show(): void
    {
        echo 'ok';
        echo 'ok';
        printf('ok');

        $message = 'dd("not called")';
        // dump('not called');
    }
}
