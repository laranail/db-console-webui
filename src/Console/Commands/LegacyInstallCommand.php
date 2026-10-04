<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Console\Commands;

use Illuminate\Console\Command;

/**
 * The pre-0.1 bare install command name, kept working as a hidden forwarder.
 *
 * Artisan's command registry is a flat map, so a bare `db-console-webui:install`
 * is a plausible collision with a sibling package or the application. The
 * command now lives at `laranail::db-console-webui.install`; this prints a
 * deprecation line and runs it.
 *
 * @deprecated use `php artisan laranail::db-console-webui.install`. Earliest
 *             removal: the next minor after 0.1.
 */
final class LegacyInstallCommand extends Command
{
    public const string LEGACY_NAME = 'db-console-webui:install';

    public const string SCOPED_NAME = 'laranail::db-console-webui.install';

    /** @var string */
    protected $signature = self::LEGACY_NAME;

    /** @var string */
    protected $description = 'Deprecated: use laranail::db-console-webui.install';

    /** @var bool */
    protected $hidden = true;

    public function handle(): int
    {
        $this->warn(sprintf(
            'The [%s] command is deprecated and will be removed no earlier than the next minor after 0.1; use [%s].',
            self::LEGACY_NAME,
            self::SCOPED_NAME,
        ));

        return $this->call(self::SCOPED_NAME);
    }
}
