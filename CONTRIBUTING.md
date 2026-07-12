# Contributing

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
- Livewire component names are `db-console-webui.<name>`; views are `db-console-webui::livewire.<name>`.
- No AI attribution in commits or PRs.
