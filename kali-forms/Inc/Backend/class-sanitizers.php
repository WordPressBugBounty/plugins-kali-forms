<?php

namespace KaliForms\Inc\Backend;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Sanitizers
 *
 * @package Inc\Libraries
 */
class Sanitizers
{
	/**
	 * Sanitizes the input
	 *
	 * @param $input
	 *
	 * @return mixed
	 */
	public static function sanitize_datetime($input)
	{
		return is_array($input) ? sanitize_text_field(implode('|', $input)) : sanitize_text_field($input);
	}
	/**
	 * Sanitize the field mapper
	 *
	 * @param [type] $props
	 * @return void
	 */
	public static function sanitize_field_mapper($props)
	{
		$obj  = new \stdClass();
		$json = self::decode_json_meta($props, false);

		if (! is_object($json) && ! is_array($json)) {
			return wp_json_encode($obj);
		}

		foreach ($json as $k => $v) {
			// Use esc_attr for keys to ensure they're safe for HTML attributes
			$safe_key = esc_attr(sanitize_text_field($k));
			$safe_value = esc_js(sanitize_text_field($v));

			$obj->{$safe_key} = $safe_value;
		}

		return wp_json_encode($obj);
	}
	/**
	 * Sanitize webhooks
	 *
	 * @param [type] $input
	 * @return void
	 */
	public static function sanitize_webhooks($input)
	{
		$sanitized = [];
		$json      = self::decode_json_meta($input, false);
		if (! is_array($json)) {
			return [];
		}

		$i = 0;
		foreach ($json as $hook) {
			$i++;
			$obj = new \stdClass();

			$obj->name           = empty($hook->name) ? __('WebHook', 'kali-forms') . ' #' . $i : sanitize_text_field($hook->name);
			$obj->event          = sanitize_text_field($hook->event);
			$obj->url            = esc_url($hook->url);
			$obj->authentication = sanitize_text_field($hook->authentication);
			$obj->method         = sanitize_text_field($hook->method);
			$obj->format         = sanitize_text_field($hook->format);
			$obj->headers        = [];
			$obj->body           = [];
			foreach ($hook->headers as $header) {
				$item        = new \stdClass();
				$item->key   = sanitize_text_field($header->key);
				$item->value = sanitize_text_field($header->value);

				$obj->headers[] = $item;
			}
			foreach ($hook->body as $body) {
				$item        = new \stdClass();
				$item->key   = sanitize_text_field($body->key);
				$item->value = sanitize_text_field($body->value);
				$obj->body[] = $item;
			}

			$conditions                   = new \stdClass();
			$conditions->conditionalLogic = sanitize_text_field($hook->conditions->conditionalLogic);
			$conditions->conditions       = [];

			foreach ($hook->conditions->conditions as $condition) {
				$conditionObj                   = new \stdClass();
				$conditionObj->conditionalIndex = absint($condition->conditionalIndex);
				$conditionObj->formField        = sanitize_text_field($condition->formField);
				$conditionObj->formFieldType    = sanitize_text_field($condition->formFieldType);
				$conditionObj->condition        = sanitize_text_field($condition->condition);
				$conditionObj->value            = sanitize_text_field($condition->value);

				$conditions->conditions[] = $conditionObj;
			}

			$obj->conditions = $conditions;
			$sanitized[]     = $obj;
		}

		return $sanitized;
	}
	/**
	 * Sanitizes a boolean field
	 *
	 * @param $input
	 *
	 * @return mixed
	 */
	public static function sanitize_boolean($input)
	{
		return filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	}
	/**
	 * Sanitize regular checkbox
	 *
	 * @param [type] $input
	 * @return void
	 */
	public static function sanitize_regular_checkbox($input)
	{
		return ($input === 'on' || $input === '1' || $input === 1) ? 1 : 0;
	}
	/**
	 * Sanitize secure options for MAIL
	 *
	 * @param [type] $input
	 * @return void
	 */
	public static function sanitize_secure_options($input)
	{
		$val     = sanitize_text_field($input);
		$allowed = ['None', 'SSL', 'TLS', 'STARTTLS'];

		return in_array($val, $allowed) ? $val : $allowed[0];
	}

