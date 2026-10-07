<?php

namespace KaliForms\Inc\Frontend\FormFields;

use KaliForms\Inc\Utils\Click_Action_Guard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Button
 *
 * @package Inc\Frontend\FormFields;
 */
class Button extends Form_Field {

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->id = 'button';
	}

	/**
	 * Render function
	 *
	 * @return void
	 */
	public function render( $item, $form_info ) {
		$item['class'] = 'input';

		$item['type']    = 'button';
		$item['default'] = $item['caption'];

		$form_id      = isset( $form_info['form_id'] ) ? absint( $form_info['form_id'] ) : 0;
		$click_action = isset( $item['clickAction'] ) ? $item['clickAction'] : '';
		if ( $click_action !== '' && Click_Action_Guard::form_may_emit( $form_id ) ) {
			$item['onclick'] = $click_action;
		}

		if ( isset( $item['style'] ) && ! empty( $item['style'] ) && $item['style'] !== 'default' ) {
			$item['class'] .= ' ' . $item['style'];
		}
		$attributes = $this->generate_attribute_string( $item );
		$offset     = isset( $item['offset'] ) && $item['offset'] > 0 ? 'offset-md-' . absint( $item['offset'] ) : '';
		$div        = '<div class="col-12 col-md-' . absint( $item['col'] ) . ' ' . $offset . '">';
		$div       .= '<input ' . $attributes . '>';
		$div       .= '</div>';

		return $div;
	}
}
