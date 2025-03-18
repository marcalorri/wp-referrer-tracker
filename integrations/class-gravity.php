<?php
/**
 * Gravity Forms integration for Referrer Tracker
 *
 * @package Referrer_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class RT_Integration_Gravity
 * 
 * Handles integration with Gravity Forms
 */
class RT_Integration_Gravity {
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
            // Add Gravity Forms integration
            add_filter('gform_pre_render', array($this, 'add_hidden_fields_gravity'));
            add_filter('gform_pre_validation', array($this, 'populate_gravity_fields'));
        }
    }

    /**
     * Add hidden fields to Gravity Forms
     *
     * @param array $form Form data
     * @return array Modified form data
     */
    public function add_hidden_fields_gravity($form) {
        // Get debug mode
        $debug = get_option('referrer_tracker_debug', 'no') === 'yes';
        
        if ($debug) {
            error_log('RT Debug: Processing Gravity Forms form - ID: ' . $form['id']);
        }
        
        $prefix = $this->field_prefix;
        $has_source = false;
        $has_medium = false;
        $has_campaign = false;
        $has_referrer = false;
        
        // Check if the form already has our tracking fields
        foreach ($form['fields'] as $field) {
            if ($field->type == 'hidden') {
                $field_label = strtolower($field->label);
                
                if (strpos($field_label, 'source') !== false) {
                    $has_source = true;
                    $field->cssClass .= ' js-rt-source';
                }
                
                if (strpos($field_label, 'medium') !== false) {
                    $has_medium = true;
                    $field->cssClass .= ' js-rt-medium';
                }
                
                if (strpos($field_label, 'campaign') !== false) {
                    $has_campaign = true;
                    $field->cssClass .= ' js-rt-campaign';
                }
                
                if (strpos($field_label, 'referrer') !== false) {
                    $has_referrer = true;
                    $field->cssClass .= ' js-rt-referrer';
                }
            }
        }
        
        // Add missing fields
        if (!$has_source) {
            $source_field = GF_Fields::create(array(
                'type' => 'hidden',
                'id' => 1000, // Use a high ID to avoid conflicts
                'formId' => $form['id'],
                'label' => 'Source',
                'cssClass' => 'js-rt-source',
                'inputName' => $prefix . 'source'
            ));
            $form['fields'][] = $source_field;
        }
        
        if (!$has_medium) {
            $medium_field = GF_Fields::create(array(
                'type' => 'hidden',
                'id' => 1001,
                'formId' => $form['id'],
                'label' => 'Medium',
                'cssClass' => 'js-rt-medium',
                'inputName' => $prefix . 'medium'
            ));
            $form['fields'][] = $medium_field;
        }
        
        if (!$has_campaign) {
            $campaign_field = GF_Fields::create(array(
                'type' => 'hidden',
                'id' => 1002,
                'formId' => $form['id'],
                'label' => 'Campaign',
                'cssClass' => 'js-rt-campaign',
                'inputName' => $prefix . 'campaign'
            ));
            $form['fields'][] = $campaign_field;
        }
        
        if (!$has_referrer) {
            $referrer_field = GF_Fields::create(array(
                'type' => 'hidden',
                'id' => 1003,
                'formId' => $form['id'],
                'label' => 'Referrer',
                'cssClass' => 'js-rt-referrer',
                'inputName' => $prefix . 'referrer'
            ));
            $form['fields'][] = $referrer_field;
        }
        
        return $form;
    }

    /**
     * Populate Gravity Forms fields with tracking values
     *
     * @param array $form Form data
     * @return array Modified form data
     */
    public function populate_gravity_fields($form) {
        // Get debug mode
        $debug = get_option('referrer_tracker_debug', 'no') === 'yes';
        
        // Get tracking values from cookies
        $source = isset($_COOKIE['rt_source']) ? $_COOKIE['rt_source'] : '';
        $medium = isset($_COOKIE['rt_medium']) ? $_COOKIE['rt_medium'] : '';
        $campaign = isset($_COOKIE['rt_campaign']) ? $_COOKIE['rt_campaign'] : '';
        $referrer = isset($_COOKIE['rt_referrer']) ? $_COOKIE['rt_referrer'] : '';
        
        if ($debug) {
            error_log('RT Debug: Populating Gravity Forms fields - source: ' . $source . ', medium: ' . $medium . ', campaign: ' . $campaign . ', referrer: ' . $referrer);
        }
        
        // Populate fields
        foreach ($form['fields'] as &$field) {
            if ($field->type == 'hidden') {
                $field_label = strtolower($field->label);
                
                if (strpos($field_label, 'source') !== false && !empty($source)) {
                    $_POST['input_' . $field->id] = $source;
                }
                
                if (strpos($field_label, 'medium') !== false && !empty($medium)) {
                    $_POST['input_' . $field->id] = $medium;
                }
                
                if (strpos($field_label, 'campaign') !== false && !empty($campaign)) {
                    $_POST['input_' . $field->id] = $campaign;
                }
                
                if (strpos($field_label, 'referrer') !== false && !empty($referrer)) {
                    $_POST['input_' . $field->id] = $referrer;
                }
            }
        }
        
        return $form;
    }
}
