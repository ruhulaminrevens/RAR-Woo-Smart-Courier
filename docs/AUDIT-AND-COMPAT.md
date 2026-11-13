# Audit notes & compatibility

## Original plugins: issues addressed
**Pathao 2.1.2** — direct postmeta (breaks HPOS); no duplicate-send guard; plain-text secrets; weak webhook handling; location endpoints open to any logged-in user; every parcel polled.
**Redx for WooCommerce 1.0.1** — `get_post_meta`/`update_post_meta` (HPOS-unsafe); no capability check on the AJAX action (nonce only); no lock/duplicate guard (double click = two parcels); COD = order total even for prepaid; billing-only address/phone, no normalisation; weight read from a custom meta (`_parcel_weight`) that nothing in the plugin sets; no status sync despite the readme; bulk action loops with no result detail.
**SteadFast API 1.0.0** — phone "fixed" by taking the last 10 digits; address from billing only; per-order invoice built from the date + id (not the order number); API errors collapsed to the string "unauthorized"; `$request['status']` read without checking the response (PHP warnings on transport errors); AJAX actions (send, amount edit) verify a nonce only, no capability check; credentials in plain options; no webhook; no lock/duplicate guard.

## RAR compatibility
| Plugin | Integration |
|---|---|
| RAR Woo Smart Courier | `_rwsc_selected_courier` decides which courier auto-send uses; button for the chosen courier is highlighted |
| RAR Advance Payment | `RAR_WAP_Order::collectable_amount` is the COD amount for all three couriers |
| RAR Order Workflow | Pathao writes `_pathao_consignment_id` / `_pathao_tracking_id` (RedX/SteadFast ids are in `rdx_id` / `sfc_id`; extend the workflow plugin if it needs them) |

## Testing performed
Real WordPress + WooCommerce on SQLite, HPOS on and off, mocked Pathao/RedX/SteadFast APIs: settings & encryption, normalisers, COD, resolver, send, duplicate/cross-courier/uncertain blocks, webhook auth/idempotency/out-of-order/final-never-reopened, Smart Courier routing, cron sync, legacy import, admin pages, bulk actions, AJAX capability checks, frontend tracking. 241 assertions per mode, 0 failures. PHP lint clean; JS `node --check` clean.
Test harness note: an earlier harness stopped at the first AJAX call (WordPress `die`d outside AJAX mode), so v3's AJAX/capability assertions had not actually run; fixed and now passing.
