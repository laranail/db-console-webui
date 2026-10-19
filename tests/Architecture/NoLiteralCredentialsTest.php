<?php

declare(strict_types=1);

use Simtabi\Laranail\Package\Tools\Testing\AssertsNoLiteralCredentials;

uses(AssertsNoLiteralCredentials::class);

/*
 * The shared check from laranail/package-tools, beside this package's own
 * NoLiteralTestCredentialsTest. That file is skipped here: it holds the
 * samples it feeds its own scanner to prove that scanner can fail.
 */
it('keeps literal credentials out of the tests', function (): void {
    $this->assertNoLiteralCredentialsIn(
        [dirname(__DIR__)],
        minFiles: 11,
        exemptFiles: ['NoLiteralTestCredentialsTest.php'],
    );

    expect(true)->toBeTrue();
});
