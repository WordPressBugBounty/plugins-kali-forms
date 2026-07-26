<?php

namespace KaliForms\Inc\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Restores legacy form/site data from V3 migration snapshots after a downgrade to 2.x.
 *
 */
class Legacy_Snapshot_Downgrade_Guard
{
	public const FORM_SNAPSHOT_META_KEY = '_kf_legacy_snapshot';

	public const SITE_SNAPSHOT_OPTION_KEY = 'kaliforms_site_legacy_snapshot';

	public const V2_MIGRATED_OPTION_KEY = 'kaliforms_v2_migrated';

	private const RESTORE_LOG_OPTION_KEY = 'kaliforms_downgrade_restore_log';

	/**
	 * Legacy meta keys that 2.x needs to render and edit forms.
	 */
	private const CRITICAL_LEGACY_KEYS = [
		'kaliforms_grid',
		'kaliforms_field_components',
	];

	/**
	 * Register the downgrade restore hook.
	 */
	public static function boot(): void
	{
		add_action('plugins_loaded', [self::class, 'maybe_restore'], 2);
	}

	/**
	 * Restore legacy data when a 3.0 upgrade marker or snapshots are present on 2.x.
	 */
	public static function maybe_restore(): void
	{
		if (!self::should_run()) {
			return;
		}

		$restored_forms = 0;
		$restored_site  = false;

		if (self::should_restore_site_options()) {
			$restored_site = self::restore_site_options();
		}

		foreach (self::get_form_ids() as $form_id) {
			if (self::restore_form_if_needed((int) $form_id)) {
				$restored_forms++;
			}
		}

		if ($restored_forms > 0 || $restored_site) {
			update_option(self::RESTORE_LOG_OPTION_KEY, [
				'timestamp'      => current_time('mysql'),
				'forms_restored' => $restored_forms,
				'site_restored'  => $restored_site,
			], false);

			if (is_admin() && current_user_can('manage_options')) {
				add_action('admin_notices', [self::class, 'render_admin_notice']);
			}
		}
	}

	public static function render_admin_notice(): void
	{
		echo '<div class="notice notice-info is-dismissible"><p>';
		echo esc_html__(
			'Kali Forms restored your form configuration from the pre-upgrade backup after detecting a version downgrade.',
			'kaliforms'
		);
		echo '</p></div>';
	}

	private static function should_run(): bool
	{
		if (!defined('KALIFORMS_VERSION') || version_compare(KALIFORMS_VERSION, '3.0.0', '>=')) {
			return false;
		}

		if (get_option(self::V2_MIGRATED_OPTION_KEY, false) !== false) {
			return true;
		}

		if (get_option(self::SITE_SNAPSHOT_OPTION_KEY, false) !== false) {
			return true;
		}

		return self::any_form_has_snapshot();
	}

	private static function should_restore_site_options(): bool
	{
		$snapshot = get_option(self::SITE_SNAPSHOT_OPTION_KEY, false);
		if (!is_array($snapshot) || empty($snapshot['options']) || !is_array($snapshot['options'])) {
			return false;
		}

		return get_option(self::V2_MIGRATED_OPTION_KEY, false) !== false;
	}

	private static function restore_site_options(): bool
	{
		$snapshot = get_option(self::SITE_SNAPSHOT_OPTION_KEY, false);
		if (!is_array($snapshot) || empty($snapshot['options']) || !is_array($snapshot['options'])) {
			return false;
		}

		$restored = false;
		$skip     = [
			self::SITE_SNAPSHOT_OPTION_KEY,
			self::V2_MIGRATED_OPTION_KEY,
			'kaliforms_v2_migration_progress',
			self::RESTORE_LOG_OPTION_KEY,
		];

		foreach ($snapshot['options'] as $name => $value) {
			if (!is_string($name) || $name === '' || in_array($name, $skip, true)) {
				continue;
			}

			if (get_option($name, null) === null) {
				update_option($name, $value, false);
				$restored = true;
			}
		}

		return $restored;
	}

	private static function restore_form_if_needed(int $form_id): bool
	{
		if ($form_id <= 0 || !self::form_has_snapshot($form_id)) {
			return false;
		}

		if (!self::form_needs_legacy_restore($form_id)) {
			return false;
		}

		return self::restore_form_from_snapshot($form_id);
	}

	private static function form_has_snapshot(int $form_id): bool
	{
		return metadata_exists('post', $form_id, self::FORM_SNAPSHOT_META_KEY);
	}

	private static function form_needs_legacy_restore(int $form_id): bool
	{
		foreach (self::CRITICAL_LEGACY_KEYS as $key) {
			if (!self::has_meaningful_meta($form_id, $key)) {
				return true;
			}
		}

		return metadata_exists('post', $form_id, 'layout')
			|| metadata_exists('post', $form_id, 'settings')
			|| get_option(self::V2_MIGRATED_OPTION_KEY, false) !== false;
	}

	private static function has_meaningful_meta(int $form_id, string $key): bool
	{
		if (!metadata_exists('post', $form_id, $key)) {
			return false;
		}

		$value = get_post_meta($form_id, $key, true);
		if ($value === '' || $value === false || $value === null) {
			return false;
		}

		if ($value === '[]' || $value === '{}') {
			return false;
		}

		if (is_array($value) && $value === []) {
			return false;
		}

		return true;
	}

	private static function restore_form_from_snapshot(int $form_id): bool
	{
		$snapshot = get_post_meta($form_id, self::FORM_SNAPSHOT_META_KEY, true);
		if (!is_array($snapshot) || empty($snapshot['meta']) || !is_array($snapshot['meta'])) {
			return false;
		}

		$restored = false;

		foreach ($snapshot['meta'] as $meta_key => $value) {
			if (!is_string($meta_key) || strpos($meta_key, 'kaliforms_') !== 0) {
				continue;
			}

			if (!metadata_exists('post', $form_id, $meta_key) || !self::has_meaningful_meta($form_id, $meta_key)) {
				update_post_meta($form_id, $meta_key, $value);
				$restored = true;
			}
		}

		return $restored;
	}

	private static function any_form_has_snapshot(): bool
	{
		foreach (self::get_form_ids() as $form_id) {
			if (self::form_has_snapshot((int) $form_id)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return int[]
	 */
	private static function get_form_ids(): array
	{
		return get_posts([
			'post_type'      => 'kaliforms_forms',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		]);
	}
}
