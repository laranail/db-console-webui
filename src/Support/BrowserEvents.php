<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Support;

/**
 * The browser events this package dispatches. Browser events share one
 * page-wide namespace with every other package and the host's own scripts, so
 * each carries the vendor and the package slug.
 */
final class BrowserEvents
{
    /** Dispatched by the server switcher after the operator picks a server. */
    public const string SERVER_CHANGED = 'laranail-db-console-webui:server-changed';

    /**
     * @deprecated the bare name of {@see self::SERVER_CHANGED}. Still dispatched
     *             beside it so existing listeners keep firing; earliest removal:
     *             the next minor after 0.1.
     */
    public const string LEGACY_SERVER_CHANGED = 'db-console:server-changed';
}
