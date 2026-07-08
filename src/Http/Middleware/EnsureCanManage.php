<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The single gate for the web UI: the caller must be authenticated, hold at
 * least one DBConsole permission (so a signed-in but unauthorised user cannot
 * reach it), and — when configured — come from an allow-listed IP. All finer
 * authorization still happens inside the core services this UI calls, so the
 * UI never re-implements a permission check.
 */
final readonly class EnsureCanManage
{
    public function __construct(private Config $config) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() === null) {
            abort(403, 'You must be signed in to use the database console.');
        }

        if (! $this->ipAllowed($request)) {
            abort(403, 'Your IP is not permitted to reach the database console.');
        }

        // Any DBConsole ability at all is the minimum bar to see the UI; the
        // core enforces the specific permission on every action.
        if (! $request->user()->can('db-console.database.view')
            && ! $request->user()->can('db-console.server.view')) {
            abort(403, 'You do not have access to the database console.');
        }

        return $next($request);
    }

    private function ipAllowed(Request $request): bool
    {
        /** @var list<string> $allowed */
        $allowed = (array) $this->config->get('laranail.db-console-webui.allowed_ips', []);

        return $allowed === [] || in_array((string) $request->ip(), $allowed, true);
    }
}
