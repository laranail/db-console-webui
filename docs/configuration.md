# Configuration

Every `laranail.db-console-webui.*` key. Publish with `php artisan laranail::db-console-webui.install` (the bare `db-console-webui:install` is a deprecated alias).

| Key | Default | Purpose |
|---|---|---|
| `enabled` | `true` | Opt-in switch; when false no routes are registered and the package is inert. |
| `path` | `db-console` | The path the UI mounts at. |
| `middleware` | `["web"]` | The middleware stack; `EnsureCanManage` is always added on top. |
| `allowed_ips` | `[]` | When non-empty, only these client IPs may reach the UI. |
| `flux.pro` | `false` | Whether Flux Pro components may be used (free set otherwise). |

---

[← Docs index](../README.md#documentation)
