<?php
/**
 * iPaymu WooCommerce Payment Gateway.
 *
 * @package Ipaymu_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	/**
	 * iPaymu WooCommerce Gateway.
	 */
	class Ipaymu_WC_Gateway extends WC_Payment_Gateway {

		public $id;
		public $method_title;
		public $method_description;
		public $icon;
		public $has_fields;
		public $redirect_url;
		public $auto_redirect;
		public $return_url;
		public $expired_time;
		public $title;
		public $description;
		public $url;
		public $va;
		public $secret;
		public $completed_payment;

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->id                 = 'ipaymu';
			$this->method_title       = __( 'iPaymu Payment', 'ipaymu-for-woocommerce' );
			$this->method_description = __( 'Accept payments via Virtual Account, QRIS, Alfamart/Indomaret, Direct Debit, Credit Card, and COD using iPaymu.', 'ipaymu-for-woocommerce' );
			$this->has_fields         = false;
			$this->icon               = IPAYMU_WCGW_PLUGIN_URL . 'ipaymu_badge.png';

			$default_return_url       = home_url( '/checkout/order-received/' );
			$this->redirect_url       = add_query_arg( 'wc-api', 'Ipaymu_WC_Gateway', home_url( '/' ) );

			// Load the form fields and settings.
			$this->init_form_fields();
			$this->init_settings();

			// User settings.
			$this->enabled       = $this->get_option( 'enabled' );
			$this->auto_redirect = $this->get_option( 'auto_redirect', '60' );
			$this->return_url    = $this->get_option( 'return_url', $default_return_url );
			$this->expired_time  = (int) $this->get_option( 'expired_time', 24 );
			$this->title         = $this->get_option( 'title', __( 'iPaymu Payment', 'ipaymu-for-woocommerce' ) );
			$this->description   = $this->get_option(
				'description',
				__( 'Pay via Virtual Account, QRIS, Alfamart/Indomaret, Direct Debit, Credit Card, and COD using iPaymu.', 'ipaymu-for-woocommerce' )
			);

			// API URL and credentials based on mode.
			if ( 'yes' === $this->get_option( 'testmode', 'yes' ) ) {
				$this->url    = 'https://sandbox.ipaymu.com/api/v2/payment';
				$this->va     = $this->get_option( 'sandbox_va' );
				$this->secret = $this->get_option( 'sandbox_key' );
			} else {
				$this->url    = 'https://my.ipaymu.com/api/v2/payment';
				$this->va     = $this->get_option( 'production_va' );
				$this->secret = $this->get_option( 'production_key' );
			}

			$this->completed_payment = ( 'yes' === $this->get_option( 'completed_payment', 'no' ) ) ? 'yes' : 'no';

			// Hooks.
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

			add_action( 'woocommerce_api_ipaymu_wc_gateway', array( $this, 'check_ipaymu_response' ) );
			add_action( 'woocommerce_api_wc_gateway_ipaymu', array( $this, 'check_ipaymu_response' ) );
		}

		/**
		 * Admin fields.
		 */
		public function init_form_fields() {

			$this->form_fields = array(
				'enabled'          => array(
					'title'       => __( 'Enable/Disable', 'ipaymu-for-woocommerce' ),
					'label'       => __( 'Enable iPaymu Payment Gateway', 'ipaymu-for-woocommerce' ),
					'type'        => 'checkbox',
					'default'     => 'yes',
				),
				'title'            => array(
					'title'       => __( 'Title', 'ipaymu-for-woocommerce' ),
					'type'        => 'text',
					'default'     => __( 'iPaymu Payment', 'ipaymu-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'description'      => array(
					'title'       => __( 'Description', 'ipaymu-for-woocommerce' ),
					'type'        => 'textarea',
					'default'     => __( 'Pay via Virtual Account, QRIS, Alfamart/Indomaret, Direct Debit, Credit Card, and COD.', 'ipaymu-for-woocommerce' ),
				),
				'testmode'         => array(
					'title'       => __( 'Sandbox Mode', 'ipaymu-for-woocommerce' ),
					'label'       => __( 'Enable Sandbox / Test Mode', 'ipaymu-for-woocommerce' ),
					'type'        => 'checkbox',
					'default'     => 'yes',
				),
				'completed_payment' => array(
					'title'       => __( 'Set Completed Status After Payment', 'ipaymu-for-woocommerce' ),
					'type'        => 'checkbox',
					'default'     => 'no',
				),
				'sandbox_va'       => array(
					'title'       => __( 'Sandbox VA', 'ipaymu-for-woocommerce' ),
					'type'        => 'text',
					'default'     => '',
				),
				'sandbox_key'      => array(
					'title'       => __( 'Sandbox API Key', 'ipaymu-for-woocommerce' ),
					'type'        => 'password',
					'default'     => '',
				),
				'production_va'    => array(
					'title'       => __( 'Production VA', 'ipaymu-for-woocommerce' ),
					'type'        => 'text',
					'default'     => '',
				),
				'production_key'   => array(
					'title'       => __( 'Production API Key', 'ipaymu-for-woocommerce' ),
					'type'        => 'password',
					'default'     => '',
				),
				'auto_redirect'    => array(
					'title'       => __( 'Redirect Time to Thank You Page (seconds)', 'ipaymu-for-woocommerce' ),
					'type'        => 'text',
					'default'     => '60',
				),
				'return_url'       => array(
					'title'       => __( 'Return URL', 'ipaymu-for-woocommerce' ),
					'type'        => 'text',
					'default'     => home_url( '/checkout/order-received/' ),
				),
				'expired_time'     => array(
					'title'       => __( 'Payment Expiry (hours)', 'ipaymu-for-woocommerce' ),
					'type'        => 'text',
					'default'     => '24',
				),
			);
		}

		/**
		 * Recursively sanitize incoming array (JSON or POST).
		 */
		private function ipaymu_wcgw_sanitize_array_recursive( $data ) {
			if ( is_array( $data ) ) {
				$out = array();
				foreach ( $data as $key => $value ) {
					$clean_key = is_string( $key ) ? sanitize_key( $key ) : $key;
					$out[ $clean_key ] = is_array( $value )
						? $this->ipaymu_wcgw_sanitize_array_recursive( $value )
						: ( is_scalar( $value ) ? sanitize_text_field( $value ) : $value );
				}
				return $out;
			}
			return is_scalar( $data ) ? sanitize_text_field( $data ) : $data;
		}

		/**
		 * Process payment request.
		 */
		public function process_payment( $order_id ) {

			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				throw new Exception( __( 'Order not found.', 'ipaymu-for-woocommerce' ) );
			}

			$buyer_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
			$buyer_email = $order->get_billing_email();
			$buyer_phone = $order->get_billing_phone();

			$notify_url = $this->redirect_url . '&id_order=' . $order_id . '&param=notify&order_status=processing';
			if ( 'yes' === $this->completed_payment ) {
				$notify_url = $this->redirect_url . '&id_order=' . $order_id . '&param=notify&order_status=completed';
			}

			$body = array(
				'product'     => array( 'Order #' . $order_id ),
				'qty'         => array( 1 ),
				'price'       => array( (float) $order->get_total() ),
				'buyerName'   => $buyer_name,
				'buyerEmail'  => $buyer_email,
				'buyerPhone'  => $buyer_phone,
				'referenceId' => (string) $order_id,
				'returnUrl'   => $this->return_url,
				'cancelUrl'   => $this->redirect_url . '&id_order=' . $order_id . '&param=cancel',
				'notifyUrl'   => $notify_url,
				'expired'     => (int) $this->expired_time,
				'expiredType' => 'hours',
			);

			$body_json    = wp_json_encode( $body, JSON_UNESCAPED_SLASHES );
			$request_body = strtolower( hash( 'sha256', $body_json ) );
			$string       = 'POST:' . $this->va . ':' . $request_body . ':' . $this->secret;
			$signature    = hash_hmac( 'sha256', $string, $this->secret );

			$headers = array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
				'va'           => $this->va,
				'signature'    => $signature,
			);

			$response_http = wp_remote_post(
				$this->url,
				array(
					'headers' => $headers,
					'body'    => $body_json,
					'timeout' => 60,
				)
			);

			if ( is_wp_error( $response_http ) ) {
				throw new Exception( sanitize_text_field( $response_http->get_error_message() ) );
			}

			$res = wp_remote_retrieve_body( $response_http );
			$json = json_decode( $res );

			if ( empty( $json->Data->Url ) ) {
				throw new Exception(
					__( 'Invalid response from iPaymu.', 'ipaymu-for-woocommerce' )
				);
			}

			if ( WC()->cart ) {
				WC()->cart->empty_cart();
			}

			return array(
				'result'   => 'success',
				'redirect' => esc_url_raw( $json->Data->Url ),
			);
		}

		/**
		 * Handle webhook callback POST from iPaymu.
		 */
		public function check_ipaymu_response() {

			// Sanitized combined payload (POST + JSON).
			$ipaymu_request = array();

			// If POST exists.
			if ( ! empty( $_POST ) ) {
				$ipaymu_request = $this->ipaymu_wcgw_sanitize_array_recursive( wp_unslash( $_POST ) );
			}

			// JSON body.
			$raw = file_get_contents( 'php://input' );
			if ( ! empty( $raw ) ) {
				$decoded = json_decode( $raw, true );
				if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
					$ipaymu_request = array_merge(
						$ipaymu_request,
						$this->ipaymu_wcgw_sanitize_array_recursive( $decoded )
					);
				}
			}

			$order_id = isset( $ipaymu_request['id_order'] ) ? absint( $ipaymu_request['id_order'] ) : 0;

			if ( ! $order_id ) {
				status_header( 400 );
				echo 'Invalid order ID';
				exit;
			}

			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				status_header( 404 );
				echo 'Order not found';
				exit;
			}

			// Server notification.
			if ( isset( $ipaymu_request['status'] ) && isset( $ipaymu_request['trx_id'] ) ) {

				$status = strtolower( sanitize_text_field( $ipaymu_request['status'] ) );
				$trx_id = sanitize_text_field( $ipaymu_request['trx_id'] );
				$new_status = isset( $ipaymu_request['order_status'] )
					? sanitize_text_field( $ipaymu_request['order_status'] )
					: 'processing';

				if ( 'berhasil' === $status ) {

					$order->add_order_note( 'Payment Success — iPaymu #' . $trx_id );

					$order->update_status( $new_status );
					$order->payment_complete();

					echo 'completed';
					exit;

				} elseif ( 'pending' === $status ) {

					$order->add_order_note( 'Waiting Payment — iPaymu #' . $trx_id );
					$order->update_status( 'pending' );

					echo 'pending';
					exit;

				} elseif ( 'expired' === $status ) {

					$order->add_order_note( 'Payment Expired — iPaymu #' . $trx_id );
					$order->update_status( 'cancelled' );

					echo 'cancelled';
					exit;

				} else {
					echo 'invalid status';
					exit;
				}
			}

			// Browser redirect.
			$order_received_url = wc_get_endpoint_url(
				'order-received',
				$order_id,
				wc_get_page_permalink( 'checkout' )
			);

			$order_received_url = add_query_arg( 'key', $order->get_order_key(), $order_received_url );
			wp_safe_redirect( $order_received_url );
			exit;
		}
	}
}
