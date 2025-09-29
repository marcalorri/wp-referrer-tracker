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
        // Siempre añadir filtros para rellenar dinámicamente los campos ocultos
        add_filter('gform_field_value_rt_source', array($this, 'gform_dynamic_value_source'));
        add_filter('gform_field_value_rt_medium', array($this, 'gform_dynamic_value_medium'));
        add_filter('gform_field_value_rt_campaign', array($this, 'gform_dynamic_value_campaign'));
        add_filter('gform_field_value_rt_referrer', array($this, 'gform_dynamic_value_referrer'));
    }

    /**
     * Add hidden fields to Gravity Forms
     *
     * @param array $form Form data
     * @return array Modified form data
     */
    public function add_hidden_fields_gravity($form) {
        // Get debug mode
        // Debug functionality removed for production
        
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
        $tracking = $this->get_tracking_values();
        $source = $tracking['source'];
        $medium = $tracking['medium'];
        $campaign = $tracking['campaign'];
        $referrer = $tracking['referrer'];

        // Debug functionality removed for production

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

    /**
     * Obtiene los valores de tracking en orden de prioridad:
     * 1. UTM en URL
     * 2. Corrección de errores tipográficos
     * 3. Cookies
     * 4. Por defecto
     * @return array
     */
    private function get_tracking_values() {
        $source = '';
        $medium = '';
        $campaign = '';
        $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
        // Debug functionality removed for production

        // PRIORIDAD 1: UTM en URL
        // Note: UTM parameters are public tracking parameters, not sensitive form data
        if (isset($_GET['utm_source']) && !empty($_GET['utm_source'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $source = sanitize_text_field(wp_unslash($_GET['utm_source'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
        if (isset($_GET['utm_medium']) && !empty($_GET['utm_medium'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $medium = sanitize_text_field(wp_unslash($_GET['utm_medium'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } elseif (isset($_GET['urm_medium']) && !empty($_GET['urm_medium'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            // Corrección de error tipográfico
            $medium = sanitize_text_field(wp_unslash($_GET['urm_medium'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
        if (isset($_GET['utm_campaign']) && !empty($_GET['utm_campaign'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $campaign = sanitize_text_field(wp_unslash($_GET['utm_campaign'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        // PRIORIDAD 2: Cookies
        if (empty($source) && isset($_COOKIE['rt_source'])) {
            $source = sanitize_text_field(wp_unslash($_COOKIE['rt_source']));
        }
        if (empty($medium) && isset($_COOKIE['rt_medium'])) {
            $medium = sanitize_text_field(wp_unslash($_COOKIE['rt_medium']));
        }
        if (empty($campaign) && isset($_COOKIE['rt_campaign'])) {
            $campaign = sanitize_text_field(wp_unslash($_COOKIE['rt_campaign']));
        }
        if (empty($referrer) && isset($_COOKIE['rt_referrer'])) {
            $referrer = esc_url_raw(wp_unslash($_COOKIE['rt_referrer']));
        }

        // Defaults
        if (empty($source)) $source = 'direct';
        if (empty($medium)) $medium = 'none';
        if (empty($campaign)) $campaign = 'none';
        if (empty($referrer)) $referrer = '';

        return array(
            'source' => $source,
            'medium' => $medium,
            'campaign' => $campaign,
            'referrer' => $referrer
        );
    }

    /**
     * Dynamic value for source field
     *
     * @return string Source value
     */
    public function gform_dynamic_value_source() {
        $tracking = $this->get_tracking_values();
        return $tracking['source'];
    }

    /**
     * Dynamic value for medium field
     *
     * @return string Medium value
     */
    public function gform_dynamic_value_medium() {
        $tracking = $this->get_tracking_values();
        return $tracking['medium'];
    }

    /**
     * Dynamic value for campaign field
     *
     * @return string Campaign value
     */
    public function gform_dynamic_value_campaign() {
        $tracking = $this->get_tracking_values();
        return $tracking['campaign'];
    }

    /**
     * Dynamic value for referrer field
     *
     * @return string Referrer value
     */
    public function gform_dynamic_value_referrer() {
        $tracking = $this->get_tracking_values();
        return $tracking['referrer'];
    }
}
