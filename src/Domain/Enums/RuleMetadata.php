<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Domain\Enums;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class RuleMetadata
{
    /**
     * @param  array<int, string>  $examples
     * @param  array<int, string>  $tags
     * @param  array<int, RuleCapability>  $capabilities
     */
    public function __construct(
        public string $ruleName,
        public string $description,
        public RuleCategory $category,
        public Severity $defaultSeverity,
        public string $findingTitle,
        public string $remediation,
        public array $examples = [],
        public Confidence $defaultConfidence = Confidence::High,
        public array $tags = [],
        public bool $isBeta = false,
        public array $capabilities = [RuleCapability::Static],
    ) {}
}
