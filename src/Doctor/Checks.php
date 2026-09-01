<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Doctor;

use Simtabi\Laranail\Package\Tools\Services\Doctor\Checks\ConfigPresentCheck;
use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorCheck;

/**
 * Doctor checks for laranail/db-console-webui. Registered on the package via
 * `->hasDoctorChecks(Checks::all())` and run by
 * `php artisan laranail::package-tools.doctor`.
 */
final class Checks
{
    /** @return list<DoctorCheck|class-string<DoctorCheck>> */
    public static function all(): array
    {
        return [
            new ConfigPresentCheck(
                ['db-console-webui config' => 'laranail.db-console-webui'],
                required: true,
                name: 'db-console-webui:config',
                description: 'DB Console Web UI config is published',
            ),
        ];
    }
}
