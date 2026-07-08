<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enable the web UI
    |--------------------------------------------------------------------------
    | The UI is opt-in. When false, no routes are registered and the package
    | is inert — the headless core is unaffected.
    */
    'enabled' => env('DB_CONSOLE_WEBUI_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    | The path the UI mounts at and the middleware stack that guards it. The
    | EnsureCanManage middleware is always applied on top (auth + the
    | db-console access gate + the IP allow-list).
    */
    'path' => env('DB_CONSOLE_WEBUI_PATH', 'db-console'),

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | IP allow-list
    |--------------------------------------------------------------------------
    | When non-empty, only these client IPs may reach the UI. Empty = no IP
    | restriction (auth + the gate still apply).
    |
    | @var list<string>
    */
    'allowed_ips' => [],

    /*
    |--------------------------------------------------------------------------
    | Flux
    |--------------------------------------------------------------------------
    | Whether Flux Pro components may be used. When false the UI uses only the
    | free Flux component set (with graceful fallbacks).
    */
    'flux' => [
        'pro' => env('DB_CONSOLE_WEBUI_FLUX_PRO', false),
    ],

];
