<?php

declare(strict_types=1);

use Livewire\Livewire;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\DatabaseWizard;

beforeEach(function (): void {
    // A local sqlite server so render()'s list() has something to resolve.
    config()->set('database.connections.ui_admin', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    config()->set('laranail.db-console.servers.local', ['engine' => 'sqlite', 'connection' => 'ui_admin', 'tls' => ['enabled' => false]]);
    config()->set('laranail.db-console.default_server', 'local');
    $this->migrateCatalog();
});

it('renders the database wizard', function (): void {
    Livewire::test(DatabaseWizard::class)->assertOk();
});

it('rejects an invalid database name using the CORE validation rules (RuleProvider)', function (): void {
    Livewire::test(DatabaseWizard::class)
        ->set('name', 'bad name; DROP TABLE users')   // spaces + semicolon → invalid identifier
        ->call('create')
        ->assertHasErrors('name');
});

it('accepts a valid identifier through the same core rule', function (): void {
    Livewire::test(DatabaseWizard::class)
        ->set('name', 'shop_prod')
        ->call('create')
        ->assertHasNoErrors('name');
});
