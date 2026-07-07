<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Enums;

enum RuleCategory: string
{
    case Development = 'development';
    case Framework = 'framework';
    case Migration = 'migration';
    case Security = 'security';
    case Eloquent = 'eloquent';
    case Health = 'health';
    case Dependency = 'dependency';
}
