<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Providers;

use Livewire\Livewire;
use Override;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\AccountManager;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\Dashboard;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\DatabaseWizard;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\RoleManager;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\ServerSwitcher;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\WebhookManager;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;
use Simtabi\Laranail\Package\Tools\Support\Definitions\InstallCommandDefinition;

/**
 * Registers the thin Livewire/Flux web UI over laranail/db-console. It ships
 * views, translations, config, and Livewire components — and NO business
 * logic. Every component calls a core service and reuses the core validation
 * layer via RuleProvider; the boundary is enforced by an architecture test.
 */
final class DBConsoleWebUIServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laranail/db-console-webui')
            ->hasConfigFile()
            ->hasViews('db-console-webui')
            ->hasTranslations('db-console-webui')
            ->hasRoutesWhen('laranail.db-console-webui.enabled', 'web')
            ->hasInstallCommand(
                InstallCommandDefinition::make()
                    ->named('db-console-webui:install')
                    ->publishes('config', 'views', 'translations'),
            );
    }

    #[Override]
    public function packageBooted(): void
    {
        Livewire::component('db-console-webui.server-switcher', ServerSwitcher::class);
        Livewire::component('db-console-webui.dashboard', Dashboard::class);
        Livewire::component('db-console-webui.database-wizard', DatabaseWizard::class);
        Livewire::component('db-console-webui.account-manager', AccountManager::class);
        Livewire::component('db-console-webui.role-manager', RoleManager::class);
        Livewire::component('db-console-webui.webhook-manager', WebhookManager::class);
    }
}
