# Upgrading

This document lists breaking changes between versions. Pre-1.0, breaking changes may occur between minor versions; each is listed here with its migration step.

## Unreleased

### Route names are vendor-scoped

The five routes are now named `laranail-db-console-webui.<page>` (`dashboard`, `databases`, `accounts`, `roles`, `webhooks`). `route('db-console-webui.<page>')` keeps working as a deprecated alias and logs a warning, so links need no change today. Two call shapes do need one, because they read the route collection directly and never reach the fallback:

- `Route::has('db-console-webui.dashboard')` now answers `false`; ask for `laranail-db-console-webui.dashboard`.
- `request()->routeIs('db-console-webui.*')` no longer matches; use `laranail-db-console-webui.*`.

### Livewire components are vendor-scoped

The six components are now registered as `laranail-db-console-webui.<name>` (`server-switcher`, `dashboard`, `database-wizard`, `account-manager`, `role-manager`, `webhook-manager`). The bare `db-console-webui.<name>` names are still registered, so `@livewire('db-console-webui.dashboard')` keeps rendering. On Livewire 4 each raises one `E_USER_DEPRECATED` per process; on Livewire 3 they render without a notice, because Livewire 3 does not expose the requested name. Move embeds to the scoped names.

### The server-changed browser event is `laranail-db-console-webui:server-changed`

The server switcher dispatches `laranail-db-console-webui:server-changed` and, during the deprecation window, `db-console:server-changed` beside it. Move listeners to the scoped name.

### Views and translations also answer to `laranail/db-console-webui::`

The slash form is now the package's canonical namespace. `laranail-db-console-webui::` keeps resolving the same files, and published view overrides stay in `resources/views/vendor/laranail-db-console-webui/`.

The package's own views and components now translate through `laranail/db-console-webui::`, which reads published overrides from `lang/vendor/laranail/db-console-webui/`, the directory `vendor:publish --tag=laranail::db-console-webui-translations` writes to. Overrides published there now take effect; before, the package read through the hyphen namespace and never saw them.

Nothing you overrode before is lost. An override made against the hyphen namespace, a file in `lang/vendor/laranail-db-console-webui/` or lines added with `addLines(…, 'laranail-db-console-webui')`, still applies wherever the canonical namespace holds only the packaged line. If both namespaces override the same line, the canonical one wins. No change is required; move hand-placed files to `lang/vendor/laranail/db-console-webui/` when convenient.

### The install command is `laranail::db-console-webui.install`

`php artisan db-console-webui:install` still works, prints a deprecation line, and runs the new command. Update scripts and deploy hooks to the new name.
