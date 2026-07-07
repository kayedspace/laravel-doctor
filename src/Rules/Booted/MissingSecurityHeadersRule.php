<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Support\Runtime\RuntimeHttpProbe;

class MissingSecurityHeadersRule extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::SecurityMissingSecurityHeaders;

    public function analyze(array $files = []): array
    {
        $context = $this->requireProbeContext();

        $findings = [];

        foreach ($context->probePaths as $path) {
            try {
                $response = RuntimeHttpProbe::probe($path);
                $headers = $response->headers;

                $missing = [];
                if (! $headers->has('Strict-Transport-Security')) {
                    $missing[] = 'Strict-Transport-Security';
                }
                if (! $headers->has('X-Frame-Options')) {
                    $missing[] = 'X-Frame-Options';
                }
                if (! $headers->has('X-Content-Type-Options')) {
                    $missing[] = 'X-Content-Type-Options';
                }

                if (! empty($missing)) {
                    $findings[] = $this->makeFinding(
                        message: "HTTP probe at '{$path}' is missing security headers: ".implode(', ', $missing),
                        evidence: "path: {$path}, missing: ".implode(', ', $missing)
                    );
                }
            } catch (\Throwable $e) {
                // ignore failed probes
            }
        }

        return $findings;
    }
}
