# Restrict access by IP

Limit the console UI to specific client IPs.

## Steps

Set the allow-list in `config/db-console-webui.php`:

```php
"allowed_ips" => ["203.0.113.10", "203.0.113.11"],
```

When non-empty, `EnsureCanManage` rejects any other client IP with a 403 before the page loads. An empty list means no IP restriction (authentication and the access gate still apply). See [Middleware](../tools/middleware.md).

---

[← Docs index](../../README.md#documentation)
