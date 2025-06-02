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
        $tracking = $this->get_tracking_values();

        $hidden_fields = "
[hidden {$prefix}source class:js-rt-source \"\" default:\"{$tracking['source']}\"]
[hidden {$prefix}medium class:js-rt-medium \"\" default:\"{$tracking['medium']}\"]
[hidden {$prefix}campaign class:js-rt-campaign \"\" default:\"{$tracking['campaign']}\"]
[hidden {$prefix}referrer class:js-rt-referrer \"\" default:\"{$tracking['referrer']}\"]
";
        return $elements . $hidden_fields;
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
        $referrer = isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field($_SERVER['HTTP_REFERER']) : '';
        $debug = get_option('referrer_tracker_debug', 'no') === 'yes';

        // PRIORIDAD 1: UTM en URL
        if (isset($_GET['utm_source']) && !empty($_GET['utm_source'])) {
            $source = sanitize_text_field($_GET['utm_source']);
            if ($debug) error_log('RT Debug: CF7 - utm_source: ' . $source);
        }
        if (isset($_GET['utm_medium']) && !empty($_GET['utm_medium'])) {
            $medium = sanitize_text_field($_GET['utm_medium']);
            if ($debug) error_log('RT Debug: CF7 - utm_medium: ' . $medium);
        } elseif (isset($_GET['urm_medium']) && !empty($_GET['urm_medium'])) {
            // Corrección de error tipográfico
            $medium = sanitize_text_field($_GET['urm_medium']);
            if ($debug) error_log('RT Debug: CF7 - urm_medium (typo): ' . $medium);
        }
        if (isset($_GET['utm_campaign']) && !empty($_GET['utm_campaign'])) {
            $campaign = sanitize_text_field($_GET['utm_campaign']);
            if ($debug) error_log('RT Debug: CF7 - utm_campaign: ' . $campaign);
        }

        // PRIORIDAD 2: Cookies
        if (empty($source) && isset($_COOKIE['rt_source'])) {
            $source = sanitize_text_field($_COOKIE['rt_source']);
            if ($debug) error_log('RT Debug: CF7 - Cookie source: ' . $source);
        }
        if (empty($medium) && isset($_COOKIE['rt_medium'])) {
            $medium = sanitize_text_field($_COOKIE['rt_medium']);
            if ($debug) error_log('RT Debug: CF7 - Cookie medium: ' . $medium);
        }
        if (empty($campaign) && isset($_COOKIE['rt_campaign'])) {
            $campaign = sanitize_text_field($_COOKIE['rt_campaign']);
            if ($debug) error_log('RT Debug: CF7 - Cookie campaign: ' . $campaign);
        }
        if (empty($referrer) && isset($_COOKIE['rt_referrer'])) {
            $referrer = sanitize_text_field($_COOKIE['rt_referrer']);
            if ($debug) error_log('RT Debug: CF7 - Cookie referrer: ' . $referrer);
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
}
