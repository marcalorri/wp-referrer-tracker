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
 * Class Refetrfo_Integration_CF7
 * 
 * Handles integration with Contact Form 7
 */
class Refetrfo_Integration_CF7 {
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
        $options = get_option('refetrfo_settings');
        $this->field_prefix = isset($options['refetrfo_field_prefix']) ? $options['refetrfo_field_prefix'] : 'refetrfo_';

        // Si está activada la inserción automática de campos
        if (isset($options['refetrfo_auto_fields']) && $options['refetrfo_auto_fields']) {
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
[hidden {$prefix}source class:js-refetrfo-source \"\" default:\"{$tracking['source']}\"]
[hidden {$prefix}medium class:js-refetrfo-medium \"\" default:\"{$tracking['medium']}\"]
[hidden {$prefix}campaign class:js-refetrfo-campaign \"\" default:\"{$tracking['campaign']}\"]
[hidden {$prefix}referrer class:js-refetrfo-referrer \"\" default:\"{$tracking['referrer']}\"]
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
        $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
        // Debug functionality removed for production

        // PRIORIDAD 1: UTM en URL
        // Note: UTM parameters are public tracking parameters used for analytics, not sensitive form data.
        // No nonce verification is needed as these are read-only GET parameters for tracking purposes.
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
        if (empty($source) && isset($_COOKIE['refetrfo_source'])) {
            $source = sanitize_text_field(wp_unslash($_COOKIE['refetrfo_source']));
        }
        if (empty($medium) && isset($_COOKIE['refetrfo_medium'])) {
            $medium = sanitize_text_field(wp_unslash($_COOKIE['refetrfo_medium']));
        }
        if (empty($campaign) && isset($_COOKIE['refetrfo_campaign'])) {
            $campaign = sanitize_text_field(wp_unslash($_COOKIE['refetrfo_campaign']));
        }
        if (empty($referrer) && isset($_COOKIE['refetrfo_referrer'])) {
            $referrer = esc_url_raw(wp_unslash($_COOKIE['refetrfo_referrer']));
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
