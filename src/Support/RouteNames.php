<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Support;

/**
 * The route names this package registers. Route names live in a flat,
 * host-owned registry, so every one carries the vendor and the package slug:
 * `laranail-db-console-webui.<page>`.
 *
 * The pre-0.1 bare names (`db-console-webui.<page>`) are no longer registered;
 * they still resolve through package-tools' `BareRouteNameAliases`, declared on
 * the package in the service provider with {@see self::legacyMap()}.
 */
final class RouteNames
{
    public const string PREFIX = 'laranail-db-console-webui.';

    /**
     * @deprecated the bare `db-console-webui.` route-name prefix. Use {@see self::PREFIX}.
     *             Earliest removal: the next minor after 0.1.
     */
    public const string LEGACY_PREFIX = 'db-console-webui.';

    /** @var list<string> */
    public const array PAGES = ['dashboard', 'databases', 'accounts', 'roles', 'webhooks'];

    public static function name(string $page): string
    {
        return self::PREFIX . $page;
    }

    /**
     * Deprecated bare name => scoped name, for every page.
     *
     * @return array<string, string>
     */
    public static function legacyMap(): array
    {
        $map = [];

        foreach (self::PAGES as $page) {
            $map[self::LEGACY_PREFIX . $page] = self::name($page);
        }

        return $map;
    }

    /**
     * The scoped name a deprecated bare name stands for, or null when the
     * name is not one of this package's legacy names.
     */
    public static function scopedFromLegacy(string $name): ?string
    {
        if (! str_starts_with($name, self::LEGACY_PREFIX)) {
            return null;
        }

        $page = substr($name, strlen(self::LEGACY_PREFIX));

        return in_array($page, self::PAGES, true) ? self::name($page) : null;
    }
}
