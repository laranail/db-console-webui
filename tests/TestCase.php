<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Tests;

use Flux\FluxServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Simtabi\Laranail\Console\Providers\ConsoleServiceProvider;
use Simtabi\Laranail\DBConsole\Providers\DBConsoleServiceProvider;
use Simtabi\Laranail\DBConsoleWebUI\Providers\DBConsoleWebUIServiceProvider;
use Simtabi\Laranail\Enumerator\EnumeratorServiceProvider;
use Simtabi\Laranail\Package\Tools\Providers\PackageToolsServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        $providers = [
            LivewireServiceProvider::class,
            ConsoleServiceProvider::class,
            PackageToolsServiceProvider::class,
            EnumeratorServiceProvider::class,
            DBConsoleServiceProvider::class,
            DBConsoleWebUIServiceProvider::class,
        ];

        // Flux registers itself; include its provider when present.
        if (class_exists(FluxServiceProvider::class)) {
            $providers[] = FluxServiceProvider::class;
        }

        return $providers;
    }

    protected function defineEnvironment($app): void
    {
        /** @var Application $app */
        $config = $app['config'];

        $config->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $config->set('database.default', 'testing');
        $config->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $config->set('database.connections.db_console_catalog', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $config->set('laranail.db-console.catalog.connection', 'db_console_catalog');
        $config->set('laranail.db-console-webui.enabled', true);
    }

    protected function migrateCatalog(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__) . '/vendor/laranail/db-console/database/migrations');
    }
}
