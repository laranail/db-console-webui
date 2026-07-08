# Changelog

All notable changes to `laranail/db-console-webui` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-07-08

Initial release.

### Added

- Thin Livewire + Flux web UI over `laranail/db-console`, with zero business logic — every component calls a core service and reuses the core validation layer via `RuleProvider`.
- Components: server switcher (scope-filtered, session-persisted), dashboard (live counts + unreachable-server error state with retry), database wizard (create/drop with core validation), account manager (create with generate-once password), role manager (read-only view of the seeded roles), webhook manager (subscribe/remove with the signing secret shown once).
- `EnsureCanManage` middleware (auth + the db-console access gate + IP allow-list); opt-in routing via `laranail.db-console-webui.enabled`; namespaced `laranail.db-console-webui.*` configuration and `db-console-webui::` translations (English).
- A build-failing boundary architecture test: the UI may never touch engines, connections, or raw SQL, and may not declare its own validation rules for a domain field.
