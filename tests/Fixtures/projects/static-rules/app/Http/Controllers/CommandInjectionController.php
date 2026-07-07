<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class CommandInjectionController
{
    public function unsafe(string $command): void
    {
        exec($command);
        shell_exec($command);
        `{$command}`;
    }

    public function safe(): void
    {
        exec('php artisan about');
    }
}
