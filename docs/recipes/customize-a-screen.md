# Customize a screen

Override any view without forking the package.

## Steps

Publish the views and edit the copy in your app:

```bash
php artisan vendor:publish --tag=laranail::db-console-webui-views
```

Edited views live in `resources/views/vendor/laranail-db-console-webui/` and take precedence, whichever namespace form (`laranail/db-console-webui::` or `laranail-db-console-webui::`) the view is rendered through. Keep the component contract intact — the Livewire component still calls the core service; you are only restyling. Do not add data-fetching or validation to the view; that belongs to the core.

---

[← Docs index](../../README.md#documentation)
