# Changelog

All notable changes to `laranail/db-console-webui` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `Support\RouteNames`, the single source of the package's route names, and `Routing\BareRouteNameResolver`, which keeps the deprecated bare route names resolving through `URL::resolveMissingNamedRoutesUsing()` and defers any other name to a resolver installed before it.
- `tests/Feature/NamingConventionTest.php`, which reads the live router, rate limiter, Artisan and middleware registries and fails on any bare name the package owns.
- `Support\Translations`, the one place the package looks up its own strings. It reads the canonical `laranail/db-console-webui::` namespace and falls back to an override made against `laranail-db-console-webui::` when the canonical line is still the packaged default, the same rule as `laranail/console` 0.1.5.
- `Support\LivewireNames` and `Support\BrowserEvents`, the single source of the package's Livewire component names and browser event names, and `RouteNames::legacyMap()`.
- A direct `laranail/package-tools ^0.1.3` requirement. The package used it only through `laranail/db-console` before, and now uses its `BareRouteNameAliases` and naming assertions.

### Changed

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

- Published translation overrides were never read. `vendor:publish` writes them to `lang/vendor/laranail/db-console-webui`, but the package translated through `laranail-db-console-webui::`, which reads `lang/vendor/laranail-db-console-webui`. The package now translates through the slash namespace, while still honouring overrides made against the hyphen one.
- The customize-a-screen recipe named `resources/views/vendor/db-console-webui/` as the override directory; it is `resources/views/vendor/laranail-db-console-webui/`.
- `BareRouteNameResolver` passed the previously installed resolver's answer through unchecked, so a foreign resolver returning anything but a string (an object, an int, an array) raised a `TypeError` inside `route()` under `strict_types`. A non-string answer now reads as "not resolved", and `route()` reports the missing route as before.

## [0.1.0] - 2026-07-11

Initial public release.

[Unreleased]: https://github.com/laranail/db-console-webui/compare/v0.1.0...HEAD
