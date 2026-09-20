# RAR Woo Smart Courier

**Bangladesh-focused smart multi-courier shipping selector for WooCommerce** — zone, weight, ETA, price, Free Shipping এবং configurable recommendation logic এক জায়গা থেকে manage করার জন্য।

**Current stable release: v1.3.0**

Built around the Nabiad.com WooCommerce workflow, but reusable on other WooCommerce stores after merchant-specific rates and zones are configured.

## Latest Download

**[Download RAR Woo Smart Courier v1.3.0](https://github.com/ruhulaminrevens/RAR-Woo-Smart-Courier/raw/main/releases/rar-woo-smart-courier-v1.3.0.zip)**

## Default customer display

```text
Pathao · 1–2 business days: 70.00৳ · Rec
Paperfly · 1–2 business days: 70.00৳
Steadfast · 1–3 business days: 70.00৳
Redx · 1–3 business days: 80.00৳
Sundarban · 1–3 business days: 110.00৳
```

Courier name-এর পরে unnecessary “Courier/Delivery” text রাখা হয় না, তাই desktop ও mobile দুই জায়গাতেই list compact থাকে।

## Features

- Dhaka / Nearby / Outside Dhaka zone-based pricing
- Weight-aware ≤1kg, ≤2kg and extra/kg rates
- Configurable ETA per courier and zone
- Smart recommendation: faster ETA → lower rate → Priority tie-break
- Compact `· Rec` marker at the end of the recommended line
- Native WooCommerce Free Shipping compatibility
- Free Shipping active হলে সব courier selectable at ৳0 এবং normal charge strike-through reference হিসেবে visible
- Repeated “Free Delivery” text removed
- Safe Test Mode for admin/store-manager testing
- Selected courier saved with WooCommerce order/shipping data
- Courier visible in WooCommerce admin order workflow
- Mobile-compact courier list
- Sixth blank Custom Courier slot with editable name, pricing, ETA and priority
- Configurable Nearby districts and cities/areas

## Recommendation workflow

1. Customer shipping address থেকে zone determine হয়.
2. Package weight থেকে courier charge calculate হয়.
3. Enabled courierগুলোর ETA compare হয়.
4. Faster ETA first.
5. ETA tie হলে lower rate first.
6. ETA + rate tie হলে lower Priority number wins.
7. First ranked option-এ `· Rec` marker দেখায়.

Priority সবসময় courier force করে না; এটি tie-breaker হিসেবে কাজ করে।

## Free Shipping behavior

WooCommerce native Free Shipping available হলে Smart Courier courier choice remove করে না. Actual shipping charge becomes ৳0, while each courier normal configured amount is shown strike-through. “Free Delivery” প্রতিটি line-এ repeat হয় না।

## Admin Settings

**WordPress Admin → WooCommerce → Smart Courier**

Configure: Engine, Safe Test Mode, fallback weight, Nearby districts/areas, courier enable/disable, Priority, Dhaka/Nearby/Outside rates, ETA এবং Custom Courier.

## Installation / Deployment

1. Download the latest ZIP above.
2. WordPress → Plugins → Add New → Upload Plugin.
3. Upload `rar-woo-smart-courier-v1.3.0.zip` and activate.
4. WooCommerce → Smart Courier.
5. Keep **Safe Test Mode ON** first.
6. Test Cart, Checkout, address change, quantity/weight change, paid shipping, Free Shipping and COD order creation.
7. Confirm the selected courier is saved in the order/admin view.
8. Then turn **Safe Test Mode OFF** and Save Courier Settings.

### Old WPCode snippet

If **`Nabiad Smart Courier Engine v1.0.0`** still exists in WPCode, keep it **OFF** while this plugin is active. After production verification it can be deleted to avoid accidental double-engine conflicts.

## Screenshots

### Smart Courier Settings
![Smart Courier Settings](docs/screenshots/smart-courier-settings.jpg)

### Cart Courier Selector
![Cart Courier Selector](docs/screenshots/cart-courier-selector.jpg)

### WooCommerce Admin Order Courier
![Admin Order Courier](docs/screenshots/admin-order-courier.jpg)

## Version Update History

| Version | Update |
|---|---|
| **v1.3.0** | Final line polish, `· Rec` at end, `Redx` naming, realistic ETA defaults, Custom Courier slot, configurable Nearby locations, mobile polish and Free Shipping strike-through behavior. |
| **v1.2.0** | Settings/admin workflow stabilization with configurable pricing, ETA, priority and improved recommendation/free-shipping presentation. |
| **v1.1.2** | Free Shipping compatibility/fix. |
| **v1.1.1** | Safe Test Mode for admin-only verification. |
| **v1.1.0** | First installable Smart Courier plugin baseline. |
| **v1.0.0** | Earlier Nabiad Smart Courier Engine WPCode prototype. |

See **[CHANGELOG.md](CHANGELOG.md)** for release notes.

## Technical Notes

- Requires WordPress + WooCommerce.
- Uses WooCommerce shipping hooks; it does not pull live courier tariffs from courier APIs.
- Merchant-specific rates and ETA must be validated before production deployment.

## License

GPL-2.0-or-later
