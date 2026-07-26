<?php

namespace KaliForms\Inc\Backend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sanitizers
 *
 * @package Inc\Libraries
 */
class Sanitizers {

	/**
	 * Sanitizes the input
	 *
	 * @param $input
	 *
	 * @return mixed
	 */
	public static function sanitize_datetime( $input ) {
		return is_array( $input ) ? sanitize_text_field( implode( '|', $input ) ) : sanitize_text_field( $input );
	}
	/**
	 * Sanitize the field mapper
	 *
	 * @param [type] $props
	 * @return void
	 */
	public static function sanitize_field_mapper( $props ) {
		$obj  = new \stdClass();
		$json = self::decode_json_meta( $props, false );

		if ( ! is_object( $json ) && ! is_array( $json ) ) {
			return wp_json_encode( $obj );
		}

		foreach ( $json as $k => $v ) {
			// Use esc_attr for keys to ensure they're safe for HTML attributes
			$safe_key   = esc_attr( sanitize_text_field( $k ) );
			$safe_value = esc_js( sanitize_text_field( $v ) );

			$obj->{$safe_key} = $safe_value;
		}

		return wp_json_encode( $obj );
	}
	/**
	 * Sanitize webhooks
	 *
	 * @param [type] $input
	 * @return void
	 */
	public static function sanitize_webhooks( $input ) {
		$sanitized = array();
		$json      = self::decode_json_meta( $input, false );
		if ( ! is_array( $json ) ) {
			return array();
		}

		$i = 0;
		foreach ( $json as $hook ) {
			++$i;
			$obj = new \stdClass();

			$obj->name           = empty( $hook->name ) ? __( 'WebHook', 'kali-forms' ) . ' #' . $i : sanitize_text_field( $hook->name );
			$obj->event          = sanitize_text_field( $hook->event );
			$obj->url            = esc_url( $hook->url );
			$obj->authentication = sanitize_text_field( $hook->authentication );
			$obj->method         = sanitize_text_field( $hook->method );
			$obj->format         = sanitize_text_field( $hook->format );
			$obj->headers        = array();
			$obj->body           = array();
			foreach ( $hook->headers as $header ) {
				$item        = new \stdClass();
				$item->key   = sanitize_text_field( $header->key );
				$item->value = sanitize_text_field( $header->value );

				$obj->headers[] = $item;
			}
			foreach ( $hook->body as $body ) {
				$item        = new \stdClass();
				$item->key   = sanitize_text_field( $body->key );
				$item->value = sanitize_text_field( $body->value );
				$obj->body[] = $item;
			}

			$conditions                   = new \stdClass();
			$conditions->conditionalLogic = sanitize_text_field( $hook->conditions->conditionalLogic );
			$conditions->conditions       = array();

			foreach ( $hook->conditions->conditions as $condition ) {
				$conditionObj                   = new \stdClass();
				$conditionObj->conditionalIndex = absint( $condition->conditionalIndex );
				$conditionObj->formField        = sanitize_text_field( $condition->formField );
				$conditionObj->formFieldType    = sanitize_text_field( $condition->formFieldType );
				$conditionObj->condition        = sanitize_text_field( $condition->condition );
				$conditionObj->value            = sanitize_text_field( $condition->value );

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
	public static function sanitize_boolean( $input ) {
		return filter_var( $input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
	}
	/**
	 * Sanitize regular checkbox
	 *
	 * @param [type] $input
	 * @return void
	 */
	public static function sanitize_regular_checkbox( $input ) {
		return ( $input === 'on' || $input === '1' || $input === 1 ) ? 1 : 0;
	}
	/**
	 * Sanitize secure options for MAIL
	 *
	 * @param [type] $input
	 * @return void
	 */
	public static function sanitize_secure_options( $input ) {
		$val     = sanitize_text_field( $input );
		$allowed = array( 'None', 'SSL', 'TLS', 'STARTTLS' );

		return in_array( $val, $allowed ) ? $val : $allowed[0];
	}

	/**
	 * Sanitizes and hashes passwords
	 *
	 * @param [type] $input
	 * @return void
	 */
	public static function sanitize_and_hash_password( $input ) {
		return wp_hash_password( $input );
	}

	/**
	 * JSON post meta keys that may have been corrupted by legacy slash escaping.
	 *
	 * @var string[]
	 */
	private static $json_meta_keys = array(
		'kaliforms_field_components',
		'kaliforms_grid',
		'kaliforms_emails',
		'kaliforms_akismet_fields',
		'kaliforms_conditional_thank_you_message',
	);

	/**
	 * Prevent recursive meta repair while persisting a fixed value.
	 *
	 * @var array<string, bool>
	 */
	private static $repairing_meta = array();

	/**
	 * Register runtime hooks for JSON meta repair and wipe protection.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'get_post_metadata', array( __CLASS__, 'filter_repair_json_post_meta' ), 10, 4 );
		add_filter( 'update_post_metadata', array( __CLASS__, 'filter_prevent_json_meta_wipe' ), 10, 5 );
	}

	/**
	 * Block updates that would replace non-empty form JSON with empty JSON.
	 *
	 * @param null|bool $check      Whether to allow updating metadata.
	 * @param int       $object_id  Post ID.
	 * @param string    $meta_key   Meta key.
	 * @param mixed     $meta_value Meta value.
	 * @param mixed     $prev_value Previous value.
	 * @return null|bool
	 */
	public static function filter_prevent_json_meta_wipe( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( ! in_array( $meta_key, self::$json_meta_keys, true ) ) {
			return $check;
		}

		if ( self::is_meaningful_json_meta( $meta_value ) ) {
			return $check;
		}

		if ( ! function_exists( 'get_post_type' ) || 'kaliforms_forms' !== get_post_type( $object_id ) ) {
			return $check;
		}

		$existing = function_exists( 'get_metadata_raw' )
			? get_metadata_raw( 'post', $object_id, $meta_key, true )
			: get_post_meta( $object_id, $meta_key, true );

		if ( self::is_meaningful_json_meta( $existing ) ) {
			// Short-circuit: keep existing meta, do not write empty JSON.
			return false;
		}

		return $check;
	}

	/**
	 * Optionally repair corrupted JSON post meta on read (in-memory only).
	 *
	 * @param mixed  $check      Short-circuit return value.
	 * @param int    $object_id  Post ID.
	 * @param string $meta_key   Meta key.
	 * @param bool   $single     Whether a single value was requested.
	 * @return mixed
	 */
	public static function filter_repair_json_post_meta( $check, $object_id, $meta_key, $single ) {
		if ( null !== $check || ! $single ) {
			return $check;
		}

		if ( ! in_array( $meta_key, self::$json_meta_keys, true ) ) {
			return null;
		}

		if ( ! function_exists( 'get_post_type' ) || 'kaliforms_forms' !== get_post_type( $object_id ) ) {
			return null;
		}

		$cache_key = $object_id . ':' . $meta_key;
		if ( ! empty( self::$repairing_meta[ $cache_key ] ) ) {
			return null;
		}

		self::$repairing_meta[ $cache_key ] = true;

		try {
			if ( ! function_exists( 'get_metadata_raw' ) ) {
				unset( self::$repairing_meta[ $cache_key ] );
				return null;
			}

			$raw = get_metadata_raw( 'post', $object_id, $meta_key, true );
			if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
				unset( self::$repairing_meta[ $cache_key ] );
				return null;
			}

			$repair = self::repair_json_meta_string( $raw );

			unset( self::$repairing_meta[ $cache_key ] );

			// Never persist from a read filter — a bad repair previously wiped forms
			// (e.g. "Quebec" matched as unicode "uebec"). Only return an in-memory fix.
			if ( ! is_array( $repair ) || empty( $repair['valid'] ) || empty( $repair['was_repaired'] ) ) {
				return null;
			}

			if ( empty( $repair['canonical'] ) || ! is_string( $repair['canonical'] ) ) {
				return null;
			}

			return $repair['canonical'];
		} catch ( \Exception $e ) {
			unset( self::$repairing_meta[ $cache_key ] );
			return null;
		}
	}

	/**
	 * Attempt to recover JSON from over-escaped post meta strings.
	 *
	 * @param string $value Raw meta value.
	 * @return array{valid:bool,was_repaired:bool,canonical:string,decoded:mixed}
	 */
	public static function repair_json_meta_string( $value ) {
		$failure = array(
			'valid'        => false,
			'was_repaired' => false,
			'canonical'    => '[]',
			'decoded'      => array(),
		);

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return array(
				'valid'        => true,
				'was_repaired' => false,
				'canonical'    => '[]',
				'decoded'      => array(),
			);
		}

		$candidates = array( $value );
		$unslashed  = wp_unslash( $value );
		if ( $unslashed !== $value ) {
			$candidates[] = $unslashed;
		}

		foreach ( array_unique( $candidates ) as $candidate ) {
			$decoded = json_decode( $candidate, true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				continue;
			}

			$repaired     = self::repair_corrupted_unicode_escapes( $decoded );
			$was_repaired = self::json_values_changed( $decoded, $repaired );

			return array(
				'valid'        => true,
				'was_repaired' => $was_repaired,
				'canonical'    => wp_json_encode( $repaired ),
				'decoded'      => $repaired,
			);
		}

		$repaired = self::iteratively_unescape_json_string( $value, 'wp_unslash' );
		if ( null !== $repaired ) {
			$repaired     = self::repair_corrupted_unicode_escapes( $repaired );
			$was_repaired = true;

			return array(
				'valid'        => true,
				'was_repaired' => $was_repaired,
				'canonical'    => wp_json_encode( $repaired ),
				'decoded'      => $repaired,
			);
		}

		$repaired = self::iteratively_unescape_json_string( $value, 'collapse_slashes' );
		if ( null !== $repaired ) {
			$repaired     = self::repair_corrupted_unicode_escapes( $repaired );
			$was_repaired = true;

			return array(
				'valid'        => true,
				'was_repaired' => $was_repaired,
				'canonical'    => wp_json_encode( $repaired ),
				'decoded'      => $repaired,
			);
		}

		return $failure;
	}

	/**
	 * Repair strings where JSON unicode escapes lost their backslash (e.g. Lu00e4 -> Lä).
	 *
	 * Only accepts likely legacy escapes (Latin-1 / common punctuation). Broad matching
	 * previously corrupted real words like "Quebec" (matched "uebec" as U+EBEC).
	 *
	 * @param mixed $value Decoded JSON value.
	 * @return mixed
	 */
	public static function repair_corrupted_unicode_escapes( $value ) {
		if ( is_string( $value ) ) {
			if ( ! preg_match( '/(?<!\\\\)u(?:00[0-9a-fA-F]{2}|201[0-9a-fA-F])/', $value ) ) {
				return $value;
			}

			$repaired = preg_replace_callback(
				'/(?<!\\\\)u(00[0-9a-fA-F]{2}|201[0-9a-fA-F])/',
				static function ( $matches ) {
					$decoded = json_decode( '"\\u' . $matches[1] . '"' );
					if ( ! is_string( $decoded ) || $decoded === '' ) {
						return $matches[0];
					}

					$codepoint = self::unicode_codepoint( $decoded );
					if ( null === $codepoint || ! self::is_plausible_repaired_codepoint( $codepoint ) ) {
						return $matches[0];
					}

					return $decoded;
				},
				$value
			);

			return is_string( $repaired ) ? $repaired : $value;
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::repair_corrupted_unicode_escapes( $item );
			}

			return $value;
		}

		if ( is_object( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value->{$key} = self::repair_corrupted_unicode_escapes( $item );
			}
		}

		return $value;
	}

	/**
	 * @param string $char Single UTF-8 character.
	 * @return int|null
	 */
	private static function unicode_codepoint( $char ) {
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$ord = unpack( 'N', mb_convert_encoding( $char, 'UCS-4BE', 'UTF-8' ) );
			if ( is_array( $ord ) && isset( $ord[1] ) ) {
				return (int) $ord[1];
			}
		}

		$bytes = unpack( 'C*', $char );
		if ( ! is_array( $bytes ) || empty( $bytes ) ) {
			return null;
		}

		$bytes = array_values( $bytes );
		$b0    = $bytes[0];
		if ( $b0 < 0x80 ) {
			return $b0;
		}
		if ( $b0 < 0xE0 && isset( $bytes[1] ) ) {
			return ( ( $b0 & 0x1F ) << 6 ) | ( $bytes[1] & 0x3F );
		}
		if ( $b0 < 0xF0 && isset( $bytes[1], $bytes[2] ) ) {
			return ( ( $b0 & 0x0F ) << 12 ) | ( ( $bytes[1] & 0x3F ) << 6 ) | ( $bytes[2] & 0x3F );
		}

		return null;
	}

