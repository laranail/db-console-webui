<?php

declare(strict_types=1);

use Livewire\Livewire;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route as RouteFacade;
use Simtabi\Laranail\DBConsoleWebUI\Support\RouteNames;
use Simtabi\Laranail\Package\Tools\Testing\NamingScope;
use Simtabi\Laranail\DBConsoleWebUI\Support\Translations;
use Simtabi\Laranail\DBConsoleWebUI\Support\BrowserEvents;
use Simtabi\Laranail\DBConsoleWebUI\Support\LivewireNames;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\Dashboard;
use Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\ServerSwitcher;
use Simtabi\Laranail\DBConsoleWebUI\Routing\BareRouteNameResolver;
use Simtabi\Laranail\Package\Tools\Testing\AssertsRegisteredNames;
use Simtabi\Laranail\DBConsoleWebUI\Console\Commands\LegacyInstallCommand;

/*
 * Every public name this package registers into a host-owned, flat registry
 * (route names, rate limiters, Artisan commands, middleware aliases, Livewire
 * components, view and translation namespaces) carries the vendor and the
 * package slug. package-tools' AssertsRegisteredNames reads the LIVE registries
 * of the booted application -- not the provider source -- so the guard holds
 * however the registration code is written.
 */

uses(AssertsRegisteredNames::class);

function dbConsoleWebUiScope(): NamingScope
{
    // basePath is src/: package-tools 0.1.3 defaults it to the package root, which
    // would also claim closures defined under vendor/ and tests/ as this package's.
    return NamingScope::for(
        'laranail/db-console-webui',
        'Simtabi\\Laranail\\DBConsoleWebUI\\',
        basePath: dirname(__DIR__, 2) . '/src',
    );
}

it('registers every route under the vendor-scoped laranail-db-console-webui. name', function (): void {
    // Non-vacuity: the package ships five pages.
    expect($this->assertRouteNamesScoped(dbConsoleWebUiScope(), atLeast: 5))
        ->toEqualCanonicalizing(array_values(RouteNames::legacyMap()));
});

it('registers no bare rate limiter', function (): void {
    // The package registers no limiter today; atLeast: 0 still fails on a bare
    // one it owns, and the read itself fails loudly if the registry moved.
    expect($this->assertRateLimitersScoped(dbConsoleWebUiScope(), atLeast: 0))->toBe([]);
});

it('registers every command under laranail::db-console-webui. except the listed deprecated forwarders', function (): void {
    $scoped = $this->assertCommandNamesScoped(
        dbConsoleWebUiScope(),
        deprecated: ['db-console-webui:install'],
        atLeast: 1,
    );

    expect($scoped)->toContain(LegacyInstallCommand::SCOPED_NAME);

    $legacy = Illuminate\Support\Facades\Artisan::all()['db-console-webui:install'];

    expect($legacy)->toBeInstanceOf(LegacyInstallCommand::class)
        ->and($legacy->isHidden())->toBeTrue('the deprecated forwarder must stay out of `artisan list`');
});

it('registers no bare middleware alias', function (): void {
    expect($this->assertMiddlewareAliasesScoped(dbConsoleWebUiScope(), atLeast: 0))->toBe([]);
});

it('registers every Livewire component under laranail-db-console-webui., keeping the bare names as deprecated aliases', function (): void {
    $scoped = $this->assertLivewireComponentsScoped(
        dbConsoleWebUiScope(),
        deprecated: array_keys(LivewireNames::legacyMap()),
        atLeast: 6,
    );

    expect($scoped)->toEqualCanonicalizing(array_values(LivewireNames::legacyMap()));
});

it('resolves each component class back to its scoped Livewire name', function (): void {
    // Livewire maps a class to the FIRST name it was registered under; full-page
    // routes and snapshots use that name, so it must be the scoped one.
    foreach (LivewireNames::COMPONENTS as $component => $class) {
        expect(app('livewire.finder')->normalizeName($class))->toBe(LivewireNames::name($component));
    }
});

