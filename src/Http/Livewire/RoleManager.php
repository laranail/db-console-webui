<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Simtabi\Laranail\DBConsole\Access\Contracts\RbacDriver;
use Simtabi\Laranail\DBConsole\Enums\ConsoleRole;

/**
 * Read-only view of the console roles and their composed permissions. All
 * data comes from the core RBAC driver; the UI defines no roles or
 * permissions of its own.
 */
final class RoleManager extends Component
{
    public function render(): View
    {
        $driver = app(RbacDriver::class);

        $roles = [];
        foreach (ConsoleRole::cases() as $role) {
            $roles[] = [
                'name' => $role->value,
                'label' => $role->label(),
                'permissions' => array_map(
                    static fn ($p): string => $p->value,
                    $driver->permissionsForRole($role->value),
                ),
            ];
        }

        return \Illuminate\Support\Facades\View::make('db-console-webui::livewire.role-manager', ['roles' => $roles]);
    }
}
