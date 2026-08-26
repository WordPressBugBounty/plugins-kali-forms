<?php

namespace KaliForms\Inc\Utils;

use KaliForms\Inc\Backend\Sanitizers;
use KaliForms\Inc\Utils\MetaHelper;

if (!defined('ABSPATH')) {
	exit;
}

class Payments_Action_Helper
{
	/**
	 * Metahelper trait
	 */
	use MetaHelper;

	/**
	 * Parent plugin slug
	 *
	 * @var string
	 */
	public $slug = 'kaliforms';
	/**
	 * Current post
	 *
	 * @var [type]
	 */
	public $post = null;
	/**
	 * Class constructor
	 *
	 * @param [type] $formId
	 */
	public function __construct($formId)
	{
		$this->form = $formId;
		if ($this->form === null) {
			return new \WP_Error();
		}
	}

	/**
	 * Whether the ID is a published Kali Forms form.
	 *
	 * @param int $form_id Form post ID.
	 * @return bool
	 */
	public static function is_published_kali_form($form_id)
	{
		return Paypal_Order_Verifier::is_published_kali_form($form_id);
	}

	/**
	 * Get payment data
	 *
	 * @return void
	 */
	public function get_payment_data($args)
	{
		$this->post   = get_post($args['formId']);
		$paymentsLive = $this->get('payments_live', false);
		$currency     = $this->get('currency', 'USD');
		$form_data    = isset($args['formData']) && is_array($args['formData']) ? $args['formData'] : [];
		$foreachRun   = $this->foreachFieldsToProducts($form_data);
		$email        = false;
		$description  = [];

		foreach ($foreachRun['products'] as $product) {
			$description[] = $product['type'] === 'multipleProducts' ? $product['variant'] : $product['caption'];
		}

		if ($foreachRun['email'] && isset($args['formData'][$foreachRun['email']])) {
			$email = $args['formData'][$foreachRun['email']];
		}

		return [
			'email'       => $email,
			'currency'    => $currency,
			'live'        => $paymentsLive,
			'amount'      => $foreachRun['total'],
			'products'    => $foreachRun['products'],
			'description' => $description,
		];
	}

	/**
	 * Catalog total, currency and merchant email for a submission.
	 *
	 * @param array $form_data Submitted field values.
	 * @return array
	 */
	public function get_expected_charge($form_data = [])
	{
		if ($this->post === null && isset($this->form)) {
			$this->post = get_post($this->form);
		}

		$foreachRun = $this->foreachFieldsToProducts(is_array($form_data) ? $form_data : []);
		$currency   = $this->get('currency', 'USD');
		if ($currency === null || $currency === '') {
			$currency = 'USD';
		}

		return [
			'amount'   => $foreachRun['total'],
			'currency' => $currency,
			'payee'    => $foreachRun['payee'],
		];
	}

	/**
	 * Get stripe data
	 *
	 * @return void
	 */
	public function get_stripe_data($args)
	{
		$this->post = get_post($args['formId']);

		if ($this->post === null) {
			return new \WP_Error(500, esc_html__('There is no form associated with this id. Make sure you copied it correctly', 'kali-forms'));
		}

		$fields       = json_decode($this->get('stripe_fields', '{}'), false, 512, JSON_HEX_QUOT);
		$paymentsLive = $this->get('payments_live', false);
		$currency     = $this->get('currency', 'USD');
		$filtered     = [];

		foreach ($fields as $k => $v) {
			if ($v !== '' && $v !== 'empty') {
				$filtered[$k] = $v;
			};
		}

		$form_data  = isset($args['formData']) && is_array($args['formData']) ? $args['formData'] : [];
		$foreachRun = $this->foreachFieldsToProducts($form_data);
		return [
			'fields'   => $filtered,
			'currency' => $currency,
			'amount'   => $foreachRun['total'],
			'products' => $foreachRun['products'],
			's_key'    => $paymentsLive ? $this->get('stripe_s_key_live', '') : $this->get('stripe_s_key', ''),
		];
	}

	/**
	 * Verify products from the database
	 * @return void
	 */
	public function get_products($args)
	{
		$form_id = isset($args['formId']) ? absint($args['formId']) : 0;
		if (!self::is_published_kali_form($form_id)) {
			return new \WP_Error(500, esc_html__('There is no form associated with this id. Make sure you copied it correctly', 'kali-forms'));
		}

		$this->post = get_post($form_id);

		$foreachRun = $this->foreachFieldsToProducts();
		if ($foreachRun['payee']) {
			foreach ($foreachRun['products'] as $i => $product) {
				$foreachRun['products'][$i]['payee'] = $foreachRun['payee'];
			}
		}

		return wp_json_encode(
			[
				'response' => $foreachRun['products'],
			]
		);
	}

