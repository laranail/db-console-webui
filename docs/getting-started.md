# Getting started

The screens and what each one does. Every screen is a Livewire component that calls a core service — it holds no logic of its own.

## Server switcher

Picks the active server (only those you can view) and remembers it in the session. A server whose db-console `hosting` names a managed provider shows it beside the engine, for example `primary (mysql · DigitalOcean Managed Databases)`.

## Dashboard

Shows the live database count for the active server, and a clear error state (with retry) when a server is unreachable — the reason is sanitized, never leaking a connection string.

## Database wizard

Create and drop databases. Input is validated by the core's rules, so an invalid identifier is rejected here exactly as it would be at the CLI or API. Dropping requires typing the name to confirm.

A drop stopped because the backup before it failed says so on its own, since nothing was dropped. On a managed server db-console takes no dump before a drop, and the screen says the provider's point-in-time recovery is the restore path.

## Account manager

Create and drop accounts; a generated password is shown once and never stored. The host field starts at the server's default host from db-console (`%` on a managed MySQL, otherwise `accounts.default_host`) and is not shown where the engine cannot scope accounts by host. Dropping an account requires typing its username again, the core's `DropAccountRequest` rule. On a managed server the provider's and the engine's own accounts are not listed.

## Role manager

A read-only view of the shipped console roles and their composed permissions.

## Webhook manager

Subscribe and unsubscribe webhooks; the signing secret is shown once on creation.

---

[← Docs index](../README.md#documentation)
