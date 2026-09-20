# RAR Woo Smart Courier

Production-focused multi-courier shipping selector for WooCommerce, designed for Bangladesh zone-based delivery pricing.

## Download

### [⬇️ Download Stable v1.3.0 Installable ZIP](https://github.com/ruhulaminrevens/RAR-Woo-Smart-Courier/raw/main/releases/rar-woo-smart-courier-v1.3.0.zip)

### [📦 Download Latest Source ZIP](https://github.com/ruhulaminrevens/RAR-Woo-Smart-Courier/archive/refs/heads/main.zip)

### [🔒 Download Stable v1.3.0 Source ZIP](https://github.com/ruhulaminrevens/RAR-Woo-Smart-Courier/archive/refs/heads/v1.3.0.zip)

**Install:** WordPress → Plugins → Add New → Upload Plugin → choose the **Installable ZIP** → Install Now → Activate.

## Features

- Five built-in courier choices: Pathao, Paperfly, Steadfast, Redx and Sundarban
- Sixth optional custom courier slot
- Dhaka / Nearby / Outside Dhaka zone pricing
- Configurable Nearby districts and cities/areas
- Weight-aware pricing for ≤1 kg, ≤2 kg and extra kg
- Separate ETA per courier and delivery zone
- Smart recommendation: faster ETA → lower rate → lower Priority number
- Native WooCommerce Free Shipping support
- Free-shipping orders keep all enabled courier choices selectable at ৳0 while the normal courier charge is shown strike-through
- Safe Test Mode for administrator/store-manager testing before public rollout
- Selected courier, ETA and zone stored with WooCommerce shipping/order data
- Courier display in the classic WooCommerce admin order view/list
- Compact Cart/Checkout labels for mobile
- Existing WooCommerce rates remain untouched if the plugin cannot determine a usable base rate

## Storefront format

Normal delivery example:

```text
Pathao · 1–2 business days: 70.00৳ · Rec
Paperfly · 1–2 business days: 70.00৳
Steadfast · 1–3 business days: 70.00৳
Redx · 1–3 business days: 80.00৳
Sundarban · 1–3 business days: 110.00৳
```

When native WooCommerce Free Shipping is available, the courier options remain selectable and the normal configured amount is displayed with strike-through instead of repeating “Free Delivery” on every row.

## Recommendation logic

1. Detect the delivery zone from the checkout address.
2. Calculate the cart/package weight.
3. Calculate rate and ETA for each enabled courier.
4. Prefer the faster ETA.
5. If ETA ties, prefer the lower normal rate.
6. If both tie, the lower Priority number wins.
7. The top choice receives the `· Rec` marker.

## Admin settings

Go to **WooCommerce → Smart Courier**.

You can configure Engine ON/OFF, Safe Test Mode, fallback parcel weight, Nearby districts, Nearby cities/areas, courier enable/disable, courier name, Priority, zone prices, ETA values and the optional custom courier.

## Safe production rollout

1. Install and activate the plugin.
2. Keep **Safe Test Mode ON**.
3. Test Cart/Checkout with Dhaka, Nearby and Outside addresses.
4. Test quantity and weight changes.
5. Test normal paid shipping.
6. Test native WooCommerce Free Shipping.
7. Place a COD test order and verify the selected courier in WooCommerce admin.
8. Turn **Safe Test Mode OFF** only after validation.

## Repository structure

```text
RAR-Woo-Smart-Courier/
├── rar-woo-smart-courier.php
├── includes/
│   └── class-rwsc-plugin.php
├── docs/
│   ├── INSTALLATION.md
│   └── screenshots/
├── releases/
│   └── rar-woo-smart-courier-v1.3.0.zip
├── README.md
├── CHANGELOG.md
├── readme.txt
├── LICENSE
├── uninstall.php
└── .gitignore
```

## Compatibility

- WordPress 6.5+
- WooCommerce 8.0+
- PHP 8.0+
- Uses WooCommerce classic shipping-rate and classic Cart/Checkout hooks
- Cart/Checkout Blocks compatibility is **not declared** in v1.3.0

## Upgrade note

If an older WPCode/custom Smart Courier engine still exists, keep that old engine **OFF** while this plugin is active. Remove the obsolete duplicate only after production verification so two shipping engines never filter the same WooCommerce rates.

## Screenshots

### Smart Courier Settings
![Smart Courier Settings](docs/screenshots/smart-courier-settings.jpg)

### Cart Courier Selector
![Cart Courier Selector](docs/screenshots/cart-courier-selector.jpg)

### WooCommerce Admin Order Courier
![Admin Order Courier](docs/screenshots/admin-order-courier.jpg)

## Version

**v1.3.0 — 2026-09-20**

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

GPL-2.0-or-later
