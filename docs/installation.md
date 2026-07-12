# Installation

Install the web UI, mount it, wire the assets, and guard access.

## Install

```bash
composer require laranail/db-console-webui
php artisan db-console-webui:install
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

---

[← Docs index](../README.md#documentation)
