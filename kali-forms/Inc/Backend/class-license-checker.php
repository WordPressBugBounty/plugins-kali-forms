<?php

namespace KaliForms\Inc\Backend;

use KaliForms\Inc\Backend\Notifications\Notification;

if (!defined('ABSPATH')) {
	exit;
}

class License_Checker
{
	/**
	 * Plugin slug
	 *
	 * @var string
	 */
	protected $slug = 'kaliforms';

	protected $plugins = [
		"kali-forms-pro" => 'not-installed',
		"kali-forms-google-sheets" => 'not-installed',
		"kali-forms-webhooks" => 'not-installed',
		"kali-forms-payments" => 'not-installed',
		"kali-forms-google-analytics" => 'not-installed',
		"kali-forms-user-registration" => 'not-installed',
		"kali-forms-digital-signature" => 'not-installed',
		"kali-forms-newsletter" => 'not-installed',
		"kali-forms-sms" => 'not-installed',
		"kali-forms-slack" => 'not-installed',
		"kali-forms-submissions" => 'not-installed',
	];

	protected $license = false;

	public function __construct()
	{
		if (!defined('KALIFORMS_PRO_PLUGIN_FILE') || wp_doing_ajax() || wp_doing_cron() || $this->is_localhost()) {
			return;
		}

		// $this->run();
	}

	public function run()
	{
		$this->check_installed();
		$this->check_license();

		$this->take_action();
	}

	public function check_installed()
	{
		$plugins = get_plugins();
		foreach ($this->plugins as $k => $v) {
			$this->plugins[$k] = array_key_exists($k . '/' . $k . '.php', $plugins) ? 'installed' : 'not-installed';
			if ($this->plugins[$k] === 'installed') {
				$this->plugins[$k] = is_plugin_active($k . '/' . $k . '.php') ? 'active' : 'installed';
			}
		}
	}

	public function check_license()
	{
		$option = get_option('kaliforms_pro_license_status');
		$licenseData = get_option('kaliforms_pro_license_data');

		if ($licenseData->success && $licenseData->license === 'item_name_mismatch') {
			$this->license = true;
			return;
		}

		if ($licenseData->success && $licenseData->expires === 'lifetime') {
			$this->license = true;
			return;
		}

		$this->license = $option === 'valid';
	}

	public function take_action()
	{
		if ($this->license) {
			return;
		}

		$transient_name = 'kaliforms_license_check_date';
		$transient = get_transient($transient_name);
		$notifications = Notifications::get_instance();

		if (!$transient) {
			set_transient($transient_name, date('Y-m-d'));
			return;
		}
		$notice_time = strtotime($transient);
		$a_week_later = strtotime('+1 week', $notice_time);
		$two_weeks_later = strtotime('+2 weeks', $notice_time);

		if (time() > $notice_time && time() < $a_week_later && current_user_can('manage_options')) {
			return $notifications->add_notice([
				"id" => "license_checker",
				"type" => "notice notice-info",
				"message" => sprintf(
					__("You have not activated your Kali Forms Pro license. Please %sactivate it%s to receive updates and support.", 'kaliforms'),

					'<a class="link" href="' . admin_url('edit.php?post_type=kaliforms_forms&page=kaliforms-store-auth') . '">',
					'</a>'
				),
				"dismissable" => true,
			]);
		}

		if (time() > $two_weeks_later) {
			if (current_user_can('manage_options')) {
				$notifications->add_notice([
					"id" => "license_checker",
					"type" => "notice notice-warning",
					"message" => __('Kali Forms Pro addons have been deactivated.', 'kaliforms'),
					"dismissable" => true,
				]);
			}

			$active = [];
			foreach ($this->plugins as $k => $v) {
				if ($v === 'active') {
					$active[] = $k . '/' . $k . '.php';
				}
			}

			deactivate_plugins($active);
			delete_transient($transient_name);
		}
	}

	public function is_localhost()
	{
		$whitelist = ['127.0.0.1', '::1'];

		if (in_array($_SERVER['REMOTE_ADDR'], $whitelist) || array_key_exists('HTTP_HOST', $_SERVER) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
			return true;
		}

		return false;
	}
}