	/**
	 * Whether a decoded codepoint is a plausible form-text character.
	 *
	 * @param int $codepoint
	 * @return bool
	 */
	private static function is_plausible_repaired_codepoint( $codepoint ) {
		// Latin-1 supplement (umlauts, ß, etc.)
		if ( $codepoint >= 0x00A0 && $codepoint <= 0x00FF ) {
			return true;
		}

		// Common general punctuation used in captions (curly quotes, dashes)
		if ( $codepoint >= 0x2010 && $codepoint <= 0x2027 ) {
			return true;
		}

		return false;
	}

	/**
	 * @param mixed $before Original decoded JSON.
	 * @param mixed $after  Repaired decoded JSON.
	 */
	private static function json_values_changed( $before, $after ): bool {
		return wp_json_encode( $before ) !== wp_json_encode( $after );
	}

	/**
	 * Repeatedly unescape a JSON string until it decodes or stops changing.
	 *
	 * @param string $value  Raw value.
	 * @param string $method Unescape strategy.
	 * @return array|null
	 */
	private static function iteratively_unescape_json_string( $value, $method ) {
		$candidate = $value;

		for ( $i = 0; $i < 25; $i++ ) {
			$decoded = json_decode( $candidate, true );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				return $decoded;
			}

			if ( 'stripslashes' === $method ) {
				$next = stripslashes( $candidate );
			} elseif ( 'wp_unslash' === $method ) {
				$next = wp_unslash( $candidate );
			} else {
				$next = str_replace( '\\\\', '\\', $candidate );
			}

			if ( $next === $candidate ) {
				break;
			}

			$candidate = $next;
		}