	/**
	 * Sanitizes and hashes passwords
	 *
	 * @param [type] $input
	 * @return void
	 */
	public static function sanitize_and_hash_password($input)
	{
		return wp_hash_password($input);
	}

	/**
	 * JSON post meta keys that may have been corrupted by legacy slash escaping.
	 *
	 * @var string[]
	 */
	private static $json_meta_keys = [
		'kaliforms_field_components',
		'kaliforms_grid',
		'kaliforms_emails',
		'kaliforms_akismet_fields',
		'kaliforms_conditional_thank_you_message',
	];

	/**
	 * Prevent recursive meta repair while persisting a fixed value.
	 *
	 * @var array<string, bool>
	 */
	private static $repairing_meta = [];

	/**
	 * Register runtime hooks for JSON meta repair.
	 *
	 * @return void
	 */
	public static function register_hooks()
	{
		add_filter('get_post_metadata', [__CLASS__, 'filter_repair_json_post_meta'], 10, 4);
	}

	/**
	 * Repair corrupted JSON post meta on read and persist the cleaned value.
	 *
	 * @param mixed  $check      Short-circuit return value.
	 * @param int    $object_id  Post ID.
	 * @param string $meta_key   Meta key.
	 * @param bool   $single     Whether a single value was requested.
	 * @return mixed
	 */
	public static function filter_repair_json_post_meta($check, $object_id, $meta_key, $single)
	{
		if (null !== $check || ! $single) {
			return $check;
		}

		if (! in_array($meta_key, self::$json_meta_keys, true)) {
			return null;
		}

		if ('kaliforms_forms' !== get_post_type($object_id)) {
			return null;
		}

		$cache_key = $object_id . ':' . $meta_key;
		if (! empty(self::$repairing_meta[$cache_key])) {
			return null;
		}

		self::$repairing_meta[$cache_key] = true;

		$raw = get_metadata_raw('post', $object_id, $meta_key, true);
		if (! is_string($raw) || '' === trim($raw)) {
			unset(self::$repairing_meta[$cache_key]);
			return null;
		}

		$repair = self::repair_json_meta_string($raw);

		if (! $repair['valid']) {
			unset(self::$repairing_meta[$cache_key]);
			return null;
		}

		if ($repair['was_repaired']) {
			update_post_meta($object_id, $meta_key, $repair['canonical']);
			$stored = get_metadata_raw('post', $object_id, $meta_key, true);
			unset(self::$repairing_meta[$cache_key]);
			return is_string($stored) ? $stored : $repair['canonical'];
		}

		unset(self::$repairing_meta[$cache_key]);
		return null;
	}

	/**
	 * Attempt to recover JSON from over-escaped post meta strings.
	 *
	 * @param string $value Raw meta value.
	 * @return array{valid:bool,was_repaired:bool,canonical:string,decoded:mixed}
	 */
	public static function repair_json_meta_string($value)
	{
		$failure = [
			'valid'        => false,
			'was_repaired' => false,
			'canonical'    => '[]',
			'decoded'      => [],
		];

		if (! is_string($value) || '' === trim($value)) {
			return [
				'valid'        => true,
				'was_repaired' => false,
				'canonical'    => '[]',
				'decoded'      => [],
			];
		}

		$candidates = [$value];
		$unslashed  = wp_unslash($value);
		if ($unslashed !== $value) {
			$candidates[] = $unslashed;
		}

		foreach (array_unique($candidates) as $candidate) {
			$decoded = json_decode($candidate, true);
			if (JSON_ERROR_NONE !== json_last_error()) {
				continue;
			}

			$repaired     = self::repair_corrupted_unicode_escapes($decoded);
			$was_repaired = self::json_values_changed($decoded, $repaired) || $candidate !== $value;

			return [
				'valid'        => true,
				'was_repaired' => $was_repaired,
				'canonical'    => wp_json_encode($repaired),
				'decoded'      => $repaired,
			];
		}

		$repaired = self::iteratively_unescape_json_string($value, 'wp_unslash');
		if (null !== $repaired) {
			$repaired     = self::repair_corrupted_unicode_escapes($repaired);
			$was_repaired = true;

			return [
				'valid'        => true,
				'was_repaired' => $was_repaired,
				'canonical'    => wp_json_encode($repaired),
				'decoded'      => $repaired,
			];
		}

		$repaired = self::iteratively_unescape_json_string($value, 'collapse_slashes');
		if (null !== $repaired) {
			$repaired     = self::repair_corrupted_unicode_escapes($repaired);
			$was_repaired = true;

			return [
				'valid'        => true,
				'was_repaired' => $was_repaired,
				'canonical'    => wp_json_encode($repaired),
				'decoded'      => $repaired,
			];
		}

		return $failure;
	}

