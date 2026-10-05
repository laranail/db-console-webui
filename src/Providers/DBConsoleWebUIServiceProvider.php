<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Providers;

use Override;
use Livewire\Livewire;
use Livewire\Component;
use Composer\InstalledVersions;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\DBConsoleWebUI\Doctor\Checks;
use Simtabi\Laranail\DBConsoleWebUI\Support\RouteNames;
use Simtabi\Laranail\DBConsoleWebUI\Support\LivewireNames;
use Simtabi\Laranail\Package\Tools\Enums\DeprecationNotice;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;
use Simtabi\Laranail\DBConsoleWebUI\Console\Commands\LegacyInstallCommand;
use Simtabi\Laranail\Package\Tools\Support\Definitions\AboutSectionDefinition;
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
            ->hasConfigFile('db-console-webui')
            ->hasViews('laranail-db-console-webui')
            ->hasTranslations('laranail-db-console-webui')
            ->hasRoutesWhen('laranail.db-console-webui.enabled', 'web')
            ->hasAboutSection(
                AboutSectionDefinition::make('DB Console Web UI')
                    ->field('Version', fn (): string => (string) InstalledVersions::getPrettyVersion('laranail/db-console-webui'))
                    ->field('UI enabled', fn (): bool => (bool) config('laranail.db-console-webui.enabled', true)),
            )
            ->hasDoctorChecks(Checks::all())
            ->hasInstallCommand(
                InstallCommandDefinition::make()
                    ->named(LegacyInstallCommand::SCOPED_NAME)
                    ->publishes('config', 'views', 'translations'),
            )
            // Deprecated bare `db-console-webui:install`: a hidden forwarder
            // that warns and runs the scoped command above.
            ->hasConsoleCommands(LegacyInstallCommand::class)
            // Route names are `laranail-db-console-webui.<page>`; the deprecated
            // bare `db-console-webui.<page>` names still resolve through route(),
            // with one logged warning per name per process.
            ->hasDeprecatedRouteNames(map: RouteNames::legacyMap(), notice: DeprecationNotice::Log);
    }

    #[Override]
    public function packageBooted(): void
    {
        // Scoped names first: Livewire maps a class back to the FIRST name it
        // was registered under, so full-page routes and snapshots use these.
        foreach (LivewireNames::COMPONENTS as $component => $class) {
            Livewire::component(LivewireNames::name($component), $class);
        }

        // Deprecated bare names, kept so `@livewire('db-console-webui.<name>')`
        // and `<livewire:db-console-webui.<name> />` still render. Mounting one
        // raises a single E_USER_DEPRECATED naming the replacement.
        foreach (LivewireNames::COMPONENTS as $component => $class) {
            Livewire::component(LivewireNames::LEGACY_PREFIX . $component, $class);
        }

        Livewire::listen('mount', static function (Component $component): void {
            LivewireNames::announceIfLegacy($component->getName());
        });
    }
}
