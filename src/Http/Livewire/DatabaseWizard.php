<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Session;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Simtabi\Laranail\DBConsole\Domain\Charset;
use Simtabi\Laranail\DBConsole\Domain\DbName;
use Simtabi\Laranail\DBConsole\Exceptions\DBConsoleException;
use Simtabi\Laranail\DBConsole\Servers\ServerRegistry;
use Simtabi\Laranail\DBConsole\Services\DatabaseManager;
use Simtabi\Laranail\DBConsole\Validation\Requests\CreateDatabaseRequest;
use Simtabi\Laranail\DBConsole\Validation\Requests\DropDatabaseRequest;
use Simtabi\Laranail\DBConsole\Validation\RuleProvider;

/**
 * Create and drop databases on the active server. Validation is the CORE's:
 * the rules come from RuleProvider::field(...) so the UI can never disagree
 * with the CLI or API about what a valid name is. Every action is a core
 * service call; errors render the exception's userMessage().
 */
final class DatabaseWizard extends Component
{
    #[Session(key: 'db-console.active-server')]
    public string $active = '';

    public string $name = '';

    public string $charset = 'utf8mb4';

    #[Validate]
    public string $confirmName = '';

    public ?string $flash = null;

    public ?string $error = null;

    /**
     * Validation rules pulled from the core's shared FormRequests — never
     * declared here. This is the boundary the architecture test enforces.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => RuleProvider::field(CreateDatabaseRequest::class, 'name'),
            'confirmName' => RuleProvider::field(DropDatabaseRequest::class, 'name'),
        ];
    }

    public function create(): void
    {
        $this->reset('flash', 'error');
        $this->validateOnly('name');

        try {
            app(DatabaseManager::class)->create(
                $this->server(),
                new DbName($this->name),
                new Charset($this->charset),
            );
            $this->flash = __('db-console-webui::ui.created');
            $this->reset('name');
        } catch (DBConsoleException $e) {
            $this->error = $e->userMessage();
        }
    }

    public function drop(): void
    {
        $this->reset('flash', 'error');
        $this->validateOnly('confirmName');

        try {
            app(DatabaseManager::class)->drop($this->server(), new DbName($this->confirmName));
            $this->flash = __('db-console-webui::ui.dropped');
            $this->reset('confirmName');
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

        return \Illuminate\Support\Facades\View::make('db-console-webui::livewire.database-wizard', ['databases' => $databases]);
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
