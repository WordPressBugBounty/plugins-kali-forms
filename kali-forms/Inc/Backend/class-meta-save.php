<?php

namespace KaliForms\Inc\Backend;

use KaliForms\Inc\Utils\Form_Definition_Backup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Meta_Save
 *
 * @package Inc\Libraries
 */
class Meta_Save {

	/**
	 * @var string
	 */
	protected $slug = 'kaliforms';
	/**
	 * @var array
	 */
	protected $post_types = array( 'kaliforms_forms' );

	/**
	 * @var array
	 */
	protected $fields = array();

	/**
	 * @var array
	 */
	protected $security_checks = array();

	/**
	 * Meta_Save constructor.
	 */
	public function __construct() {
		add_action( 'save_post', array( $this, 'save_post' ), 1, 2 );
	}

	/**
	 * @return Meta_Save
	 */
	public static function get_instance() {
		static $inst;
		if ( ! $inst ) {
			$inst = new Meta_Save();
		}

		return $inst;
	}

	/**
	 * @param $args
	 */
	public function add_field( $args ) {
		$this->fields[ $args['id'] ] = $args;
	}

	/**
	 * Go through the fields and add them
	 *
	 * @param [type] ...$args
	 * @return void
	 */
	public function add_fields( ...$args ) {
		array_map( array( $this, 'add_field' ), $args );
	}

	/**
	 *
	 */
	private function _security_loop( $post ) {
		$security = array(
			'nonce'     => false,
			'isset'     => false,
			'empty'     => false,
			'post_type' => false,
		);

		foreach ( $security as $k => $v ) {
			$this->security_checks[ $k ] = $this->_run_check( $k, $post );
		}
	}

	/**
	 * Runs a security check
	 *
	 * @param $key
	 * @param $post
	 *
	 * @return bool
	 */
	private function _run_check( $key, $post ) {
		switch ( $key ) {
			case 'post_type':
				$check = in_array( $post->post_type, $this->post_types );
				break;
			case 'empty':
				$check = ! empty( $_POST['kaliforms'] );
				break;
			case 'isset':
				$check = isset( $_POST['kaliforms'] );
				break;
			case 'nonce':
				$check = ! isset( $_POST['kaliforms_fields'] ) ? false : wp_verify_nonce( sanitize_key( wp_unslash( $_POST['kaliforms_fields'] ) ), KALIFORMS_BASE ) === 1;
				break;
			default:
				$check = false;
				break;
		}

		return $check;
	}

	/**
	 * @param $post_id
	 * @param $post
	 *
	 * @return mixed
	 */
	public function save_post( $post_id, $post ) {
		/**
		 * Run the security loop
		 */
		$this->_security_loop( $post );

		/**
		 * In case it's not what we were expecting, return here
		 */
		if ( count( array_filter( $this->security_checks ) ) < 4 ) {
			return $post;
		}

		/**
		 * Snapshot the live definition before overwriting it.
		 */
		Form_Definition_Backup::capture( $post_id, 'save' );

		/**
		 * Start sanitizing post data
		 */
		$sanitized = $this->_sanitize_post();

		/**
		 * Save sanitized data
		 */
		$this->_save( $post_id, $sanitized );

		/**
		 * Return post
		 */
		return $post;
	}

	/**
	 * Sanitizes post data according to our "schema"
	 *
	 * @return array
	 */
	private function _sanitize_post() {
		$sanitized = array();
		foreach ( $_POST['kaliforms'] as $key => $value ) {
			if ( ! array_key_exists( $key, $this->fields ) ) {
				continue;
			}

			if ( is_string( $value ) ) {
				$value = wp_unslash( $value );
			}

			$sanitized[ sanitize_text_field( $key ) ] = call_user_func( $this->fields[ $key ]['sanitize'], $value );
		}

		return $sanitized;
	}

	/**
	 * Meta keys that must never be overwritten with empty JSON when existing data is present.
	 *
	 * @var string[]
	 */
	private $protected_json_keys = array(
		'field_components',
		'grid',
		'emails',
	);

	/**
	 * @param $post_id
	 * @param $data
	 */
	private function _save( $post_id, $data ) {
		foreach ( $data as $key => $value ) {
			$meta_key = $this->slug . '_' . $key;

			if ( $this->would_wipe_protected_json( $post_id, $key, $value ) ) {
				continue;
			}

			update_post_meta( $post_id, $meta_key, $value );
		}
	}

	/**
	 * Refuse to replace a non-empty form definition with empty JSON.
	 *
	 * @param int    $post_id
	 * @param string $key
	 * @param mixed  $value
	 * @return bool
	 */
	private function would_wipe_protected_json( $post_id, $key, $value ) {
		if ( ! in_array( $key, $this->protected_json_keys, true ) ) {
			return false;
		}

		if ( Sanitizers::is_meaningful_json_meta( $value ) ) {
			return false;
		}

		$existing = get_post_meta( $post_id, $this->slug . '_' . $key, true );

		return Sanitizers::is_meaningful_json_meta( $existing );
	}
	/**
	 * Sanitize option
	 *
	 * @param [type] $key
	 * @param [type] $value
	 * @return void
	 */
	public function sanitize_option( $key, $value ) {
		if ( ! array_key_exists( $key, $this->fields ) ) {
			return null;
		}

		return call_user_func( $this->fields[ $key ]['sanitize'], $value );
	}
}
