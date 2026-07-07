<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class DynamicEvalController
{
    public function unsafe(string $code): void
    {
        eval($code);
        assert($code);
        create_function('$x', $code);
    }
}
