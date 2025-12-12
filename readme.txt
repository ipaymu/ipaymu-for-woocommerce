=== iPaymu Payment Gateway for WooCommerce ===
Contributors: ipaymu
Tags: payment, payment-gateway, indonesia, ecommerce, checkout
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: woocommerce

iPaymu Payment Gateway for WooCommerce enables secure payments via Virtual Account, QRIS, Minimarket, Credit Card, and Direct Debit in Indonesia.

== Description ==

This plugin integrates iPaymu Indonesia's payment system into WooCommerce.  
It supports Virtual Accounts, QRIS, Retail Payments (Alfamart/Indomaret), Credit Card, Direct Debit, and more.

To use this plugin, you need an active iPaymu account along with your API Key and Virtual Account number.

== External Services ==

This plugin communicates with iPaymu’s external API services for payment processing. These services are required for the plugin to function properly.

= 1. iPaymu Production API =
- Endpoint: https://my.ipaymu.com/api/v2/payment
- Purpose: To create and validate live payment transactions.
- Data Sent:
  - Order details (order ID, amount, items)
  - Customer information (name, email, phone)
  - Return URL / Notify URL
  - Merchant identifiers (API Key and Virtual Account Number)
- When:
  - During checkout after a customer confirms payment.
- Terms of Service: https://ipaymu.com/syarat-dan-ketentuan/
- Privacy Policy: https://ipaymu.com/kebijakan-privasi/

= 2. iPaymu Sandbox API =
- Endpoint: https://sandbox.ipaymu.com/api/v2/payment
- Purpose: Testing transactions without real payments.
- Data Sent: Same as production, but only for simulation.
- Terms & Privacy: Same as above.

Notes on data handling:
- The plugin does not store or handle sensitive payment credentials such as credit card numbers.
- All requests use secure HTTPS communication.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/`
2. Activate it from the WordPress Plugins menu
3. Go to: **WooCommerce → Settings → Payments**
4. Enable "iPaymu Payment Gateway"
5. Enter your VA and API Key (Sandbox or Production)

== Frequently Asked Questions ==

= Do I need an iPaymu account? =
Yes, you must have an active iPaymu account with API Key and VA.

= Does this support WooCommerce Subscriptions? =
Yes, manual renewal is supported.

= Does this plugin support HPOS (High Performance Order Storage)? =
Yes, full compatibility is declared.

= Is SSL required? =
Recommended for secure transactions.

== Screenshots ==

1. iPaymu settings page in WooCommerce
2. iPaymu payment option on checkout
3. Payment instruction page

== Changelog ==

= 2.0.1 =
* Add HPOS compatibility
* Add WooCommerce Blocks compatibility
* Improve error handling and API request format
* Fix expired_time bug

== Upgrade Notice ==

= 2.0.1 =
This update improves API stability, Block Checkout compatibility, and HPOS support.

== Webhook Endpoint ==

The plugin exposes a webhook endpoint used for server-to-server notifications from iPaymu.
Use the following query parameter on your site URL to deliver notifications to the plugin:

```
?wc-api=Ipaymu_WC_Gateway
```

Example: `https://example.com/?wc-api=Ipaymu_WC_Gateway`

If you're upgrading from older plugin versions that used `?wc-api=WC_Gateway_iPaymu`,
please update any external webhook configuration to use the new endpoint so that
notifications continue to be delivered.

== License ==

GPLv2 or later.

