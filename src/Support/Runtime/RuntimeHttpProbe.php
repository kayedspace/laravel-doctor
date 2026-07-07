<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Runtime;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class RuntimeHttpProbe
{
    /**
     * Run a read-only HTTP probe against the given path.
     */
    public static function probe(string $path): Response
    {
        $timeout = Config::get('doctor.runtime.timeout_seconds', 5);
        $originalSocketTimeout = ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', (string) $timeout);

        try {
            $request = Request::create($path, 'GET');
            $response = app()->handle($request);
        } finally {
            ini_set('default_socket_timeout', $originalSocketTimeout);
        }

        return $response;
    }
}
