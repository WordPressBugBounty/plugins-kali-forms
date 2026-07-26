<?php

namespace KaliForms\Inc\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Silent form-definition backups — no user opt-in, no UI.
 *
 * Runs automatically on:
 * - first load after this code ships (baseline for every form)
 * - each plugin version change
 * - each form save (admin + REST), capturing the pre-save state
 *
 * Storage:
 * - `_kaliforms_definition_backups` — rolling history (last N)
 * - `_kf_legacy_snapshot` — always the latest full definition (support / downgrade)
 */
class Form_Definition_Backup {

	/**
	 * Post meta key holding the snapshot list (hidden / private meta).
	 */
	const META_KEY = '_kaliforms_definition_backups';

	/**
	 * Option that records which plugin version last ran an upgrade snapshot.
	 */
	const VERSION_OPTION = 'kaliforms_definition_backup_version';

	/**
	 * Default number of snapshots kept per form in the rolling history.
	 */
	const DEFAULT_MAX_SNAPSHOTS = 10;

	/**
	 * @var bool
	 */
	private static $suppress = false;

	/**
	 * Register automatic (non-optional) hooks.
	 *
	 * @return void
	 */
	public static function boot() {
		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_snapshot_on_upgrade' ), 5 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'maybe_snapshot_after_plugin_upgrade' ), 10, 2 );
	}

	/**
	 * Snapshot every form once when the installed plugin version changes.
	 *
	 * @return void
	 */
	public static function maybe_snapshot_on_upgrade() {
		if ( ! defined( 'KALIFORMS_VERSION' ) ) {
			return;
		}

		$stored = get_option( self::VERSION_OPTION, '' );
		if ( $stored === KALIFORMS_VERSION ) {
			return;
		}

		$reason = ( $stored === '' || false === $stored ) ? 'baseline' : 'upgrade';
		self::snapshot_all_forms( $reason );
		update_option( self::VERSION_OPTION, KALIFORMS_VERSION, false );
	}

	/**
	 * Also fire when WP finishes updating this plugin (covers update before next full bootstrap).
	 *
	 * @param \WP_Upgrader $upgrader Upgrader instance.
	 * @param array        $options  Upgrade context.
	 * @return void
	 */
	public static function maybe_snapshot_after_plugin_upgrade( $upgrader, $options ) {
		unset( $upgrader );

		if ( empty( $options['action'] ) || 'update' !== $options['action'] ) {
			return;
		}
		if ( empty( $options['type'] ) || 'plugin' !== $options['type'] ) {
			return;
		}

		$plugins = array();
		if ( ! empty( $options['plugins'] ) && is_array( $options['plugins'] ) ) {
			$plugins = $options['plugins'];
		} elseif ( ! empty( $options['plugin'] ) ) {
			$plugins = array( $options['plugin'] );
		}

		$basename = plugin_basename( KALIFORMS_PLUGIN_FILE );
		if ( ! in_array( $basename, $plugins, true ) ) {
			return;
		}

		// Force a fresh upgrade pass even if VERSION_OPTION was already bumped.
		delete_option( self::VERSION_OPTION );
		self::maybe_snapshot_on_upgrade();
	}

