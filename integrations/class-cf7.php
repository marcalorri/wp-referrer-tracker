<?php
/**
 * Contact Form 7 integration for Referrer Tracker
 *
 * @package Referrer_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class RT_Integration_CF7
 * 
 * Handles integration with Contact Form 7
 */
class RT_Integration_CF7 {
    /**
     * Field prefix for the tracking fields
     *
     * @var string
     */
    private $field_prefix;

    /**
     * Constructor
     */
    public function __construct() {
        // Get settings
        $options = get_option('rt_settings');
        $this->field_prefix = isset($options['rt_field_prefix']) ? $options['rt_field_prefix'] : 'rt_';

        // Si está activada la inserción automática de campos
        if (isset($options['rt_auto_fields']) && $options['rt_auto_fields']) {
            add_filter('wpcf7_form_elements', array($this, 'add_hidden_fields_cf7'));
        }
    }

    /**
     * Añadir campos ocultos a Contact Form 7
     *
     * @param string $elements Form elements
     * @return string Modified form elements
     */
    public function add_hidden_fields_cf7($elements) {
        $prefix = $this->field_prefix;
        
        $hidden_fields = "
[hidden {$prefix}source class:js-rt-source \"\"]
[hidden {$prefix}medium class:js-rt-medium \"\"]
[hidden {$prefix}campaign class:js-rt-campaign \"\"]
[hidden {$prefix}referrer class:js-rt-referrer \"\"]
";
        
        return $elements . $hidden_fields;
    }
}
