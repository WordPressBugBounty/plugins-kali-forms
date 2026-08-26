<?php

namespace KaliForms\Inc;

if (!defined('ABSPATH')) {
	exit;
}

use KaliForms\Inc\Utils\Payments_Action_Helper;
use KaliForms\Inc\Utils\Paypal_Order_Verifier;

class Payments_Simple
{
	/**
	 * Plugin slug
	 *
	 * @var string
	 */
	protected $slug = 'kaliforms';
	/**
	 * Keeps the current post
	 *
	 * @var WP_POST
	 */
	protected $post = null;
	/**
	 * Class constructor
	 */
	public function __construct()
	{
		/**
		 * Filter for the form shortcode (init)
		 */
		add_filter($this->slug . '_form_shortcode_init', [$this, 'hook_into_shortcode']);

		add_action('wp_ajax_kaliforms_form_verify_products', [$this, 'verify_products']);
		add_action('wp_ajax_nopriv_kaliforms_form_verify_products', [$this, 'verify_products']);

		add_action('wp_ajax_kaliforms_form_paypal_confirm_log', [$this, 'confirm_paypal_order']);
		add_action('wp_ajax_nopriv_kaliforms_form_paypal_confirm_log', [$this, 'confirm_paypal_order']);
	}

	/**
	 * Verify a captured PayPal order against the form catalog.
	 *
	 * The submission handler is the actual gate; this endpoint exists so
	 * capture-time logging can fail closed instead of returning a stub.
	 *
	 * @return void
	 */
	public function confirm_paypal_order()
	{
		$args = $this->sanitize_post();
		$this->verify($args);

		$form_id = absint($args['formId']);
		if (!Paypal_Order_Verifier::is_published_kali_form($form_id)) {
			$this->denied();
		}

		$order_id = '';
		if (!empty($args['payment_id'])) {
			$order_id = $args['payment_id'];
		} elseif (!empty($args['id'])) {
			$order_id = $args['id'];
		}

		$form_data = [];
		if (isset($args['formData']) && is_array($args['formData'])) {
			$form_data = $args['formData'];
		}

		$result = Paypal_Order_Verifier::verify_captured_order($form_id, $order_id, $form_data, false);
		if (is_wp_error($result)) {
			wp_die(
				wp_json_encode(
					[
						'error'   => true,
						'message' => $result->get_error_message(),
					]
				)
			);
		}

		wp_die(
			wp_json_encode(
				[
					'success' => true,
				]
			)
		);
	}

	/**
	 * If the user is not authorized, deny action
	 */
	public function denied()
	{
		wp_die(esc_html__('Denied', 'kali-forms'));
	}

	/**
	 * Verify products
	 *
	 * @return void
	 */
	public function verify_products()
	{
		$args = $this->sanitize_post();
		$this->verify($args);

		$form_id = absint($args['formId']);
		if (!Paypal_Order_Verifier::is_published_kali_form($form_id)) {
			$this->denied();
		}

		$actionHelper = new Payments_Action_Helper($form_id);
		if (is_wp_error($actionHelper)) {
			wp_die(esc_html__('Something went wrong', 'kali-forms'));
		}

		wp_die($actionHelper->get_products($args)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON AJAX response.
	}

	/**
	 * Run security check and return args
	 *
	 * @return array|false
	 */
	private function sanitize_post()
	{
		if (!isset($_POST['data']) || !is_array($_POST['data'])) {
			return false;
		}

		$data = stripslashes_deep($_POST['data']);

		$args = [
			'formId' => isset($data['formId']) ? absint($data['formId']) : 0,
			'nonce'  => isset($data['nonce']) ? sanitize_key($data['nonce']) : '',
		];

		if (isset($data['payment_id'])) {
			$args['payment_id'] = sanitize_text_field($data['payment_id']);
		}
		if (isset($data['id'])) {
			$args['id'] = sanitize_text_field($data['id']);
		}
		if (isset($data['formData']) && is_array($data['formData'])) {
			$args['formData'] = $data['formData'];
		}

		return $args;
	}

	/**
	 * When hooked into the shortcode we need to register the script
	 *
	 * @param Form_Shortcode $args
	 * @return void
	 */
	public function hook_into_shortcode($args)
	{
		foreach ($args->fields as $k => $v) {
			if ($v->id === 'paypal') {
				$paymentMethod = $v->id;
				break;
			}
		}

		if (empty($paymentMethod)) {
			return $args;
		}

		switch ($paymentMethod) {
			case 'paypal':
				$this->load_paypal($args);
				break;
			default:
				break;
		}

		return $args;
	}

	/**
	 * Load paypal scripts
	 *
	 * @return void
	 */
	protected function load_paypal($args)
	{
		$paymentsLive = get_post_meta($args->post->ID, $this->slug . '_payments_live', true);
		if ($paymentsLive === null || $paymentsLive === '') {
			$paymentsLive = 0;
		}

		$key = $paymentsLive === 0
			? get_post_meta($args->post->ID, $this->slug . '_paypal_client_id_sandbox', true)
			: get_post_meta($args->post->ID, $this->slug . '_paypal_client_id', true);

		if (empty($key)) {
			return false;
		}

		$currency = get_post_meta($args->post->ID, $this->slug . '_currency', true);
		if ($currency === null || $currency === '') {
			$currency = 'USD';
		}

		$url = 'https://www.paypal.com/sdk/js?client-id=' . $key . '&currency=' . $currency;

		wp_enqueue_script(
			'kaliforms-paypal',
			$url,
			false,
			null,
			true
		);
	}
	/**
	 * Verifies stuff, dies if something happens
	 *
	 * @param [type] $args
	 * @return void
	 */
	private function verify($args)
	{
		if (!$args) {
			$this->denied();
		}

		if (!isset($args['formId']) || absint($args['formId']) <= 0) {
			$this->denied();
		}

		if (!isset($args['nonce'])) {
			$this->denied();
		}

		if (!wp_verify_nonce($args['nonce'], 'kaliforms_nonce')) {
			$this->denied();
		}
	}
}
