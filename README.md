# RAR Courier Suite

**One WooCommerce plugin for Bangladesh couriers — Pathao, RedX, SteadFast, Paperfly and Sundarban.**
HPOS-ready, plain PHP (works on Hostinger shared hosting — no Node, no CDNs), built for nabiad.com and designed to work with *RAR Woo Smart Courier*.

[![Version](https://img.shields.io/badge/version-5.1.0-4f46e5)](CHANGELOG.md) ![WordPress](https://img.shields.io/badge/WordPress-6.2%2B-blue) ![WooCommerce](https://img.shields.io/badge/WooCommerce-7.9%2B-96588a) ![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb3) ![License](https://img.shields.io/badge/license-GPLv3-green)

## Download

### [⬇️ Download Installable ZIP — v5.1.0](https://github.com/ruhulaminrevens/RAR-Courier-Suite/raw/main/releases/rar-courier-suite-v5.1.0.zip)

WordPress → Plugins → Add New → Upload Plugin → choose the ZIP → Install Now → Activate.
(Use this ZIP, not GitHub's "Download source code", so the plugin folder is named `rar-courier-suite`.)

> **Note:** this repository (formerly `RAR-Woo-Smart-Courier`) previously held the *RAR Woo Smart Courier* checkout shipping selector (v1.3.0). That code is preserved on the [`v1.3.0` branch](https://github.com/ruhulaminrevens/RAR-Courier-Suite/tree/v1.3.0). Courier Suite reads the customer's courier choice from Smart Courier (`_rwsc_selected_courier`) when both are installed.

## Features

- **Dashboard** for all five couriers: setup checklist, KPIs, pipeline bar, per-courier cards with connection state, needs-attention list, recent parcels, performance table (success rate, open COD).
- **Parcels page**: status views × courier filter, search by order / name / phone / any consignment, bulk send / refresh, CSV export.
- **Safe dispatch**: one named lock per order across *all* couriers, duplicate-send protection, timed-out sends block every courier for 10 minutes, "one courier per order" guard.
- **Status sync**: hardened webhooks (Pathao, SteadFast) + 30-minute safety-net polling; idempotent, ordered, a final parcel is never reopened.
- **COD / advance-payment aware** (RAR Woo Advance Payment): collects only the balance due.
- **RAR Woo Smart Courier routing**: auto-send follows the customer's checkout choice.
- **Settings** in tabs with switches, copy buttons for webhook URLs, encrypted secrets (AES-256-CBC, HMAC-checked).
- One "Courier" column / meta box on WooCommerce orders (HPOS and legacy), order timeline, customer tracking block in My Account and e-mails.
- Debug log to WooCommerce → Status → Logs with secrets redacted and phone numbers masked.

## Install / upgrade
1. **Back up** files + DB.
2. Deactivate (do not delete yet): *RAR Pathao Courier v3*, original *Pathao Courier*, *Redx for WooCommerce*, *SteadFast API*. The suite refuses to run beside them (duplicate buttons / webhook routes).
3. Upload `rar-courier-suite-v5.1.0.zip` → Activate. Pathao settings and parcel data are reused automatically.
4. **Courier Suite → Settings**
   - Pathao: unchanged (credentials already stored).
   - RedX: tick *Enable*, paste API token, *Test RedX connection*.
   - SteadFast: tick *Enable*, API key + Secret key, *Test SteadFast connection* (shows balance). Set a long random **Webhook Bearer token**, enter the same in the SteadFast portal with webhook URL `https://nabiad.com/wp-json/ptc/v1/steadfast`.
   - Paperfly: Enable, username/password/key (+ merchant code if given), pickup details, then *Test Paperfly credentials*.
   - Sundarban: just Enable. On an order click *Sundarban*, type the CN from the Sundarban portal/app; change status from the dropdown.
   - Routing: choose *Default courier for auto-send*; leave *One courier per order* on.
5. **Tools & Health → Import RedX / SteadFast parcels** to bring in parcels created by the old plugins (so they join status sync).
6. Test on one real order per courier (see Verification) before bulk use. Delete the old plugins after a clean week.

Rollback: deactivate the suite, reactivate the old plugins. Order data is never deleted on uninstall.

## Where things are
- Orders list → columns *Pathao* and *RedX / SteadFast*; bulk actions "Send with …" / "Refresh … status" (max 25 per RedX/SteadFast bulk run).
- Order edit screen → meta boxes for Pathao and RedX / SteadFast.
- Courier Suite menu → Pathao dashboard, Settings, Tools & Health (suite summary for RedX / SteadFast).

## Verification status (read before go-live)
| Item | Status |
|---|---|
| Pathao v3 engine | tested against a mock of the Pathao API docs; live untested |
| RedX create parcel (`POST /v1.0.0-beta/wordpress/parcel`, `API-ACCESS-TOKEN: Bearer`) | taken from RedX plugin 1.0.1; live untested |
| RedX status sync (`/parcel/track/{id}`, `/parcel/info/{id}`), sandbox host, `/areas`, area-based `/parcel` | **unverified** (redx.com.bd/developer-api was unreachable); override via *API base URL* / filter `rpc_redx_request` if different. Status words are mapped by keyword. |
| SteadFast create / status_by_cid / get_balance / webhook | from SteadFast plugin 1.0.0 + package docs; live untested. Webhook payload fields: `consignment_id, invoice, status, updated_at`. |
| Paperfly create/track (Basic auth + `paperflykey`; `OrderPlacement` or `NewOrderUpload`; `API-Order-Tracking`) | from two community SDKs; **docs not public → unverified**, the two SDKs disagree on the create path (setting). Tracking-response status is searched in the usual keys. Test one parcel in sandbox first. |
| Paperfly requirements | pickup name/address/thana/district/phone (Settings) + customer thana (order City field) + district (state) are mandatory per parcel |
| Sundarban | **manual module** — no verifiable merchant API. Staff enter the CN number; status set by hand. Nothing is sent to Sundarban from the site. |
| Tracking URLs (all couriers) | unverified — editable in Settings (Paperfly default is its home page) |
| HPOS on/off | tested (241 assertions each) on real WordPress + WooCommerce |

## Developer notes
Filters kept/added: `pathao_modal_order_data`, `pathao_order_payload`, `ptc_amount_to_collect`, `rpc_redx_fields`, `rpc_steadfast_fields`, `rpc_redx_request`, `rpc_operator_cap`. Actions: `pathao_before_send_order`, `pathao_after_send_order`, `pathao_send_order_failed`, `rpc_courier_sent`, `rpc_courier_status_changed`, `rpc_status_changed`.
Meta: Pathao `ptc_*` (unchanged); RedX `rdx_*` (+`_redx_tracking_id` mirror); SteadFast `sfc_*` (+`steadfast_consignment_id`, `steadfast_is_sent` mirrors).

## Requirements
WordPress 6.2+, WooCommerce 7.9+ (tested up to 10.0), PHP 7.4+.

## Repository structure
```text
RAR-Courier-Suite/
├── rar-courier-suite.php      # plugin bootstrap
├── includes/                  # one class per concern (couriers, API, orders, admin, webhook, cron…)
├── assets/                    # admin CSS / JS / logo (no build step)
├── docs/AUDIT-AND-COMPAT.md   # audit notes of the original plugins and RAR compatibility
├── tests/logic-test.php       # stand-alone logic tests: `php tests/logic-test.php`
├── releases/                  # installable ZIPs
├── README.md · CHANGELOG.md · readme.txt · NOTICE.txt · LICENSE · uninstall.php
```

## Tests
`php tests/logic-test.php` runs 24 stand-alone assertions (phone normalisation, status mapping, log redaction, secret encryption round-trip, district normalisation). Integration behaviour (HPOS on/off, mocked courier APIs, AJAX capability checks, webhooks) was exercised on a real WordPress + WooCommerce install for v5.0.0; v5.1.0 changes are limited to the items in [CHANGELOG.md](CHANGELOG.md) and were linted (`php -l`, `node --check`) but not re-run against live WordPress — test one real order per courier after upgrading.

## Credits & license
GPL-3.0-or-later (see `LICENSE`). Third-party names are trademarks of their owners; this plugin is not affiliated with or endorsed by any courier company. See `NOTICE.txt` for GPL reference credits.