	/**
	 * Repair strings where JSON unicode escapes lost their backslash (e.g. Lu00e4 -> Lä).
	 *
	 * @param mixed $value Decoded JSON value.
	 * @return mixed
	 */
	public static function repair_corrupted_unicode_escapes($value)
	{
		if (is_string($value)) {
			if (! preg_match('/(?<!\\\\)u[0-9a-fA-F]{4}/', $value)) {
				return $value;
			}

			$repaired = preg_replace_callback(
				'/(?<!\\\\)u([0-9a-fA-F]{4})/',
				static function ($matches) {
					$decoded = json_decode('"\\u' . $matches[1] . '"');

					return is_string($decoded) ? $decoded : $matches[0];
				},
				$value
			);

			return is_string($repaired) ? $repaired : $value;
		}

		if (is_array($value)) {
			foreach ($value as $key => $item) {
				$value[$key] = self::repair_corrupted_unicode_escapes($item);
			}

			return $value;
		}

		if (is_object($value)) {
			foreach ($value as $key => $item) {
				$value->{$key} = self::repair_corrupted_unicode_escapes($item);
			}
		}

		return $value;
	}

	/**
	 * @param mixed $before Original decoded JSON.
	 * @param mixed $after  Repaired decoded JSON.
	 */
	private static function json_values_changed($before, $after): bool
	{
		return wp_json_encode($before) !== wp_json_encode($after);
	}

	/**
	 * Repeatedly unescape a JSON string until it decodes or stops changing.
	 *
	 * @param string $value  Raw value.
	 * @param string $method Unescape strategy.
	 * @return array|null
	 */
	private static function iteratively_unescape_json_string($value, $method)
	{
		$candidate = $value;

		for ($i = 0; $i < 25; $i++) {
			$decoded = json_decode($candidate, true);
			if (JSON_ERROR_NONE === json_last_error()) {
				return $decoded;
			}

			if ('stripslashes' === $method) {
				$next = stripslashes($candidate);
			} elseif ('wp_unslash' === $method) {
				$next = wp_unslash($candidate);
			} else {
				$next = str_replace('\\\\', '\\', $candidate);
			}

			if ($next === $candidate) {
				break;
			}

			$candidate = $next;
		}

		return null;
	}

	/**
	 * Decode JSON stored in post meta, tolerating legacy slash-escaping.
	 *
	 * @param mixed $value   Raw meta value.
	 * @param bool  $assoc   json_decode associative flag.
	 * @param int   $depth   json_decode depth.
	 * @param int   $flags   json_decode flags.
	 * @return mixed
	 */
	public static function decode_json_meta($value, $assoc = false, $depth = 512, $flags = 0)
	{
		if (is_array($value)) {
			return $value;
		}

		if (! is_string($value) || '' === trim($value)) {
			return [];
		}

		$repair = self::repair_json_meta_string($value);
		if (! $repair['valid']) {
			return [];
		}

		$decoded = json_decode($repair['canonical'], $assoc, $depth, $flags);
		if (JSON_ERROR_NONE === json_last_error()) {
			return $decoded;
		}

		return [];
	}

	/**
	 * @param $input
	 *
	 * @return false|string
	 */
	public static function sanitize_grid_layout($input)
	{
		$input = self::decode_json_meta($input);
		if (! is_array($input)) {
			return wp_json_encode([]);
		}

		$sanitized = [];
		foreach ($input as $item) {
			$grid = Sanitizers::sanitize_grid_item($item);

			$sanitized[] = $grid;
		}

		usort($sanitized, ['KaliForms\Inc\Backend\Sanitizers', 'sort_by_row']);
		return wp_json_encode($sanitized);
	}
	/**
	 * Sort stuff by index
	 *
	 * @param [type] $a
	 * @param [type] $b
	 * @return void
	 */
	public static function sort_by_row($a, $b)
	{
		return strnatcmp($a->y, $b->y);
	}

