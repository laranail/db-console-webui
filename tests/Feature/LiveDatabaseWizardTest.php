<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PDO;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\DatabaseWizard;

/**
 * Drives the wizard against a real MySQL server, proving the UI actually goes
 * through the audited core to create a live database — zero logic of its own.
 */
beforeEach(function (): void {
    $host = (string) env('DB_CONSOLE_TEST_MYSQL_HOST', '127.0.0.1');
    $port = (int) env('DB_CONSOLE_TEST_MYSQL_PORT', 33061);
    $user = (string) env('DB_CONSOLE_TEST_MYSQL_USER', 'db_console_admin');
    $pass = (string) env('DB_CONSOLE_TEST_MYSQL_PASSWORD', 'admin-secret-change-me');

    try {
        (new PDO("mysql:host={$host};port={$port}", $user, $pass, [PDO::ATTR_TIMEOUT => 2]))->query('SELECT 1');
    } catch (Throwable $e) {
        $this->markTestSkipped("Docker MySQL not reachable ({$e->getMessage()}).");
    }

    config()->set('database.connections.ui_mysql', [
        'driver' => 'mysql', 'host' => $host, 'port' => $port, 'database' => 'db_console_demo',
        'username' => $user, 'password' => $pass, 'charset' => 'utf8mb4', 'prefix' => '',
    ]);
    config()->set('laranail.db-console.servers.primary', ['engine' => 'mysql', 'connection' => 'ui_mysql', 'tls' => ['enabled' => false]]);
    config()->set('laranail.db-console.default_server', 'primary');
    $this->migrateCatalog();
    Gate::before(fn ($user = null): bool => true);

    $this->db = 'dbc_ui_' . substr(bin2hex(random_bytes(4)), 0, 8);
});

afterEach(function (): void {
    try {
        DB::connection('ui_mysql')->statement("DROP DATABASE IF EXISTS `{$this->db}`");
    } catch (Throwable) {
    }
});

it('creates a real database on MySQL through the core service, then drops it', function (): void {
    Livewire::test(DatabaseWizard::class)
        ->set('name', $this->db)
        ->call('create')
        ->assertHasNoErrors()
        ->assertSee($this->db);

    // The database really exists on the server.
    $exists = DB::connection('ui_mysql')->selectOne(
        'SELECT COUNT(*) c FROM information_schema.schemata WHERE schema_name = ?',
        [$this->db],
    );
    expect((int) $exists->c)->toBe(1);

    Livewire::test(DatabaseWizard::class)
        ->set('confirmName', $this->db)
        ->call('drop')
        ->assertHasNoErrors();

    $gone = DB::connection('ui_mysql')->selectOne(
        'SELECT COUNT(*) c FROM information_schema.schemata WHERE schema_name = ?',
        [$this->db],
    );
    expect((int) $gone->c)->toBe(0);
});
