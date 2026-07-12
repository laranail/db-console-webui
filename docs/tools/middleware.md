# Middleware

`EnsureCanManage` is the single gate for the web UI.

It enforces, in order:

1. the caller is authenticated (else 403);
2. the caller's IP is allow-listed when `allowed_ips` is non-empty (else 403);
3. the caller holds at least one DBConsole ability (`db-console.database.view` or `db-console.server.view`), so a signed-in but unauthorized user cannot reach the UI.

This is a coarse entry gate only. Every action the UI performs is still authorized inside the core service it calls, through the same Gate as the CLI and REST API — the middleware never re-implements a permission check.

---

[← Docs index](../../README.md#documentation)
