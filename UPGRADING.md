# Upgrading

This document lists breaking changes between versions. Pre-1.0, breaking changes may occur between minor versions; each is listed here with its migration step.

## Unreleased

### Route names are vendor-scoped

The five routes are now named `laranail-db-console-webui.<page>` (`dashboard`, `databases`, `accounts`, `roles`, `webhooks`). `route('db-console-webui.<page>')` keeps working as a deprecated alias and logs a warning, so links need no change today. Two call shapes do need one, because they read the route collection directly and never reach the fallback:

- `Route::has('db-console-webui.dashboard')` now answers `false`; ask for `laranail-db-console-webui.dashboard`.
- `request()->routeIs('db-console-webui.*')` no longer matches; use `laranail-db-console-webui.*`.

### The install command is `laranail::db-console-webui.install`

`php artisan db-console-webui:install` still works, prints a deprecation line, and runs the new command. Update scripts and deploy hooks to the new name.
