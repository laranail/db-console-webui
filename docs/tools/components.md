# Components

The Livewire components and the core service each one calls.

| Component | Livewire name | Calls |
|---|---|---|
| `ServerSwitcher` | `db-console-webui.server-switcher` | `ServerRegistry` |
| `Dashboard` | `db-console-webui.dashboard` | `DatabaseManager::list` |
| `DatabaseWizard` | `db-console-webui.database-wizard` | `DatabaseManager::create` / `drop` + `RuleProvider` |
| `AccountManager` | `db-console-webui.account-manager` | `AccountManager::create` / `list` + `RuleProvider` |
| `RoleManager` | `db-console-webui.role-manager` | `RbacDriver` (read-only) |
| `WebhookManager` | `db-console-webui.webhook-manager` | `Webhooks\WebhookManager` + `RuleProvider` |

Each component is thin: it resolves the active server, calls the service, and renders. Validation and authorization belong to the core.

---

[← Docs index](../../README.md#documentation)
