<?php

declare(strict_types=1);

use Livewire\Livewire;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\DBConsole\Enums\Assurance;
use Simtabi\Laranail\DBConsole\Enums\OperationType;
use Simtabi\Laranail\DBConsole\Services\AccountManager;
use Simtabi\Laranail\DBConsole\Services\DatabaseManager;
use Simtabi\Laranail\DBConsole\Enums\ExistingResourcePolicy;
use Simtabi\Laranail\DBConsole\Exceptions\AssuranceRequired;
use Simtabi\Laranail\DBConsole\Exceptions\AccountAlreadyExists;
use Simtabi\Laranail\DBConsole\Exceptions\DatabaseAlreadyExists;
use Simtabi\Laranail\DBConsole\Services\Results\OperationResult;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\DatabaseWizard;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\AccountManager as AccountComponent;

/*
 * The core refuses an account or database that already exists, unless the
 * operator may adopt it (resource.adopt), and says which policies they may
 * resubmit with. These tests stand a recording service in for the core so
 * each refusal can be produced on demand; SQLite, the suite's server, has
 * no accounts and never reports an existing database.
 */

beforeEach(function (): void {
    config()->set('database.connections.ui_admin', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    config()->set('laranail.db-console.servers.local', ['engine' => 'sqlite', 'connection' => 'ui_admin', 'tls' => ['enabled' => false]]);
    config()->set('laranail.db-console.default', 'local');
    $this->migrateCatalog();
    Gate::before(fn ($user = null): bool => true);
});

/**
 * A stand-in for a core service: create() answers with the next queued
 * outcome (a result, or an exception to throw) and records the policy it
 * was called with.
 */
function standIn(array $outcomes): object
{
    return new class($outcomes)
    {
        /** @var list<?ExistingResourcePolicy> */
        public array $policies = [];

        public function __construct(private array $outcomes) {}

        public function create(mixed ...$arguments): OperationResult
        {
            $this->policies[] = end($arguments) instanceof ExistingResourcePolicy ? end($arguments) : null;
            $next = array_shift($this->outcomes);
            if ($next instanceof Throwable) {
                throw $next;
            }

            return $next;
        }

        public function list(string $server): array
        {
            return [];
        }
    };
}

function adoptedResult(OperationType $operation, bool $passwordApplied = false, ?string $password = null): OperationResult
{
    return OperationResult::succeeded(
        $operation,
        'local',
        ['adopted' => true, 'password_applied' => $passwordApplied],
        alreadyExisted: ! $passwordApplied,
        generatedPassword: $password,
    );
}

it('offers the operator the adopt choices the refusal carries, and nothing more', function (): void {
    app()->instance(AccountManager::class, standIn([
        AccountAlreadyExists::on('local', 'app@%', ['adopt', 'adopt_and_apply']),
    ]));

    Livewire::test(AccountComponent::class)
        ->set('username', 'app')->set('host', '%')
        ->call('create')
        ->assertSet('adoptOptions', ['adopt', 'adopt_and_apply'])
        ->assertSee('Adopt the existing account')
        ->assertSee('Adopt it and set a new password');
});

it('offers no adopt choice to an operator who may not adopt', function (): void {
    app()->instance(AccountManager::class, standIn([
        AccountAlreadyExists::on('local', 'app@%', []),
    ]));

    Livewire::test(AccountComponent::class)
        ->set('username', 'app')->set('host', '%')
        ->call('create')
        ->assertSet('adoptOptions', [])
        ->assertDontSee('Adopt the existing account');
});

it('adopts with the chosen policy and says adopted, not created', function (): void {
    $service = standIn([
        AccountAlreadyExists::on('local', 'app@%', ['adopt', 'adopt_and_apply']),
        adoptedResult(OperationType::AccountCreate),
    ]);
    app()->instance(AccountManager::class, $service);

    Livewire::test(AccountComponent::class)
        ->set('username', 'app')->set('host', '%')
        ->call('create')
        ->call('adopt', 'adopt')
        ->assertSet('flash', 'Adopted the existing account. Its password is unchanged.')
        ->assertSet('adoptOptions', []);

    expect($service->policies)->toBe([null, ExistingResourcePolicy::Adopt]);
});

it('shows the new password once when adopting and applying one', function (): void {
    $generated = testPassword();
    app()->instance(AccountManager::class, standIn([
        AccountAlreadyExists::on('local', 'app@%', ['adopt', 'adopt_and_apply']),
        adoptedResult(OperationType::AccountRotate, passwordApplied: true, password: $generated),
    ]));

    Livewire::test(AccountComponent::class)
        ->set('username', 'app')->set('host', '%')
        ->call('create')
        ->call('adopt', 'adopt_and_apply')
        ->assertSet('generatedPassword', $generated)
        ->assertSet('flash', 'Adopted the existing account and set a new password.');
});

it('refuses an adopt choice the operator was not offered', function (): void {
    $service = standIn([AccountAlreadyExists::on('local', 'app@%', ['adopt'])]);
    app()->instance(AccountManager::class, $service);

    Livewire::test(AccountComponent::class)
        ->set('username', 'app')->set('host', '%')
        ->call('create')
        ->call('adopt', 'adopt_and_apply');

    // The only call is the original create: the unoffered policy never reached the core.
    expect($service->policies)->toBe([null]);
});

it('says adopted when the configured policy adopted the account without asking', function (): void {
    app()->instance(AccountManager::class, standIn([adoptedResult(OperationType::AccountCreate)]));

    Livewire::test(AccountComponent::class)
        ->set('username', 'app')->set('host', '%')
        ->call('create')
        ->assertSet('flash', 'Adopted the existing account. Its password is unchanged.');
});

it('offers to adopt an existing database when the refusal allows it', function (): void {
    $service = standIn([
        DatabaseAlreadyExists::on('local', 'shop', ['adopt']),
        adoptedResult(OperationType::DatabaseCreate),
    ]);
    app()->instance(DatabaseManager::class, $service);

    Livewire::test(DatabaseWizard::class)
        ->set('name', 'shop')
        ->call('create')
        ->assertSet('adoptOptions', ['adopt'])
        ->assertSee('Adopt the existing database')
        ->call('adopt', 'adopt')
        ->assertSet('flash', 'Adopted the existing database.');

    expect($service->policies)->toBe([null, ExistingResourcePolicy::Adopt]);
});

// The host authenticates; db-console only says what is missing. The UI's job
// is to send the operator to the host's own confirmation page when one exists.
it('links an assurance refusal to the host\'s confirmation page', function (): void {
    Route::get('/user/confirm-password', fn (): string => 'confirm')->name('password.confirm');
    app()->instance(AccountManager::class, standIn([
        AssuranceRequired::for('laranail-db-console.account.rotate', [Assurance::RecentConfirmation]),
    ]));

    Livewire::test(AccountComponent::class)
        ->set('username', 'app')->set('host', '%')
        ->call('create')
        ->assertSet('confirmUrl', url('/user/confirm-password'))
        ->assertSee('Confirm your identity');
});

it('shows no confirmation link when the host has no confirmation page', function (): void {
    app()->instance(AccountManager::class, standIn([
        AssuranceRequired::for('laranail-db-console.account.rotate', [Assurance::RecentConfirmation]),
    ]));

    Livewire::test(AccountComponent::class)
        ->set('username', 'app')->set('host', '%')
        ->call('create')
        ->assertSet('confirmUrl', null);
});
