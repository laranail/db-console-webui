<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Http\Livewire;

use Livewire\Component;
use Livewire\Attributes\Session;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Simtabi\Laranail\DBConsole\Domain\Host;
use Simtabi\Laranail\DBConsole\Domain\Username;
use Simtabi\Laranail\DBConsole\Servers\ServerRegistry;
use Simtabi\Laranail\DBConsole\Validation\RuleProvider;
use Simtabi\Laranail\DBConsoleWebUI\Support\Translations;
use Simtabi\Laranail\DBConsole\Enums\ExistingResourcePolicy;
use Simtabi\Laranail\DBConsole\Exceptions\DBConsoleException;
use Simtabi\Laranail\DBConsole\Validation\Requests\DropAccountRequest;
use Simtabi\Laranail\DBConsole\Validation\Requests\CreateAccountRequest;
use Simtabi\Laranail\DBConsole\Services\AccountManager as AccountService;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\Concerns\RespondsToRefusals;

/**
 * Create and drop database accounts on the active server. Validation comes
 * from the core (RuleProvider); every action is a core service call. The
 * generated password is shown once via the result and never stored.
 *
 * Where the server runs comes from the core too: the host field starts at
 * ServerRegistry::defaultAccountHost() (`%` on a managed MySQL) and is not
 * offered where the engine cannot scope accounts by host.
 */
final class AccountManager extends Component
{
    use RespondsToRefusals;

    #[Session(key: 'db-console.active-server')]
    public string $active = '';

    public string $username = '';

    public string $host = '';

    public string $dropUsername = '';

    public string $dropHost = '';

    public string $confirmDrop = '';

    public ?string $generatedPassword = null;

    public ?string $flash = null;

    public ?string $error = null;

    public function mount(): void
    {
        $this->host = app(ServerRegistry::class)->defaultAccountHost($this->server());
    }

    public function create(): void
    {
        $this->reset('flash', 'error', 'generatedPassword');
        $this->clearRefusal();
        $this->validate();

        $this->submit(null);
    }

    /**
     * Resubmit a refused create with one of the adopt policies the refusal
     * offered: adopt the existing account, or adopt it and set a new
     * generated password. The core still authorizes and confirms both.
     */
    public function adopt(string $policy): void
    {
        if (! $this->offered($policy)) {
            return;
        }

        $this->reset('flash', 'error', 'generatedPassword');
        $this->clearRefusal();

        $this->submit(ExistingResourcePolicy::from($policy));
    }

    /**
     * Pick a listed account ("user@host", or "user" where the engine has no
     * hosts) to drop. Nothing is dropped until its username is typed again.
     */
    public function chooseDrop(string $account): void
    {
        $this->reset('flash', 'error', 'confirmDrop');
        $this->resetErrorBag();

        $at = strrpos($account, '@');
        $this->dropUsername = $at === false ? $account : substr($account, 0, $at);
        $this->dropHost = $at === false
            ? app(ServerRegistry::class)->defaultAccountHost($this->server())
            : substr($account, $at + 1);
    }

    public function drop(): void
    {
        $this->reset('flash', 'error');
        $this->resetErrorBag();

        // The core's DropAccountRequest, unchanged: its `confirm` rule is
        // `same:username`, so it is checked against these fields by those
        // names and the failures are reported on the screen's own fields.
        $validator = Validator::make(
            ['username' => $this->dropUsername, 'host' => $this->dropHost, 'confirm' => $this->confirmDrop],
            RuleProvider::for(DropAccountRequest::class),
            RuleProvider::messages(DropAccountRequest::class),
        );

        if ($validator->fails()) {
            $fields = ['username' => 'dropUsername', 'host' => 'dropHost', 'confirm' => 'confirmDrop'];
            foreach ($validator->errors()->messages() as $field => $messages) {
                $this->addError($fields[$field] ?? $field, (string) ($messages[0] ?? ''));
            }

            return;
        }

        try {
            app(AccountService::class)->drop($this->server(), new Username($this->dropUsername), new Host($this->dropHost));
        } catch (DBConsoleException $e) {
            $this->error = $e->userMessage();

            return;
        }

        $this->flash = Translations::get('ui.dropped');
        $this->reset('dropUsername', 'dropHost', 'confirmDrop');
    }

    public function render(): View
    {
        $accounts = [];
        try {
            $accounts = app(AccountService::class)->list($this->server());
        } catch (DBConsoleException $e) {
            $this->error ??= $e->userMessage();
        }

        return \Illuminate\Support\Facades\View::make('laranail/db-console-webui::livewire.account-manager', [
            'accounts'    => $accounts,
            'scopesHosts' => app(ServerRegistry::class)->capabilities($this->server())->canScopeAccountsByHost,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'username' => RuleProvider::field(CreateAccountRequest::class, 'username'),
            'host'     => RuleProvider::field(CreateAccountRequest::class, 'host'),
        ];
    }

    private function submit(?ExistingResourcePolicy $onExisting): void
    {
        try {
            $result = app(AccountService::class)->create(
                $this->server(),
                new Username($this->username),
                // Without host scoping the field is not shown, so use the default.
                new Host($this->host !== '' ? $this->host : app(ServerRegistry::class)->defaultAccountHost($this->server())),
                null,   // generate a strong password
                $onExisting,
            );
        } catch (DBConsoleException $e) {
            $this->respondToRefusal($e);

            return;
        }

        $this->generatedPassword = $result->takeGeneratedPassword();
        $this->flash = match (true) {
            ($result->data['password_applied'] ?? false) === true => Translations::get('ui.adopted_with_password'),
            $this->adopted($result)                               => Translations::get('ui.adopted_account'),
            default                                               => Translations::get('ui.created'),
        };
        $this->reset('username');
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
