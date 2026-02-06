<?php

namespace KaliForms\Inc\Backend\BuilderFormFields;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Button
 *
 * @package Inc\Backend\BuilderFormFields
 */
class Button extends Form_Field
{
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
    public function __construct($args)
    {
        parent::__construct($args);
        $this->icon = 'icon-button';
        $this->label = esc_html__('Button', 'kali-forms');
    }
    /**
     * Sets common props
     */
    public function set_common_props()
    {
        $this->properties = [
            'id' => [
                'label' => esc_html__('Button id', 'kali-forms'),
                'type' => 'textbox',
                'value' => $this->id,
                'group' => 'advanced',
            ],
            'clickAction' => [
                'label' => esc_html__('Function to run on click', 'kali-forms'),
                'type' => 'textbox',
                'value' => '',
                'group' => 'advanced',
            ],
            'caption' => [
                'label' => esc_html__('Button caption/label', 'kali-forms'),
                'type' => 'textbox',
                'value' => '',
            ],
            'description' => [
                'label' => esc_html__('Button description', 'kali-forms'),
                'type' => 'textbox',
                'value' => '',
            ],
        ];
    }
}