		return null;
	}

	/**
	 * Decode JSON stored in post meta, tolerating legacy slash-escaping.
	 *
	 * On decode failure returns an empty array. Callers that must not wipe
	 * existing form data should check is_meaningful_json_meta() on the original
	 * input before treating an empty result as intentional.
	 *
	 * @param mixed $value   Raw meta value.
	 * @param bool  $assoc   json_decode associative flag.
	 * @param int   $depth   json_decode depth.
	 * @param int   $flags   json_decode flags.
	 * @return mixed
	 */
	public static function decode_json_meta( $value, $assoc = false, $depth = 512, $flags = 0 ) {
		if ( is_array( $value ) ) {
			return $value;
		}

		if ( is_object( $value ) ) {
			return $assoc ? (array) $value : $value;
		}

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return array();
		}

		$repair = self::repair_json_meta_string( $value );
		if ( empty( $repair['valid'] ) ) {
			return array();
		}

		$decoded = json_decode( $repair['canonical'], $assoc, $depth, $flags );
		if ( JSON_ERROR_NONE === json_last_error() ) {
			return $decoded;
		}

		return array();
	}

	/**
	 * Whether a JSON meta string represents a meaningful (non-empty) form definition.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	public static function is_meaningful_json_meta( $value ) {
		if ( is_array( $value ) ) {
			return ! empty( $value );
		}

		if ( ! is_string( $value ) ) {
			return false;
		}

		$trimmed = trim( $value );
		if ( '' === $trimmed || '[]' === $trimmed || '{}' === $trimmed || 'null' === $trimmed ) {
			return false;
		}

		return true;
	}

	/**
	 * Preserve the original payload when sanitization would wipe meaningful data.
	 *
	 * @param mixed  $original Original input.
	 * @param string $fallback Encoded fallback (usually "[]").
	 * @return string
	 */
	private static function preserve_or_fallback( $original, $fallback = '[]' ) {
		if ( is_string( $original ) && self::is_meaningful_json_meta( $original ) ) {
			return $original;
		}

		return $fallback;
	}

	/**
	 * @param $input
	 *
	 * @return false|string
	 */
	public static function sanitize_grid_layout( $input ) {
		$original = $input;
		$decoded  = self::decode_json_meta( $input );

		if ( ! is_array( $decoded ) ) {
			return self::preserve_or_fallback( $original );
		}

		if ( empty( $decoded ) && self::is_meaningful_json_meta( $original ) ) {
			return self::preserve_or_fallback( $original );
		}

		$sanitized = array();
		foreach ( $decoded as $item ) {
			if ( ! is_object( $item ) ) {
				continue;
			}
			$sanitized[] = Sanitizers::sanitize_grid_item( $item );
		}

		if ( empty( $sanitized ) && self::is_meaningful_json_meta( $original ) ) {
			return self::preserve_or_fallback( $original );
		}

		usort( $sanitized, array( 'KaliForms\Inc\Backend\Sanitizers', 'sort_by_row' ) );
		return wp_json_encode( $sanitized );
	}
	/**
	 * Sort stuff by index
	 *
	 * @param [type] $a
	 * @param [type] $b
	 * @return void
	 */
	public static function sort_by_row( $a, $b ) {
		return strnatcmp( $a->y, $b->y );
	}

	/**
	 * @param $item
	 *
	 * @return \stdClass
	 */
	public static function sanitize_grid_item( $item ) {
		$grid_item         = new \stdClass();
		$grid_item->h      = absint( $item->h );
		$grid_item->i      = sanitize_key( $item->i );
		$grid_item->maxH   = absint( $item->maxH );
		$grid_item->minW   = absint( $item->minW );
		$grid_item->moved  = Sanitizers::sanitize_boolean( $item->moved );
		$grid_item->static = Sanitizers::sanitize_boolean( $item->static );
		$grid_item->w      = absint( $item->w );
		$grid_item->x      = absint( $item->x );
		$grid_item->y      = absint( $item->y );

		return $grid_item;
	}

	/**
	 * @param $input
	 *
	 * @return false|string
	 */
	public static function sanitize_field_components( $input ) {
		$original = $input;
		$decoded  = self::decode_json_meta( $input, false );

		// Never wipe existing form definitions when JSON cannot be decoded.
		if ( ! is_array( $decoded ) ) {
			return self::preserve_or_fallback( $original );
		}

		if ( empty( $decoded ) && self::is_meaningful_json_meta( $original ) ) {
			return self::preserve_or_fallback( $original );
		}

		$sanitized = array();
		foreach ( $decoded as $field ) {
			if ( is_array( $field ) ) {
				$field = (object) $field;
			}
			if ( ! is_object( $field ) ) {
				continue;
			}

			try {
				$sanitized[] = Sanitizers::sanitize_field_component( $field );
			} catch ( \Throwable $e ) {
				// Keep going — one bad field must not wipe the whole form.
				continue;
			}
		}

		if ( empty( $sanitized ) && self::is_meaningful_json_meta( $original ) ) {
			return self::preserve_or_fallback( $original );
		}

		return wp_json_encode( $sanitized, JSON_HEX_QUOT );
	}

	/**
	 * @param $item
	 *
	 * @return \stdClass
	 */
	public static function sanitize_field_component( $item ) {
		if ( is_array( $item ) ) {
			$item = (object) $item;
		}

		$fieldItem             = new \stdClass();
		$fieldItem->id         = sanitize_text_field( isset( $item->id ) ? $item->id : '' );
		$fieldItem->internalId = sanitize_key( isset( $item->internalId ) ? $item->internalId : '' );
		$fieldItem->label      = sanitize_text_field( isset( $item->label ) ? $item->label : '' );
		$properties            = isset( $item->properties ) ? $item->properties : new \stdClass();
		if ( is_array( $properties ) ) {
			$properties = (object) $properties;
		}
		if ( ! is_object( $properties ) ) {
			$properties = new \stdClass();
		}
		$fieldItem->properties = Sanitizers::sanitize_properties_object( $properties, $fieldItem->id );
		$fieldItem->constraint = ( empty( $item->constraint ) || 'none' === $item->constraint ) ? 'none' : absint( $item->constraint );
		return $fieldItem;
	}

	/**
	 * @param $item
	 *
	 * @return \stdClass
	 */
	public static function sanitize_properties_object( $item, $id ) {
		$props = new \stdClass();
		if ( is_array( $item ) ) {
			$item = (object) $item;
		}
		if ( ! is_object( $item ) ) {
			return $props;
		}

		foreach ( $item as $k => $v ) {
			if ( $k === 'price' ) {
				$props->{sanitize_text_field( $k )} = str_replace( ',', '', number_format( floatval( str_replace( ',', '.', $v ) ), 2 ) );
				continue;
			}
			if ( $k === 'products' ) {
				$sanitized_data = array();
				foreach ( $v as $product ) {
					$sanitized_product = array(
						'id'    => sanitize_key( $product->id ),
						'label' => sanitize_text_field( $product->label ),
						'price' => str_replace( ',', '', number_format( floatval( str_replace( ',', '.', $product->price ) ), 2 ) ),
						'image' => Sanitizers::sanitize_object( $product->image ),
					);

					$sanitized_data[] = $sanitized_product;
				}

				$props->{sanitize_text_field( $k )} = $sanitized_data;
				continue;
			}
			if ( $id === 'imageRadio' && $k === 'choices' ) {
				$sanitized_data = array();
				foreach ( $v as $r_item ) {
					$sanitized_r_item = array(
						'label'   => sanitize_text_field( $r_item->label ),
						'caption' => sanitize_text_field( $r_item->caption ),
						'image'   => Sanitizers::sanitize_object( $r_item->image ),
					);

					$sanitized_data[] = $sanitized_r_item;
				}
				$props->{sanitize_text_field( $k )} = $sanitized_data;
				continue;
			}
			if ( $k === 'content' ) {
				$props->{sanitize_text_field( $k )} = wp_kses_post( $v );
				continue;
			}
			if ( $k === 'name' || $k === 'id' ) {
				if ( empty( $v ) ) {
					$v = $id . substr( md5( $id . wp_rand( 15, 50 ) ), 0, 3 );
				}

				$props->{sanitize_text_field( $k )} = sanitize_text_field( $v );
				continue;
			}

			$props->{sanitize_text_field( $k )} = is_string( $v )
				? sanitize_text_field( $v )
				: Sanitizers::sanitize_unknown( $v );
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
	public static function sanitize_unknown( $value ) {
		switch ( gettype( $value ) ) {
			case 'array':
				$value = Sanitizers::sanitize_array_with_props( $value );
				break;
			case 'boolean':
				$value = Sanitizers::sanitize_boolean( $value );
				break;
			case 'object':
				$value = Sanitizers::sanitize_object( $value );
				break;
			default:
				$value = sanitize_text_field( $value );
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
	public static function sanitize_array_with_props( $value ) {
		$sanitized = array();

		if ( ! is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $item ) {
			$obj = new \stdClass();

			foreach ( $item as $k => $v ) {
				$safe_key   = esc_attr( sanitize_text_field( $k ) );
				$safe_value = esc_js( sanitize_text_field( $v ) );

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
	public static function sanitize_object( $value ) {
		if ( ! is_object( $value ) ) {
			return false;
		}

		$obj = new \stdClass();
		foreach ( (array) $value as $k => $v ) {
			$safe_key   = esc_attr( sanitize_text_field( $k ) );
			$safe_value = esc_js( sanitize_text_field( $v ) );

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
	public static function sanitize_email_builder( $value ) {
		$original  = $value;
		$decoded   = self::decode_json_meta( $value, false );
		$sanitized = array();

		if ( ! is_array( $decoded ) ) {
			return self::preserve_or_fallback( $original );
		}

		if ( empty( $decoded ) && self::is_meaningful_json_meta( $original ) ) {
			return self::preserve_or_fallback( $original );
		}

		foreach ( $decoded as $item ) {
			if ( is_array( $item ) ) {
				$item = (object) $item;
			}
			if ( ! is_object( $item ) ) {
				continue;
			}
			$obj = new \stdClass();
			foreach ( $item as $k => $v ) {
				if ( $k === 'emailContentType' ) {
					$obj->{sanitize_text_field( $k )} = in_array( $v, array( 'plain', 'html' ), true ) ? $v : 'html';
					continue;
				}
				if ( $k === 'emailBody' ) {
					if ( isset( $item->emailContentType ) && $item->emailContentType === 'plain' ) {
						$obj->{sanitize_text_field( $k )} = sanitize_textarea_field( $v );
						continue;
					}
					if ( isset( $item->saveAsHtml ) && $item->saveAsHtml ) {
						$obj->{sanitize_text_field( $k )} = wp_kses_post( $v );
						continue;
					}

					$obj->{sanitize_text_field( $k )} = wp_kses_post( str_replace( array( "\n", "\r" ), '', $v ) );
					continue;
				}
				if ( $k === 'emailAttachmentMediaIds' ) {
					$obj->{sanitize_text_field( $k )} = is_string( $v )
						? Sanitizers::rewrite_value_to_new_type( $v )
						: Sanitizers::sanitize_new_media_type( $v );

					continue;
				}
				$obj->{sanitize_text_field( $k )} = sanitize_text_field( $v );
			}

			$cleaned = array_filter( get_object_vars( $obj ) );
			if ( ! empty( $cleaned ) && count( $cleaned ) > 1 ) {
				$sanitized[] = $obj;
			}
		}

		if ( empty( $sanitized ) && self::is_meaningful_json_meta( $original ) ) {
			return self::preserve_or_fallback( $original );
		}

		return wp_json_encode( $sanitized, JSON_HEX_QUOT );
	}

	/**
	 * This function will re-write the value from the email media attachments
	 *
	 * @param [type] $val
	 * @return void
	 */
	public static function rewrite_value_to_new_type( $val ) {
		$attachments = explode( ',', $val );
		$sanitized   = array();
		foreach ( $attachments as $id ) {
			if ( empty( $id ) ) {
				continue;
			}
			$obj          = new \stdClass();
			$prev         = wp_get_attachment_image_src( $id, 'form-edit-image-preview' );
			$obj->id      = $id;
			$obj->fullUrl = wp_get_attachment_url( $id );
			$obj->preview = isset( $prev[0] ) ? $prev[0] : '';

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
	public static function sanitize_new_media_type( $val ) {
		$sanitized = array();
		foreach ( $val as $idx => $media ) {
			$sanitized[ $idx ] = new \stdClass();
			foreach ( $media as $k => $v ) {
				$sanitized[ $idx ]->{$k} = sanitize_text_field( $v );
			}
		}

		return $sanitized;
	}
}
