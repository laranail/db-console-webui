<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Simtabi\Laranail\DBConsole\Access\Contracts\RbacDriver;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\AccountManager;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\Dashboard;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\RoleManager;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\ServerSwitcher;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\WebhookManager;

beforeEach(function (): void {
    config()->set('database.connections.ui_admin', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    config()->set('laranail.db-console.servers.local', ['engine' => 'sqlite', 'connection' => 'ui_admin', 'tls' => ['enabled' => false]]);
    config()->set('laranail.db-console.default_server', 'local');
    $this->migrateCatalog();
    Gate::before(fn ($user = null): bool => true);
});

it('renders the server switcher listing registered servers', function (): void {
    Livewire::test(ServerSwitcher::class)
        ->assertOk()
        ->assertSee('local');
});

it('renders the dashboard', function (): void {
    Livewire::test(Dashboard::class)->assertOk();
});

it('renders the account manager', function (): void {
    Livewire::test(AccountManager::class)->assertOk();
});

it('renders the role manager showing the seeded roles from the core', function (): void {
    app(RbacDriver::class)->seedDefaultRoles();

    Livewire::test(RoleManager::class)
        ->assertOk()
        ->assertSee('owner');
});

it('renders the webhook manager', function (): void {
    Livewire::test(WebhookManager::class)->assertOk();
});

it('surfaces a core exception as a sanitized error, not a crash', function (): void {
    // An unknown server → the core throws; the UI shows userMessage(), no leak.
    config()->set('laranail.db-console.default_server', 'nonexistent');

    Livewire::test(Dashboard::class)->assertOk();   // renders the error state, does not 500
});
