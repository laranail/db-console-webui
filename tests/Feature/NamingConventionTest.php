<?php

declare(strict_types=1);

use Illuminate\Routing\Route;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route as RouteFacade;
use Simtabi\Laranail\DBConsoleWebUI\Support\RouteNames;
use Simtabi\Laranail\DBConsoleWebUI\Routing\BareRouteNameResolver;
use Simtabi\Laranail\DBConsoleWebUI\Http\Middleware\EnsureCanManage;
use Simtabi\Laranail\DBConsoleWebUI\Console\Commands\LegacyInstallCommand;

/*
 * Every public name this package registers into a host-owned, flat registry
 * (route names, rate limiters, Artisan commands, middleware aliases) carries
 * the vendor and the package slug. The assertions read the LIVE registries of
 * the booted application -- not the provider source -- so they hold however
 * the registration code is written.
 *
 * A name is "owned" by this package when it mentions the slug, or when the
 * thing it points at is a class in this package's namespace. Bias is toward
 * "owned": a false positive costs a look, a false negative ships a bare name.
 */

const NAMING_SLUG = 'db-console-webui';
const NAMING_NAMESPACE = 'Simtabi\\Laranail\\DBConsoleWebUI\\';

/**
 * Deprecated bare names kept working on purpose. Each entry is a ceiling, not
 * a licence: the test fails if an entry stops being registered, so a stale row
 * cannot quietly cover a new bare name.
 *
 * @var array<string, string> name => reason
 */
const NAMING_DEPRECATED_COMMANDS = [
    'db-console-webui:install' => 'deprecated forwarder to laranail::db-console-webui.install; earliest removal: next minor after 0.1',
];

/** @return list<Route> */
function namingOwnedRoutes(): array
{
    $owned = [];

    foreach (app('router')->getRoutes()->getRoutes() as $route) {
        $controller = $route->getAction('controller');
        $controller = is_string($controller) ? ltrim($controller, '\\') : '';
        $name = (string) $route->getName();

        // Owned when the name mentions the slug, the action is one of this
        // package's classes, or the route sits behind this package's gate.
        $gated = in_array(EnsureCanManage::class, $route->gatherMiddleware(), true);

        if ($gated || str_starts_with($controller, NAMING_NAMESPACE) || str_contains($name, NAMING_SLUG)) {
            $owned[] = $route;
        }
    }

    return $owned;
}

it('registers every route under the vendor-scoped laranail-db-console-webui. name', function (): void {
    $routes = namingOwnedRoutes();

    // Non-vacuity: the package ships five pages. A filter that matches nothing
    // would pass every assertion below trivially.
    expect(count($routes))->toBeGreaterThanOrEqual(5);

    foreach ($routes as $route) {
        expect($route->getName())
            ->not->toBeNull("route [{$route->uri()}] has no name")
            ->and((string) $route->getName())->toStartWith('laranail-' . NAMING_SLUG . '.');
    }
});

it('registers no bare rate limiter', function (): void {
    /** @var array<string, mixed> $limiters */
    $limiters = (fn (): array => $this->limiters)->call(app(RateLimiter::class));

    // Proves the registry was read; the package registers no limiter today.
    expect($limiters)->toBeArray();

    foreach (array_keys($limiters) as $name) {
        if (str_contains($name, NAMING_SLUG)) {
            expect($name)->toStartWith('laranail-' . NAMING_SLUG . '.');
        }
    }
});

it('registers every command under laranail::db-console-webui. except the listed deprecated forwarders', function (): void {
    $owned = [];

    foreach (Artisan::all() as $name => $command) {
        if (str_contains($name, NAMING_SLUG) || str_starts_with($command::class, NAMING_NAMESPACE)) {
            $owned[$name] = $command;
        }
    }

    // Non-vacuity: at least the install command must have been found.
    expect($owned)->toHaveKey('laranail::' . NAMING_SLUG . '.install');

    foreach ($owned as $name => $command) {
        if (array_key_exists($name, NAMING_DEPRECATED_COMMANDS)) {
            expect($command)->toBeInstanceOf(LegacyInstallCommand::class)
                ->and($command->isHidden())->toBeTrue("deprecated [{$name}] must stay out of `artisan list`");

            continue;
        }

        expect($name)->toStartWith('laranail::' . NAMING_SLUG . '.');
    }

    // A stale exemption fails instead of covering something new.
    foreach (array_keys(NAMING_DEPRECATED_COMMANDS) as $legacy) {
        expect($owned)->toHaveKey($legacy);
    }
});

it('registers no bare middleware alias', function (): void {
    // Non-vacuity: the framework's own aliases prove the registry was read.
    expect(app('router')->getMiddleware())->toHaveKey('auth');

    foreach (app('router')->getMiddleware() as $alias => $class) {
        $ownedClass = is_string($class) && str_starts_with(ltrim($class, '\\'), NAMING_NAMESPACE);

        if ($ownedClass || str_contains($alias, NAMING_SLUG)) {
            expect($alias)->toStartWith('laranail-' . NAMING_SLUG);
        }
    }
});

it('still resolves every deprecated bare route name to the scoped route, with a warning', function (): void {
    Log::spy();

    foreach (RouteNames::PAGES as $page) {
        $bare = RouteNames::LEGACY_PREFIX . $page;
        $scoped = RouteNames::name($page);

        // The registry holds only the scoped name...
        expect(RouteFacade::has($bare))->toBeFalse()
            ->and(RouteFacade::has($scoped))->toBeTrue()
            // ...and the bare one still generates the same URL.
            ->and(route($bare))->toBe(route($scoped));
    }

    Log::shouldHaveReceived('warning')->withArgs(
        fn (string $message): bool => str_contains($message, 'deprecated') && str_contains($message, RouteNames::name('dashboard')),
    );
});

it('keeps an unknown route name an error', function (): void {
    expect(fn (): string => route('db-console-webui.nope'))
        ->toThrow(Symfony\Component\Routing\Exception\RouteNotFoundException::class);
});

it('defers names it does not own to the resolver that was installed before it', function (): void {
    $url = app(UrlGenerator::class);
    $url->resolveMissingNamedRoutesUsing(fn (string $name): ?string => $name === 'someone-else.login' ? 'https://example.test/login' : null);

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
