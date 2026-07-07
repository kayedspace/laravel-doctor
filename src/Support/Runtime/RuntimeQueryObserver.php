<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Runtime;

use Illuminate\Support\Facades\DB;

class RuntimeQueryObserver
{
    protected static array $queries = [];

    protected static bool $listening = false;

    public static function start(): void
    {
        self::$queries = [];
        self::$listening = true;

        // Register the DB listener only once per process
        static $registered = false;
        if (! $registered) {
            DB::listen(function ($queryEvent) {
                if (self::$listening) {
                    self::$queries[] = [
                        'sql' => $queryEvent->sql,
                        'bindings' => $queryEvent->bindings,
                    ];
                }
            });
            $registered = true;
        }
    }

    public static function stop(): array
    {
        self::$listening = false;

        return self::$queries;
    }
}