	/**
	 * @param string $reason baseline|upgrade|save|pre_restore
	 * @return void
	 */
	public static function snapshot_all_forms( $reason = 'upgrade' ) {
		$form_ids = get_posts(
			array(
				'post_type'      => 'kaliforms_forms',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		foreach ( $form_ids as $form_id ) {
			self::capture( (int) $form_id, $reason );
		}
	}

	/**
	 * Capture the current form definition into history + latest legacy snapshot.
	 *
	 * @param int    $form_id Form post ID.
	 * @param string $reason  baseline|upgrade|save|pre_restore.
	 * @return array|false Snapshot array on success, false when skipped.
	 */
	public static function capture( $form_id, $reason = 'save' ) {
		if ( self::$suppress ) {
			return false;
		}

		$form_id = absint( $form_id );
		if ( $form_id <= 0 ) {
			return false;
		}

		$post = get_post( $form_id );
		if ( ! $post || 'kaliforms_forms' !== $post->post_type ) {
			return false;
		}

		$meta = self::collect_definition_meta( $form_id );
		if ( empty( $meta ) ) {
			return false;
		}

		$hash     = md5( wp_json_encode( $meta ) );
		$backups  = self::get_backups( $form_id );
		$previous = isset( $backups[0] ) ? $backups[0] : null;

		if ( is_array( $previous ) && isset( $previous['hash'] ) && $previous['hash'] === $hash && 'save' === $reason ) {
			return false;
		}

		$snapshot = array(
			'id'             => uniqid( 'kf_', true ),
			'created_at'     => current_time( 'mysql' ),
			'plugin_version' => defined( 'KALIFORMS_VERSION' ) ? KALIFORMS_VERSION : '',
			'reason'         => sanitize_key( $reason ),
			'title'          => $post->post_title,
			'post_status'    => $post->post_status,
			'hash'           => $hash,
			'meta'           => $meta,
		);

		array_unshift( $backups, $snapshot );
		$backups = array_slice( $backups, 0, self::get_max_snapshots() );

		update_post_meta( $form_id, self::META_KEY, $backups );

		// Always keep a single latest full copy for support / downgrade restore.
		update_post_meta(
			$form_id,
			Legacy_Snapshot_Downgrade_Guard::FORM_SNAPSHOT_META_KEY,
			array(
				'created_at'     => $snapshot['created_at'],
				'plugin_version' => $snapshot['plugin_version'],
				'reason'         => $snapshot['reason'],
				'title'          => $snapshot['title'],
				'post_status'    => $snapshot['post_status'],
				'meta'           => $meta,
			)
		);

		/**
		 * Fires after a form definition snapshot is stored.
		 *
		 * @param int   $form_id
		 * @param array $snapshot
		 */
		do_action( 'kaliforms_form_definition_backup_captured', $form_id, $snapshot );

		return $snapshot;
	}

	/**
	 * Restore a snapshot from the rolling history onto the form post.
	 *
	 * Support / CLI use only — there is no admin UI.
	 *
	 * @param int    $form_id     Form post ID.
	 * @param string $snapshot_id Snapshot id from the list.
	 * @return true|\WP_Error
	 */
	public static function restore( $form_id, $snapshot_id ) {
		$form_id     = absint( $form_id );
		$snapshot_id = sanitize_text_field( $snapshot_id );
		$snapshot    = self::get_snapshot( $form_id, $snapshot_id );

		if ( ! $snapshot || empty( $snapshot['meta'] ) || ! is_array( $snapshot['meta'] ) ) {
			return new \WP_Error( 'kaliforms_backup_missing', __( 'Backup snapshot not found.', 'kali-forms' ) );
		}

		return self::apply_snapshot( $form_id, $snapshot );
	}

	/**
	 * Restore the latest `_kf_legacy_snapshot` for a form.
	 *
	 * @param int $form_id Form post ID.
	 * @return true|\WP_Error
	 */
	public static function restore_latest( $form_id ) {
		$form_id  = absint( $form_id );
		$snapshot = get_post_meta( $form_id, Legacy_Snapshot_Downgrade_Guard::FORM_SNAPSHOT_META_KEY, true );

		if ( ! is_array( $snapshot ) || empty( $snapshot['meta'] ) || ! is_array( $snapshot['meta'] ) ) {
			return new \WP_Error( 'kaliforms_backup_missing', __( 'Backup snapshot not found.', 'kali-forms' ) );
		}

		return self::apply_snapshot( $form_id, $snapshot );
	}

	/**
	 * @param int   $form_id
	 * @param array $snapshot
	 * @return true|\WP_Error
	 */
	private static function apply_snapshot( $form_id, $snapshot ) {
		self::capture( $form_id, 'pre_restore' );

		self::$suppress = true;

		foreach ( $snapshot['meta'] as $meta_key => $value ) {
			if ( ! is_string( $meta_key ) || 0 !== strpos( $meta_key, 'kaliforms_' ) ) {
				continue;
			}
			if ( $meta_key === self::META_KEY ) {
				continue;
			}
			update_post_meta( $form_id, $meta_key, $value );
		}

		$post_update = array( 'ID' => $form_id );
		if ( ! empty( $snapshot['title'] ) ) {
			$post_update['post_title'] = $snapshot['title'];
		}
		if ( ! empty( $snapshot['post_status'] ) ) {
			$post_update['post_status'] = $snapshot['post_status'];
		}
		if ( count( $post_update ) > 1 ) {
			wp_update_post( $post_update );
		}

		self::$suppress = false;

		/**
		 * Fires after a form definition snapshot is restored.
		 *
		 * @param int   $form_id
		 * @param array $snapshot
		 */
		do_action( 'kaliforms_form_definition_backup_restored', $form_id, $snapshot );

		return true;
	}

	/**
	 * @param int $form_id
	 * @return array[]
	 */
	public static function get_backups( $form_id ) {
		$backups = get_post_meta( absint( $form_id ), self::META_KEY, true );
		if ( ! is_array( $backups ) ) {
			return array();
		}

		return array_values( $backups );
	}

	/**
	 * @param int    $form_id
	 * @param string $snapshot_id
	 * @return array|null
	 */
	public static function get_snapshot( $form_id, $snapshot_id ) {
		foreach ( self::get_backups( $form_id ) as $snapshot ) {
			if ( isset( $snapshot['id'] ) && $snapshot['id'] === $snapshot_id ) {
				return $snapshot;
			}
		}

		return null;
	}

	/**
	 * Collect every kaliforms_* config key for a form (PRO keys included).
	 *
	 * @param int $form_id
	 * @return array
	 */
	public static function collect_definition_meta( $form_id ) {
		$custom = get_post_custom( absint( $form_id ) );
		if ( ! is_array( $custom ) ) {
			return array();
		}

		$meta = array();
		foreach ( $custom as $key => $values ) {
			if ( ! is_string( $key ) || 0 !== strpos( $key, 'kaliforms_' ) ) {
				continue;
			}
			if ( $key === self::META_KEY ) {
				continue;
			}

			$meta[ $key ] = isset( $values[0] ) ? maybe_unserialize( $values[0] ) : '';
		}

		return $meta;
	}

	/**
	 * @return int
	 */
	public static function get_max_snapshots() {
		$max = (int) apply_filters( 'kaliforms_definition_backup_max', self::DEFAULT_MAX_SNAPSHOTS );
		return max( 1, min( 50, $max ) );
	}
}
