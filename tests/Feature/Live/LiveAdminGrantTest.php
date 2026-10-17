<?php

declare(strict_types=1);

use Simtabi\Laranail\DBConsole\Engines\MySqlEngine;
use Simtabi\Laranail\DBConsole\Doctor\MinimalAdminGrant;

/*
 * The live job's MySQL admin is meant to hold exactly the grant db-console's
 * doctor recommends, so the screens are proven under the privileges people are
 * told to give it. This keeps the workflow and that recommendation the same.
 * It is not in the `live` group: it reads a file, not a server.
 */

it('provisions the live MySQL admin with exactly the grant db-console recommends', function (): void {
    if (! class_exists(MinimalAdminGrant::class)) {
        $this->markTestSkipped('the installed laranail/db-console predates MinimalAdminGrant');
    }

    $workflow = file_get_contents(dirname(__DIR__, 3) . '/.github/workflows/tests.yml');
    expect($workflow)->toBeString();

    expect(preg_match('/# >>> minimal-admin\n(.*?)\n\s*# <<< minimal-admin/s', (string) $workflow, $block))->toBe(1);

    $lines = array_map(
        static fn (string $line): string => str_replace("IDENTIFIED BY 'secret'", "IDENTIFIED BY '...'", preg_replace('/^\s*echo "(.*)"$/', '$1', $line) ?? ''),
        array_values(array_filter(explode("\n", $block[1]), static fn (string $line): bool => str_contains($line, 'echo "'))),
    );

    expect($lines)->toBe(MinimalAdminGrant::statements(new MySqlEngine));
});
