<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Session;
use Livewire\Component;
use Simtabi\Laranail\DBConsole\Exceptions\DBConsoleException;
use Simtabi\Laranail\DBConsole\Servers\ServerRegistry;
use Simtabi\Laranail\DBConsole\Services\DatabaseManager;

/**
 * The landing page: for the active server it shows the live database count and
 * an unreachable-server error state (with a sanitized reason and retry). It
 * reads only through core services — no inspection logic of its own.
 */
final class Dashboard extends Component
{
    #[Session(key: 'db-console.active-server')]
    public string $active = '';

    public function render(): View
    {
        $databases = [];
        $error = null;

        $server = $this->resolveServer();
        if ($server !== null) {
            try {
                $databases = app(DatabaseManager::class)->list($server);
            } catch (DBConsoleException $e) {
                $error = $e->userMessage();   // sanitized, secret-free
            }
        }

        return \Illuminate\Support\Facades\View::make('db-console-webui::livewire.dashboard', [
            'server' => $server,
            'databases' => $databases,
            'error' => $error,
        ]);
    }

    private function resolveServer(): ?string
    {
        $registry = app(ServerRegistry::class);
        if ($this->active !== '' && $registry->has($this->active)) {
            return $this->active;
        }

        $names = $registry->names();

        return $names[0] ?? null;
    }
}
