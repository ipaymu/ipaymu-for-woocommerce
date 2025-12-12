<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Blocks integration for iPaymu.
 */
final class Ipaymu_Blocks extends AbstractPaymentMethodType {

	private $gateway;

	protected $name = 'ipaymu';

	public function initialize() {

		$raw_settings = get_option( 'woocommerce_ipaymu_settings', array() );

		if ( is_array( $raw_settings ) ) {
			$clean_settings = array();

			foreach ( $raw_settings as $key => $val ) {
				$clean_settings[ sanitize_key( $key ) ] =
					is_scalar( $val ) ? sanitize_text_field( $val ) : $val;
			}

			$this->settings = $clean_settings;
		} else {
			$this->settings = array();
		}

		$this->gateway = new Ipaymu_WC_Gateway();
	}

	public function is_active() {
		return $this->gateway && $this->gateway->is_available();
	}

	public function get_payment_method_script_handles() {

		wp_register_script(
			'ipaymu-blocks-integration',
			plugin_dir_url( __FILE__ ) . 'checkout.js',
			array(
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			),
			IPAYMU_WCGW_VERSION,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				'ipaymu-blocks-integration',
				'ipaymu-for-woocommerce'
			);
		}

		return array( 'ipaymu-blocks-integration' );
	}

	public function get_payment_method_data() {

		$title = isset( $this->settings['title'] )
			? sanitize_text_field( $this->settings['title'] )
			: __( 'iPaymu Payment', 'ipaymu-for-woocommerce' );

		$description = isset( $this->settings['description'] )
			? wp_kses_post( $this->settings['description'] )
			: __( 'Pembayaran melalui Virtual Account (VA), QRIS, Alfamart/Indomaret, Direct Debit, Kartu Kredit, dan COD.', 'ipaymu-for-woocommerce' );

		return array(
			'title'       => $title,
			'description' => $description,
			'icon'        => esc_url( plugins_url( '/ipaymu_badge.png', __FILE__ ) ),
		);
	}
}
