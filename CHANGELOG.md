# Changelog

## 5.1.0 — Bug-fix and hardening release
**Security / privacy**
- Debug log now redacts Paperfly username, password and `paperflykey` (previously only Pathao / RedX / SteadFast secrets were masked).

**Bug fixes**
- Settings: a partial save (a form that does not post every field) no longer resets the Pathao environment or any courier tracking-URL template to its default.
- Admin notices ("no connected courier", "secrets unreadable") were printed on every wp-admin screen; they now appear only on Courier Suite pages and the WooCommerce orders screens.
- Orders list: the "Send with Pathao" / "Refresh Pathao status" bulk actions are shown only when Pathao credentials are configured.
- Sundarban: setting a status to the same value now still records the sync timestamp.
- Customer plain-text e-mails no longer print a blank line when a courier has no tracking URL.
- Courier detection (Smart Courier routing): more Paperfly / Sundarban spellings, including Bangla variants.

**Optimisation**
- Dashboard / Tools statistics cache extended from 5 to 15 minutes (still cleared immediately on every send and status change), avoiding up to 2,500 order loads per refresh on busy shops.
- Uninstall (when "Delete data on uninstall" is on) also removes plugin transients and leftover lock options. Order meta is still never deleted.

**Docs**
- README install step referenced a v4.1.0 file name; corrected. `readme.txt` changelog brought up to date.

## 5.0.0 — Design, organisation and own branding
- **Rebrand:** RAR Courier Suite is presented as its own product for Pathao, RedX, SteadFast, Paperfly and Sundarban Courier. Plugin header no longer says "derived from"; Pathao's logo removed and replaced by an own logo; non-affiliation disclaimer; `NOTICE.txt` keeps GPL credits. Text domain is now `rar-courier-suite`.
- **New Dashboard** (all five couriers): setup checklist, KPIs, pipeline bar, courier cards with connection state, needs-attention list, recent parcels, performance table (success rate, open COD).
- **New Parcels page** (replaces Pathao-only list): status views x courier filter, search by order/name/phone/any consignment, bulk send/refresh for any courier, CSV export with courier column.
- **Settings redesigned:** tabs (General, Pathao, RedX, SteadFast, Paperfly, Sundarban), switches, copy buttons for webhook URLs, sticky Save bar, remembers the tab, mobile friendly. One Save covers all tabs.
- **One "Courier" column / meta box** on WooCommerce orders (was two columns + two boxes); order timeline for every courier.
- **Tools & Health** grouped per courier with status dots; notice now checks all couriers.
- Summary statistics now include Pathao (cached 5 min).
- Tests: 285 assertions pass with HPOS on and off.
- Upgrade: install over v4.1.0 (deactivate first, settings and parcel data are reused). Old Parcels URL (`page=pt_hms_orders`) still works.

## 4.1.0
- **New: Paperfly** — send, bulk, status sync, credentials test. Built from community SDKs (Paperfly's API docs are not public): Basic auth + `paperflykey`, create path configurable (`OrderPlacement` / `NewOrderUpload`), pickup profile in Settings, thana = City field, district = state. Tracking response shape unverified.
- **New: Sundarban Courier (manual)** — no verifiable merchant API exists, so staff book in the Sundarban portal/app and record the CN number on the order; status is set from a dropdown (corrections allowed). Keeps one-courier-per-order, duplicate-CN protection, COD tracking, customer tracking link. Never auto-sent, never polled. Hook `rpc_sundarban_status` is ready if API access is granted.
- Smart Courier detection for Paperfly / Sundarban (English + Bangla names).

## 4.0.0 — RAR Courier Suite
Pathao v3 engine + RedX + SteadFast in one HPOS-ready plugin.
- **New: RedX module** (from "Redx for WooCommerce 1.0.1"): order-list button, bulk send/refresh, 30-min status sync, COD via the shared tested rules, normalised phone/address, weight in grams, optional area-based mode, encrypted token.
- **New: SteadFast module** (from "SteadFast API 1.0.0"): send, bulk, status sync, balance/connection test, Bearer-token webhook (`/wp-json/ptc/v1/steadfast`; rejects everything until a token is set), `steadfast_amount` override honoured.
- **Cross-courier safety:** one named lock per order for all couriers; timed-out sends block every courier for 10 min; an order already sent to one courier cannot be sent to another (setting, default on).
- **RAR Woo Smart Courier routing:** auto-send follows the customer's choice (Pathao / RedX / SteadFast); unsupported couriers are left for staff; otherwise "Default courier" setting.
- Combined customer tracking block (My Account + e-mails) listing every parcel.
- Tools & Health: per-courier 30-day summary, webhook health, one-click import of parcels created by the old RedX / SteadFast plugins.
- Fixes: `wc_get_orders()` ignores `meta_query` with HPOS off → all order queries go through `RPC_Orders::query()`; partial settings save no longer resets delivery/item type.
- Includes everything in RAR Pathao Courier 3.0.0 (see git history / v3 notes).
