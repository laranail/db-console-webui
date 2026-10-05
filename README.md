# laranail/db-console-webui

[![Tests](https://img.shields.io/github/actions/workflow/status/laranail/db-console-webui/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/laranail/db-console-webui/actions/workflows/tests.yml)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/laranail/db-console-webui/static-analysis.yml?branch=main&label=static%20analysis&style=flat-square)](https://github.com/laranail/db-console-webui/actions/workflows/static-analysis.yml)
[![License MIT](https://img.shields.io/packagist/l/laranail/db-console-webui.svg?style=flat-square)](LICENSE)

`laranail/db-console-webui` is not published to Packagist, so there is no registry-version badge to show: see [Install](#install).

> A thin Livewire + Flux web UI for [`laranail/db-console`](https://github.com/laranail/db-console) — all UI, zero business logic. Every screen calls the audited core services and reuses the core validation layer.

Requires PHP `^8.4.1 || ^8.5`, Laravel `^13.0`, Livewire `^3.5.19 || ^4.0`, and Flux `^2.0`. This package is a **front end** only: the logic, security, and audit live in `laranail/db-console`, which this depends on.

The design is deliberate — the UI can never grow its own business logic or validation. A build-failing architecture test forbids any component from touching an engine, a connection, or raw SQL, or declaring its own validation rules for a database field. Names, hosts, and privileges are validated by the exact same rules the CLI and REST API use, so the three surfaces can never disagree.

## Install

```bash
composer require laranail/db-console-webui
php artisan laranail::db-console-webui.install
```

The bare `db-console-webui:install` still works as a deprecated alias: it prints a deprecation line and runs `laranail::db-console-webui.install`.

The installer publishes the config, views, and language files. Build the CSS/JS entrypoints (`resources/css/db-console.css`, `resources/js/db-console.js`) into your app's Vite pipeline, or publish and adapt them.

The UI mounts at `/db-console` (configurable) and is guarded by the `EnsureCanManage` middleware: the caller must be signed in, hold a DBConsole permission, and — when configured — come from an allow-listed IP. Every action is still authorized inside the core services.

## Quick start guide and usage

### Getting started

1. Install and configure the core, `laranail/db-console`, first: register your servers and run its install.
2. Build the entrypoints into your app's Vite pipeline:

   ```js
   // vite.config.js
   laravel({ input: ["resources/css/db-console.css", "resources/js/db-console.js", ...] })
   ```

3. Optionally move the mount point or turn on Flux Pro components in `.env`:

   ```dotenv
   DB_CONSOLE_WEBUI_PATH=db-console
   DB_CONSOLE_WEBUI_FLUX_PRO=false
   ```

### Usage

```blade
{{-- resources/views/layouts/partials/admin-nav.blade.php --}}
@can('db-console.database.view')
    <a href="{{ route('laranail-db-console-webui.dashboard') }}">Databases</a>
    <a href="{{ route('laranail-db-console-webui.accounts') }}">Database accounts</a>
@endcan
```

Route names are `laranail-db-console-webui.<page>` (`dashboard`, `databases`, `accounts`, `roles`, `webhooks`), and Livewire components `laranail-db-console-webui.<name>`. The bare `db-console-webui.<page>` route names and `db-console-webui.<name>` components are deprecated aliases that still work; `Route::has()` and `routeIs()` see only the scoped route names. Views and translations answer to `laranail/db-console-webui::`.

The full walkthrough is in [Getting started](docs/getting-started.md); everything else is in the [documentation index](#documentation).

## <a name="documentation"></a>Documentation

Full documentation is hosted at **<https://opensource.simtabi.com/documentation/laranail/db-console-webui/>**.

### Guides

- [Installation](docs/installation.md) — install, mount the UI, wire the assets, guard access.
- [Getting started](docs/getting-started.md) — the screens and what each one does.
- [Configuration](docs/configuration.md) — every `laranail.db-console-webui.*` key.
- [Architecture](docs/architecture.md) — the boundary, and why the UI holds no logic.

### Reference

- [Components](docs/tools/components.md) — the Livewire components and the core services each calls.
- [Middleware](docs/tools/middleware.md) — `EnsureCanManage` and access control.

### Recipes

- [Customize a screen](docs/recipes/customize-a-screen.md)
- [Restrict access by IP](docs/recipes/restrict-by-ip.md)

## Stability

Pre-1.0, tracking `laranail/db-console`. Breaking changes are in [UPGRADING.md](UPGRADING.md) and the [CHANGELOG](CHANGELOG.md).

## Local development

```bash
composer install      # resolves laranail/db-console from Packagist
vendor/bin/pest       # component + boundary tests
composer lint         # Pint, PHPStan, Rector
```

To develop against unreleased `laranail/db-console` changes, add a local path repository for it (`composer config repositories.db-console path ../db-console`) — this is a local-only override and is not committed.

## Sister packages

- [`laranail/db-console`](https://github.com/laranail/db-console) — the headless core this UI wraps.
- [`laranail/console`](https://github.com/laranail/console), [`laranail/package-tools`](https://github.com/laranail/package-tools), [`laranail/enumerator`](https://github.com/laranail/enumerator) — the shared toolkit.

## Community

Questions in [GitHub Discussions](https://github.com/laranail/db-console-webui/discussions); bugs in [Issues](https://github.com/laranail/db-console-webui/issues).

## Contributing & security

See [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per [SECURITY.md](SECURITY.md) (`opensource@simtabi.com`), never in a public issue.

## License

MIT — see [LICENSE](LICENSE). Copyright (c) 2026 Simtabi LLC.
