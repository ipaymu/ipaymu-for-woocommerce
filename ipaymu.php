<?php
/*
  Plugin Name: iPaymu Payment Gateway
  Plugin URI: https://github.com/ipaymu/ipaymu-for-woocommerce
  Description: iPaymu Indonesia Online Payment Gateway. Accept payments via Virtual Account, QRIS, Retail Outlets, Direct Debit, Credit Card, and COD.
  Version: 2.0.1
  Author: iPaymu Development Team
  Author URI: https://ipaymu.com
  License: GPLv2 or later
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
  Requires at least: 6.0
  Tested up to: 6.9
  Requires PHP: 7.4
  WC requires at least: 8.0.0
  WC tested up to: 8.6.0
  Text Domain: ipaymu-for-woocommerce
  Domain Path: /languages
  Requires Plugins: woocommerce
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin constants.
 */
define( 'IPAYMU_WCGW_VERSION', '2.0.1' );
define( 'IPAYMU_WCGW_PLUGIN_FILE', __FILE__ );
define( 'IPAYMU_WCGW_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'IPAYMU_WCGW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load translation.
 */
function ipaymu_wcgw_load_textdomain() {
	load_plugin_textdomain(
		'ipaymu-for-woocommerce',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages/'
	);
}
add_action( 'plugins_loaded', 'ipaymu_wcgw_load_textdomain' );

/**
 * Load the gateway class.
 */
function ipaymu_wcgw_load_gateway() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}
	require_once IPAYMU_WCGW_PLUGIN_PATH . 'gateway.php';
}
add_action( 'plugins_loaded', 'ipaymu_wcgw_load_gateway', 0 );

/**
 * HPOS + Blocks compatibility.
 */
function ipaymu_wcgw_declare_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'cart_checkout_blocks',
			IPAYMU_WCGW_PLUGIN_FILE,
			true
		);

		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			IPAYMU_WCGW_PLUGIN_FILE,
			true
		);
	}
}
add_action( 'before_woocommerce_init', 'ipaymu_wcgw_declare_compatibility' );

/**
 * Register Blocks integration.
 */
function ipaymu_wcgw_register_blocks_support() {
	if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}

	require_once IPAYMU_WCGW_PLUGIN_PATH . 'block.php';

	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
			$payment_method_registry->register( new Ipaymu_Blocks() );
		}
	);
}
add_action( 'woocommerce_blocks_loaded', 'ipaymu_wcgw_register_blocks_support' );
