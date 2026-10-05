# Changelog

All notable changes to `laranail/db-console-webui` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `Support\RouteNames`, the single source of the package's route names, and `Routing\BareRouteNameResolver`, which keeps the deprecated bare route names resolving through `URL::resolveMissingNamedRoutesUsing()` and defers any other name to a resolver installed before it.
- `tests/Feature/NamingConventionTest.php`, which reads the live router, rate limiter, Artisan and middleware registries and fails on any bare name the package owns.

### Changed

- Route names are vendor-scoped: `db-console-webui.<page>` is now `laranail-db-console-webui.<page>` for `dashboard`, `databases`, `accounts`, `roles` and `webhooks`.
- The install command is `laranail::db-console-webui.install`.

### Deprecated

- The bare route names `db-console-webui.<page>`. `route()` still resolves them to the scoped routes and logs a one-time warning; `Route::has()` and `routeIs()` see only the scoped names. Earliest removal: the next minor after 0.1.
- The bare `db-console-webui:install` command, kept as a hidden forwarder that prints a deprecation line and runs `laranail::db-console-webui.install`. Earliest removal: the next minor after 0.1.

### Fixed

- `BareRouteNameResolver` passed the previously installed resolver's answer through unchecked, so a foreign resolver returning anything but a string (an object, an int, an array) raised a `TypeError` inside `route()` under `strict_types`. A non-string answer now reads as "not resolved", and `route()` reports the missing route as before.

## [0.1.0] - 2026-07-11

Initial public release.

[Unreleased]: https://github.com/laranail/db-console-webui/compare/v0.1.0...HEAD
