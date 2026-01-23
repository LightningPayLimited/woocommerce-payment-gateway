# Lightning Pay WooCommerce Payment Gateway

WooCommerce payment gateway for accepting Bitcoin Lightning Network payments via [Lightning Pay](https://app.lightningpay.nz).

## Requirements

- WordPress 5.0+
- WooCommerce 5.0+
- Store currency set to NZD
- A Lightning Pay merchant account ([register here](https://app.lightningpay.nz/auth/register))

## Installation

1. Copy the `lightning-pay-gateway` folder to `wp-content/plugins/`.
2. Activate the plugin in **Plugins > Installed Plugins**.
3. Go to **WooCommerce > Settings > Payments** and enable **Lightning Pay**.
4. Enter your API key (found in your Lightning Pay merchant profile).

## Configuration

Navigate to **WooCommerce > Settings > Payments > Lightning Pay** to configure:

| Setting | Description |
|---------|-------------|
| **Title** | Payment method name shown at checkout (default: "Lightning Pay") |
| **Description** | Text shown to customers at checkout |
| **API Key** | Your Lightning Pay API key |
| **API Base URL** | Lightning Pay API endpoint (default: `https://app.lightningpay.nz`) |
| **Debug Logging** | Enable to log API interactions to WooCommerce logs |

## How It Works

1. Customer selects "Pay with Bitcoin" at checkout.
2. The plugin creates a payment link via the Lightning Pay API.
3. Customer is redirected to Lightning Pay to complete the payment.
4. After paying, the customer returns to the order confirmation page.
5. The plugin polls Lightning Pay to confirm payment and marks the order as complete.

## Admin Features

- **Order details panel**: View the Lightning Pay reference and payment link for each order.
- **Manual status check**: Click "Check Payment Status" on pending orders to re-query the Lightning Pay API.

## Debugging

Enable **Debug Logging** in the gateway settings. Logs are written to **WooCommerce > Status > Logs** under the `lightning-pay` source.

## License

GPL-2.0-or-later
