<?php

namespace KaliForms\Inc\Backend;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Translations is used to translate stuff
 *
 * @package App\Libraries
 */
class Translations
{
	/**
	 * Translations array
	 *
	 * @var array
	 */
	public $translations = [];

	/**
	 * Basic constructor
	 *
	 * Translations constructor
	 */
	public function __construct()
	{
		$this->set_general_translations();
		$this->frontend();
		$this->backend();
	}

	/**
	 * Set general translations
	 *
	 * @return array
	 */
	public function set_general_translations() {}

	public function frontend()
	{
		$this->translations['frontend'] = [
			'filePond' => [
				'labelIdle' => sprintf(
					'%s <span class="filepond--label-action"> %s </span>',
					esc_html__('Drag & Drop your files or', 'kali-forms'),
					esc_html__('Browse', 'kali-forms')
				),
			],
			'general'  => [
				'loading'   => esc_html__('LOADING', 'kali-forms'),
				'recaptcha' => esc_html__('Please complete recaptcha challenge', 'kali-forms'),
			],
		];

		$this->translations['filePond'] = [
			'labelIdle'                      => sprintf(
				'%s <span class="filepond--label-action"> %s </span>',
				esc_html__('Drag & Drop your files or', 'kali-forms'),
				esc_html__('Browse', 'kali-forms')
			),
			'labelInvalidField'              => esc_html__('Field contains invalid files', 'kali-forms'),
			'labelFileWaitingForSize'        => esc_html__('Waiting for size', 'kali-forms'),
			'labelFileSizeNotAvailable'      => esc_html__('Size not available', 'kali-forms'),
			'labelFileLoading'               => esc_html__('Loading', 'kali-forms'),
			'labelFileLoadError'             => esc_html__('Error during load', 'kali-forms'),
			'labelFileProcessing'            => esc_html__('Uploading', 'kali-forms'),
			'labelFileProcessingComplete'    => esc_html__('Upload complete', 'kali-forms'),
			'labelFileProcessingAborted'     => esc_html__('Upload cancelled', 'kali-forms'),
			'labelFileProcessingError'       => esc_html__('Error during upload', 'kali-forms'),
			'labelFileProcessingRevertError' => esc_html__('Error during revert', 'kali-forms'),
			'labelFileRemoveError'           => esc_html__('Error during remove', 'kali-forms'),
			'labelTapToCancel'               => esc_html__('tap to cancel', 'kali-forms'),
			'labelTapToRetry'                => esc_html__('tap to retry', 'kali-forms'),
			'labelTapToUndo'                 => esc_html__('tap to undo', 'kali-forms'),
			'labelButtonRemoveItem'          => esc_html__('Remove', 'kali-forms'),
			'labelButtonAbortItemLoad'       => esc_html__('Abort', 'kali-forms'),
			'labelButtonRetryItemLoad'       => esc_html__('Retry', 'kali-forms'),
			'labelButtonAbortItemProcessing' => esc_html__('Cancel', 'kali-forms'),
			'labelButtonUndoItemProcessing'  => esc_html__('Undo', 'kali-forms'),
			'labelButtonRetryItemProcessing' => esc_html__('Retry', 'kali-forms'),
			'labelButtonProcessItem'         => esc_html__('Upload', 'kali-forms'),
		];
	}

	public function backend() {}
}