it('registers the views and translations under both namespace forms', function (): void {
    $scope = dbConsoleWebUiScope();

    expect($this->assertViewNamespacesScoped($scope, atLeast: 2))
        ->toContain('laranail/db-console-webui', 'laranail-db-console-webui')
        ->and($this->assertTranslationNamespacesScoped($scope, atLeast: 2))
        ->toContain('laranail/db-console-webui', 'laranail-db-console-webui');

    // The canonical view form resolves through exactly the hint paths of the hyphen
    // form, the application's published override directory included.
    $hints = view()->getFinder()->getHints();

    expect($hints['laranail/db-console-webui'])->toBe($hints['laranail-db-console-webui']);

    // The hyphen form a host may still write resolves the same file and line.
    expect(view()->exists('laranail-db-console-webui::livewire.dashboard'))->toBeTrue()
        ->and(view()->exists('laranail/db-console-webui::livewire.dashboard'))->toBeTrue()
        ->and(__('laranail-db-console-webui::ui.dashboard'))->toBe(__('laranail/db-console-webui::ui.dashboard'))
        ->and(__('laranail/db-console-webui::ui.dashboard'))->not->toBe('laranail/db-console-webui::ui.dashboard');
});

it('still resolves every deprecated bare route name to the scoped route, with a warning', function (): void {
    Log::spy();

    $this->assertDeprecatedRouteNamesResolve(RouteNames::legacyMap());

    foreach (RouteNames::legacyMap() as $bare => $scoped) {
        // The registry holds only the scoped name, and the bare one generates the same URL.
        expect(RouteFacade::has($bare))->toBeFalse()
            ->and(route($bare))->toBe(route($scoped));
    }

    Log::shouldHaveReceived('warning')->withArgs(
        fn (string $message): bool => str_contains($message, 'deprecated') && str_contains($message, RouteNames::name('dashboard')),
    )->once();
});

it('keeps an unknown route name an error', function (): void {
    expect(fn (): string => route('db-console-webui.nope'))
        ->toThrow(Symfony\Component\Routing\Exception\RouteNotFoundException::class);
});

it('defers names it does not own to the resolver that was installed before it', function (): void {
    $url = app(UrlGenerator::class);
    $url->resolveMissingNamedRoutesUsing(fn (string $name): ?string => $name === 'someone-else.login' ? 'https://example.test/login' : null);

    // The deprecated hand-installed resolver still works, delegating to the shared one.
    BareRouteNameResolver::install(app('router'), $url, app());

    // Another package's fallback still answers...
    expect(route('someone-else.login'))->toBe('https://example.test/login')
        // ...and ours still answers for its own legacy names.
        ->and(route('db-console-webui.dashboard'))->toBe(route(RouteNames::name('dashboard')));
});

it('treats a non-string answer from the previous resolver as no answer', function (mixed $answer): void {
    $url = app(UrlGenerator::class);
    $url->resolveMissingNamedRoutesUsing(fn (string $name): mixed => $name === 'someone-else.odd' ? $answer : null);

    $resolver = BareRouteNameResolver::install(app('router'), $url, app());

    // Under strict_types an unchecked pass-through is a TypeError against the
    // ?string return type; a foreign non-string must read as "not resolved".
    expect($resolver('someone-else.odd'))->toBeNull()
        ->and(fn (): string => route('someone-else.odd'))
        ->toThrow(Symfony\Component\Routing\Exception\RouteNotFoundException::class);
})->with([
    'int'           => [42],
    'url generator' => [fn (): UrlGenerator => app(UrlGenerator::class)],
    'array'         => [['https://example.test']],
]);

it('runs the deprecated bare install command through the scoped one, with a warning', function (): void {
    $this->artisan('db-console-webui:install')
        ->expectsOutputToContain('is deprecated and will be removed no earlier than the next minor after 0.1; use [laranail::db-console-webui.install]')
        ->assertSuccessful();
});

