<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Rules;

class RuleSkippedException extends \Exception
{
    // Signifies that a booted rule was skipped during execution
}
