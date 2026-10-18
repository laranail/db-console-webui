# Changelog

All notable changes to `laranail/db-console-webui` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- **The release SBOM is named after this package.** `release.yml` wrote and attached it as
  `db-console.cdx.json`, copied from laranail/db-console, so v0.1.1's asset carries the other package's
  name. Releases from now on attach `db-console-webui.cdx.json`.

## [0.1.1] - 2026-10-18

### Added

- **Accounts can be dropped from the account manager.** Pick a listed account, type its username again
  (the core's `DropAccountRequest` rule), and it calls `AccountManager::drop`. The component's docblock
  promised this; only create existed.
- **The screens read where a server runs, from db-console's hosting profile:**
  - the account host starts at `ServerRegistry::defaultAccountHost()` (`%` on a managed MySQL), not a
    hard-coded `localhost`, and is not shown where the engine has no host scoping;
  - the server switcher shows a managed server's provider;
  - the database wizard reports a drop stopped by a failed backup on its own (`BackupFailed`), and on a
    managed server says the provider's point-in-time recovery is the restore path.
- **Existing accounts and databases are a choice, not a silent success.** db-console now refuses to create an account or database that already exists unless the operator may adopt it, and says which policies they may resubmit with. The account manager and database wizard show those choices (adopt; for an account, also adopt and set a new generated password) with the risk of each, only when the refusal offers them, and never send a policy the refusal did not offer. An adopted resource is reported as adopted, not created. `Http\Livewire\Concerns\RespondsToRefusals` holds the shared handling.
- **Assurance refusals link to the host's confirmation page.** When db-console needs a recent identity confirmation, the components show a "Confirm your identity" link to the host's `password.confirm` route when it exists. The host authenticates; the WebUI only points at it.

- `Support\RouteNames`, the single source of the package's route names, and `Routing\BareRouteNameResolver`, which keeps the deprecated bare route names resolving through `URL::resolveMissingNamedRoutesUsing()` and defers any other name to a resolver installed before it.
- `tests/Feature/NamingConventionTest.php`, which reads the live router, rate limiter, Artisan and middleware registries and fails on any bare name the package owns.
- `Support\Translations`, the one place the package looks up its own strings. It reads the canonical `laranail/db-console-webui::` namespace and falls back to an override made against `laranail-db-console-webui::` when the canonical line is still the packaged default, the same rule as `laranail/console` 0.1.5.
- `Support\LivewireNames` and `Support\BrowserEvents`, the single source of the package's Livewire component names and browser event names, and `RouteNames::legacyMap()`.
- A `live` CI job and Pest group that drive the database wizard and the account manager against real MySQL 8.4 and PostgreSQL 18, each as a restricted admin: on MySQL exactly db-console's recommended minimal grant with `sql_require_primary_key=ON`, on Postgres a `CREATEDB CREATEROLE` role that is not a superuser. The wizard creates and drops a database, and an account the manager creates logs in with the password it shows. The group skips without its `DB_CONSOLE_LIVE_*` variables and runs in CI with `--fail-on-skipped`; `LiveAdminGrantTest` keeps the workflow's grant equal to db-console's `MinimalAdminGrant`.
- A direct `laranail/package-tools ^0.1.3` requirement. The package used it only through `laranail/db-console` before, and now uses its `BareRouteNameAliases` and naming assertions.

### Changed

- Requires `laranail/db-console ^0.1.1`, the first release with the existing-resource policy and operator assurance.
- `illuminate/console`, `illuminate/http`, `illuminate/routing` are now declared in `require` at `^13.0`. `src/` imports them, and they were only arriving transitively.
- The access middleware and README check laranail/db-console's scoped abilities
  (`laranail-db-console.database.view`, `laranail-db-console.server.view`). db-console still answers the
  old `db-console.*` names as deprecated aliases, so a host policy granting either keeps working.

- Route names are vendor-scoped: `db-console-webui.<page>` is now `laranail-db-console-webui.<page>` for `dashboard`, `databases`, `accounts`, `roles` and `webhooks`.
- The install command is `laranail::db-console-webui.install`.
- The deprecated bare route names are served by package-tools' shared `BareRouteNameAliases`, declared with `$package->hasDeprecatedRouteNames()`, instead of the package's own resolver. Behaviour is unchanged: one logged warning per name, chained to any resolver installed earlier. The warning now reads "the route name [...] is deprecated and will stop resolving no earlier than the next minor after 0.1; use [...]", and is announced once per process rather than once per resolver instance.
- Livewire components are vendor-scoped: `db-console-webui.<name>` is now `laranail-db-console-webui.<name>` for `server-switcher`, `dashboard`, `database-wizard`, `account-manager`, `role-manager` and `webhook-manager`.
- The server switcher dispatches `laranail-db-console-webui:server-changed`.
- Views and translations are registered under `laranail/db-console-webui::` as well as `laranail-db-console-webui::`. The package's own views use the slash form, and its own strings go through `Support\Translations`, so overrides in either namespace apply and nothing a host overrode before is lost; when both override a line, the canonical one wins.
- `NamingConventionTest` runs on package-tools' `AssertsRegisteredNames`, and also covers Livewire components, view and translation namespaces, and the browser event.

### Deprecated

- The bare route names `db-console-webui.<page>`. `route()` still resolves them to the scoped routes and logs a one-time warning; `Route::has()` and `routeIs()` see only the scoped names. Earliest removal: the next minor after 0.1.
- The bare `db-console-webui:install` command, kept as a hidden forwarder that prints a deprecation line and runs `laranail::db-console-webui.install`. Earliest removal: the next minor after 0.1.
- `Routing\BareRouteNameResolver`. It still works when installed by hand, delegating to package-tools' `BareRouteNameAliases`. Earliest removal: the next minor after 0.1.
- The bare Livewire component names `db-console-webui.<name>`. They are still registered for the same classes; on Livewire 4, mounting one raises one `E_USER_DEPRECATED` per name per process naming the replacement. Livewire 3 normalises the requested name before any hook runs, so there they work without a notice. Earliest removal: the next minor after 0.1.
- The bare `db-console:server-changed` browser event. It is still dispatched beside `laranail-db-console-webui:server-changed`. Earliest removal: the next minor after 0.1.

### Fixed

- **The install-command test left published views in the Testbench skeleton**, inside `vendor/`, where they shadowed the package's own views on every later local run, so a changed view never showed. It now removes what the install added.

- Published translation overrides were never read. `vendor:publish` writes them to `lang/vendor/laranail/db-console-webui`, but the package translated through `laranail-db-console-webui::`, which reads `lang/vendor/laranail-db-console-webui`. The package now translates through the slash namespace, while still honouring overrides made against the hyphen one.
- The customize-a-screen recipe named `resources/views/vendor/db-console-webui/` as the override directory; it is `resources/views/vendor/laranail-db-console-webui/`.
- `BareRouteNameResolver` passed the previously installed resolver's answer through unchecked, so a foreign resolver returning anything but a string (an object, an int, an array) raised a `TypeError` inside `route()` under `strict_types`. A non-string answer now reads as "not resolved", and `route()` reports the missing route as before.

## [0.1.0] - 2026-07-11

Initial public release.

[Unreleased]: https://github.com/laranail/db-console-webui/compare/v0.1.1...HEAD
[0.1.1]: https://github.com/laranail/db-console-webui/compare/v0.1.0...v0.1.1
