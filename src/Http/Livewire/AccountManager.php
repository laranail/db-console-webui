<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Session;
use Livewire\Component;
use Simtabi\Laranail\DBConsole\Domain\Host;
use Simtabi\Laranail\DBConsole\Domain\Username;
use Simtabi\Laranail\DBConsole\Exceptions\DBConsoleException;
use Simtabi\Laranail\DBConsole\Servers\ServerRegistry;
use Simtabi\Laranail\DBConsole\Services\AccountManager as AccountService;
use Simtabi\Laranail\DBConsole\Validation\Requests\CreateAccountRequest;
use Simtabi\Laranail\DBConsole\Validation\RuleProvider;

/**
 * Create and drop database accounts on the active server. Validation comes
 * from the core (RuleProvider); every action is a core service call. The
 * generated password is shown once via the result and never stored.
 */
final class AccountManager extends Component
{
    #[Session(key: 'db-console.active-server')]
    public string $active = '';

    public string $username = '';

    public string $host = 'localhost';

    public ?string $generatedPassword = null;

    public ?string $flash = null;

    public ?string $error = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'username' => RuleProvider::field(CreateAccountRequest::class, 'username'),
            'host' => RuleProvider::field(CreateAccountRequest::class, 'host'),
        ];
    }

    public function create(): void
    {
        $this->reset('flash', 'error', 'generatedPassword');
        $this->validate();

        try {
            $result = app(AccountService::class)->create(
                $this->server(),
                new Username($this->username),
                new Host($this->host),
                null,   // generate a strong password
            );
            $this->generatedPassword = $result->takeGeneratedPassword();
            $this->flash = __('db-console-webui::ui.created');
            $this->reset('username');
        } catch (DBConsoleException $e) {
            $this->error = $e->userMessage();
        }
    }

    public function render(): View
    {
        $accounts = [];
        try {
            $accounts = app(AccountService::class)->list($this->server());
        } catch (DBConsoleException $e) {
            $this->error ??= $e->userMessage();
        }

        return \Illuminate\Support\Facades\View::make('db-console-webui::livewire.account-manager', ['accounts' => $accounts]);
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
