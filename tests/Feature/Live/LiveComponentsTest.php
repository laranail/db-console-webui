<?php

declare(strict_types=1);

use Livewire\Livewire;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Gate;
use Simtabi\Laranail\DBConsoleWebUI\Support\Translations;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\AccountManager;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\DatabaseWizard;

/*
 * The Livewire screens against real servers: the `live` job in
 * .github/workflows/tests.yml starts MySQL 8.4 and PostgreSQL 18, creates a
 * restricted admin on each -- db-console's recommended minimal grant on MySQL,
 * CREATEDB CREATEROLE on Postgres, never root or a superuser -- sets the env
 * read below, and runs this group with --fail-on-skipped, so a missing
 * variable fails that job instead of passing it empty. Everywhere else each
 * test skips.
 *
 * The rest of the suite drives these components against SQLite, which has no
 * accounts and whose databases are files, so it cannot say whether a click in
 * the UI provisions anything on the servers people actually run.
 */

uses()->group('live');

function webuiLive(string $engine, string $variable, string $default = ''): string
{
    $value = getenv('DB_CONSOLE_LIVE_' . strtoupper($engine) . "_{$variable}");

    return $value === false || $value === '' ? $default : $value;
}

function webuiLiveName(string $prefix): string
{
    return $prefix . '_' . bin2hex(random_bytes(4));
}

/**
 * @return array<string, mixed>
 */
function webuiLiveConnection(string $engine, ?string $database, string $username, string $password): array
{
    $common = [
        'host'     => webuiLive($engine, 'HOST', '127.0.0.1'),
        'port'     => webuiLive($engine, 'PORT'),
        'username' => $username,
        'password' => $password,
        'prefix'   => '',
    ];

    return $engine === 'mysql'
        ? $common + ['driver' => 'mysql', 'database' => $database, 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'strict' => true]
        : $common + ['driver' => 'pgsql', 'database' => $database ?? 'postgres', 'charset' => 'utf8', 'schema' => 'public', 'sslmode' => 'disable'];
}

/**
 * A connection as the admin, or as an account the UI just created -- the
 * second proves the generated password is the one the server holds.
 */
function webuiLiveAs(string $engine, ?string $user = null, ?string $password = null): Connection
{
    $name = "webui_live_{$engine}_" . ($user ?? 'admin');
    config()->set("database.connections.{$name}", webuiLiveConnection(
        $engine,
        null,
        $user ?? webuiLive($engine, 'ADMIN_USER'),
        $password ?? webuiLive($engine, 'ADMIN_PASSWORD'),
    ));

    return DB::connection($name);
}

function webuiLiveDatabaseCount(string $engine, string $db): int
{
    $sql = $engine === 'mysql'
        ? 'SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = ?'
        : 'SELECT COUNT(*) FROM pg_database WHERE datname = ?';

    return (int) webuiLiveAs($engine)->scalar($sql, [$db]);
}

function webuiLiveAccountCount(string $engine, string $user, string $host): int
{
    return $engine === 'mysql'
        ? (int) webuiLiveAs($engine)->scalar('SELECT COUNT(*) FROM mysql.user WHERE User = ? AND Host = ?', [$user, $host])
        : (int) webuiLiveAs($engine)->scalar('SELECT COUNT(*) FROM pg_roles WHERE rolname = ?', [$user]);
}

/**
 * Point db-console's default server at the live one, as the restricted admin.
 */
function webuiLiveServer(string $engine): void
{
    config()->set("database.connections.live_{$engine}", webuiLiveConnection(
        $engine,
        null,
        webuiLive($engine, 'ADMIN_USER'),
        webuiLive($engine, 'ADMIN_PASSWORD'),
    ));
    config()->set("laranail.db-console.servers.live_{$engine}", [
        'engine' => $engine, 'connection' => "live_{$engine}", 'tls' => ['enabled' => false],
    ]);
    config()->set('laranail.db-console.default', "live_{$engine}");
}

beforeEach(function (): void {
    // The screens authorize through db-console's gate; this suite is about
    // what reaches the server, not who may click.
    Gate::before(fn ($user = null): bool => true);
    $this->migrateCatalog();

    // The databases here are created empty, so backup-before-drop has nothing
    // to snapshot; that path is db-tools' and has its own suite.
    config()->set('laranail.db-console.backup.enabled', false);

    $this->cleanup = [];
});

afterEach(function (): void {
    if (! property_exists($this, 'cleanup') || $this->cleanup === null) {
        return;
    }

    // Best effort. A test that already cleaned up leaves nothing here.
    foreach ($this->cleanup as [$engine, $kind, $name, $host]) {
        try {
            $admin = webuiLiveAs($engine);
            if ($kind === 'account' && webuiLiveAccountCount($engine, $name, $host) === 1) {
                $admin->statement($engine === 'mysql' ? "DROP USER '{$name}'@'{$host}'" : "DROP ROLE \"{$name}\"");
            }
            if ($kind === 'database' && webuiLiveDatabaseCount($engine, $name) === 1) {
                $admin->statement($engine === 'mysql' ? "DROP DATABASE `{$name}`" : "DROP DATABASE \"{$name}\"");
            }
        } catch (Throwable) {
            // leave it; the service container is discarded with the job
        }
    }
});

