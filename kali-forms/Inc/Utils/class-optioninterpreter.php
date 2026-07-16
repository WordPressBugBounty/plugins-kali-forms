<?php

namespace KaliForms\Inc\Utils;

use KaliForms\Inc\Backend\Sanitizers;

/**
 * Trait Option Interpreter
 *
 * @package Inc\Utils
 */
trait OptionInterpreter
{
	/**
	 * Option array
	 *
	 * @var array
	 */
	public $options = [
		'userRegistration' => 'userRegistrationData',
		'newsletter'       => 'newsletterData',
		'slack'            => 'slackData',
		'googleSheets'     => 'googleSheetsData',
	];
	/**
	 * A middleware function
	 *
	 * @return void
	 */
	private function _middleware($option, $value)
	{
		if (in_array($option, ['emails', 'userRegistrationData'], true)) {
			$value = ($value !== null && $value !== '')
				? Sanitizers::decode_json_meta($value, false)
				: [];
		}

		if (in_array($option, ['fieldComponents'], true)) {
			$value = ($value !== null && $value !== '')
				? Sanitizers::decode_json_meta($value, false, 512, JSON_HEX_QUOT)
				: [];
		}

		return $value;
	}
}
