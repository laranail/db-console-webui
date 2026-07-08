# Getting started

The screens and what each one does. Every screen is a Livewire component that calls a core service — it holds no logic of its own.

## Server switcher

Picks the active server (only those you can view) and remembers it in the session.

## Dashboard

Shows the live database count for the active server, and a clear error state (with retry) when a server is unreachable — the reason is sanitized, never leaking a connection string.

## Database wizard

Create and drop databases. Input is validated by the core's rules, so an invalid identifier is rejected here exactly as it would be at the CLI or API. Dropping requires typing the name to confirm.

## Account manager

Create accounts; a generated password is shown once and never stored.

## Role manager

A read-only view of the shipped console roles and their composed permissions.

## Webhook manager

Subscribe and unsubscribe webhooks; the signing secret is shown once on creation.

---

[← Docs index](../README.md#documentation)
