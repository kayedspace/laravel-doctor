<?php

declare(strict_types=1);

namespace App\Http\Middleware;

class EncryptCookies
{
    protected $except = ['public_cookie'];
}
