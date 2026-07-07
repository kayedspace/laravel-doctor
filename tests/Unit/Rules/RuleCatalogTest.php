<?php

declare(strict_types=1);

use kayedspace\Doctor\Domain\Enums\RuleId;
use kayedspace\Doctor\Rules\RuleCatalog;
use kayedspace\Doctor\Rules\RuleRegistry;

test('rule catalog exposes shared rule metadata', function () {
    $catalog = new RuleCatalog(RuleRegistry::default());

    $rule = $catalog->find(RuleId::SecurityCommandInjection->value);

    expect($rule)->not->toBeNull()
        ->and($rule)->toHaveKeys([
            'id',
            'name',
            'category',
            'severity',
            'confidence',
            'capabilities',
            'beta',
            'description',
            'remediation',
            'examples',
        ])
        ->and($rule['id'])->toBe(RuleId::SecurityCommandInjection->value)
        ->and($rule['severity'])->toBe(RuleId::SecurityCommandInjection->defaultSeverity()->value)
        ->and($rule['examples'])->not->toBeEmpty();
});

test('rule catalog returns all rules sorted by id and null for unknown ids', function () {
    $catalog = new RuleCatalog(RuleRegistry::default());
    $rules = $catalog->all();
    $ids = array_column($rules, 'id');

    expect($ids)->toBe(array_values(collect($ids)->sort()->all()))
        ->and($catalog->find('missing.rule'))->toBeNull();
});
