<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Session;
use Livewire\Component;
use Simtabi\Laranail\DBConsole\Servers\ServerRegistry;

/**
 * Lets the operator pick the active server. It only lists servers the
 * operator can view (scope-filtered by the core) and persists the choice in
 * the session. It holds NO logic beyond presentation: the registry is the
 * source of truth and the capability badge comes straight from the engine.
 */
final class ServerSwitcher extends Component
{
    #[Session(key: 'db-console.active-server')]
    public string $active = '';

    public function select(string $server): void
    {
        // Guard the selection through the registry (unknown → ignored); no
        // authorization or SQL here.
        $registry = app(ServerRegistry::class);
        if ($registry->has($server)) {
            $this->active = $server;
            $this->dispatch('db-console:server-changed', server: $server);
        }
    }

    public function render(): View
    {
        $registry = app(ServerRegistry::class);

        $servers = [];
        foreach ($registry->names() as $name) {
            $definition = $registry->definition($name);
            $servers[] = [
                'name' => $name,
                'engine' => $definition->engine->value,
            ];
        }

        if ($this->active === '' && $servers !== []) {
            $this->active = $servers[0]['name'];
        }

        return \Illuminate\Support\Facades\View::make('db-console-webui::livewire.server-switcher', ['servers' => $servers]);
    }
}
