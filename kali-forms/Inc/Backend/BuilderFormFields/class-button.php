<?php

namespace KaliForms\Inc\Backend\BuilderFormFields;

use KaliForms\Inc\Utils\Click_Action_Guard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Button
 *
 * @package Inc\Backend\BuilderFormFields
 */
class Button extends Form_Field {

	/**
	 * @var string
	 */
	public $id = 'button';
	/**
	 * @var string
	 */
	public $type = 'button';
	/**
	 * Button constructor.
	 *
	 * @param $args
	 */
	public function __construct( $args ) {
		parent::__construct( $args );
		$this->icon  = 'icon-button';
		$this->label = esc_html__( 'Button', 'kali-forms' );

		// Custom JS on click requires unfiltered_html (same privilege bar as raw HTML/JS in core).
		if ( ! Click_Action_Guard::current_user_can_use() ) {
			$this->remove_props( array( 'clickAction' ) );
		}
	}
	/**
	 * Sets common props
	 */
	public function set_common_props() {
		$this->properties = array(
			'id'          => array(
				'label' => esc_html__( 'Button id', 'kali-forms' ),
				'type'  => 'textbox',
				'value' => $this->id,
				'group' => 'advanced',
			),
			'clickAction' => array(
				'label' => esc_html__( 'Function to run on click', 'kali-forms' ),
				'type'  => 'textbox',
				'value' => '',
				'group' => 'advanced',
			),
			'caption'     => array(
				'label' => esc_html__( 'Button caption/label', 'kali-forms' ),
				'type'  => 'textbox',
				'value' => '',
			),
			'description' => array(
				'label' => esc_html__( 'Button description', 'kali-forms' ),
				'type'  => 'textbox',
				'value' => '',
			),
		);
	}
}
