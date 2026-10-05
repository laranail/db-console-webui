<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Support;

use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\Dashboard;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\RoleManager;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\AccountManager;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\DatabaseWizard;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\ServerSwitcher;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\WebhookManager;

/**
 * The Livewire component names this package registers. Livewire keeps them in
 * one flat, host-owned map, so each carries the vendor and the package slug:
 * `laranail-db-console-webui.<component>`.
 *
 * The pre-0.1 names (`db-console-webui.<component>`) are still registered, after
 * the scoped ones so that Livewire resolves a class back to its scoped name, and
 * mounting a component under one announces the replacement once per process.
 */
final class LivewireNames
{
    public const string PREFIX = 'laranail-db-console-webui.';

    /**
     * @deprecated the bare `db-console-webui.` Livewire prefix. Use {@see self::PREFIX}.
     *             Earliest removal: the next minor after 0.1.
     */
    public const string LEGACY_PREFIX = 'db-console-webui.';

    /** @var array<string, class-string<\Livewire\Component>> component => class */
    public const array COMPONENTS = [
        'server-switcher' => ServerSwitcher::class,
        'dashboard'       => Dashboard::class,
        'database-wizard' => DatabaseWizard::class,
        'account-manager' => AccountManager::class,
        'role-manager'    => RoleManager::class,
        'webhook-manager' => WebhookManager::class,
    ];

    /** @var array<string, true> legacy names already announced in this process */
    private static array $announced = [];

    public static function name(string $component): string
    {
        return self::PREFIX . $component;
    }

    /**
     * Deprecated bare name => scoped name, for every component.
     *
     * @return array<string, string>
     */
    public static function legacyMap(): array
    {
        $map = [];

        foreach (array_keys(self::COMPONENTS) as $component) {
            $map[self::LEGACY_PREFIX . $component] = self::name($component);
        }

        return $map;
    }

    /**
     * Raise one E_USER_DEPRECATED notice per process when a component was
     * mounted under its deprecated bare name. Any other name is ignored.
     */
    public static function announceIfLegacy(string $name): void
    {
        $scoped = self::legacyMap()[$name] ?? null;

        if ($scoped === null || isset(self::$announced[$name])) {
            return;
        }

        self::$announced[$name] = true;

        trigger_error(sprintf(
            'laranail/db-console-webui: the Livewire component name [%s] is deprecated and will be removed no earlier than the next minor after 0.1; use [%s].',
            $name,
            $scoped,
        ), E_USER_DEPRECATED);
    }

    /**
     * Forget which names were announced. For test suites.
     */
    public static function forgetWarnings(): void
    {
        self::$announced = [];
    }
}
