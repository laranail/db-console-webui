<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Http\Livewire;

use Livewire\Component;
use Livewire\Attributes\Session;
use Livewire\Attributes\Validate;
use Illuminate\Contracts\View\View;
use Simtabi\Laranail\DBConsole\Domain\DbName;
use Simtabi\Laranail\DBConsole\Domain\Charset;
use Simtabi\Laranail\DBConsole\Servers\ServerRegistry;
use Simtabi\Laranail\DBConsole\Exceptions\BackupFailed;
use Simtabi\Laranail\DBConsole\Validation\RuleProvider;
use Simtabi\Laranail\DBConsole\Services\DatabaseManager;
use Simtabi\Laranail\DBConsoleWebUI\Support\Translations;
use Simtabi\Laranail\DBConsole\Enums\ExistingResourcePolicy;
use Simtabi\Laranail\DBConsole\Exceptions\DBConsoleException;
use Simtabi\Laranail\DBConsole\Validation\Requests\DropDatabaseRequest;
use Simtabi\Laranail\DBConsole\Validation\Requests\CreateDatabaseRequest;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\Concerns\RespondsToRefusals;

/**
 * Create and drop databases on the active server. Validation is the CORE's:
 * the rules come from RuleProvider::field(...) so the UI can never disagree
 * with the CLI or API about what a valid name is. Every action is a core
 * service call; errors render the exception's userMessage().
 *
 * A drop stopped by a failed backup says so on its own, since nothing was
 * dropped and the fix is the backup, not the drop. On a server whose
 * hosting keeps point-in-time recovery the screen says that is the restore
 * path, because the core takes no dump there.
 */
final class DatabaseWizard extends Component
{
    use RespondsToRefusals;

    #[Session(key: 'db-console.active-server')]
    public string $active = '';

    public string $name = '';

    public string $charset = 'utf8mb4';

    #[Validate]
    public string $confirmName = '';

    public ?string $flash = null;

    public ?string $error = null;

    public bool $backupFailed = false;

    public function create(): void
    {
        $this->reset('flash', 'error');
        $this->clearRefusal();
        $this->validateOnly('name');

        $this->submit(null);
    }

    /**
     * Resubmit a refused create with the adopt policy the refusal offered.
     * A database has no password, so adopt is the only choice.
     */
    public function adopt(string $policy): void
    {
        if (! $this->offered($policy)) {
            return;
        }

        $this->reset('flash', 'error');
        $this->clearRefusal();

        $this->submit(ExistingResourcePolicy::from($policy));
    }

    public function drop(): void
    {
        $this->reset('flash', 'error', 'backupFailed');
        $this->validateOnly('confirmName');

        try {
            app(DatabaseManager::class)->drop($this->server(), new DbName($this->confirmName));
            $this->flash = Translations::get('ui.dropped');
            $this->reset('confirmName');
        } catch (BackupFailed $e) {
            $this->backupFailed = true;
            $this->error = $e->userMessage();
        } catch (DBConsoleException $e) {
            $this->error = $e->userMessage();
        }
    }

    public function render(): View
    {
        $databases = [];
        try {
            $databases = app(DatabaseManager::class)->list($this->server());
        } catch (DBConsoleException $e) {
            $this->error ??= $e->userMessage();
        }

        return \Illuminate\Support\Facades\View::make('laranail/db-console-webui::livewire.database-wizard', [
            'databases'       => $databases,
            'providerBackups' => app(ServerRegistry::class)->hosting($this->server())->providerBackups,
        ]);
    }

    /**
     * Validation rules pulled from the core's shared FormRequests — never
     * declared here. This is the boundary the architecture test enforces.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name'        => RuleProvider::field(CreateDatabaseRequest::class, 'name'),
            'confirmName' => RuleProvider::field(DropDatabaseRequest::class, 'name'),
        ];
    }

    private function submit(?ExistingResourcePolicy $onExisting): void
    {
        try {
            $result = app(DatabaseManager::class)->create(
                $this->server(),
                new DbName($this->name),
                new Charset($this->charset),
                $onExisting,
            );
        } catch (DBConsoleException $e) {
            $this->respondToRefusal($e);

            return;
        }

        $this->flash = $this->adopted($result) ? Translations::get('ui.adopted_database') : Translations::get('ui.created');
        $this->reset('name');
    }

    private function server(): string
    {
        $registry = app(ServerRegistry::class);
        if ($this->active !== '' && $registry->has($this->active)) {
            return $this->active;
        }

        return $registry->default();
    }
}
