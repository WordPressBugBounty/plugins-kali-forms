<?php

namespace KaliForms\Inc\Utils;

/**
 * Trait TransientHelper
 *
 * @package Inc\Utils
 */
trait TransientHelper
{
	/**
	 * Adds a transient to the website
	 *
	 * @return void
	 */
	public function add_transient($id, $value, $expiration)
	{
	}

	/**
	 * Removes transient
	 */
	public function remove_transient($id, $value)
	{
	}

	/**
	 * Schedules an event
	 *
	 * @return void
	 */
	public function schedule_event($action, $function)
	{
	}

	/**
	 * Delets a transient file
	 *
	 * @param [type] $args
	 * @return void
	 */
	public function delete_transient_file($id)
	{
		$attachment_id = absint($id);
		if (!$attachment_id || get_post_type($attachment_id) !== 'attachment') {
			return;
		}

		$transient = get_transient('kaliforms_dont_delete_this_image_' . $attachment_id);
		if (!$transient && $this->_attachment_referenced_by_submission($attachment_id)) {
			return;
		}

		if (!$transient) {
			return wp_delete_post($attachment_id);
		}

		delete_transient('kaliforms_dont_delete_this_image_' . $attachment_id);
	}

	/**
	 * Whether an attachment ID is stored on a form submission entry.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return bool
	 */
	private function _attachment_referenced_by_submission($attachment_id)
	{
		global $wpdb;

		$attachment_id = absint($attachment_id);
		if (!$attachment_id) {
			return false;
		}

		$referenced = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT pm.post_id
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE p.post_type = %s
				AND (
					pm.meta_value = %s
					OR FIND_IN_SET(%d, pm.meta_value) > 0
				)
				LIMIT 1",
				'kaliforms_submitted',
				(string) $attachment_id,
				$attachment_id
			)
		);

		return !empty($referenced);
	}
}
