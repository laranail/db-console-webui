<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\DBConsoleWebUI\Support\RouteNames;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\Dashboard;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\RoleManager;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\AccountManager;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\DatabaseWizard;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\WebhookManager;
use Simtabi\Laranail\DBConsoleWebUI\Http\Middleware\EnsureCanManage;

/*
 * Web UI routes. Loaded only when laranail.db-console-webui.enabled is true.
 * Every route is guarded by the configured middleware stack plus
 * EnsureCanManage (auth + the db-console access gate + IP allow-list). The
 * pages are Livewire components that call the core services.
 *
 * Route names are vendor-scoped (`laranail-db-console-webui.<page>`). The
 * deprecated bare names (`db-console-webui.<page>`) still resolve through
 * Routing\BareRouteNameResolver, installed by the service provider.
 */
Route::prefix((string) config('laranail.db-console-webui.path', 'db-console'))
    ->middleware([...(array) config('laranail.db-console-webui.middleware', ['web']), EnsureCanManage::class])
    ->group(function (): void {
        Route::get('/', Dashboard::class)->name(RouteNames::name('dashboard'));
        Route::get('/databases', DatabaseWizard::class)->name(RouteNames::name('databases'));
        Route::get('/accounts', AccountManager::class)->name(RouteNames::name('accounts'));
        Route::get('/roles', RoleManager::class)->name(RouteNames::name('roles'));
        Route::get('/webhooks', WebhookManager::class)->name(RouteNames::name('webhooks'));
    });
