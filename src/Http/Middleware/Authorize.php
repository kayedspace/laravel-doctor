<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class Authorize
{
    /**
     * Allow the dashboard in local; elsewhere require the `viewDoctor` gate.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (App::environment('local')) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $gateKey = is_string($routeName) && str_starts_with($routeName, 'doctor.api.')
            ? 'doctor.api.gate'
            : 'doctor.ui.gate';
        $gate = Config::get($gateKey, 'viewDoctor');
        if (! is_string($gate) || $gate === '') {
            $gate = 'viewDoctor';
        }

        // ponytail: undefined gate => denied (403). Safe-by-default; hosts opt in.
        Gate::authorize($gate);

        return $next($request);
    }
}
