<?php

namespace KaliForms\Inc\Utils;

/**
 * Trait Duplicate_Post
 *
 * @package Inc\Utils
 */
trait Duplicate_Post
{
	/**
	 * Init the duplicate post functionality
	 *
	 * @return void
	 */
	public function init_duplicate_post()
	{
		/**
		 * Add the post row actions
		 */
		add_filter('post_row_actions', [$this, 'add_duplicate_link'], 10, 2);
		/**
		 * Register the AJAX action
		 */
		add_action(
			'wp_ajax_kaliforms_duplicate_post',
			[$this, 'duplicate_post']
		);
		/**
		 * Register the "deny" action
		 */
		add_action(
			'wp_ajax_nopriv_kaliforms_duplicate_post',
			[$this, 'denied']
		);
	}
	/**
	 * If the user is not authorized, deny action
	 */
	public function denied()
	{
		wp_die(esc_html__('Denied', 'kali-forms'));
	}
	/**
	 * Duplicate post function
	 *
	 * @return void
	 */
	public function duplicate_post()
	{
		if (!isset($_POST['args'], $_POST['args']['nonce'], $_POST['args']['id'])) {
			wp_die('Denied');
		}
		if (!wp_verify_nonce(sanitize_key(wp_unslash($_POST['args']['nonce'])), 'kaliforms_nonce')) {
			wp_die('Denied');
		}

		if (!current_user_can('edit_posts')) {
			wp_die('Denied');
		}

		$source_id = absint(wp_unslash($_POST['args']['id']));
		if (!$source_id) {
			wp_die('Denied');
		}

		if (!current_user_can('edit_post', $source_id)) {
			wp_die('Denied');
		}

		$oldpost = get_post($source_id);
		if (!$oldpost || $oldpost->post_type !== 'kaliforms_forms') {
			wp_die('Denied');
		}

		$title = get_the_title($source_id);
		$post  = [
			'post_title'  => $title . ' ' . esc_html__('(duplicate)', 'kali-forms'),
			'post_status' => current_user_can('publish_posts') ? 'publish' : 'draft',
			'post_type'   => 'kaliforms_forms',
			'post_author' => get_current_user_id(),
		];

		$new_post_id = wp_insert_post($post);
		if (is_wp_error($new_post_id) || !$new_post_id) {
			wp_die('Denied');
		}

		$data = get_post_custom($source_id);
		foreach ($data as $key => $values) {
			foreach ($values as $value) {
				if (in_array($key, ['kaliforms_field_components', 'kaliforms_grid', 'kaliforms_emails'], true)) {
					$value = $this->_get_value($value);
				}
				add_post_meta($new_post_id, $key, $value);
			}
		}

		wp_die(wp_json_encode([
			'success' => true,
			'newId'   => $new_post_id,
		]));
	}
	/**
	 * Loops and hoops just to sanitize properly
	 *
	 * @param [type] $value
	 * @return void
	 */
	public function _get_value($value)
	{
		return wp_json_encode(json_decode($this->preg_replace_add_slash_json($value)));
	}
	/**
	 * Sanitize properly the stuff
	 *
	 * @param [type] $value
	 * @return string
	 */
	public function preg_replace_add_slash_json($value)
	{
		return preg_replace('/(u[0-9a-fA-F]{4})/i', '\\\$1', $value);
	}
}
