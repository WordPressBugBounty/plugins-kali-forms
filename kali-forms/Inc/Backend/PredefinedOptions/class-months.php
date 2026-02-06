<?php

namespace KaliForms\Inc\Backend\PredefinedOptions;

if (!defined('ABSPATH')) {
    exit;
}

class Months
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
    public $id = 'months';
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
        $this->label = esc_html__('Months', 'kali-forms');
        $this->options = [
            ['january' => esc_html__('January', 'kali-forms')],
            ['february' => esc_html__('February', 'kali-forms')],
            ['march' => esc_html__('March', 'kali-forms')],
            ['april' => esc_html__('April', 'kali-forms')],
            ['may' => esc_html__('May', 'kali-forms')],
            ['june' => esc_html__('June', 'kali-forms')],
            ['july' => esc_html__('July', 'kali-forms')],
            ['august' => esc_html__('August', 'kali-forms')],
            ['september' => esc_html__('September', 'kali-forms')],
            ['october' => esc_html__('October', 'kali-forms')],
            ['november' => esc_html__('November', 'kali-forms')],
            ['december' => esc_html__('December', 'kali-forms')],
        ];
    }
}