	/**
	 * @param $item
	 *
	 * @return \stdClass
	 */
	public static function sanitize_grid_item($item)
	{
		$grid_item         = new \stdClass();
		$grid_item->h      = absint($item->h);
		$grid_item->i      = sanitize_key($item->i);
		$grid_item->maxH   = absint($item->maxH);
		$grid_item->minW   = absint($item->minW);
		$grid_item->moved  = Sanitizers::sanitize_boolean($item->moved);
		$grid_item->static = Sanitizers::sanitize_boolean($item->static);
		$grid_item->w      = absint($item->w);
		$grid_item->x      = absint($item->x);
		$grid_item->y      = absint($item->y);

		return $grid_item;
	}

	/**
	 * @param $input
	 *
	 * @return false|string
	 */
	public static function sanitize_field_components($input)
	{
		$input = self::decode_json_meta($input);
		if (! is_array($input)) {
			return wp_json_encode([]);
		}

		$sanitized = [];
		foreach ($input as $field) {
			$sanitized[] = Sanitizers::sanitize_field_component($field);
		}

		return wp_json_encode($sanitized, JSON_HEX_QUOT);
	}

	/**
	 * @param $item
	 *
	 * @return \stdClass
	 */
	public static function sanitize_field_component($item)
	{
		$fieldItem             = new \stdClass();
		$fieldItem->id         = sanitize_text_field($item->id);
		$fieldItem->internalId = sanitize_key($item->internalId);
		$fieldItem->label      = sanitize_text_field($item->label);
		$fieldItem->properties = Sanitizers::sanitize_properties_object($item->properties, $item->id);
		$fieldItem->constraint = (empty($item->constraint) || 'none' === $item->constraint) ? 'none' : absint($item->constraint);
		return $fieldItem;
	}

	/**
	 * @param $item
	 *
	 * @return \stdClass
	 */
	public static function sanitize_properties_object($item, $id)
	{
		$props = new \stdClass();
		foreach ($item as $k => $v) {
			if ($k === 'price') {
				$props->{sanitize_text_field($k)} = str_replace(',', '', number_format(floatval(str_replace(',', '.', $v)), 2));
				continue;
			}
			if ($k === 'products') {
				$sanitized_data = [];
				foreach ($v as $product) {
					$sanitized_product = [
						'id'    => sanitize_key($product->id),
						'label' => sanitize_text_field($product->label),
						'price' => str_replace(',', '', number_format(floatval(str_replace(',', '.', $product->price)), 2)),
						'image' => Sanitizers::sanitize_object($product->image),
					];

					$sanitized_data[] = $sanitized_product;
				}

				$props->{sanitize_text_field($k)} = $sanitized_data;
				continue;
			}
			if ($id === 'imageRadio' && $k === 'choices') {
				$sanitized_data = [];
				foreach ($v as $r_item) {
					$sanitized_r_item = [
						'label'   => sanitize_text_field($r_item->label),
						'caption' => sanitize_text_field($r_item->caption),
						'image'   => Sanitizers::sanitize_object($r_item->image),
					];

					$sanitized_data[] = $sanitized_r_item;
				}
				$props->{sanitize_text_field($k)} = $sanitized_data;
				continue;
			}
			if ($k === 'content') {
				$props->{sanitize_text_field($k)} = wp_kses_post($v);
				continue;
			}
			if ($k === 'name' || $k === 'id') {
				if (empty($v)) {
					$v = $id . substr(md5($id . wp_rand(15, 50)), 0, 3);
				}

				$props->{sanitize_text_field($k)} = sanitize_text_field($v);
				continue;
			}

			$props->{sanitize_text_field($k)} = is_string($v)
				? sanitize_text_field($v)
				: Sanitizers::sanitize_unknown($v);
		}
		return $props;
	}

	/**
	 * When we're deep inside props, we dont really know what
	 * we're sanitizing so lets make sure everything is going
	 * according to plan
	 *
	 * @param [any] $value
	 * @return {*}
	 */
	public static function sanitize_unknown($value)
	{
		switch (gettype($value)) {
			case 'array':
				$value = Sanitizers::sanitize_array_with_props($value);
				break;
			case 'boolean':
				$value = Sanitizers::sanitize_boolean($value);
				break;
			case 'object':
				$value = Sanitizers::sanitize_object($value);
				break;
			default:
				$value = sanitize_text_field($value);
				break;
		}

		return $value;
	}

