<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class UnserializeController
{
    public function unsafe(string $payload): mixed
    {
        return unserialize($payload);
    }

    public function safe(): mixed
    {
        return unserialize('a:0:{}');
    }
}
