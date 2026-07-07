<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use Illuminate\Support\Facades\Route;
use kayedspace\Doctor\Domain\Enums\RuleId;

class LoginNotThrottledRule extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::SecurityLoginNotThrottled;

    public function analyze(array $files = []): array
    {
        $findings = [];
        $routes = Route::getRoutes()->get('POST');

        foreach ($routes as $route) {
            $uri = $route->uri();
            $methods = $route->methods();

            // Check if the URI contains 'login' or the name contains 'login'
            // Usually login page (GET) doesn't strictly need throttling, but POST login definitely does.
            // Let's inspect both or check if it's a POST/GET route.
            if (str_contains(strtolower($uri), 'login') || str_contains(strtolower((string) $route->getName()), 'login')) {
                $middleware = $route->gatherMiddleware();
                $hasThrottle = false;

                foreach ($middleware as $m) {
                    if (is_string($m) && (str_starts_with($m, 'throttle') || str_contains($m, 'throttle'))) {
                        $hasThrottle = true;
                        break;
                    }
                }

                if (! $hasThrottle) {
                    $methodsStr = implode('|', $methods);
                    $findings[] = $this->makeFinding(
                        message: "Login route [{$methodsStr}] '{$uri}' is not throttled.",
                        evidence: "route: {$uri}, methods: {$methodsStr}, middleware: ".implode(', ', $middleware)
                    );
                }
            }
        }

        return $findings;
    }
}
