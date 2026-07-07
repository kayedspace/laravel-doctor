<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Enums;

enum RuleCapability: string
{
    case Static = 'static';
    case Booted = 'booted';
    case Dependency = 'dependency';
}
