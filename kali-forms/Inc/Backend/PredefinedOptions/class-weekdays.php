<?php

namespace KaliForms\Inc\Backend\PredefinedOptions;

if (!defined('ABSPATH')) {
    exit;
}

class WeekDays
{
    /**
     * Options array
     *
     * @var array
     */
    public $options = [];
    /**
     * Does it have sub categories
     *
     * @var boolean
     */
    public $subcategories = false;
    /**
     * Predefined option label
     *
     * @var string
     */
    public $label = '';
    /**
     * Preset id
     *
     * @var string
     */
    public $id = 'weekdays';
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->set_options();
    }
    /**
     * Set options
     *
     * @return void
     */
    public function set_options()
    {
        $this->label = esc_html__('Week Days', 'kali-forms');
        $this->options = [
            ['monday' => esc_html__('Monday', 'kali-forms')],
            ['tuesday' => esc_html__('Tuesday', 'kali-forms')],
            ['wednesday' => esc_html__('Wednesday', 'kali-forms')],
            ['thursday' => esc_html__('Thursday', 'kali-forms')],
            ['friday' => esc_html__('Friday', 'kali-forms')],
            ['saturday' => esc_html__('Saturday', 'kali-forms')],
            ['sunday' => esc_html__('Sunday', 'kali-forms')],
        ];
    }
}
