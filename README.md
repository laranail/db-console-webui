# laranail/db-console-webui

[![Packagist Version](https://img.shields.io/packagist/v/laranail/db-console-webui.svg?style=flat-square)](https://packagist.org/packages/laranail/db-console-webui)
[![Tests](https://img.shields.io/github/actions/workflow/status/laranail/db-console-webui/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/laranail/db-console-webui/actions/workflows/tests.yml)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/laranail/db-console-webui/static-analysis.yml?branch=main&label=static%20analysis&style=flat-square)](https://github.com/laranail/db-console-webui/actions/workflows/static-analysis.yml)
[![License MIT](https://img.shields.io/packagist/l/laranail/db-console-webui.svg?style=flat-square)](LICENSE)

> A thin Livewire + Flux web UI for [`laranail/db-console`](https://github.com/laranail/db-console) — all UI, zero business logic. Every screen calls the audited core services and reuses the core validation layer.

Requires PHP `^8.4.1 || ^8.5`, Laravel `^13.0`, Livewire `^3.5.19 || ^4.0`, and Flux `^2.0`. This package is a **front end** only: the logic, security, and audit live in `laranail/db-console`, which this depends on.

The design is deliberate — the UI can never grow its own business logic or validation. A build-failing architecture test forbids any component from touching an engine, a connection, or raw SQL, or declaring its own validation rules for a database field. Names, hosts, and privileges are validated by the exact same rules the CLI and REST API use, so the three surfaces can never disagree.

## Install

```bash
composer require laranail/db-console-webui
php artisan db-console-webui:install
```

The installer publishes the config, views, and language files. Build the CSS/JS entrypoints (`resources/css/db-console.css`, `resources/js/db-console.js`) into your app's Vite pipeline, or publish and adapt them.

The UI mounts at `/db-console` (configurable) and is guarded by the `EnsureCanManage` middleware: the caller must be signed in, hold a DBConsole permission, and — when configured — come from an allow-listed IP. Every action is still authorized inside the core services.

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
composer install
vendor/bin/pest       # component + boundary tests (skips live engines without Docker)
composer lint         # Pint, PHPStan, Rector
```

The sibling `laranail/db-console` is consumed as a Composer path repository (`../db-console`) during development.

## Sister packages

- [`laranail/db-console`](https://github.com/laranail/db-console) — the headless core this UI wraps.
- [`laranail/console`](https://github.com/laranail/console), [`laranail/package-tools`](https://github.com/laranail/package-tools), [`laranail/enumerator`](https://github.com/laranail/enumerator) — the shared toolkit.

## Community

Questions in [GitHub Discussions](https://github.com/laranail/db-console-webui/discussions); bugs in [Issues](https://github.com/laranail/db-console-webui/issues).

## Contributing & security

See [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per [SECURITY.md](SECURITY.md) (`opensource@simtabi.com`), never in a public issue.

## License

MIT — see [LICENSE](LICENSE). Copyright (c) 2026 Simtabi LLC.
