<?php

namespace KaliForms\Inc\Utils;

use KaliForms\Inc\Backend\Sanitizers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gates Button "Function to run on click" (clickAction) behind unfiltered_html.
 *
 * The value is emitted into an onclick JavaScript context. Escaping cannot make
 * that safe, so only users with unfiltered_html may persist it, and the front
 * end only emits it when a privileged save has marked the form as allowed.
 */
class Click_Action_Guard {

	/**
	 * Post meta flag: form may emit stored button clickAction on the front end.
	 */
	const ALLOWED_META_KEY = 'kaliforms_button_click_action_allowed';

	/**
	 * Option set after the one-time legacy cleanup has run.
	 */
	const MIGRATION_OPTION = 'kaliforms_click_action_unfiltered_html_migrated';

	/**
	 * Wire hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_migrate_legacy_click_actions' ), 25 );
		add_action( 'added_post_meta', array( __CLASS__, 'sync_allowed_flag_after_meta_change' ), 10, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'sync_allowed_flag_after_meta_change' ), 10, 4 );
	}

	/**
	 * Whether the current user may configure clickAction.
	 *
	 * @return bool
	 */
	public static function current_user_can_use() {
		return current_user_can( 'unfiltered_html' );
	}

	/**
	 * Sanitize a clickAction value for storage.
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
	 * Whether a form may emit stored clickAction into onclick.
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
	 * Strip clickAction from field components loaded into the builder for users without the cap.
	 *
	 * @param array $components Decoded field components.
	 * @return array
	 */
	public static function strip_from_field_components( $components ) {
		if ( ! is_array( $components ) || self::current_user_can_use() ) {
			return $components;
		}

		foreach ( $components as $index => $component ) {
			if ( is_object( $component ) && isset( $component->properties ) && is_object( $component->properties ) ) {
				if ( isset( $component->properties->clickAction ) ) {
					$component->properties->clickAction = '';
				}
				$components[ $index ] = $component;
				continue;
			}

			if ( is_array( $component ) && isset( $component['properties'] ) && is_array( $component['properties'] ) ) {
				$component['properties']['clickAction'] = '';
				$components[ $index ]                   = $component;
			}
		}

		return $components;
	}

	/**
	 * Keep the emit-allowed flag in sync after field_components meta changes.
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $object_id  Post ID.
	 * @param string $meta_key   Meta key.
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
		if ( self::components_have_click_action( $fields ) ) {
			update_post_meta( $object_id, self::ALLOWED_META_KEY, '1' );
			return;
		}

		delete_post_meta( $object_id, self::ALLOWED_META_KEY );
	}

	/**
	 * One-time cleanup: neutralize Author-stored clickAction, allow Admin-authored ones.
	 *
	 * @return void
	 */
	public static function maybe_migrate_legacy_click_actions() {
		if ( get_option( self::MIGRATION_OPTION ) ) {
			return;
		}

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
			self::migrate_form_click_actions( (int) $form_id );
		}

		update_option( self::MIGRATION_OPTION, '1', false );
	}

	/**
	 * Migrate a single form's stored clickAction values.
	 *
	 * @param int $form_id Form post ID.
	 * @return void
	 */
	private static function migrate_form_click_actions( $form_id ) {
		$raw    = get_post_meta( $form_id, 'kaliforms_field_components', true );
		$fields = Sanitizers::decode_json_meta( $raw, false );
		if ( ! self::components_have_click_action( $fields ) ) {
			delete_post_meta( $form_id, self::ALLOWED_META_KEY );
			return;
		}

		$author_id = (int) get_post_field( 'post_author', $form_id );
		if ( $author_id && user_can( $author_id, 'unfiltered_html' ) ) {
			update_post_meta( $form_id, self::ALLOWED_META_KEY, '1' );
			return;
		}

		$changed = false;
		foreach ( $fields as $index => $field ) {
			if ( is_object( $field ) && isset( $field->properties ) && is_object( $field->properties ) && ! empty( $field->properties->clickAction ) ) {
				$field->properties->clickAction = '';
				$fields[ $index ]               = $field;
				$changed                        = true;
			} elseif ( is_array( $field ) && isset( $field['properties']['clickAction'] ) && $field['properties']['clickAction'] !== '' ) {
				$field['properties']['clickAction'] = '';
				$fields[ $index ]                   = $field;
				$changed                            = true;
			}
		}

		if ( $changed ) {
			// Persist stripped components without re-entering the updated_post_meta sync with stale data.
			remove_action( 'updated_post_meta', array( __CLASS__, 'sync_allowed_flag_after_meta_change' ), 10 );
			update_post_meta( $form_id, 'kaliforms_field_components', Sanitizers::encode_json_meta_for_storage( $fields ) );
			add_action( 'updated_post_meta', array( __CLASS__, 'sync_allowed_flag_after_meta_change' ), 10, 4 );
		}

		delete_post_meta( $form_id, self::ALLOWED_META_KEY );
	}

	/**
	 * @param mixed $fields Decoded field components.
	 * @return bool
	 */
	private static function components_have_click_action( $fields ) {
		if ( ! is_array( $fields ) ) {
			return false;
		}

		foreach ( $fields as $field ) {
			if ( is_object( $field ) && isset( $field->properties ) && is_object( $field->properties ) ) {
				if ( isset( $field->properties->clickAction ) && $field->properties->clickAction !== '' ) {
					return true;
				}
			} elseif ( is_array( $field ) && ! empty( $field['properties']['clickAction'] ) ) {
				return true;
			}
		}

		return false;
	}
}
