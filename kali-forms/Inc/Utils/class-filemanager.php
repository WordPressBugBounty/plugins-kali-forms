<?php

namespace KaliForms\Inc\Utils;

trait FileManager
{
	/**
	 * Validated upload context for the current request.
	 *
	 * @var array{form_id: int, field_name: string, field: object}|null
	 */
	private $upload_context = null;

	/**
	 * Load files needed for image upload
	 *
	 * @return void
	 */
	public function load_files()
	{
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/post.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
	}
	/**
	 * Runs security checks, should improve
	 *
	 * @return void
	 */
	public function run_checks()
	{
		if (empty($_FILES)) {
			$this->display_error(esc_html__('There are no files', 'kali-forms'));
		}
		if (!isset($_POST['nonce'])) {
			$this->display_error(esc_html__('Something went wrong!', 'kali-forms'));
		}
		if (!wp_verify_nonce(sanitize_key(wp_unslash($_POST['nonce'])), 'kaliforms_nonce')) {
			$this->display_error(esc_html__('Something went wrong!', 'kali-forms'));
		}

		$this->upload_context = $this->_validate_upload_context();
	}
	/**
	 * File upload
	 *
	 * @return void
	 */
	public function upload_file()
	{
		/**
		 * Run checks so we dont have any surprises
		 */
		$this->run_checks();

		/**
		 * Load files needed for the media handling
		 */
		$this->load_files();

		// Is multiple? Split
		$uploaded = count($_FILES) > 1 ? $this->_upload_multiple() : $this->_upload_single();

		// SCHEDULE IT FOR DELETE
		wp_schedule_single_event(time() + 900, $this->slug . '_delete_transient_file', [$uploaded['id']]);

		wp_die(esc_html($uploaded['frontend']));
	}
	/**
	 * Returns the current field
	 *
	 * @param [string] $id
	 * @param [array] $fields
	 * @return void
	 */
	private function _get_current_field($id, $fields)
	{
		$returnObj = null;
		foreach ($fields as $field) {
			if ($field->properties->name === $id) {
				$returnObj = $field;
			}
		}
		return $returnObj;
	}
	/**
	 * Upload single file
	 *
	 * @return void
	 */
	private function _upload_single()
	{
		$file = reset($_FILES);
		$this->_filter_extensions($this->upload_context['field']);
		$obj = apply_filters(
			$this->slug . '_before_file_upload',
			[
				'file'     => $file,
				'continue' => true,
				'post'     => $_POST,
			]
		);

		if (!$obj['continue']) {
			wp_die(
				wp_json_encode(['errors' => esc_html__('Something went wrong', 'kali-forms')])
			);
		}

		$id = media_handle_sideload($file, 0, sanitize_file_name($file['name']));
		if (is_wp_error($id)) {
			wp_delete_file($file['tmp_name']);
			wp_die(
				wp_json_encode(['errors' => $id->get_error_message()])
			);
		}

		$uniqueId = uniqid();
		update_post_meta($id, 'kaliforms_file_id', $uniqueId);
		update_post_meta($id, 'kaliforms_form_id', $this->upload_context['form_id']);
		update_post_meta($id, 'kaliforms_field_name', $this->upload_context['field_name']);
		return ['frontend' => $uniqueId, 'id' => $id];
	}

	private function _upload_multiple()
	{
		$files = reset($_FILES);
		$this->_filter_extensions($this->upload_context['field']);
		$obj   = apply_filters(
			$this->slug . '_before_file_upload',
			[
				'file'     => $files,
				'continue' => true,
				'post'     => $_POST,
			]
		);

		if (!$obj['continue']) {
			wp_die(
				wp_json_encode(['errors' => esc_html__('Something went wrong', 'kali-forms')])
			);
		}
	}

	/**
	 * Deletes a files from wp
	 *
	 * @return void
	 */
	public function delete_file()
	{
		if (!isset($_POST['nonce'])) {
			$this->display_error(esc_html__('Something went wrong!', 'kali-forms'));
		}
		if (!wp_verify_nonce(sanitize_key(wp_unslash($_POST['nonce'])), 'kaliforms_nonce')) {
			$this->display_error(esc_html__('Something went wrong!', 'kali-forms'));
		}

		if (!isset($_POST['id'])) {
			$this->display_error(esc_html__('Something went wrong!', 'kali-forms'));
		}

		$posted_id     = sanitize_text_field(wp_unslash($_POST['id']));
		$attachment_id = absint($posted_id);

		if (!$attachment_id || get_post_type($attachment_id) !== 'attachment') {
			$attachment_id = $this->_resolve_upload_attachment_id($posted_id);
		}

		if (!$attachment_id || get_post_type($attachment_id) !== 'attachment') {
			$this->display_error(esc_html__('Something went wrong!', 'kali-forms'));
		}

		if (!get_post_meta($attachment_id, 'kaliforms_file_id', true)) {
			$this->display_error(esc_html__('Something went wrong!', 'kali-forms'));
		}

		$_POST['id'] = $attachment_id;

		$crons  = _get_cron_array();
		$delete = false;
		foreach ($crons as $time => $hooks) {
			foreach ($hooks as $hook => $event) {
				if ($hook !== $this->slug . '_delete_transient_file') {
					continue;
				}

				foreach ($event as $sig => $data) {
					if ((int) $data['args'][0] === (int) $_POST['id']) {
						$delete = true;
					}
				}
			}
		}

		if ($delete) {
			// return wp_delete_post($_POST['id'], true);
			wp_update_post(
				[
					'ID'         => $_POST['id'],
					'post_title' => esc_html__('Marked for deletion', 'kali-forms'),
				]
			);

			wp_die(esc_html((string) $attachment_id));
		}

		$this->display_error(esc_html__('Something went wrong!', 'kali-forms'));
	}

