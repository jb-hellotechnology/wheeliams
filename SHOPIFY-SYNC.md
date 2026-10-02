# Shopify product / dashboard-name sync

`products.php` refreshes the local `shopify_products` cache (product + variant
ids, titles, **dashboard names**, show-on-dashboard flag) from Shopify via the
GraphQL Admin API. The public **Dashboard** (`index.php`) reads product/dashboard
names from that cache, so names only update when this sync runs.

It runs over HTTP (open the URL) **or** from cron/CLI (it falls back to its own
directory for `DOCUMENT_ROOT`). Each run `TRUNCATE`s and repopulates the table,
so schedule it at a low-traffic time.

## Staging (this Mac, MAMP)

Installed in the user crontab (nightly 02:30). It only fires when the Mac is
awake and MAMP is running, so it's best-effort on a dev box:

```
30 2 * * * /Applications/MAMP/bin/php/php8.5.2/bin/php /Users/jackbarber/Sites/wheeliams/products.php >/tmp/wheeliams-products-sync.log 2>&1
```

Run it by hand any time with:

```
/Applications/MAMP/bin/php/php8.5.2/bin/php products.php
```

## Live host

Add a cron job (control panel "Cron Jobs", or `crontab -e`). Replace the PHP
binary and site path with the host's:

```
30 2 * * * /usr/bin/php /home/<user>/public_html/products.php >/home/<user>/logs/products-sync.log 2>&1
```

Daily is usually fine; use `0 */6 * * *` for every 6 hours if names change often.

## Notes
- Needs `WHEELIAMS_SHOPIFY_CLIENT_ID` / `_CLIENT_SECRET` in `secrets.php` and the
  app's `read_products` scope (already granted).
- A product with no `dashboard_name` metafield falls back to its Shopify title.
