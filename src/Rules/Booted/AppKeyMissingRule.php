<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules\Booted;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Domain\Enums\RuleId;

class AppKeyMissingRule extends AbstractBootedRule
{
    protected const RULE_ID = RuleId::ConfigAppKeyMissing;

    public function analyze(array $files = []): array
    {
        if (! App::environment('production')) {
            return [];
        }

        $key = (string) Config::get('app.key', '');
        $isMissing = false;
        $status = 'valid';

        if ($key === '') {
            $isMissing = true;
            $status = 'missing';
        } elseif (str_contains($key, 'SomeRandomString')) {
            $isMissing = true;
            $status = 'default_placeholder';
        } elseif (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded === false || strlen($decoded) < 32) {
                $isMissing = true;
                $status = 'invalid_base64_or_too_short';
            }
        } elseif (strlen($key) < 32) {
            $isMissing = true;
            $status = 'too_short';
        }

        if ($isMissing) {
            return [
                $this->makeFinding(
                    message: 'The application key (APP_KEY) is missing or unsafe for production use.',
                    evidence: "key_status: {$status}"
                ),
            ];
        }

        return [];
    }
}
