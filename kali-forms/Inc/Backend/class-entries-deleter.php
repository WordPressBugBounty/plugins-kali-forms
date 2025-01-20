<?php

namespace KaliForms\Inc\Backend;

if (!defined('ABSPATH')) {
	exit;
}

class Entries_Deleter
{
	/**
	 * Plugin slug
	 *
	 * @var string
	 */
	protected $slug = 'kaliforms';

	/**
	 * Forms that are eligible
	 *
	 * @var array
	 */
	private $forms = [];
	/**
	 * Basic constructor
	 *
	 * Entries deleter
	 */
	public function __construct()
	{
		$this->_collect_eligible_forms();
		add_action($this->slug . '_delete_entries', [$this, '_delete_entries']);
		$event = wp_get_scheduled_event($this->slug . '_delete_entries');
		if (!$event) {
			wp_schedule_event(strtotime('00:00:00'), 'daily', $this->slug . '_delete_entries');
		}
	}
	/**
	 * Get forms that are eligible for deletion
	 *
	 * @return void
	 */
	private function _collect_eligible_forms()
	{
		$args = [
			'post_type' => "{$this->slug}_forms",
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'meta_query' => [
				'relation' => 'AND',
				[
					'key' => "{$this->slug}_delete_entries_after",
					'value' => 0,
					'compare' => '>',
				],
				[
					'key' => "{$this->slug}_save_form_submissions",
					'value' => 1,
				],
			],
		];

		$query = new \WP_Query($args);
		wp_reset_postdata();

		foreach ($query->posts as $post) {
			$this->forms[] = $post->ID;
		};
	}

	public function _delete_entries()
	{
		// Add initial log entry
		error_log('[KaliForms] Starting scheduled entries cleanup: ' . current_time('mysql'));

		$formWithEntries = [];

		foreach ($this->forms as $form) {
			$args = [
				'post_type'      => $this->slug . '_submitted',
				'meta_key'       => 'formId',
				'posts_per_page' => -1,
				'meta_query'     => [
					[
						'key'     => 'formId',
						'value'   => $form,
						'compare' => '=',
					],
				],
			];

			$query   = new \WP_Query($args);
			$counter = 0;
			if ($query->have_posts()) {
				$counter = $query->post_count;
			}
			wp_reset_postdata();

			if ($counter > 0) {
				$formWithEntries[] = [
					'id' => $form,
					'interval' => absint(get_post_meta($form, $this->slug . '_delete_entries_after', true)),
				];
			}
		}

		foreach ($formWithEntries as $form) {
			error_log("[KaliForms] Processing form ID: {$form['id']} with interval: {$form['interval']} days");

			$args = [
				'post_type'      => $this->slug . '_submitted',
				'meta_key'       => 'formId',
				'posts_per_page' => -1,
				'meta_query'     => [
					[
						'key'     => 'formId',
						'value'   => $form['id'],
						'compare' => '=',
					],
				],
			];

			$query = new \WP_Query($args);
			if ($query->have_posts()) {
				error_log("[KaliForms] Found {$query->post_count} entries to process for form {$form['id']}");

				foreach ($query->posts as $post) {
					// Get timestamps in site's timezone
					$current_time = current_time('timestamp', true);
					$post_time = get_date_from_gmt($post->post_date, 'U');
					$diff = $current_time - $post_time;
					$days_diff = round($diff / DAY_IN_SECONDS, 2);

					error_log(sprintf(
						'[KaliForms] Entry ID: %d, Post Date: %s, Age: %.2f days, Threshold: %d days',
						$post->ID,
						$post->post_date,
						$days_diff,
						$form['interval']
					));

					if ($diff > $form['interval'] * DAY_IN_SECONDS) {
						error_log("[KaliForms] Deleting entry ID: {$post->ID} (exceeded {$form['interval']} days)");
						wp_delete_post($post->ID, true);
					}
				}
			}
			wp_reset_postdata();
		}

		error_log('[KaliForms] Finished scheduled entries cleanup: ' . current_time('mysql'));
	}
}
