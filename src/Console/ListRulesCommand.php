<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Console;

use Illuminate\Console\Command;
use kayedspace\Doctor\Rules\RuleCatalog;

class ListRulesCommand extends Command
{
    protected $signature = 'doctor:rules';

    protected $description = 'List available Laravel Doctor rules';

    public function handle(RuleCatalog $catalog): int
    {
        $rows = array_map(
            fn (array $rule): array => [
                $rule['id'],
                $rule['name'],
                $rule['category'],
                $rule['severity'],
                $rule['confidence'],
                $rule['beta'] ? 'yes' : 'no',
                implode(',', $rule['capabilities']),
            ],
            $catalog->all()
        );

        $this->table(
            ['ID', 'Name', 'Category', 'Severity', 'Confidence', 'Beta', 'Capabilities'],
            $rows
        );

        return 0;
    }
}
