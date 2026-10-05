# Components

The Livewire components and the core service each one calls.

| Component | Livewire name | Calls |
|---|---|---|
| `ServerSwitcher` | `laranail-db-console-webui.server-switcher` | `ServerRegistry` |
| `Dashboard` | `laranail-db-console-webui.dashboard` | `DatabaseManager::list` |
| `DatabaseWizard` | `laranail-db-console-webui.database-wizard` | `DatabaseManager::create` / `drop` + `RuleProvider` |
| `AccountManager` | `laranail-db-console-webui.account-manager` | `AccountManager::create` / `list` + `RuleProvider` |
| `RoleManager` | `laranail-db-console-webui.role-manager` | `RbacDriver` (read-only) |
| `WebhookManager` | `laranail-db-console-webui.webhook-manager` | `Webhooks\WebhookManager` + `RuleProvider` |

Each component is thin: it resolves the active server, calls the service, and renders. Validation and authorization belong to the core.

```blade
<livewire:laranail-db-console-webui.server-switcher />
@livewire('laranail-db-console-webui.dashboard')
```

## Deprecated component names

The bare `db-console-webui.<name>` names are still registered as deprecated aliases of the same classes, so an existing `@livewire('db-console-webui.dashboard')` keeps rendering. Mounting a component under a bare name raises one `E_USER_DEPRECATED` per name per process, naming the replacement; Laravel writes it to the `deprecations` log channel when one is configured. The aliases are removed no earlier than the next minor after 0.1.

The scoped names are registered first, so Livewire maps each class back to its scoped name: the full-page routes and component snapshots never use the bare one.

## Browser events

| Event | Dispatched by | Payload |
|---|---|---|
| `laranail-db-console-webui:server-changed` | `ServerSwitcher::select()` | `server` |
| `db-console:server-changed` | the same call, deprecated | `server` |

Listen for the scoped name:

```blade
<div x-on:laranail-db-console-webui:server-changed.window="refresh($event.detail.server)"></div>
```

The bare `db-console:server-changed` is dispatched beside the scoped event during the deprecation window, so existing listeners keep firing. It stops no earlier than the next minor after 0.1. The package's own views and `resources/js/db-console.js` listen for neither.

---

[← Docs index](../../README.md#documentation)
