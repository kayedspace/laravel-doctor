<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class RawSqlInterpolationController
{
    public function unsafe(string $name): void
    {
        User::whereRaw('name = '.$name)->get();
        User::orderByRaw("created_at {$name}")->get();
        DB::raw('id = '.$name);
    }

    public function safe(): void
    {
        User::whereRaw('name = ?', ['ali'])->get();
    }

    public function dynamicCalls($className, $methodName): void
    {
        $className::raw('id = 1');
        $this->getClass()::raw('id = 1');
        $this->{$methodName}();
    }
}