	/**
	 * Sanitizes the array
	 *
	 * @param [Array] $value
	 * @return array
	 */
	public static function sanitize_array_with_props($value)
	{
		$sanitized = [];

		if (!is_array($value)) {
			return false;
		}

		foreach ($value as $item) {
			$obj = new \stdClass();

			foreach ($item as $k => $v) {
				$safe_key = esc_attr(sanitize_text_field($k));
				$safe_value = esc_js(sanitize_text_field($v));

				$obj->{$safe_key} = $safe_value;
			}

			$sanitized[] = $obj;
		}

		return $sanitized;
	}
	/**
	 * Sanitizes the object
	 *
	 * @param [stdClass] $value
	 * @return array
	 */
	public static function sanitize_object($value)
	{
		if (!is_object($value)) {
			return false;
		}

		$obj = new \stdClass();
		foreach ((array) $value as $k => $v) {
			$safe_key = esc_attr(sanitize_text_field($k));
			$safe_value = esc_js(sanitize_text_field($v));

			$obj->{$safe_key} = $safe_value;
		}

		return $obj;
	}

	/**
	 * Sanitizer for the email builder
	 *
	 * @param [Array] $value
	 * @return void
	 */
	public static function sanitize_email_builder($value)
	{
		$value     = self::decode_json_meta($value, false);
		$sanitized = [];
		if (! is_array($value)) {
			return false;
		}

		foreach ($value as $item) {
			$obj = new \stdClass();
			foreach ($item as $k => $v) {
				if ($k === 'emailContentType') {
					$obj->{sanitize_text_field($k)} = in_array($v, ['plain', 'html'], true) ? $v : 'html';
					continue;
				}
				if ($k === 'emailBody') {
					if (isset($item->emailContentType) && $item->emailContentType === 'plain') {
						$obj->{sanitize_text_field($k)} = sanitize_textarea_field($v);
						continue;
					}
					if (isset($item->saveAsHtml) && $item->saveAsHtml) {
						$obj->{sanitize_text_field($k)} = wp_kses_post($v);
						continue;
					}

					$obj->{sanitize_text_field($k)} = wp_kses_post(str_replace(["\n", "\r"], '', $v));
					continue;
				}
				if ($k === 'emailAttachmentMediaIds') {
					$obj->{sanitize_text_field($k)} = is_string($v)
						? Sanitizers::rewrite_value_to_new_type($v)
						: Sanitizers::sanitize_new_media_type($v);

					continue;
				}
				$obj->{sanitize_text_field($k)} = sanitize_text_field($v);
			}

			$cleaned = array_filter(get_object_vars($obj));
			if (!empty($cleaned) && count($cleaned) > 1) {
				$sanitized[] = $obj;
			}
		}

		return wp_json_encode($sanitized, JSON_HEX_QUOT);
	}

	/**
	 * This function will re-write the value from the email media attachments
	 *
	 * @param [type] $val
	 * @return void
	 */
	public static function rewrite_value_to_new_type($val)
	{
		$attachments = explode(',', $val);
		$sanitized   = [];
		foreach ($attachments as $id) {
			if (empty($id)) {
				continue;
			}
			$obj          = new \stdClass();
			$prev         = wp_get_attachment_image_src($id, 'form-edit-image-preview');
			$obj->id      = $id;
			$obj->fullUrl = wp_get_attachment_url($id);
			$obj->preview = isset($prev[0]) ? $prev[0] : '';

			$sanitized[] = $obj;
		}

		return $sanitized;
	}

	/**
	 * Sanitize the email media attachments
	 *
	 * @param [type] $val
	 * @return void
	 */
	public static function sanitize_new_media_type($val)
	{
		$sanitized = [];
		foreach ($val as $idx => $media) {
			$sanitized[$idx] = new \stdClass();
			foreach ($media as $k => $v) {
				$sanitized[$idx]->{$k} = sanitize_text_field($v);
			}
		}

		return $sanitized;
	}
}
