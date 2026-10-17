<?php

declare(strict_types=1);

use Livewire\Livewire;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Simtabi\Laranail\DBConsole\Domain\Host;
use Simtabi\Laranail\DBConsole\Domain\DbName;
use Simtabi\Laranail\DBConsole\Domain\Username;
use Simtabi\Laranail\DBConsole\Enums\OperationType;
use Simtabi\Laranail\DBConsole\Exceptions\BackupFailed;
use Simtabi\Laranail\DBConsole\Services\AccountManager;
use Simtabi\Laranail\DBConsole\Services\DatabaseManager;
use Simtabi\Laranail\DBConsole\Services\Results\OperationResult;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\DatabaseWizard;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\ServerSwitcher;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\AccountManager as AccountComponent;

/*
 * The screens read what the core knows about where a server runs
 * (ServerRegistry::capabilities(), defaultAccountHost(), the hosting label)
 * instead of assuming every server is a self-hosted one reached from
 * localhost. A "MySQL" server here is SQLite with a database attached as
 * `mysql`, so MySqlEngine's own `SELECT User, Host FROM mysql.user` runs.
 */

beforeEach(function (): void {
    Gate::before(fn ($user = null): bool => true);
    $this->migrateCatalog();

    config()->set('database.connections.ui_admin', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    config()->set('laranail.db-console.servers.local', ['engine' => 'sqlite', 'connection' => 'ui_admin', 'tls' => ['enabled' => false]]);
    config()->set('laranail.db-console.accounts.default_host', 'localhost');
    config()->set('laranail.db-console.default', 'local');
});

function mysqlLookalike(?string $hosting = null): void
{
    config()->set('database.connections.ui_mysql', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    $connection = DB::connection('ui_mysql');
    $connection->statement("ATTACH DATABASE ':memory:' AS mysql");
    $connection->statement('CREATE TABLE mysql.user (User TEXT, Host TEXT)');
    $connection->insert("INSERT INTO mysql.user (User, Host) VALUES ('shop_app', '%'), ('mysql.sys', 'localhost')");

    $server = ['engine' => 'mysql', 'connection' => 'ui_mysql', 'tls' => ['enabled' => false]];
    if ($hosting !== null) {
        $server['hosting'] = $hosting;
    }
    config()->set('laranail.db-console.servers.my', $server);
    config()->set('laranail.db-console.default', 'my');
}

/**
 * Records drop() calls on a core service, and fails on request.
 */
function recordingDrops(?Throwable $fails = null): object
{
    return new class($fails)
    {
        /** @var list<list<mixed>> */
        public array $drops = [];

        public function __construct(private ?Throwable $fails) {}

        public function list(string $server): array
        {
            return ['shop_app@%'];
        }

        public function drop(mixed ...$arguments): OperationResult
        {
            $this->drops[] = $arguments;
            if ($this->fails instanceof Throwable) {
                throw $this->fails;
            }

            return OperationResult::succeeded(OperationType::AccountDrop, (string) $arguments[0], []);
        }
    };
}

// ------------------------------------------------------------- accounts

it('defaults a new account to the server\'s host: % on a managed MySQL', function (): void {
    mysqlLookalike('digitalocean');

    Livewire::test(AccountComponent::class)->assertSet('host', '%');
});

it('keeps accounts.default_host on a self-hosted server', function (): void {
    mysqlLookalike();

    Livewire::test(AccountComponent::class)->assertSet('host', 'localhost');
});

it('hides the engine\'s own accounts on a managed server', function (): void {
    mysqlLookalike('generic');

    Livewire::test(AccountComponent::class)->assertSee('shop_app@%')->assertDontSee('mysql.sys');
});

it('offers no host field where the engine has no host scoping', function (): void {
    Livewire::test(AccountComponent::class)->assertDontSeeHtml('wire:model="host"');

    mysqlLookalike();
    Livewire::test(AccountComponent::class)->assertSeeHtml('wire:model="host"');
});

it('drops an account once its username is typed again', function (): void {
    mysqlLookalike();
    app()->instance(AccountManager::class, $service = recordingDrops());

    Livewire::test(AccountComponent::class)
        ->call('chooseDrop', 'shop_app@%')
        ->assertSet('dropUsername', 'shop_app')
        ->assertSet('dropHost', '%')
        ->set('confirmDrop', 'shop_app')
        ->call('drop')
        ->assertHasNoErrors()
        ->assertSet('flash', 'Dropped.');

    expect($service->drops)->toHaveCount(1)
        ->and($service->drops[0][0])->toBe('my')
        ->and($service->drops[0][1])->toBeInstanceOf(Username::class)
        ->and($service->drops[0][1]->value)->toBe('shop_app')
        ->and($service->drops[0][2])->toBeInstanceOf(Host::class)
        ->and($service->drops[0][2]->value)->toBe('%');
});

it('refuses to drop an account whose username was not typed again', function (): void {
    mysqlLookalike();
    app()->instance(AccountManager::class, $service = recordingDrops());

    Livewire::test(AccountComponent::class)
        ->call('chooseDrop', 'shop_app@%')
        ->set('confirmDrop', 'shop_ap')
        ->call('drop')
        ->assertHasErrors('confirmDrop');

    expect($service->drops)->toBe([]);
});

it('drops a role on an engine without hosts, using the server\'s default host', function (): void {
    app()->instance(AccountManager::class, $service = recordingDrops());

    Livewire::test(AccountComponent::class)
        ->call('chooseDrop', 'reporting')
        ->assertSet('dropHost', 'localhost')
        ->set('confirmDrop', 'reporting')
        ->call('drop')
        ->assertHasNoErrors();

    expect($service->drops[0][1]->value)->toBe('reporting');
});

// ------------------------------------------------------------ databases

it('says plainly that a failed backup stopped the drop', function (): void {
    app()->instance(DatabaseManager::class, new class
    {
        public function list(string $server): array
        {
            return ['shop_prod'];
        }

        public function drop(string $server, DbName $name): never
        {
            throw BackupFailed::beforeDrop($server, $name->value, new RuntimeException('mysqldump: Got error: 2026'));
        }
    });

    Livewire::test(DatabaseWizard::class)
        ->set('confirmName', 'shop_prod')
        ->call('drop')
        ->assertSet('backupFailed', true)
        ->assertSee('The backup failed, so nothing was dropped.')
        ->assertSet('flash', null);
});

it('names the provider\'s point-in-time recovery as the restore path on a managed server', function (): void {
    config()->set('laranail.db-console.servers.local.hosting', 'generic');

    Livewire::test(DatabaseWizard::class)->assertSee('point-in-time recovery');
});

it('says nothing about point-in-time recovery on a self-hosted server', function (): void {
    Livewire::test(DatabaseWizard::class)->assertDontSee('point-in-time recovery');
});

// --------------------------------------------------------------- servers

it('shows where each server runs', function (): void {
    config()->set('laranail.db-console.servers.local.hosting', 'digitalocean');

    Livewire::test(ServerSwitcher::class)->assertSee('DigitalOcean Managed Databases');
});

it('adds no hosting badge to a self-hosted server', function (): void {
    Livewire::test(ServerSwitcher::class)->assertDontSee('Self-hosted');
});
