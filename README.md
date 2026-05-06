# Stacked WooCommerce Payment Gateway

WooCommerce payment gateway for accepting Bitcoin Lightning Network payments via [Stacked](https://app.stackedbitcoin.com?landing=githubwoocommerce).

## Requirements

- WordPress 5.0+
- WooCommerce 5.0+
- Store currency set to NZD
- A Stacked merchant account ([register here](https://app.stackedbitcoin.com/auth/register?landing=githubwoocommerce))

## Installation

1. Copy the `stacked` folder to `wp-content/plugins/`.
2. Activate the plugin in **Plugins > Installed Plugins**.
3. Go to **WooCommerce > Settings > Payments** and enable **Stacked**.
4. Enter your API key (found in your Stacked merchant profile).

## Configuration

Navigate to **WooCommerce > Settings > Payments > Stacked** to configure:

| Setting | Description |
|---------|-------------|
| **Title** | Payment method name shown at checkout (default: "Stacked") |
| **Description** | Text shown to customers at checkout |
| **API Key** | Your Stacked API key |
| **API Base URL** | Stacked API endpoint (default: `https://app.stackedbitcoin.com`) |
| **Debug Logging** | Enable to log API interactions to WooCommerce logs |

## How It Works

1. Customer selects "Pay with Bitcoin" at checkout.
2. The plugin creates a payment link via the Stacked API.
3. Customer is redirected to Stacked to complete the payment.
4. After paying, the customer returns to the order confirmation page.
5. The plugin polls Stacked to confirm payment and marks the order as complete.

## Admin Features

- **Order details panel**: View the Stacked reference and payment link for each order.
- **Manual status check**: Click "Check Payment Status" on pending orders to re-query the Stacked API.

## Debugging

Enable **Debug Logging** in the gateway settings. Logs are written to **WooCommerce > Status > Logs** under the `stacked` source.

## License

GPL-2.0-or-later