	/**
	 * Get products from fields
	 *
	 * @param array $form_data Optional submitted values so donations and selected variants are included in the total.
	 * @return array
	 */
	public function foreachFieldsToProducts($form_data = [])
	{
		$fields          = Sanitizers::decode_json_meta($this->get('field_components', '[]'), false);
		$products        = [];
		$payee           = false;
		$total           = 0;
		$emailFieldFound = false;
		$currency        = $this->get('currency', 'USD');
		if (!is_array($fields)) {
			$fields = [];
		}
		if (!is_array($form_data)) {
			$form_data = [];
		}
		foreach ($fields as $k => $v) {
			if (!is_object($v) || !isset($v->id)) {
				continue;
			}
			if ($v->id === 'email' && isset($v->properties->name)) {
				$emailFieldFound = $v->properties->name;
			}
			if ($v->id === 'paypal' && !empty($v->properties->merchantEmail)) {
				$payee = $v->properties->merchantEmail;
			}
			if ($v->id === 'donation') {
				$products[] = [
					'type'         => $v->id,
					'id'           => $v->properties->id,
					'internalId'   => $v->internalId,
					'name'         => $v->properties->name,
					'price'        => '',
					'caption'      => $v->properties->donationName,
					'description'  => $v->properties->description,
					'donationType' => $v->properties->donationType,
					'choices'      => $v->properties->choices,
					'currency'     => $currency,
				];
				$total += $this->donation_amount_from_submission($v, $form_data);
			}
			if ($v->id === 'product') {
				$products[] = [
					'type'        => $v->id,
					'internalId'  => $v->internalId,
					'id'          => $v->properties->id,
					'price'       => $v->properties->price,
					'caption'     => $v->properties->caption,
					'description' => $v->properties->description,
					'currency'    => $currency,
				];
				$total += floatval($v->properties->price);
			}
			if ($v->id === 'multipleProducts') {
				foreach ($v->properties->products as $deepK => $deepV) {
					$products[] = [
						'type'        => $v->id,
						'internalId'  => $v->internalId,
						'parentId'    => $v->properties->id,
						'name'        => $v->properties->name,
						'id'          => $deepV->id,
						'price'       => $deepV->price,
						'caption'     => $v->properties->caption,
						'variant'     => $deepV->label,
						'description' => $v->properties->description,
						'currency'    => $currency,
					];
				}
				$total += $this->selected_multiple_product_amount($v, $form_data);
			}
		}
		return ['payee' => $payee, 'products' => $products, 'total' => $total, 'email' => $emailFieldFound];
	}

	/**
	 * Donation amount from submitted field values (user-chosen by design).
	 *
	 * @param object $field     Donation field.
	 * @param array  $form_data Submitted values.
	 * @return float
	 */
	private function donation_amount_from_submission($field, array $form_data)
	{
		if (empty($form_data) || empty($field->properties->name)) {
			return 0;
		}

		$name = $field->properties->name;
		if (!isset($form_data[$name])) {
			return 0;
		}

		$submitted = $form_data[$name];
		if (is_array($submitted)) {
			$submitted = reset($submitted);
		}
		if (!is_string($submitted) && !is_numeric($submitted)) {
			return 0;
		}

		$amount = floatval($submitted);
		if ($amount <= 0) {
			return 0;
		}

		$donation_type = isset($field->properties->donationType) ? $field->properties->donationType : 'custom';
		if ($donation_type === 'custom') {
			return $amount;
		}

		if (empty($field->properties->choices)) {
			return 0;
		}

		foreach ($field->properties->choices as $choice) {
			$choice_value = is_object($choice) && isset($choice->value) ? $choice->value : $choice;
			if ((string) $choice_value === (string) $submitted) {
				return floatval($choice_value);
			}
		}

		return 0;
	}

	/**
	 * Selected multiple-product variant price from the catalog (never from client-supplied prices).
	 *
	 * @param object $field     Multiple products field.
	 * @param array  $form_data Submitted values.
	 * @return float
	 */
	private function selected_multiple_product_amount($field, array $form_data)
	{
		if (empty($form_data) || empty($field->properties->name) || empty($field->properties->products)) {
			return 0;
		}

		$name = $field->properties->name;
		if (!isset($form_data[$name])) {
			return 0;
		}

		$selected = $form_data[$name];
		if (is_array($selected)) {
			$selected = reset($selected);
		}

		foreach ($field->properties->products as $variant) {
			if (isset($variant->id) && (string) $variant->id === (string) $selected) {
				return floatval($variant->price);
			}
		}

		return 0;
	}
}
