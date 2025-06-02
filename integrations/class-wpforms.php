<?php
/**
 * WPForms integration for Referrer Tracker
 *
 * @package Referrer_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class RT_Integration_WPForms
 * 
 * Handles integration with WPForms
 */
class RT_Integration_WPForms {
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
        // Make sure WordPress functions are available
        if (!function_exists('get_option') || !function_exists('add_filter')) {
            return;
        }
        
        // Get settings
        $options = get_option('rt_settings');
        $this->field_prefix = isset($options['rt_field_prefix']) ? $options['rt_field_prefix'] : 'rt_';

        // Siempre añadir los filtros si WPForms está seleccionado
        add_filter('wpforms_field_properties', array($this, 'add_hidden_fields_wpforms'), 10, 3);
        add_filter('wpforms_frontend_form_data', array($this, 'populate_wpforms_fields'));
    }

    /**
     * Get tracking values with proper priority
     * 
     * 1. UTM parameters from URL
     * 2. Cookies
     * 3. Default values
     *
     * @return array Tracking values
     */
    private function get_tracking_values() {
        // Make sure WordPress functions are available
        if (!function_exists('get_option') || !function_exists('sanitize_text_field')) {
            return array(
                'source' => '',
                'medium' => '',
                'campaign' => '',
                'referrer' => ''
            );
        }
        
        // Get debug mode
        $debug = get_option('referrer_tracker_debug', 'no') === 'yes';
        
        // Initialize values
        $source = '';
        $medium = '';
        $campaign = '';
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        // PRIORITY 1: Check for UTM parameters first
        if (isset($_GET['utm_source']) && !empty($_GET['utm_source'])) {
            $source = sanitize_text_field($_GET['utm_source']);
            if ($debug) {
                error_log('RT Debug: WPForms - Found utm_source parameter: ' . $source);
            }
        }
        
        if (isset($_GET['utm_medium']) && !empty($_GET['utm_medium'])) {
            $medium = sanitize_text_field($_GET['utm_medium']);
            if ($debug) {
                error_log('RT Debug: WPForms - Found utm_medium parameter: ' . $medium);
            }
        } else if (isset($_GET['urm_medium']) && !empty($_GET['urm_medium'])) {
            // Corrección para posibles errores tipográficos en los parámetros
            $medium = sanitize_text_field($_GET['urm_medium']);
            if ($debug) {
                error_log('RT Debug: WPForms - Found urm_medium parameter (typo correction): ' . $medium);
            }
        }
        
        if (isset($_GET['utm_campaign']) && !empty($_GET['utm_campaign'])) {
            $campaign = sanitize_text_field($_GET['utm_campaign']);
            if ($debug) {
                error_log('RT Debug: WPForms - Found utm_campaign parameter: ' . $campaign);
            }
        }
        
        // PRIORITY 2: If no UTM parameters, check cookies
        if (empty($source) && isset($_COOKIE['rt_source'])) {
            $source = $_COOKIE['rt_source'];
            if ($debug) {
                error_log('RT Debug: WPForms - Using cookie value for source: ' . $source);
            }
        }
        
        if (empty($medium) && isset($_COOKIE['rt_medium'])) {
            $medium = $_COOKIE['rt_medium'];
            if ($debug) {
                error_log('RT Debug: WPForms - Using cookie value for medium: ' . $medium);
            }
        }
        
        if (empty($campaign) && isset($_COOKIE['rt_campaign'])) {
            $campaign = $_COOKIE['rt_campaign'];
            if ($debug) {
                error_log('RT Debug: WPForms - Using cookie value for campaign: ' . $campaign);
            }
        }
        
        // Si no hay referrer actual, usar el valor de la cookie
        if (empty($referrer) && isset($_COOKIE['rt_referrer'])) {
            $referrer = $_COOKIE['rt_referrer'];
            if ($debug) {
                error_log('RT Debug: WPForms - Using cookie value for referrer: ' . $referrer);
            }
        }
        
        // PRIORITY 3: Set default values if still empty
        if (empty($source)) {
            $source = 'direct';
            if ($debug) {
                error_log('RT Debug: WPForms - No source found, using default: direct');
            }
        }
        
        if (empty($medium)) {
            $medium = 'none';
            if ($debug) {
                error_log('RT Debug: WPForms - No medium found, using default: none');
            }
        }
        
        if (empty($campaign)) {
            $campaign = 'none';
            if ($debug) {
                error_log('RT Debug: WPForms - No campaign found, using default: none');
            }
        }
        
        if ($debug) {
            error_log('RT Debug: WPForms - Final tracking values - source: ' . $source . ', medium: ' . $medium . ', campaign: ' . $campaign . ', referrer: ' . $referrer);
        }
        
        return array(
            'source' => $source,
            'medium' => $medium,
            'campaign' => $campaign,
            'referrer' => $referrer
        );
    }

    /**
     * Add hidden fields to WPForms
     *
     * @param array $properties Field properties
     * @param array $field Field data
     * @param array $form_data Form data
     * @return array Modified field properties
     */
    public function add_hidden_fields_wpforms($properties, $field, $form_data) {
        // Only process hidden fields
        if ($field['type'] !== 'hidden') {
            return $properties;
        }
        
        // Make sure WordPress functions are available
        if (!function_exists('get_option')) {
            return $properties;
        }
        
        // Get debug mode
        $debug = get_option('referrer_tracker_debug', 'no') === 'yes';
        
        if ($debug) {
            error_log('RT Debug: Processing WPForms hidden field - ID: ' . $field['id'] . ', Label: ' . $field['label']);
        }
        
        // Get field ID and name
        $field_id = $field['id'];
        $field_name = isset($field['label']) ? strtolower($field['label']) : '';
        
        // Get tracking values with proper priority
        $tracking_values = $this->get_tracking_values();
        $source = $tracking_values['source'];
        $medium = $tracking_values['medium'];
        $campaign = $tracking_values['campaign'];
        $referrer = $tracking_values['referrer'];
        
        if ($debug) {
            error_log('RT Debug: Tracking values for field - source: ' . $source . ', medium: ' . $medium . ', campaign: ' . $campaign . ', referrer: ' . $referrer);
        }
        
        // Check field name or label for tracking fields
        $is_source = strpos($field_name, '_source') !== false || $field_id === 8;
        $is_medium = strpos($field_name, '_medium') !== false || $field_id === 9;
        $is_campaign = strpos($field_name, '_campaign') !== false || $field_id === 10;
        $is_referrer = strpos($field_name, '_referrer') !== false || $field_id === 11;
        
        // Add CSS classes to help JavaScript identify these fields
        if ($is_source || $is_medium || $is_campaign || $is_referrer) {
            $properties['container']['class'][] = 'wpforms-field-hidden';
            
            if ($is_source) {
                $properties['container']['class'][] = 'js-rt-source';
                $properties['inputs']['primary']['class'][] = 'js-rt-source';
                $properties['inputs']['primary']['attr']['data-field-type'] = 'source';
                $properties['inputs']['primary']['value'] = $source;
                
                if ($debug) {
                    error_log('RT Debug: Set source field value: ' . $source);
                }
            } 
            else if ($is_medium) {
                $properties['container']['class'][] = 'js-rt-medium';
                $properties['inputs']['primary']['class'][] = 'js-rt-medium';
                $properties['inputs']['primary']['attr']['data-field-type'] = 'medium';
                $properties['inputs']['primary']['value'] = $medium;
                
                if ($debug) {
                    error_log('RT Debug: Set medium field value: ' . $medium);
                }
            }
            else if ($is_campaign) {
                $properties['container']['class'][] = 'js-rt-campaign';
                $properties['inputs']['primary']['class'][] = 'js-rt-campaign';
                $properties['inputs']['primary']['attr']['data-field-type'] = 'campaign';
                $properties['inputs']['primary']['value'] = $campaign;
                
                if ($debug) {
                    error_log('RT Debug: Set campaign field value: ' . $campaign);
                }
            }
            else if ($is_referrer) {
                $properties['container']['class'][] = 'js-rt-referrer';
                $properties['inputs']['primary']['class'][] = 'js-rt-referrer';
                $properties['inputs']['primary']['attr']['data-field-type'] = 'referrer';
                $properties['inputs']['primary']['value'] = $referrer;
                
                if ($debug) {
                    error_log('RT Debug: Set referrer field value: ' . $referrer);
                }
            }
        }
        
        return $properties;
    }
    
    /**
     * Populate WPForms fields with tracking values
     *
     * @param array $form_data Form data
     * @return array Modified form data
     */
    public function populate_wpforms_fields($form_data) {
        // Make sure WordPress functions are available
        if (!function_exists('get_option')) {
            return $form_data;
        }
        
        // Get debug mode
        $debug = get_option('referrer_tracker_debug', 'no') === 'yes';
        
        if ($debug) {
            error_log('RT Debug: Populating WPForms fields');
        }
        
        // Get tracking values with proper priority
        $tracking_values = $this->get_tracking_values();
        $source = $tracking_values['source'];
        $medium = $tracking_values['medium'];
        $campaign = $tracking_values['campaign'];
        $referrer = $tracking_values['referrer'];
        
        if ($debug) {
            error_log('RT Debug: Tracking values for form population - source: ' . $source . ', medium: ' . $medium . ', campaign: ' . $campaign . ', referrer: ' . $referrer);
        }
        
        // Loop through fields
        if (isset($form_data['fields']) && is_array($form_data['fields'])) {
            foreach ($form_data['fields'] as $id => $field) {
                // Only process hidden fields
                if ($field['type'] !== 'hidden') {
                    continue;
                }
                
                $field_name = isset($field['label']) ? strtolower($field['label']) : '';
                
                // Check field name or ID for tracking fields
                if (strpos($field_name, '_source') !== false || $id === 8) {
                    $form_data['fields'][$id]['default_value'] = $source;
                    if ($debug) {
                        error_log('RT Debug: Set source field default value: ' . $source);
                    }
                } 
                else if (strpos($field_name, '_medium') !== false || $id === 9) {
                    $form_data['fields'][$id]['default_value'] = $medium;
                    if ($debug) {
                        error_log('RT Debug: Set medium field default value: ' . $medium);
                    }
                }
                else if (strpos($field_name, '_campaign') !== false || $id === 10) {
                    $form_data['fields'][$id]['default_value'] = $campaign;
                    if ($debug) {
                        error_log('RT Debug: Set campaign field default value: ' . $campaign);
                    }
                }
                else if (strpos($field_name, '_referrer') !== false || $id === 11) {
                    $form_data['fields'][$id]['default_value'] = $referrer;
                    if ($debug) {
                        error_log('RT Debug: Set referrer field default value: ' . $referrer);
                    }
                }
            }
        }
        
        return $form_data;
    }
}
