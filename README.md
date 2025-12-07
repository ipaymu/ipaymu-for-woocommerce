![iPaymu Badge](ipaymu_badge.png)

# iPaymu Payment Gateway for WooCommerce

**Contributors:** ipaymu  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  
**Requires at least:** WordPress 6.0, PHP 7.4  
**Tested up to:** WordPress 6.9, WooCommerce 8.6.0  
**Stable tag:** 2.0.1

## Overview

Official iPaymu Payment Gateway integration for WooCommerce. Accept payments via **Virtual Account (VA)**, **QRIS**, **Retail Outlets** (Alfamart/Indomaret), **Direct Debit**, **Credit Card**, and **COD** from your Indonesian customers.

## Description

This plugin seamlessly integrates the iPaymu Indonesia payment system into WooCommerce, enabling your store to accept multiple payment methods popular in Indonesia:

- **Virtual Account (VA)** - Bank transfer payments
- **QRIS** - QR code-based payments
- **Retail Payments** - Alfamart / Indomaret transfers
- **Direct Debit** - Automatic payment deductions
- **Credit Card** - Card payments
- **Cash on Delivery (COD)** - Payment on delivery

**Requirement:** An active [iPaymu](https://ipaymu.com) account with API Key and Virtual Account number.

## Installation

1. **Upload** the plugin folder to `/wp-content/plugins/`
2. **Activate** via **Plugins → Installed Plugins**
3. Navigate to **WooCommerce → Settings → Payments**
4. Click on **iPaymu Payment Gateway** and configure:
   - Enable/Disable the payment method
   - Set test mode (Sandbox) or production
   - Enter your VA and API Key
   - Configure auto-redirect delay
5. **Save changes**

## Frequently Asked Questions

### Do I need an iPaymu account?

Yes. You must have an active iPaymu account with:
- VA (Virtual Account) number
- API Key (for integration)

[Register for iPaymu](https://ipaymu.com) or [create an account](https://sandbox.ipaymu.com) for testing.

### Does this support HPOS (High Performance Order Storage)?

Yes! The plugin is fully compatible with WooCommerce's High Performance Order Storage feature.

### Does this plugin support WooCommerce Blocks Checkout?

Yes! The plugin includes full support for the WooCommerce Blocks checkout system.

### Is SSL/TLS required?

Yes, SSL/TLS is **recommended** (not optional) for secure payment processing and PCI compliance.

## Changelog

### Version 2.0.1

- ✅ Add HPOS (High Performance Order Storage) compatibility
- ✅ Add WooCommerce Blocks checkout support
- ✅ Improve error handling and logging
- ✅ Fix expired_time calculation
- ✅ Align API request format with iPaymu API V2
- ✅ Improve code quality and security

## Configuration

### Test Mode (Sandbox)

To test payments:
1. Enable **Mode Test/Sandbox** in settings
2. Get test credentials from [iPaymu Sandbox](https://sandbox.ipaymu.com/integration)
3. Enter your **Sandbox VA** and **API Key**

### Production Mode

When ready for live payments:
1. Disable **Mode Test/Sandbox**
2. Get live credentials from [iPaymu Production](https://my.ipaymu.com/integration)
3. Enter your **Live VA** and **API Key**

## Webhook Endpoint (for iPaymu Configuration)

The plugin exposes a webhook endpoint for receiving payment notifications from iPaymu:

```
?wc-api=Ipaymu_WC_Gateway
```

**Full URL Example:**
```
https://example.com/?wc-api=Ipaymu_WC_Gateway
```

Configure this URL in your [iPaymu Dashboard](https://my.ipaymu.com) under **Integration Settings** → **Notification/Webhook URL**.

### Backward Compatibility

> **Note for upgrades:** Older plugin versions used `?wc-api=WC_Gateway_iPaymu`. The new endpoint is `?wc-api=Ipaymu_WC_Gateway`. Both endpoints remain supported for compatibility, but we recommend using the new one.

## Support

For issues or questions:
- Visit [iPaymu Documentation](https://ipaymu.com/dokumentasi)
- Contact [iPaymu Support](mailto:support@ipaymu.com)
- Check the [GitHub Repository](https://github.com/ipaymu/ipaymu-for-woocommerce)

## License

GPLv2 or later. See [LICENSE](LICENSE) for details.
