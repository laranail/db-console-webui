<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Routing;

use Psr\Log\LoggerInterface;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Contracts\Container\Container;
use Simtabi\Laranail\DBConsoleWebUI\Support\RouteNames;
use Simtabi\Laranail\Package\Tools\Enums\DeprecationNotice;
use Simtabi\Laranail\Package\Tools\Support\Routing\BareRouteNameAliases;

/**
 * Keeps the deprecated bare route names (`db-console-webui.<page>`) working
 * after the routes moved to `laranail-db-console-webui.<page>`.
 *
 * Superseded by package-tools' {@see BareRouteNameAliases}, which the service
 * provider now declares through `$package->hasDeprecatedRouteNames()`. This
 * class is kept so a caller that installs it by hand still works: it delegates
 * every call to a `BareRouteNameAliases` built with the same map, the same
 * logged warning, and the resolver that was installed before it.
 *
 * `Route::has()` asks the collection directly and never reaches this
 * resolver: `Route::has('db-console-webui.dashboard')` answers false. Ask for
 * the scoped name, or `BareRouteNameAliases::has()`.
 *
 * @deprecated use package-tools' BareRouteNameAliases (installed by the
 *             provider). Earliest removal: the next minor after 0.1.
 */
final readonly class BareRouteNameResolver
{
    private BareRouteNameAliases $aliases;

    /**
     * @param (callable(string, mixed, ?bool): mixed)|null $previous
     */
    public function __construct(
        Router $router,
        UrlGenerator $url,
        Container $container,
        mixed $previous = null,
    ) {
        $this->aliases = new BareRouteNameAliases(
            router: $router,
            url: $url,
            package: 'laranail/db-console-webui',
            map: RouteNames::legacyMap(),
            notice: DeprecationNotice::Log,
            // Resolved per warning, not at boot, so a swapped logger is honoured.
            logger: static fn (): LoggerInterface => $container->make(LoggerInterface::class),
            previous: $previous,
        );
    }

    public function __invoke(string $name, mixed $parameters = [], ?bool $absolute = true): ?string
    {
        return ($this->aliases)($name, $parameters, $absolute);
    }

    /**
     * Install on the given generator, chaining whatever resolver it already holds.
     */
    public static function install(Router $router, UrlGenerator $url, Container $container): self
    {
        $resolver = new self($router, $url, $container, BareRouteNameAliases::previousResolver($url));

        $url->resolveMissingNamedRoutesUsing($resolver);

        return $resolver;
    }

    /**
     * The shared implementation this class delegates to.
     */
    public function aliases(): BareRouteNameAliases
    {
        return $this->aliases;
    }
}
