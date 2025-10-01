<?php
/**
 * Core tracking functionality for Referrer Tracker
 *
 * @package Referrer_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Refetrfo_Tracker
 * 
 * Handles core tracking functionality for the Referrer Tracker plugin
 */
class Refetrfo_Tracker {
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

        // Initialize tracking
        add_action('wp', array($this, 'init_tracking'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Insert tracking code
        add_action('wp_footer', array($this, 'insert_tracking_code'));
    }

    /**
     * Initialize tracking
     *
     * Set up initial tracking values
     */
    public function init_tracking() {
        if (!is_admin()) {
            $this->set_cookies();
        }
    }

    /**
     * Set up cookies
     *
     * Set up cookies to track referrer information
     */
    private function set_cookies() {
        // Make sure WordPress functions are available
        if (!function_exists('get_option') || !function_exists('sanitize_text_field') || !function_exists('wp_parse_url')) {
            return;
        }
        
        // Debug functionality removed for production
        
        // Get the referrer URL (full URL, not just domain)
        $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
        
        // Si ya existe una cookie de referrer, no la sobreescribimos (mantenemos el referrer original)
        if (isset($_COOKIE['refetrfo_referrer']) && !empty($_COOKIE['refetrfo_referrer'])) {
            $referrer = esc_url_raw(wp_unslash($_COOKIE['refetrfo_referrer']));
        }
        
        // Get UTM parameters if present
        $source = '';
        $medium = '';
        $campaign = '';
        
        // Check for UTM parameters (priority 1)
        // Note: UTM parameters are public tracking parameters used for analytics, not sensitive form data.
        // No nonce verification is needed as these are read-only GET parameters for tracking purposes.
        if (isset($_GET['utm_source']) && !empty($_GET['utm_source'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $source = sanitize_text_field(wp_unslash($_GET['utm_source'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
        
        if (isset($_GET['utm_medium']) && !empty($_GET['utm_medium'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $medium = sanitize_text_field(wp_unslash($_GET['utm_medium'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } else if (isset($_GET['urm_medium']) && !empty($_GET['urm_medium'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            // Corrección para posibles errores tipográficos en los parámetros
            $medium = sanitize_text_field(wp_unslash($_GET['urm_medium'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
        
        if (isset($_GET['utm_campaign']) && !empty($_GET['utm_campaign'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $campaign = sanitize_text_field(wp_unslash($_GET['utm_campaign'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
        
        // Debug information removed for production
        
        // If no UTM parameters, use the parse_referrer function to determine source and medium
        if ((empty($source) || empty($medium)) && !empty($referrer)) {
            $parsed_data = $this->parse_referrer($referrer);
            
            if (empty($source)) {
                $source = $parsed_data['source'];
            }
            
            if (empty($medium)) {
                $medium = $parsed_data['medium'];
            }
            
            if (empty($campaign) && !empty($parsed_data['campaign'])) {
                $campaign = $parsed_data['campaign'];
            }
        }
        
        // If still no source, use 'direct'
        if (empty($source)) {
            $source = 'direct';
            
            // If direct traffic and no medium specified, set it to 'none'
            if (empty($medium)) {
                $medium = 'none';
            }
        }
        
        // If no campaign specified, set it to 'none'
        if (empty($campaign)) {
            $campaign = 'none';
        }
        
        // Debug information removed for production
        
        // Set cookie expiration (30 days by default)
        $expire = time() + (30 * 24 * 60 * 60);
        $path = '/';
        
        // Debug information removed for production
        
        // Delete any existing cookies first to prevent duplicates
        if (isset($_COOKIE['refetrfo_source'])) {
            setcookie('refetrfo_source', '', time() - 3600, $path);
            unset($_COOKIE['refetrfo_source']);
        }
        if (isset($_COOKIE['refetrfo_medium'])) {
            setcookie('refetrfo_medium', '', time() - 3600, $path);
            unset($_COOKIE['refetrfo_medium']);
        }
        if (isset($_COOKIE['refetrfo_campaign'])) {
            setcookie('refetrfo_campaign', '', time() - 3600, $path);
            unset($_COOKIE['refetrfo_campaign']);
        }
        // No eliminamos la cookie de referrer si ya existe, para mantener el referrer original
        if (!isset($_COOKIE['refetrfo_referrer'])) {
            if (isset($_COOKIE['refetrfo_referrer'])) {
                setcookie('refetrfo_referrer', '', time() - 3600, $path);
                unset($_COOKIE['refetrfo_referrer']);
            }
        }
        
        // Set new cookies with refetrfo_ prefix
        setcookie('refetrfo_source', $source, $expire, $path);
        setcookie('refetrfo_medium', $medium, $expire, $path);
        setcookie('refetrfo_campaign', $campaign, $expire, $path);
        
        // Solo establecemos la cookie de referrer si no existe o si hay un nuevo referrer
        if (!empty($referrer)) {
            setcookie('refetrfo_referrer', $referrer, $expire, $path);
        }
        
        // Also set in $_COOKIE for immediate use in this request
        $_COOKIE['refetrfo_source'] = $source;
        $_COOKIE['refetrfo_medium'] = $medium;
        $_COOKIE['refetrfo_campaign'] = $campaign;
        if (!empty($referrer)) {
            $_COOKIE['refetrfo_referrer'] = $referrer;
        }
        
        // Debug information removed for production
    }

    /**
     * Parse referrer information to determine traffic source and medium
     *
     * This method analyzes the referrer URL and various parameters to determine
     * the traffic source (e.g., google, facebook) and medium (e.g., organic, cpc).
     * It can differentiate between paid and organic traffic through various indicators.
     *
     * @param string $referrer The referrer URL to analyze
     * @return array Associative array with source, medium, and campaign information
     *               - source: The traffic source (e.g., google, facebook, direct)
     *               - medium: The traffic medium (e.g., organic, cpc, social)
     *               - campaign: The campaign name (from UTM parameters)
     */
    private function parse_referrer($referrer) {
        $parsed = array(
            'source' => 'direct',
            'medium' => 'none',
            'campaign' => ''
        );

        if (empty($referrer)) {
            return $parsed;
        }

        // Analizar la URL del referrer
        $parsed_url = wp_parse_url($referrer);
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';

        // Si no hay host, devolver valores por defecto
        if (empty($host)) {
            return $parsed;
        }

        // Obtener el host actual para comparar
        $current_host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';

        // Comprobar si el referrer es el mismo sitio
        if ($host === $current_host) {
            $parsed['source'] = 'direct';
            $parsed['medium'] = 'none';
            
            // Incluso si es interno, revisamos si hay parámetros UTM en la URL del referrer
            if (isset($parsed_url['query'])) {
                parse_str($parsed_url['query'], $query_params);
                
                // Extraer parámetros UTM del referrer si existen
                if (isset($query_params['utm_campaign']) && !empty($query_params['utm_campaign'])) {
                    $parsed['campaign'] = sanitize_text_field($query_params['utm_campaign']);
                }
            }
            
            return $parsed;
        }
        
        // Comprobar parámetros de campaña pagada en la URL actual
        // Note: These are public tracking parameters used for analytics, not sensitive form data.
        // No nonce verification is needed as these are read-only GET parameters for tracking purposes.
        
        // Google Ads (gclid)
        if (isset($_GET['gclid'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $parsed['source'] = 'google';
            $parsed['medium'] = 'cpc';
            
            // Extraer campaña de UTM si existe
            if (isset($_GET['utm_campaign']) && !empty($_GET['utm_campaign'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $parsed['campaign'] = sanitize_text_field(wp_unslash($_GET['utm_campaign'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }
            
            return $parsed;
        }
        
        // Facebook Ads (fbclid)
        if (isset($_GET['fbclid'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $parsed['source'] = 'facebook';
            $parsed['medium'] = 'paid-social';
            
            // Extraer campaña de UTM si existe
            if (isset($_GET['utm_campaign']) && !empty($_GET['utm_campaign'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $parsed['campaign'] = sanitize_text_field(wp_unslash($_GET['utm_campaign'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }
            
            return $parsed;
        }
        
        // Microsoft Ads (msclkid)
        if (isset($_GET['msclkid'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $parsed['source'] = 'bing';
            $parsed['medium'] = 'cpc';
            
            // Extraer campaña de UTM si existe
            if (isset($_GET['utm_campaign']) && !empty($_GET['utm_campaign'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $parsed['campaign'] = sanitize_text_field(wp_unslash($_GET['utm_campaign'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }
            
            return $parsed;
        }
        
        // TikTok Ads (ttclid)
        if (isset($_GET['ttclid'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $parsed['source'] = 'tiktok';
            $parsed['medium'] = 'paid-social';
            
            // Extraer campaña de UTM si existe
            if (isset($_GET['utm_campaign']) && !empty($_GET['utm_campaign'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $parsed['campaign'] = sanitize_text_field(wp_unslash($_GET['utm_campaign'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }
            
            return $parsed;
        }
        
        // Extraer campaña de UTM de la URL actual si existe
        if (isset($_GET['utm_campaign']) && !empty($_GET['utm_campaign'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $parsed['campaign'] = sanitize_text_field(wp_unslash($_GET['utm_campaign'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
        
        // Identificar fuentes de tráfico comunes
        $search_engines = array(
            'google' => array('google', 'medium' => 'organic'),
            'bing' => array('bing', 'msn', 'medium' => 'organic'),
            'yahoo' => array('yahoo', 'medium' => 'organic'),
            'duckduckgo' => array('duckduckgo', 'medium' => 'organic'),
            'yandex' => array('yandex', 'medium' => 'organic'),
            'baidu' => array('baidu', 'medium' => 'organic')
        );
        
        $social_networks = array(
            'facebook' => array('facebook', 'fb.com', 'medium' => 'social'),
            'twitter' => array('twitter', 'x.com', 't.co', 'medium' => 'social'),
            'instagram' => array('instagram', 'medium' => 'social'),
            'linkedin' => array('linkedin', 'medium' => 'social'),
            'pinterest' => array('pinterest', 'medium' => 'social'),
            'youtube' => array('youtube', 'youtu.be', 'medium' => 'social'),
            'reddit' => array('reddit', 'medium' => 'social'),
            'tiktok' => array('tiktok', 'medium' => 'social')
        );
        
        // Comprobar si el referrer es un motor de búsqueda
        foreach ($search_engines as $engine => $domains) {
            $medium = $domains['medium'];
            unset($domains['medium']);
            
            foreach ($domains as $domain) {
                if (stripos($host, $domain) !== false) {
                    $parsed['source'] = $engine;
                    $parsed['medium'] = $medium;
                    
                    return $parsed;
                }
            }
        }
        
        // Comprobar si el referrer es una red social
        foreach ($social_networks as $network => $domains) {
            $medium = $domains['medium'];
            unset($domains['medium']);
            
            foreach ($domains as $domain) {
                if (stripos($host, $domain) !== false) {
                    $parsed['source'] = $network;
                    $parsed['medium'] = $medium;
                    
                    return $parsed;
                }
            }
        }
        
        // Si llegamos aquí, es un referrer genérico
        $parsed['source'] = $host;
        $parsed['medium'] = 'referral';

        return $parsed;
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        
        // Enqueue the JavaScript file
        wp_enqueue_script(
            'refetrfo-referrer-tracker',
            plugins_url('js/referrer-tracker.js', REFETRFO_PLUGIN_FILE),
            array('jquery'),
            REFETRFO_VERSION,
            true
        );
        
        // Get tracking values from cookies
        $source = isset($_COOKIE['refetrfo_source']) ? sanitize_text_field(wp_unslash($_COOKIE['refetrfo_source'])) : '';
        $medium = isset($_COOKIE['refetrfo_medium']) ? sanitize_text_field(wp_unslash($_COOKIE['refetrfo_medium'])) : '';
        $campaign = isset($_COOKIE['refetrfo_campaign']) ? sanitize_text_field(wp_unslash($_COOKIE['refetrfo_campaign'])) : '';
        $referrer = isset($_COOKIE['refetrfo_referrer']) ? esc_url_raw(wp_unslash($_COOKIE['refetrfo_referrer'])) : '';
        
        // Pass values to JavaScript
        wp_localize_script(
            'refetrfo-referrer-tracker',
            'refetrfoValues',
            array(
                'source' => $source,
                'medium' => $medium,
                'campaign' => $campaign,
                'referrer' => $referrer,
            )
        );
    }

    /**
     * Insert tracking code
     */
    public function insert_tracking_code() {
        $source = $this->get_source();
        $medium = $this->get_medium();
        $campaign = $this->get_campaign();
        $referrer = $this->get_referrer();

        // Enqueue script
        wp_enqueue_script(
            'refetrfo-referrer-tracker',
            plugins_url('js/referrer-tracker.js', REFETRFO_PLUGIN_FILE),
            array('jquery'),
            REFETRFO_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'refetrfo-referrer-tracker',
            'refetrfoValues',
            array(
                'source' => $source,
                'medium' => $medium,
                'campaign' => $campaign,
                'referrer' => $referrer
            )
        );
    }

    /**
     * Get source value from the cookie
     *
     * @return string The source value
     */
    private function get_source() {
        if (!isset($_COOKIE['refetrfo_source'])) {
            return '';
        }
        return sanitize_text_field(wp_unslash($_COOKIE['refetrfo_source']));
    }

    /**
     * Get medium value from the cookie
     *
     * @return string The medium value
     */
    private function get_medium() {
        if (!isset($_COOKIE['refetrfo_medium'])) {
            return '';
        }
        return sanitize_text_field(wp_unslash($_COOKIE['refetrfo_medium']));
    }

    /**
     * Get campaign value from the cookie
     *
     * @return string The campaign value
     */
    private function get_campaign() {
        if (!isset($_COOKIE['refetrfo_campaign'])) {
            return '';
        }
        return sanitize_text_field(wp_unslash($_COOKIE['refetrfo_campaign']));
    }

    /**
     * Get referrer value from the cookie
     *
     * @return string The referrer value
     */
    private function get_referrer() {
        if (!isset($_COOKIE['refetrfo_referrer'])) {
            return '';
        }
        return esc_url_raw(wp_unslash($_COOKIE['refetrfo_referrer']));
    }

    /**
     * Debug logging (removed for production)
     */
    private function debug_log($message) {
        // Debug functionality removed for production
    }
}
