<?php

namespace KaliForms\Inc\Utils;

if (!defined('ABSPATH')) {
	exit;
}

class Digital_Signature_Helper
{
	/**
	 * Allowed base64-encoded signature data URI pattern.
	 */
	private const DATA_URI_PATTERN = '/^data:image\/(?:png|jpe?g);base64,[A-Za-z0-9+\/=]+$/';

	/**
	 * Whether a value is a valid signature data URI.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public static function is_valid_data_uri($value)
	{
		return is_string($value) && preg_match(self::DATA_URI_PATTERN, $value) === 1;
	}

	/**
	 * Render a signature image tag from a stored value.
	 *
	 * @param mixed  $value Stored field value.
	 * @param string $width CSS width for the image.
	 * @return string
	 */
	public static function render_image_tag($value, $width = '350px')
	{
		if (self::is_valid_data_uri($value)) {
			return '<img style="width:' . esc_attr($width) . '" src="' . esc_attr($value) . '" />';
		}

		$attachment_id = absint($value);
		if ($attachment_id > 0) {
			$img = wp_get_attachment_url($attachment_id);
			if ($img) {
				return '<img style="width:' . esc_attr($width) . '" src="' . esc_url($img) . '" />';
			}
		}

		return '';
	}
}
