=== Lightning Pay Bitcoin Payment Gateway ===
Contributors: lightningpay
Tags: woocommerce, bitcoin, lightning network, payments, cryptocurrency
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept Bitcoin Lightning Network payments in your WooCommerce store via Lightning Pay.

== Description ==

WooCommerce payment gateway for accepting Bitcoin Lightning Network payments via [Lightning Pay](https://app.lightningpay.nz?landing=githubwoocommerce).

= How It Works =

1. Customer selects "Pay with Bitcoin" at checkout.
2. The plugin creates a payment link via the Lightning Pay API.
3. Customer is redirected to Lightning Pay to complete the payment.
4. After paying, the customer returns to the order confirmation page.
5. The plugin polls Lightning Pay to confirm payment and marks the order as complete.

= Admin Features =

* **Order details panel**: View the Lightning Pay reference and payment link for each order.
* **Manual status check**: Click "Check Payment Status" on pending orders to re-query the Lightning Pay API.

== Installation ==

1. Upload the plugin folder to `wp-content/plugins/`.
2. Activate the plugin in **Plugins > Installed Plugins**.
3. Go to **WooCommerce > Settings > Payments** and enable **Lightning Pay**.
4. Enter your API key (found in your Lightning Pay merchant profile).

== Configuration ==

Navigate to **WooCommerce > Settings > Payments > Lightning Pay** to configure:

* **Title** - Payment method name shown at checkout (default: "Lightning Pay")
* **Description** - Text shown to customers at checkout
* **API Key** - Your Lightning Pay API key
* **API Base URL** - Lightning Pay API endpoint (default: `https://app.lightningpay.nz`)
* **Debug Logging** - Enable to log API interactions to WooCommerce logs

== Frequently Asked Questions ==

= What currencies are supported? =

Currently the store currency must be set to NZD.

= Where do I get an API key? =

Register for a Lightning Pay merchant account at [Lightning Pay](https://app.lightningpay.nz/auth/register?landing=githubwoocommerce).

== Changelog ==

= 1.0.0 =
* Initial release.
