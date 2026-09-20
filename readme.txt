=== RAR Woo Smart Courier ===
Contributors: ruhulaminrevens
Tags: woocommerce, shipping, courier, delivery, bangladesh
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Smart multi-courier selector for WooCommerce with Bangladesh-focused zone/weight pricing, ETA-aware recommendation, native Free Shipping support and order courier metadata.

== Description ==

RAR Woo Smart Courier adds configurable courier choices to WooCommerce shipping.

Features:
* Dhaka / Nearby / Outside zone pricing.
* Configurable Nearby districts and cities/areas.
* Weight-aware 1 kg / 2 kg / extra kg charges.
* Per-zone courier ETA.
* Smart recommendation by ETA, price and priority.
* Five built-in couriers plus one optional custom courier.
* Native WooCommerce Free Shipping support.
* Safe Test Mode.
* Selected courier/ETA stored with order shipping data.
* Compact mobile labels.

The plugin does not fetch live courier API tariffs. Configure and verify merchant-specific rates before production use.

== Installation ==

1. Upload the installable ZIP from WordPress > Plugins > Add New > Upload Plugin.
2. Activate RAR Woo Smart Courier.
3. Go to WooCommerce > Smart Courier.
4. Keep Safe Test Mode ON while testing.
5. Configure delivery rates, ETA and Nearby locations.
6. Test normal shipping and native WooCommerce Free Shipping.
7. Turn Safe Test Mode OFF after validation.

== Frequently Asked Questions ==

= Does it call courier company APIs? =
No. Version 1.3.0 uses merchant-configured rates and ETA values.

= What happens when WooCommerce Free Shipping applies? =
Enabled courier choices remain selectable at zero shipping cost and the normal configured amount is shown as strike-through reference.

= Can I add another courier? =
Yes. A sixth custom courier row is available and disabled by default.

== Changelog ==

= 1.3.0 =
* Configurable Nearby locations and custom courier.
* ETA/rate/priority recommendation.
* Native Free Shipping presentation improvements.
* Compact mobile display and order courier metadata.
* Repository source structure normalized for maintainability.
