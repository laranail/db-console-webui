# Contributing

Where this file is silent, the [laranail contributing guide](https://github.com/laranail/.github/blob/HEAD/CONTRIBUTING.md) applies.

Thanks for helping improve `laranail/db-console-webui`.

## Setup

```bash
composer install
vendor/bin/pest
composer lint
```

The web UI is a **thin wrapper** over `laranail/db-console`. The one rule that governs every change: components contain no business logic. They call core services, reuse core validation via `RuleProvider`, and render results. The boundary architecture test (`tests/Architecture/BoundaryTest.php`) fails the build if this is crossed — never weaken it.

## Conventions

- PHP `^8.4.1 || ^8.5`, `declare(strict_types=1)` everywhere.
- Pint (Laravel preset), PHPStan (level 6), Rector must pass.
- Livewire component names are `laranail-db-console-webui.<name>`; views are `laranail/db-console-webui::livewire.<name>`, and the package's own strings are looked up with `Support\Translations::get('ui.<key>')`, never a bare `__()`, so hyphen-namespace overrides keep applying. The bare `db-console-webui.<name>` components and the `laranail-db-console-webui::` namespaces are still registered for hosts, but new code inside the package uses the scoped names. `tests/Feature/NamingConventionTest.php` reads the live registries and fails on a new bare name.
- No AI attribution in commits or PRs.
