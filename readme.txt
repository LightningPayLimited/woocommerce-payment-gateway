=== Stacked Bitcoin Payment Gateway ===
Contributors: stacked
Tags: woocommerce, bitcoin, lightning network, payments, cryptocurrency
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept Bitcoin Lightning Network payments in your WooCommerce store via Stacked.

== Description ==

WooCommerce payment gateway for accepting Bitcoin Lightning Network payments via [Stacked](https://app.stackedbitcoin.com?landing=githubwoocommerce).

= How It Works =

1. Customer selects "Pay with Bitcoin" at checkout.
2. The plugin creates a payment link via the Stacked API.
3. Customer is redirected to Stacked to complete the payment.
4. After paying, the customer returns to the order confirmation page.
5. The plugin polls Stacked to confirm payment and marks the order as complete.

= Admin Features =

* **Order details panel**: View the Stacked reference and payment link for each order.
* **Manual status check**: Click "Check Payment Status" on pending orders to re-query the Stacked API.

== Installation ==

1. Upload the plugin folder to `wp-content/plugins/`.
2. Activate the plugin in **Plugins > Installed Plugins**.
3. Go to **WooCommerce > Settings > Payments** and enable **Stacked**.
4. Enter your API key (found in your Stacked merchant profile).

== Configuration ==

Navigate to **WooCommerce > Settings > Payments > Stacked** to configure:

* **Title** - Payment method name shown at checkout (default: "Stacked")
* **Description** - Text shown to customers at checkout
* **API Key** - Your Stacked API key
* **API Base URL** - Stacked API endpoint (default: `https://app.stackedbitcoin.com`)
* **Debug Logging** - Enable to log API interactions to WooCommerce logs

== Frequently Asked Questions ==

= What currencies are supported? =

Currently the store currency must be set to NZD.

= Where do I get an API key? =

Register for a Stacked merchant account at [Stacked](https://app.stackedbitcoin.com/auth/register?landing=githubwoocommerce).

== Changelog ==

= 1.2.0 =
* Add Stacked logo to checkout, admin settings, and order details.

= 1.1.0 =
* Add block-based checkout support.
* Add admin notice when store currency is not set to NZD.

= 1.0.2 =
* Fix typos in admin settings descriptions.
* Fix duplicate CSS property in admin order panel.
* Add order validation in payment processing.
* Declare WooCommerce HPOS compatibility.

= 1.0.1 =
* Fix nonce verification and input sanitization for WordPress plugin review compliance.

= 1.0.0 =
* Initial release.