	/**
	 * Resolve a frontend upload token to a media attachment ID.
	 *
	 * @param string $token Frontend uniqid or attachment ID string.
	 * @return int
	 */
	private function _resolve_upload_attachment_id($token)
	{
		$attachment_id = absint($token);
		if ($attachment_id && get_post_type($attachment_id) === 'attachment') {
			return $attachment_id;
		}

		$attachments = get_posts(
			[
				'post_type'      => 'attachment',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'   => 'kaliforms_file_id',
						'value' => $token,
					],
				],
			]
		);

		if (empty($attachments)) {
			return 0;
		}

		return absint($attachments[0]);
	}

	/**
	 * Validate that the upload request is tied to a published form field.
	 *
	 * @return array{form_id: int, field_name: string, field: object}
	 */
	private function _validate_upload_context()
	{
		if (!isset($_POST['formId'], $_POST['fieldName'])) {
			$this->display_error(esc_html__('Something went wrong!', 'kali-forms'));
		}

		$form_id    = absint(wp_unslash($_POST['formId']));
		$field_name = sanitize_text_field(wp_unslash($_POST['fieldName']));

		if (!$form_id || $field_name === '') {
			$this->display_error(esc_html__('Something went wrong!', 'kali-forms'));
		}

		$form = get_post($form_id);
		if (
			!$form
			|| $form->post_type !== 'kaliforms_forms'
			|| $form->post_status !== 'publish'
		) {
			$this->display_error(esc_html__('Something went wrong!', 'kali-forms'));
		}

		$fields_json = get_post_meta($form_id, $this->slug . '_field_components', true);
		$fields      = json_decode($fields_json, false, 512, JSON_HEX_QUOT);
		if (!is_array($fields)) {
			$this->display_error(esc_html__('Something went wrong!', 'kali-forms'));
		}

		$field = $this->_get_current_field($field_name, $fields);
		if (
			$field === null
			|| !isset($field->id)
			|| $field->id !== 'fileUpload'
		) {
			$this->display_error(esc_html__('Something went wrong!', 'kali-forms'));
		}

		return [
			'form_id'    => $form_id,
			'field_name' => $field_name,
			'field'      => $field,
		];
	}

	/**
	 * Restrict uploads to the MIME types configured on the target field.
	 *
	 * @param object $field Form field configuration object.
	 * @return void
	 */
	private function _filter_extensions($field)
	{
		$file = reset($_FILES);
		if (empty($file['tmp_name']) || empty($file['name'])) {
			$this->display_error(esc_html__('Something went wrong!', 'kali-forms'));
		}

		$allowed_mimes = $this->_get_allowed_mimes_for_field($field);
		$checked       = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed_mimes);

		if (empty($checked['ext']) || empty($checked['type'])) {
			if (is_uploaded_file($file['tmp_name'])) {
				wp_delete_file($file['tmp_name']);
			}
			wp_die(
				wp_json_encode(['errors' => esc_html__('File type not allowed', 'kali-forms')])
			);
		}
	}

	/**
	 * Build an extension-to-MIME map from a field's acceptedExtensions value.
	 *
	 * @param object $field Form field configuration object.
	 * @return array<string, string>
	 */
	private function _get_allowed_mimes_for_field($field)
	{
		$accepted = '';
		if (isset($field->properties->acceptedExtensions)) {
			$accepted = $field->properties->acceptedExtensions;
		}

		if ($accepted === '') {
			return $this->_get_default_upload_mimes();
		}

		$mime_types = array_filter(array_map('trim', explode(',', $accepted)));
		$allowed    = $this->_build_mime_map_from_types($mime_types);

		return !empty($allowed) ? $allowed : $this->_get_default_upload_mimes();
	}

	/**
	 * Default MIME allowlist when a field has no explicit configuration.
	 *
	 * @return array<string, string>
	 */
	private function _get_default_upload_mimes()
	{
		return [
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'gif'          => 'image/gif',
			'webp'         => 'image/webp',
			'pdf'          => 'application/pdf',
		];
	}

	/**
	 * Map requested MIME types to WordPress upload MIME entries.
	 *
	 * @param array<int, string> $mime_types MIME types configured on the field.
	 * @return array<string, string>
	 */
	private function _build_mime_map_from_types($mime_types)
	{
		$allowed   = [];
		$all_mimes = wp_get_mime_types();

		foreach ($all_mimes as $extensions => $mime) {
			if (in_array($mime, $mime_types, true)) {
				$allowed[$extensions] = $mime;
			}
		}

		return $allowed;
	}
}