dataset('live engines', [
    'mysql 8.4' => ['mysql'],
    'pgsql 18'  => ['pgsql'],
]);

function webuiLiveSkipUnlessConfigured(string $engine): void
{
    if (webuiLive($engine, 'PORT') === '' || webuiLive($engine, 'ADMIN_USER') === '') {
        test()->markTestSkipped('needs DB_CONSOLE_LIVE_' . strtoupper($engine) . '_PORT and _ADMIN_USER (the live CI job)');
    }
}

it('runs as the restricted admin, not root or a superuser', function (string $engine): void {
    webuiLiveSkipUnlessConfigured($engine);

    // Without this the rest of the group proves nothing: root and a superuser
    // bypass every privilege check a managed database's admin is held to.
    if ($engine === 'mysql') {
        expect(webuiLiveAs($engine)->scalar('SELECT CURRENT_USER()'))->toBe(webuiLive($engine, 'ADMIN_USER') . '@%');
        expect((int) webuiLiveAs($engine)->scalar('SELECT @@GLOBAL.sql_require_primary_key'))->toBe(1);

        return;
    }

    $role = (array) webuiLiveAs($engine)->selectOne('SELECT current_user AS name, rolsuper, rolcreatedb, rolcreaterole FROM pg_roles WHERE rolname = current_user');
    expect($role)->toBe(['name' => webuiLive($engine, 'ADMIN_USER'), 'rolsuper' => false, 'rolcreatedb' => true, 'rolcreaterole' => true]);
})->with('live engines');

it('creates and then drops a database through the database wizard', function (string $engine): void {
    webuiLiveSkipUnlessConfigured($engine);
    webuiLiveServer($engine);

    $db = webuiLiveName('webui_wiz');
    $this->cleanup = [[$engine, 'database', $db, null]];

    Livewire::test(DatabaseWizard::class)
        ->set('name', $db)
        ->call('create')
        ->assertHasNoErrors()
        ->assertSet('error', null)
        ->assertSet('flash', Translations::get('ui.created'));

    expect(webuiLiveDatabaseCount($engine, $db))->toBe(1);

    // A fresh mount reads the server's list, as a page load would.
    Livewire::test(DatabaseWizard::class)
        ->assertSee($db)
        ->set('confirmName', $db)
        ->call('drop')
        ->assertHasNoErrors()
        ->assertSet('error', null)
        ->assertSet('flash', Translations::get('ui.dropped'));

    expect(webuiLiveDatabaseCount($engine, $db))->toBe(0);
})->with('live engines');

it('creates an account through the account manager that can log in with the password it shows', function (string $engine): void {
    webuiLiveSkipUnlessConfigured($engine);
    webuiLiveServer($engine);

    $user = webuiLiveName('webui_acct');
    // MySQL scopes an account by host, and the runner reaches the service
    // container over its bridge, never as localhost. Postgres has no host
    // scope and ignores the field.
    $host = $engine === 'mysql' ? '%' : 'localhost';
    $this->cleanup = [[$engine, 'account', $user, $host]];

    $component = Livewire::test(AccountManager::class)
        ->set('username', $user)
        ->set('host', $host)
        ->call('create')
        ->assertHasNoErrors()
        ->assertSet('error', null)
        ->assertSet('flash', Translations::get('ui.created'));

    expect(webuiLiveAccountCount($engine, $user, $host))->toBe(1);

    $password = $component->get('generatedPassword');
    expect($password)->toBeString()->not->toBeEmpty();

    $whoami = webuiLiveAs($engine, $user, $password)->scalar($engine === 'mysql' ? 'SELECT CURRENT_USER()' : 'SELECT current_user');
    expect($whoami)->toBe($engine === 'mysql' ? "{$user}@%" : $user);
    DB::purge("webui_live_{$engine}_{$user}");
})->with('live engines');

it('creates and drops an account on a managed server without being told its host', function (string $engine): void {
    webuiLiveSkipUnlessConfigured($engine);
    webuiLiveServer($engine);
    config()->set("laranail.db-console.servers.live_{$engine}.hosting", 'generic');

    $user = webuiLiveName('webui_managed');
    // A managed MySQL scopes new accounts to any host; Postgres has none.
    $host = $engine === 'mysql' ? '%' : 'localhost';
    $this->cleanup = [[$engine, 'account', $user, $host]];

    Livewire::test(AccountManager::class)
        ->assertSet('host', $host)
        ->set('username', $user)
        ->call('create')
        ->assertHasNoErrors()
        ->assertSet('error', null);

    expect(webuiLiveAccountCount($engine, $user, $host))->toBe(1);

    $listed = $engine === 'mysql' ? "{$user}@%" : $user;
    $screen = Livewire::test(AccountManager::class)->assertSee($listed);
    if ($engine === 'mysql') {
        // The engine's own accounts are the provider's, not the operator's.
        $screen->assertDontSee('mysql.sys');
    }

    $screen->call('chooseDrop', $listed)
        ->set('confirmDrop', $user)
        ->call('drop')
        ->assertHasNoErrors()
        ->assertSet('error', null)
        ->assertSet('flash', Translations::get('ui.dropped'));

    expect(webuiLiveAccountCount($engine, $user, $host))->toBe(0);
})->with('live engines');
