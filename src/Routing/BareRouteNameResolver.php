<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Routing;

use Psr\Log\LoggerInterface;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Contracts\Container\Container;
use Simtabi\Laranail\DBConsoleWebUI\Support\RouteNames;

/**
 * Keeps the deprecated bare route names (`db-console-webui.<page>`) working
 * after the routes moved to `laranail-db-console-webui.<page>`.
 *
 * Installed with `URL::resolveMissingNamedRoutesUsing()`, which the URL
 * generator consults only for a name it does NOT hold. So it cannot shadow a
 * route the application defines itself, and it survives `route:cache` (it is a
 * runtime callback, not a replacement of the route collection).
 *
 * The URL generator holds a single resolver slot, so a later registration
 * silently replaces an earlier one. This resolver captures whatever was there
 * before it and defers to it for any name it does not own, so installing it
 * never disables another package's fallback.
 *
 * `Route::has()` asks the collection directly and never reaches this
 * resolver: `Route::has('db-console-webui.dashboard')` answers false. Ask for
 * the scoped name instead.
 *
 * @deprecated exists only for the bare names; goes with them, no earlier than
 *             the next minor after 0.1.
 */
final class BareRouteNameResolver
{
    /** @var array<string, true> legacy names already warned about in this process */
    private array $warned = [];

    /**
     * @param (callable(string, mixed, ?bool): ?string)|null $previous
     */
    public function __construct(
        private readonly Router $router,
        private readonly UrlGenerator $url,
        private readonly Container $container,
        private readonly mixed $previous = null,
    ) {}

    public function __invoke(string $name, mixed $parameters = [], ?bool $absolute = true): ?string
    {
        $scoped = RouteNames::scopedFromLegacy($name);

        if ($scoped !== null && $this->router->has($scoped)) {
            $this->warnOnce($name, $scoped);

            return $this->url->route($scoped, $parameters ?? [], $absolute ?? true);
        }

        return is_callable($this->previous) ? ($this->previous)($name, $parameters, $absolute) : null;
    }

    /**
     * Install on the given generator, chaining whatever resolver it already holds.
     */
    public static function install(Router $router, UrlGenerator $url, Container $container): self
    {
        /** @var (callable(string, mixed, ?bool): ?string)|null $previous */
        $previous = (fn (): mixed => $this->missingNamedRouteResolver)->call($url);

        $resolver = new self($router, $url, $container, $previous);

        $url->resolveMissingNamedRoutesUsing($resolver);

        return $resolver;
    }

    private function warnOnce(string $legacy, string $scoped): void
    {
        if (isset($this->warned[$legacy])) {
            return;
        }

        $this->warned[$legacy] = true;

        // Resolved per warning, not at boot, so a swapped logger is honoured.
        /** @var LoggerInterface $logger */
        $logger = $this->container->make(LoggerInterface::class);

        $logger->warning(
            "laranail/db-console-webui: route name [{$legacy}] is deprecated and will be removed no earlier than the next minor after 0.1; use [{$scoped}].",
        );
    }
}
