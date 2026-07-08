# Architecture

The boundary, and why the UI holds no logic.

## The rule

This package is a thin front end over `laranail/db-console`. Every component:

- calls a core service (`DatabaseManager`, `AccountManager`, `WebhookManager`, the RBAC driver) for every action;
- pulls its validation rules from the core `RuleProvider`, never declaring its own for a database field;
- renders results and shows a core exception's `userMessage()` on failure.

## Enforcement

A build-failing architecture test (`tests/Architecture/BoundaryTest.php`) fails the suite if any component references an engine, a connection, or raw SQL, or declares its own rules for a domain field. It includes an anti-tautology probe so it can never silently pass. This is why the UI, CLI, and REST API can never disagree about what is valid or who is authorized: they share one core.

## Why a separate package?

Keeping the UI out of the core keeps the core headless and reusable — you can put any front end on it. It also keeps the security-critical logic in one tested place, rather than spread across view code.

---

[← Docs index](../README.md#documentation)
