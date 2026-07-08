<?php

declare(strict_types=1);

/*
 * The mandatory boundary: the wrapper is a THIN UI over laranail/db-console.
 * It may call core services and reuse core validation, but it must contain no
 * business logic — no engine or connection use, no raw SQL, no gate or
 * privilege definitions, and no validation rules of its own for a domain
 * field (those come from the core's RuleProvider). These tests fail the build
 * if the boundary is crossed.
 */

arch('every source file declares strict types')
    ->expect('Simtabi\Laranail\DBConsoleWebUI')
    ->toUseStrictTypes();

arch('the UI never touches engines, connections, or the quoter')
    ->expect('Simtabi\Laranail\DBConsoleWebUI')
    ->not->toUse([
        'Simtabi\Laranail\DBConsole\Engines',
        'Simtabi\Laranail\DBConsole\Servers\AdminConnection',
        'Simtabi\Laranail\DBConsole\Domain\Statement',
    ]);

arch('the UI never builds SQL or runs raw queries')
    ->expect('Simtabi\Laranail\DBConsoleWebUI')
    ->not->toUse([
        'Illuminate\Support\Facades\DB',
        'Illuminate\Database\Query\Builder',
        'PDO',
    ]);

/**
 * Source scan: no Livewire component may (a) contain a raw SQL string, (b)
 * define a validation rule for a domain field inline instead of pulling it
 * from RuleProvider, or (c) reference an engine/connection class. Any
 * component that validates a domain field must go through RuleProvider.
 *
 * @return array<string, list<string>> offending file => reasons
 */
function boundaryViolations(): array
{
    $srcDir = dirname(__DIR__, 2) . '/src';
    $violations = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($srcDir, FilesystemIterator::SKIP_DOTS),
    );

    /** @var SplFileInfo $file */
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $path = (string) $file->getRealPath();
        $relative = str_replace($srcDir . DIRECTORY_SEPARATOR, '', $path);
        $contents = (string) file_get_contents($path);
        $reasons = [];

        // Raw SQL keywords in a string literal.
        if (preg_match('/([\'"])\s*(SELECT|INSERT|UPDATE|DELETE|CREATE|DROP|GRANT|ALTER)\s+/i', $contents) === 1) {
            $reasons[] = 'contains a raw SQL string';
        }

        // A component that declares its own rules() must source them from the
        // core RuleProvider (not hand-written rule arrays for domain fields).
        if (preg_match('/function\s+rules\s*\(/', $contents) === 1
            && ! str_contains($contents, 'RuleProvider::')) {
            $reasons[] = 'declares rules() without using the core RuleProvider';
        }

        // No new IdentifierRule/etc. minted in the UI — those belong to the core.
        if (preg_match('/new\s+\\\\?Simtabi\\\\Laranail\\\\DBConsole\\\\Validation\\\\Rules\\\\/', $contents) === 1) {
            $reasons[] = 'instantiates a core validation Rule directly instead of via RuleProvider';
        }

        if ($reasons !== []) {
            $violations[$relative] = $reasons;
        }
    }

    return $violations;
}

test('no Livewire component crosses the boundary (raw SQL, own rules, or minted core Rules)', function (): void {
    expect(boundaryViolations())->toBe([]);
});

test('the boundary scan actually detects a violation (guards against a tautology)', function (): void {
    $srcDir = dirname(__DIR__, 2) . '/src';
    $probeDir = $srcDir . '/Http/Livewire';
    $probe = $probeDir . '/__BoundaryProbe.php';

    file_put_contents($probe, "<?php\n// SELECT * FROM users\n\$sql = 'SELECT 1 FROM t';\n");

    try {
        expect(array_keys(boundaryViolations()))->toContain('Http/Livewire/__BoundaryProbe.php');
    } finally {
        @unlink($probe);
    }
});
