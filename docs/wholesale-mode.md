# Wholesale mode

Set the master switch in `.env`:

```dotenv
WHOLESALE=false
```

The default is `true`, which preserves the existing wholesale functionality. Laravel boolean values `true`, `false`, `1`, and `0` are supported. Only `config/features.php` reads the environment variable; application code uses `App\Support\Features::wholesaleEnabled()`.

After changing the flag, refresh the running configuration:

```sh
php artisan optimize:clear
php artisan config:cache
```

With the switch disabled, wholesale POS and report endpoints return 404. Wholesale fields, prices, settings, permissions, navigation, charts, and export columns disappear. Product and purchase submissions work without wholesale prices and preserve existing stored prices. Imports accept both the retail template and older files with a wholesale column; the disabled column is ignored.

Lorry order creation and selling require wholesale pricing and are disabled. Lorry stock, loading, returns, transfers, trips, expenses, reconciliation, and administration remain available.

No database migration is required. Existing prices, invoices, holds, returns, accounts, journals, and permission grants remain stored. Historical financial totals retain their receipts under neutral labels. New daily closings use zero for the inactive channel; saving an existing closing preserves its stored channel amount. Historical wholesale sales remain readable and printable, while their editing, payments, returns, and hold resumption are unavailable until the feature is enabled again.

Database settings cannot override the disabled master switch. Stored wholesale login/POS defaults fall back to retail without rewriting their saved values. Wholesale settings and permissions return when `WHOLESALE=true` and configuration is refreshed.

`tests/Feature/WholesaleFeatureFlagTest.php` verifies both modes, cached configuration, route and crafted-request protection, product and purchase price preservation, imports/exports, settings, permissions, seeders, login fallbacks, lorry workspaces, and historical accounting receipts.
