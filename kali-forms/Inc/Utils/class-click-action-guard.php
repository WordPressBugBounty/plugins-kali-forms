<?php

namespace KaliForms\Inc\Utils;

use KaliForms\Inc\Backend\Sanitizers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gates author-controlled values that reach a JavaScript execution context
 * (onclick / other on* handlers, and Button clickAction) behind unfiltered_html.
 *
 * Escaping cannot make those values safe: they run as JavaScript in the visitor's
 * session. Only users with unfiltered_html may persist them, and the front end
 * only emits onclick when a privileged save has marked the form as allowed.
 */
class Click_Action_Guard {

	/**
	 * Post meta flag: form may emit stored JS handler attributes on the front end.
	 */
	const ALLOWED_META_KEY = 'kaliforms_button_click_action_allowed';

	/**
	 * Option set after the 2.4.26 clickAction-only cleanup.
	 */
	const MIGRATION_OPTION = 'kaliforms_click_action_unfiltered_html_migrated';

	/**
	 * Option set after the 2.4.27 cleanup that also strips raw onclick / on* props.
	 */
	const MIGRATION_OPTION_JS_ATTRS = 'kaliforms_js_attr_unfiltered_html_migrated';

	/**
	 * Form ID currently being rendered (0 = not in a form emit context).
	 *
	 * @var int
	 */
	private static $emit_form_id = 0;

	/**
	 * Whether the current form render may emit onclick.
	 *
	 * @var bool
	 */
	private static $emit_allowed = false;

	/**
	 * Wire hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_migrate_legacy_click_actions' ), 25 );
		add_action( 'init', array( __CLASS__, 'maybe_migrate_legacy_js_handler_attrs' ), 26 );
		add_action( 'added_post_meta', array( __CLASS__, 'sync_allowed_flag_after_meta_change' ), 10, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'sync_allowed_flag_after_meta_change' ), 10, 4 );
	}

	/**
	 * Whether a property key maps to a JS execution context when rendered.
	 *
	 * @param string $key Property name.
	 * @return bool
	 */
	public static function is_js_execution_property( $key ) {
		if ( ! is_string( $key ) || $key === '' ) {
			return false;
		}

		if ( 'clickAction' === $key ) {
			return true;
		}

		// HTML event-handler attributes: onclick, onerror, onload, …
		return (bool) preg_match( '/^on[a-z]+$/i', $key );
	}

	/**
	 * Whether the current user may configure JS execution properties.
	 *
	 * @return bool
	 */
	public static function current_user_can_use() {
		return current_user_can( 'unfiltered_html' );
	}

	/**
	 * Sanitize a JS-execution property value for storage.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_for_storage( $value ) {
		if ( ! self::current_user_can_use() ) {
			return '';
		}

		if ( ! is_string( $value ) ) {
			return '';
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Whether a form may emit stored JS handler attributes.
	 *
	 * @param int $form_id Form post ID.
	 * @return bool
	 */
	public static function form_may_emit( $form_id ) {
		$form_id = absint( $form_id );
		if ( ! $form_id ) {
			return false;
		}

		return '1' === (string) get_post_meta( $form_id, self::ALLOWED_META_KEY, true );
	}

	/**
	 * Begin a form render emit context (call before rendering fields).
	 *
	 * @param int $form_id Form post ID.
	 * @return void
	 */
	public static function begin_form_emit( $form_id ) {
		self::$emit_form_id = absint( $form_id );
		self::$emit_allowed = self::form_may_emit( self::$emit_form_id );
	}

	/**
	 * End the form render emit context.
	 *
	 * @return void
	 */
	public static function end_form_emit() {
		self::$emit_form_id = 0;
		self::$emit_allowed = false;
	}

	/**
	 * Whether the current form render may emit onclick.
	 *
	 * @return bool
	 */
	public static function current_emit_allowed() {
		return self::$emit_allowed;
	}

	/**
	 * Strip JS-execution properties from field components loaded into the builder
	 * for users without the cap.
	 *
	 * @param array $components Decoded field components.
	 * @return array
	 */
	public static function strip_from_field_components( $components ) {
		if ( ! is_array( $components ) || self::current_user_can_use() ) {
			return $components;
		}

		foreach ( $components as $index => $component ) {
			$components[ $index ] = self::strip_js_execution_props_from_component( $component, true );
		}

		return $components;
	}

	/**
	 * Keep the emit-allowed flag in sync after field_components meta changes.
	 *
	 * @param int    $meta_id     Meta ID.
	 * @param int    $object_id   Post ID.
	 * @param string $meta_key    Meta key.
	 * @param mixed  $_meta_value Meta value (unused; re-read for consistency).
	 * @return void
	 */
	public static function sync_allowed_flag_after_meta_change( $meta_id, $object_id, $meta_key, $_meta_value ) {
		unset( $meta_id, $_meta_value );

		if ( 'kaliforms_field_components' !== $meta_key ) {
			return;
		}

		$object_id = absint( $object_id );
		if ( ! $object_id || 'kaliforms_forms' !== get_post_type( $object_id ) ) {
			return;
		}

		$raw    = get_post_meta( $object_id, 'kaliforms_field_components', true );
		$fields = Sanitizers::decode_json_meta( $raw, false );
		if ( self::components_have_js_execution_props( $fields ) ) {
			update_post_meta( $object_id, self::ALLOWED_META_KEY, '1' );
			return;
		}

		delete_post_meta( $object_id, self::ALLOWED_META_KEY );
	}

	/**
	 * One-time cleanup (2.4.26): neutralize Author-stored clickAction.
	 *
	 * @return void
	 */
	public static function maybe_migrate_legacy_click_actions() {
		if ( get_option( self::MIGRATION_OPTION ) ) {
			return;
		}

		self::migrate_all_forms( false );
		update_option( self::MIGRATION_OPTION, '1', false );
	}

