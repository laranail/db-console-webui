# Installation

Install the web UI, mount it, wire the assets, and guard access.

## Install

```bash
composer require laranail/db-console-webui
php artisan laranail::db-console-webui.install
```

This publishes the config, views, and language files. It depends on `laranail/db-console` — install and configure the core first (register your servers, run its install).

## Assets

Build the entrypoints into your app's Vite pipeline:

```js
// vite.config.js
laravel({ input: ["resources/css/db-console.css", "resources/js/db-console.js", ...] })
```

Or publish and adapt them. The UI uses Flux, which ships its own styles.

## Access

The UI mounts at `/db-console` (see [Configuration](configuration.md)) behind `EnsureCanManage`: the caller must be authenticated, hold a DBConsole permission, and pass the IP allow-list. All finer authorization happens in the core.

## Names

Every name the package registers into a shared Laravel registry carries the vendor and the package slug, so it cannot silently replace, or be replaced by, a sibling package's name.

| Registry | Name | Deprecated alias |
|---|---|---|
| Artisan command | `laranail::db-console-webui.install` | `db-console-webui:install` |
| Route names | `laranail-db-console-webui.dashboard`, `.databases`, `.accounts`, `.roles`, `.webhooks` | `db-console-webui.<page>` |
| Livewire components | `laranail-db-console-webui.<name>` (see [Components](tools/components.md)) | `db-console-webui.<name>` |
| View namespace | `laranail/db-console-webui::` | `laranail-db-console-webui::` (kept as an alias, not deprecated) |
| Translation namespace | `laranail/db-console-webui::` | `laranail-db-console-webui::` (kept as an alias, not deprecated) |
| Browser event | `laranail-db-console-webui:server-changed` | `db-console:server-changed` |

The package registers no rate limiter and no middleware alias; `EnsureCanManage` is applied by class.

The deprecated aliases keep working until the next minor after 0.1 at the earliest:

- `php artisan db-console-webui:install` prints a deprecation line and runs `laranail::db-console-webui.install`.
- `route('db-console-webui.dashboard')` generates the same URL as `route('laranail-db-console-webui.dashboard')` and logs a one-time warning per name. It works through package-tools' `BareRouteNameAliases`, which hooks `URL::resolveMissingNamedRoutesUsing()`. Laravel consults that hook only for a name it does not hold, so an application route of the same name always wins, and any resolver another package installed earlier is still consulted for names this one does not own.
- `@livewire('db-console-webui.dashboard')` renders the same component and raises one `E_USER_DEPRECATED` per name.
- `db-console:server-changed` is still dispatched beside `laranail-db-console-webui:server-changed`.
- `Route::has()` and `request()->routeIs()` read the route collection directly and never reach that fallback. Ask them for the scoped names.

---

[← Docs index](../README.md#documentation)
