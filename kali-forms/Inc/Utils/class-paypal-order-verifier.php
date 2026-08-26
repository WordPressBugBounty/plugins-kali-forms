<?php

namespace KaliForms\Inc\Utils;

use KaliForms\Inc\Backend\Sanitizers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches a captured PayPal order with server-held REST credentials and
 * compares amount, currency and payee against the form catalog.
 */
class Paypal_Order_Verifier {

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	public $slug = 'kaliforms';

	/**
	 * PayPal order IDs that were already accepted for a form.
	 */
	const USED_ORDER_OPTION_PREFIX = 'kaliforms_paypal_used_';

	/**
	 * Currencies that PayPal represents with zero decimal places.
	 *
	 * @var string[]
	 */
	const ZERO_DECIMAL_CURRENCIES = array( 'HUF', 'JPY', 'TWD' );

	/**
	 * Whether a post is a published Kali Forms form.
	 *
	 * @param int $form_id Form post ID.
	 * @return bool
	 */
	public static function is_published_kali_form( $form_id ) {
		$form_id = absint( $form_id );
		if ( $form_id <= 0 ) {
			return false;
		}

		$post = get_post( $form_id );
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		return $post->post_type === 'kaliforms_forms' && $post->post_status === 'publish';
	}

	/**
	 * Whether the form definition includes a PayPal field.
	 *
	 * @param int $form_id Form post ID.
	 * @return bool
	 */
	public static function form_has_paypal_field( $form_id ) {
		if ( ! self::is_published_kali_form( $form_id ) ) {
			return false;
		}

		$fields = Sanitizers::decode_json_meta(
			get_post_meta( $form_id, 'kaliforms_field_components', true ),
			false
		);
		if ( ! is_array( $fields ) ) {
			return false;
		}

		foreach ( $fields as $field ) {
			if ( is_object( $field ) && isset( $field->id ) && $field->id === 'paypal' ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Fetch the PayPal order and reject unless it matches the catalog total.
	 *
	 * @param int    $form_id   Form post ID.
	 * @param string $order_id  PayPal order ID from the client.
	 * @param array  $form_data Submitted field values (donations / selected variants).
	 * @param bool   $consume   Whether to mark the order as used after success.
	 * @return true|\WP_Error
	 */
	public static function verify_captured_order( $form_id, $order_id, $form_data = array(), $consume = true ) {
		$form_id  = absint( $form_id );
		$order_id = self::sanitize_order_id( $order_id );

		if ( ! self::is_published_kali_form( $form_id ) ) {
			return new \WP_Error(
				'kaliforms_paypal_invalid_form',
				esc_html__( 'Payment could not be verified.', 'kali-forms' )
			);
		}

		if ( $order_id === '' ) {
			return new \WP_Error(
				'kaliforms_paypal_missing_order',
				esc_html__( 'A completed PayPal payment is required before this form can be submitted.', 'kali-forms' )
			);
		}

		if ( $consume && self::order_already_used( $form_id, $order_id ) ) {
			return new \WP_Error(
				'kaliforms_paypal_order_reused',
				esc_html__( 'This PayPal payment has already been used.', 'kali-forms' )
			);
		}

		$helper = new Payments_Action_Helper( $form_id );
		$helper->post = get_post( $form_id );
		$expected     = $helper->get_expected_charge( is_array( $form_data ) ? $form_data : array() );

		if ( (float) $expected['amount'] <= 0 ) {
			return new \WP_Error(
				'kaliforms_paypal_empty_catalog',
				esc_html__( 'Payment could not be verified.', 'kali-forms' )
			);
		}

		$order = self::fetch_order( $form_id, $order_id );
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		$captured = self::extract_capture( $order );
		if ( is_wp_error( $captured ) ) {
			return $captured;
		}

		if ( ! self::amounts_match( $captured['amount'], $expected['amount'], $captured['currency'] ) ) {
			return new \WP_Error(
				'kaliforms_paypal_amount_mismatch',
				esc_html__( 'Payment could not be verified.', 'kali-forms' )
			);
		}

		if ( strtoupper( $captured['currency'] ) !== strtoupper( (string) $expected['currency'] ) ) {
			return new \WP_Error(
				'kaliforms_paypal_currency_mismatch',
				esc_html__( 'Payment could not be verified.', 'kali-forms' )
			);
		}

		if ( $expected['payee'] !== false && $expected['payee'] !== '' ) {
			if ( strtolower( $captured['payee'] ) !== strtolower( $expected['payee'] ) ) {
				return new \WP_Error(
					'kaliforms_paypal_payee_mismatch',
					esc_html__( 'Payment could not be verified.', 'kali-forms' )
				);
			}
		}

		if ( $consume && ! self::mark_order_used( $form_id, $order_id ) ) {
			return new \WP_Error(
				'kaliforms_paypal_order_reused',
				esc_html__( 'This PayPal payment has already been used.', 'kali-forms' )
			);
		}

		return true;
	}

	/**
	 * Normalise a PayPal order ID.
	 *
	 * @param mixed $order_id Raw ID.
	 * @return string
	 */
	public static function sanitize_order_id( $order_id ) {
		if ( ! is_string( $order_id ) && ! is_numeric( $order_id ) ) {
			return '';
		}

		$order_id = strtoupper( sanitize_text_field( (string) $order_id ) );
		if ( $order_id === '' || strlen( $order_id ) > 50 ) {
			return '';
		}

		if ( ! preg_match( '/^[A-Z0-9-]+$/', $order_id ) ) {
			return '';
		}

		return $order_id;
	}

	/**
	 * Compare two money amounts using PayPal decimal rules.
	 *
	 * @param mixed  $captured Captured amount.
	 * @param mixed  $expected Expected catalog amount.
	 * @param string $currency Currency code.
	 * @return bool
	 */
	public static function amounts_match( $captured, $expected, $currency ) {
		$decimals = in_array( strtoupper( (string) $currency ), self::ZERO_DECIMAL_CURRENCIES, true ) ? 0 : 2;
		$left     = number_format( (float) $captured, $decimals, '.', '' );
		$right    = number_format( (float) $expected, $decimals, '.', '' );

		return hash_equals( $left, $right );
	}

	/**
	 * Fetch the order from the PayPal Orders API.
	 *
	 * @param int    $form_id  Form post ID.
	 * @param string $order_id PayPal order ID.
	 * @return array|\WP_Error
	 */
	private static function fetch_order( $form_id, $order_id ) {
		$token = self::get_access_token( $form_id );
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$credentials = self::get_credentials( $form_id );
		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}

		$response = wp_remote_get(
			$credentials['api_base'] . '/v2/checkout/orders/' . rawurlencode( $order_id ),
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'kaliforms_paypal_http',
				esc_html__( 'Payment could not be verified.', 'kali-forms' )
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code === 401 ) {
			self::clear_access_token( $form_id );
			$token = self::get_access_token( $form_id, true );
			if ( is_wp_error( $token ) ) {
				return $token;
			}

			$response = wp_remote_get(
				$credentials['api_base'] . '/v2/checkout/orders/' . rawurlencode( $order_id ),
				array(
					'timeout' => 15,
					'headers' => array(
						'Authorization' => 'Bearer ' . $token,
						'Content-Type'  => 'application/json',
						'Accept'        => 'application/json',
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return new \WP_Error(
					'kaliforms_paypal_http',
					esc_html__( 'Payment could not be verified.', 'kali-forms' )
				);
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
		}

		if ( $code < 200 || $code >= 300 || ! is_array( $body ) ) {
			return new \WP_Error(
				'kaliforms_paypal_order_lookup',
				esc_html__( 'Payment could not be verified.', 'kali-forms' )
			);
		}

		return $body;
	}

	/**
	 * Pull captured amount / currency / payee from a PayPal order resource.
	 *
	 * @param array $order Order payload.
	 * @return array|\WP_Error
	 */
	private static function extract_capture( array $order ) {
		$status = isset( $order['status'] ) ? strtoupper( (string) $order['status'] ) : '';
		if ( $status !== 'COMPLETED' ) {
			return new \WP_Error(
				'kaliforms_paypal_not_completed',
				esc_html__( 'Payment could not be verified.', 'kali-forms' )
			);
		}

		if ( empty( $order['purchase_units'][0] ) || ! is_array( $order['purchase_units'][0] ) ) {
			return new \WP_Error(
				'kaliforms_paypal_missing_unit',
				esc_html__( 'Payment could not be verified.', 'kali-forms' )
			);
		}

		$unit = $order['purchase_units'][0];
		$amount   = '';
		$currency = '';

		if ( ! empty( $unit['payments']['captures'][0]['amount'] ) && is_array( $unit['payments']['captures'][0]['amount'] ) ) {
			$capture = $unit['payments']['captures'][0];
			$capture_status = isset( $capture['status'] ) ? strtoupper( (string) $capture['status'] ) : '';
			if ( $capture_status !== 'COMPLETED' ) {
				return new \WP_Error(
					'kaliforms_paypal_not_completed',
					esc_html__( 'Payment could not be verified.', 'kali-forms' )
				);
			}
			$amount   = isset( $capture['amount']['value'] ) ? $capture['amount']['value'] : '';
			$currency = isset( $capture['amount']['currency_code'] ) ? $capture['amount']['currency_code'] : '';
		} elseif ( ! empty( $unit['amount'] ) && is_array( $unit['amount'] ) ) {
			$amount   = isset( $unit['amount']['value'] ) ? $unit['amount']['value'] : '';
			$currency = isset( $unit['amount']['currency_code'] ) ? $unit['amount']['currency_code'] : '';
		}

		if ( $amount === '' || $currency === '' ) {
			return new \WP_Error(
				'kaliforms_paypal_missing_amount',
				esc_html__( 'Payment could not be verified.', 'kali-forms' )
			);
		}

		$payee = '';
		if ( ! empty( $unit['payee']['email_address'] ) ) {
			$payee = sanitize_email( $unit['payee']['email_address'] );
		}

		return array(
			'amount'   => $amount,
			'currency' => strtoupper( sanitize_text_field( $currency ) ),
			'payee'    => $payee,
		);
	}

	/**
	 * OAuth client-credentials token for the form's PayPal app.
	 *
	 * @param int  $form_id Form post ID.
	 * @param bool $force   Bypass cache.
	 * @return string|\WP_Error
	 */
	private static function get_access_token( $form_id, $force = false ) {
		$credentials = self::get_credentials( $form_id );
		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}

		$cache_key = self::token_cache_key( $credentials['client_id'] );
		if ( ! $force ) {
			$cached = get_transient( $cache_key );
			if ( is_string( $cached ) && $cached !== '' ) {
				return $cached;
			}
		}

		$response = wp_remote_post(
			$credentials['api_base'] . '/v1/oauth2/token',
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $credentials['client_id'] . ':' . $credentials['secret'] ),
					'Content-Type'  => 'application/x-www-form-urlencoded',
					'Accept'        => 'application/json',
				),
				'body'    => 'grant_type=client_credentials',
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'kaliforms_paypal_token',
				esc_html__( 'Payment could not be verified.', 'kali-forms' )
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( (int) wp_remote_retrieve_response_code( $response ) !== 200 || empty( $body['access_token'] ) ) {
			return new \WP_Error(
				'kaliforms_paypal_token',
				esc_html__( 'Payment could not be verified.', 'kali-forms' )
			);
		}

		$token   = is_string( $body['access_token'] ) ? trim( $body['access_token'] ) : '';
		if ( $token === '' ) {
			return new \WP_Error(
				'kaliforms_paypal_token',
				esc_html__( 'Payment could not be verified.', 'kali-forms' )
			);
		}
		$expires = isset( $body['expires_in'] ) ? absint( $body['expires_in'] ) : 300;
		$ttl     = max( 60, min( 300, $expires - 60 ) );
		set_transient( $cache_key, $token, $ttl );

		return $token;
	}

	/**
	 * @param int $form_id Form post ID.
	 * @return void
	 */
	private static function clear_access_token( $form_id ) {
		$credentials = self::get_credentials( $form_id );
		if ( is_wp_error( $credentials ) ) {
			return;
		}

		delete_transient( self::token_cache_key( $credentials['client_id'] ) );
	}

	/**
	 * @param string $client_id Client ID.
	 * @return string
	 */
	private static function token_cache_key( $client_id ) {
		return 'kaliforms_pp_tok_' . md5( $client_id );
	}

	/**
	 * Server-held REST credentials for this form.
	 *
	 * @param int $form_id Form post ID.
	 * @return array|\WP_Error
	 */
	private static function get_credentials( $form_id ) {
		$live = get_post_meta( $form_id, 'kaliforms_payments_live', true );
		$is_live = filter_var( $live, FILTER_VALIDATE_BOOLEAN );

		if ( $is_live ) {
			$client_id = (string) get_post_meta( $form_id, 'kaliforms_paypal_client_id', true );
			$secret    = (string) get_post_meta( $form_id, 'kaliforms_paypal_client_secret', true );
			$api_base  = 'https://api-m.paypal.com';
		} else {
			$client_id = (string) get_post_meta( $form_id, 'kaliforms_paypal_client_id_sandbox', true );
			$secret    = (string) get_post_meta( $form_id, 'kaliforms_paypal_client_secret_sandbox', true );
			$api_base  = 'https://api-m.sandbox.paypal.com';
		}

		$client_id = trim( $client_id );
		$secret    = trim( $secret );

		if ( $client_id === '' || $secret === '' ) {
			return new \WP_Error(
				'kaliforms_paypal_missing_credentials',
				esc_html__( 'PayPal is not configured for server-side verification. Add the REST client ID and secret in form Payments settings.', 'kali-forms' )
			);
		}

		return array(
			'client_id' => $client_id,
			'secret'    => $secret,
			'api_base'  => $api_base,
			'live'      => $is_live,
		);
	}

	/**
	 * @param int    $form_id  Form post ID.
	 * @param string $order_id PayPal order ID.
	 * @return bool
	 */
	private static function order_already_used( $form_id, $order_id ) {
		return get_option( self::used_order_option_key( $form_id, $order_id ), false ) !== false;
	}

	/**
	 * Atomically mark an order as consumed for this form.
	 *
	 * @param int    $form_id  Form post ID.
	 * @param string $order_id PayPal order ID.
	 * @return bool False when the order was already recorded.
	 */
	private static function mark_order_used( $form_id, $order_id ) {
		return add_option(
			self::used_order_option_key( $form_id, $order_id ),
			time(),
			'',
			'no'
		);
	}

	/**
	 * @param int    $form_id  Form post ID.
	 * @param string $order_id PayPal order ID.
	 * @return string
	 */
	private static function used_order_option_key( $form_id, $order_id ) {
		return self::USED_ORDER_OPTION_PREFIX . absint( $form_id ) . '_' . strtolower( $order_id );
	}
}
