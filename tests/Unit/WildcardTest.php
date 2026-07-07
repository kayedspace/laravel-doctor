<?php

declare(strict_types=1);

use kayedspace\Doctor\Support\Wildcard;

test('it matches exact strings and fnmatch wildcards', function () {
    expect(Wildcard::matchesAny('config/app.php', 'config/app.php'))->toBeTrue()
        ->and(Wildcard::matchesAny('app/Models/User.php', 'app/Models/*'))->toBeTrue()
        ->and(Wildcard::matchesAny('config/a.php', 'config/?.php'))->toBeTrue()
        ->and(Wildcard::matchesAny('config/b.php', 'config/[ab].php'))->toBeTrue()
        ->and(Wildcard::matchesAny('security.raw-sql-interpolation', ['framework.*', 'security.raw-*']))->toBeTrue()
        ->and(Wildcard::matchesAny('security.command-injection', ['framework.*', 'queue.?']))->toBeFalse();
});