	/**
	 * One-time cleanup (2.4.27): also strip raw onclick / on* properties.
	 *
	 * @return void
	 */
	public static function maybe_migrate_legacy_js_handler_attrs() {
		if ( get_option( self::MIGRATION_OPTION_JS_ATTRS ) ) {
			return;
		}

		self::migrate_all_forms( true );
		update_option( self::MIGRATION_OPTION_JS_ATTRS, '1', false );
	}

	/**
	 * @param bool $strip_all_js_props When true, strip every JS-execution property; otherwise clickAction only.
	 * @return void
	 */
	private static function migrate_all_forms( $strip_all_js_props ) {
		$forms = get_posts(
			array(
				'post_type'              => 'kaliforms_forms',
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $forms as $form_id ) {
			self::migrate_form_js_execution_props( (int) $form_id, $strip_all_js_props );
		}
	}

	/**
	 * Migrate a single form's stored JS-execution property values.
	 *
	 * @param int  $form_id             Form post ID.
	 * @param bool $strip_all_js_props  Strip all on* / clickAction, or clickAction only.
	 * @return void
	 */
	private static function migrate_form_js_execution_props( $form_id, $strip_all_js_props ) {
		$raw    = get_post_meta( $form_id, 'kaliforms_field_components', true );
		$fields = Sanitizers::decode_json_meta( $raw, false );
		if ( ! self::components_have_js_execution_props( $fields, $strip_all_js_props ) ) {
			if ( ! self::components_have_js_execution_props( $fields, true ) ) {
				delete_post_meta( $form_id, self::ALLOWED_META_KEY );
			}
			return;
		}

		$author_id = (int) get_post_field( 'post_author', $form_id );
		if ( $author_id && user_can( $author_id, 'unfiltered_html' ) ) {
			update_post_meta( $form_id, self::ALLOWED_META_KEY, '1' );
			return;
		}

		$changed = false;
		foreach ( $fields as $index => $field ) {
			$did_change = false;
			$field      = self::strip_js_execution_props_from_component( $field, $strip_all_js_props, $did_change );
			if ( $did_change ) {
				$fields[ $index ] = $field;
				$changed          = true;
			}
		}

		if ( $changed ) {
			remove_action( 'updated_post_meta', array( __CLASS__, 'sync_allowed_flag_after_meta_change' ), 10 );
			update_post_meta( $form_id, 'kaliforms_field_components', Sanitizers::encode_json_meta_for_storage( $fields ) );
			add_action( 'updated_post_meta', array( __CLASS__, 'sync_allowed_flag_after_meta_change' ), 10, 4 );
		}

		delete_post_meta( $form_id, self::ALLOWED_META_KEY );
	}

	/**
	 * @param mixed $component          Field component object/array.
	 * @param bool  $strip_all_js_props Strip all JS-execution keys (default true).
	 * @param bool  $changed            Set true when a property was cleared/removed.
	 * @return mixed
	 */
	private static function strip_js_execution_props_from_component( $component, $strip_all_js_props = true, &$changed = null ) {
		if ( null === $changed ) {
			$changed = false;
		}

		if ( is_object( $component ) && isset( $component->properties ) && is_object( $component->properties ) ) {
			foreach ( get_object_vars( $component->properties ) as $key => $value ) {
				if ( ! self::should_strip_property_key( $key, $strip_all_js_props ) ) {
					continue;
				}
				if ( 'clickAction' === $key ) {
					if ( $component->properties->clickAction !== '' ) {
						$component->properties->clickAction = '';
						$changed                            = true;
					}
				} else {
					unset( $component->properties->{$key} );
					$changed = true;
				}
			}
			return $component;
		}

		if ( is_array( $component ) && isset( $component['properties'] ) && is_array( $component['properties'] ) ) {
			foreach ( array_keys( $component['properties'] ) as $key ) {
				if ( ! self::should_strip_property_key( $key, $strip_all_js_props ) ) {
					continue;
				}
				if ( 'clickAction' === $key ) {
					if ( $component['properties']['clickAction'] !== '' ) {
						$component['properties']['clickAction'] = '';
						$changed                                = true;
					}
				} else {
					unset( $component['properties'][ $key ] );
					$changed = true;
				}
			}
		}

		return $component;
	}

	/**
	 * @param string $key
	 * @param bool   $strip_all_js_props
	 * @return bool
	 */
	private static function should_strip_property_key( $key, $strip_all_js_props ) {
		if ( $strip_all_js_props ) {
			return self::is_js_execution_property( $key );
		}

		return 'clickAction' === $key;
	}

	/**
	 * @param mixed $fields             Decoded field components.
	 * @param bool  $include_all_js_props Check all JS-execution keys (default true).
	 * @return bool
	 */
	private static function components_have_js_execution_props( $fields, $include_all_js_props = true ) {
		if ( ! is_array( $fields ) ) {
			return false;
		}

		foreach ( $fields as $field ) {
			if ( is_object( $field ) && isset( $field->properties ) && is_object( $field->properties ) ) {
				foreach ( get_object_vars( $field->properties ) as $key => $value ) {
					if ( ! self::should_strip_property_key( $key, $include_all_js_props ) ) {
						continue;
					}
					if ( is_string( $value ) && $value !== '' ) {
						return true;
					}
				}
			} elseif ( is_array( $field ) && isset( $field['properties'] ) && is_array( $field['properties'] ) ) {
				foreach ( $field['properties'] as $key => $value ) {
					if ( ! self::should_strip_property_key( $key, $include_all_js_props ) ) {
						continue;
					}
					if ( is_string( $value ) && $value !== '' ) {
						return true;
					}
				}
			}
		}

		return false;
	}
}
