# Installation & Production Checklist

## Install

1. Download `rar-woo-smart-courier-v1.3.0.zip` from the repository's `releases/` directory or README installable-download link.
2. WordPress Admin → Plugins → Add New → Upload Plugin.
3. Select the ZIP, install and activate **RAR Woo Smart Courier**.
4. Open WooCommerce → Smart Courier.

## Safe rollout

Keep **Safe Test Mode ON** during verification so normal customers continue using the existing WooCommerce shipping setup.

Test at minimum:

- Dhaka address
- Nearby district/address
- Outside Dhaka address
- ≤1 kg, ≤2 kg and >2 kg carts
- quantity changes
- each enabled courier
- recommendation ordering
- native WooCommerce Free Shipping threshold
- COD test order
- selected courier saved in the order/admin view
- desktop and mobile Cart/Checkout labels

After all checks pass, turn **Safe Test Mode OFF** and save settings.

## Existing shipping methods

The plugin derives its courier choices from an existing WooCommerce shipping rate. If it cannot determine a usable base rate, it leaves the original WooCommerce rates unchanged instead of forcing courier options.

## Duplicate-engine warning

Do not run an old WPCode/custom Smart Courier engine at the same time. Keep any older prototype OFF while testing this plugin, then remove the obsolete duplicate only after production verification.

## Free Shipping

Native WooCommerce Free Shipping remains the trigger. When it becomes available, enabled courier choices remain visible at zero shipping cost and their normal configured amounts are shown as strike-through reference.