it('mounts a component under its deprecated bare name, announcing the replacement once', function (): void {
    config()->set('database.connections.ui_admin', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    config()->set('laranail.db-console.servers.local', ['engine' => 'sqlite', 'connection' => 'ui_admin', 'tls' => ['enabled' => false]]);
    config()->set('laranail.db-console.default_server', 'local');
    $this->migrateCatalog();
    Gate::before(fn ($user = null): bool => true);

    $notices = [];
    set_error_handler(function (int $level, string $message) use (&$notices): bool {
        $notices[] = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        Livewire::test(LivewireNames::name('dashboard'))->assertOk();
        expect($notices)->toBe([]);

        Livewire::test('db-console-webui.dashboard')->assertOk();
        Livewire::test('db-console-webui.dashboard')->assertOk();
    } finally {
        restore_error_handler();
    }

    expect($notices)->toHaveCount(1)
        ->and($notices[0])->toContain('[db-console-webui.dashboard] is deprecated')
        ->and($notices[0])->toContain('[' . LivewireNames::name('dashboard') . ']');

    expect(Livewire::new('db-console-webui.dashboard'))->toBeInstanceOf(Dashboard::class);
});

it('dispatches the scoped server-changed browser event, and the deprecated bare one beside it', function (): void {
    config()->set('database.connections.ui_admin', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    config()->set('laranail.db-console.servers.local', ['engine' => 'sqlite', 'connection' => 'ui_admin', 'tls' => ['enabled' => false]]);
    config()->set('laranail.db-console.default_server', 'local');
    $this->migrateCatalog();
    Gate::before(fn ($user = null): bool => true);

    Livewire::test(ServerSwitcher::class)
        ->call('select', 'local')
        ->assertDispatched(BrowserEvents::SERVER_CHANGED, server: 'local')
        ->assertDispatched(BrowserEvents::LEGACY_SERVER_CHANGED, server: 'local');
});

/**
 * Write a translation override file for `ui` into lang/vendor/<dir>/en, run $test, and remove it.
 *
 * @param array<string, array<string, string>> $overrides lang/vendor sub-directory => lines
 */
function withUiOverrides(array $overrides, Closure $test): void
{
    $written = [];

    foreach ($overrides as $dir => $lines) {
        $path = lang_path("vendor/{$dir}/en");
        @mkdir($path, 0777, true);
        file_put_contents($path . '/ui.php', '<?php return ' . var_export($lines, true) . ';');
        $written[] = $path;
    }

    app('translator')->setLoaded([]);

    try {
        $test();
    } finally {
        foreach ($written as $path) {
            @unlink($path . '/ui.php');
            @rmdir($path);
            @rmdir(dirname($path));
            if (str_contains($path, 'vendor/laranail/')) {
                @rmdir(dirname($path, 2));
            }
        }

        app('translator')->setLoaded([]);
    }
}

/**
 * Render the package's own role-manager view straight from its file. A copy published into the
 * Testbench skeleton by the install-command test would otherwise shadow it through the namespace.
 */
function renderRoleManager(): string
{
    return view()->file(dirname(__DIR__, 2) . '/resources/views/livewire/role-manager.blade.php', ['roles' => []])->render();
}

it('reads published translation overrides from the directory the publish tag writes to', function (): void {
    // vendor:publish writes to lang/vendor/laranail/db-console-webui, which only the
    // canonical slash namespace reads.
    withUiOverrides(['laranail/db-console-webui' => ['roles' => 'Published roles']], function (): void {
        expect(Translations::get('ui.roles'))->toBe('Published roles')
            ->and(renderRoleManager())->toContain('Published roles');
    });
});

it('still applies an override made against the hyphen namespace', function (): void {
    // A host that overrode the namespace the package used before keeps its lines:
    // a file in lang/vendor/laranail-db-console-webui...
    withUiOverrides(['laranail-db-console-webui' => ['roles' => 'Hyphen roles']], function (): void {
        expect(Translations::get('ui.roles'))->toBe('Hyphen roles')
            ->and(renderRoleManager())->toContain('Hyphen roles');
    });

    // ...or lines added at runtime on that namespace.
    app('translator')->addLines(['ui.webhooks' => 'Runtime webhooks'], 'en', Translations::LEGACY_NAMESPACE);

    expect(Translations::get('ui.webhooks'))->toBe('Runtime webhooks');
});

it('prefers the canonical override when both namespaces are overridden', function (): void {
    withUiOverrides([
        'laranail/db-console-webui' => ['roles' => 'Canonical roles'],
        'laranail-db-console-webui' => ['roles' => 'Hyphen roles'],
    ], function (): void {
        expect(Translations::get('ui.roles'))->toBe('Canonical roles')
            ->and(renderRoleManager())->toContain('Canonical roles');
    });
});

it('falls back to the packaged line, and to the canonical key for a missing one', function (): void {
    expect(Translations::get('ui.roles'))->toBe(__('laranail/db-console-webui::ui.roles'))
        ->and(Translations::get('ui.roles'))->not->toBe(Translations::key('ui.roles'))
        ->and(Translations::get('ui.no_such_line'))->toBe(Translations::key('ui.no_such_line'));
});
