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
 * Class RT_Tracker
 * 
 * Handles core tracking functionality for the Referrer Tracker plugin
 */
class RT_Tracker {
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
        
        $debug = get_option('referrer_tracker_debug', 'no') === 'yes';
        
        if ($debug) {
            error_log('RT Debug: Setting cookies');
            error_log('RT Debug: Request URI: ' . $_SERVER['REQUEST_URI']);
            if (isset($_SERVER['HTTP_REFERER'])) {
                error_log('RT Debug: HTTP_REFERER: ' . $_SERVER['HTTP_REFERER']);
            }
            error_log('RT Debug: GET params: ' . print_r($_GET, true));
        }
        
        // Get the referrer URL (full URL, not just domain)
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        // Si ya existe una cookie de referrer, no la sobreescribimos (mantenemos el referrer original)
        if (isset($_COOKIE['rt_referrer']) && !empty($_COOKIE['rt_referrer'])) {
            $referrer = $_COOKIE['rt_referrer'];
            if ($debug) {
                error_log('RT Debug: Using existing referrer cookie value: ' . $referrer);
            }
        }
        
        // Get UTM parameters if present
        $source = '';
        $medium = '';
        $campaign = '';
        
        // Check for UTM parameters (priority 1)
        if (isset($_GET['utm_source']) && !empty($_GET['utm_source'])) {
            $source = sanitize_text_field($_GET['utm_source']);
            if ($debug) {
                error_log('RT Debug: Found utm_source parameter: ' . $source);
            }
        }
        
        if (isset($_GET['utm_medium']) && !empty($_GET['utm_medium'])) {
            $medium = sanitize_text_field($_GET['utm_medium']);
            if ($debug) {
                error_log('RT Debug: Found utm_medium parameter: ' . $medium);
            }
        } else if (isset($_GET['urm_medium']) && !empty($_GET['urm_medium'])) {
            // Corrección para posibles errores tipográficos en los parámetros
            $medium = sanitize_text_field($_GET['urm_medium']);
            if ($debug) {
                error_log('RT Debug: Found urm_medium parameter (typo correction): ' . $medium);
            }
        }
        
        if (isset($_GET['utm_campaign']) && !empty($_GET['utm_campaign'])) {
            $campaign = sanitize_text_field($_GET['utm_campaign']);
            if ($debug) {
                error_log('RT Debug: Found utm_campaign parameter: ' . $campaign);
            }
        }
        
        if ($debug) {
            error_log('RT Debug: UTM parameters - source: ' . $source . ', medium: ' . $medium . ', campaign: ' . $campaign);
            error_log('RT Debug: Referrer URL: ' . $referrer);
        }
        
        // If no UTM parameters, use the parse_referrer function to determine source and medium
        if ((empty($source) || empty($medium)) && !empty($referrer)) {
            $parsed_data = $this->parse_referrer($referrer);
            
            if (empty($source)) {
                $source = $parsed_data['source'];
                if ($debug) {
                    error_log('RT Debug: No utm_source, using parsed source: ' . $source);
                }
            }
            
            if (empty($medium)) {
                $medium = $parsed_data['medium'];
                if ($debug) {
                    error_log('RT Debug: No utm_medium, using parsed medium: ' . $medium);
                }
            }
            
            if (empty($campaign) && !empty($parsed_data['campaign'])) {
                $campaign = $parsed_data['campaign'];
                if ($debug) {
                    error_log('RT Debug: No utm_campaign, using parsed campaign: ' . $campaign);
                }
            }
        }
        
        // If still no source, use 'direct'
        if (empty($source)) {
            $source = 'direct';
            
            if ($debug) {
                error_log('RT Debug: No source found, setting to "direct"');
            }
            
            // If direct traffic and no medium specified, set it to 'none'
            if (empty($medium)) {
                $medium = 'none';
                
                if ($debug) {
                    error_log('RT Debug: No medium found for direct traffic, setting to "none"');
                }
            }
        }
        
        // If no campaign specified, set it to 'none'
        if (empty($campaign)) {
            $campaign = 'none';
            
            if ($debug) {
                error_log('RT Debug: No campaign found, setting to "none"');
            }
        }
        
        if ($debug) {
            error_log('RT Debug: Final values before setting cookies - source: ' . $source . ', medium: ' . $medium . ', campaign: ' . $campaign . ', referrer: ' . $referrer);
        }
        
        // Set cookie expiration (30 days by default)
        $expire = time() + (30 * 24 * 60 * 60);
        $path = '/';
        
        if ($debug) {
            error_log('RT Debug: Setting cookies with values - source: ' . $source . ', medium: ' . $medium . ', campaign: ' . $campaign . ', referrer: ' . $referrer);
        }
        
        // Delete any existing cookies first to prevent duplicates
        if (isset($_COOKIE['rt_source'])) {
            setcookie('rt_source', '', time() - 3600, $path);
            unset($_COOKIE['rt_source']);
        }
        if (isset($_COOKIE['rt_medium'])) {
            setcookie('rt_medium', '', time() - 3600, $path);
            unset($_COOKIE['rt_medium']);
        }
        if (isset($_COOKIE['rt_campaign'])) {
            setcookie('rt_campaign', '', time() - 3600, $path);
            unset($_COOKIE['rt_campaign']);
        }
        // No eliminamos la cookie de referrer si ya existe, para mantener el referrer original
        if (!isset($_COOKIE['rt_referrer'])) {
            if (isset($_COOKIE['rt_referrer'])) {
                setcookie('rt_referrer', '', time() - 3600, $path);
                unset($_COOKIE['rt_referrer']);
            }
        }
        
        // Set new cookies with rt_ prefix
        setcookie('rt_source', $source, $expire, $path);
        setcookie('rt_medium', $medium, $expire, $path);
        setcookie('rt_campaign', $campaign, $expire, $path);
        
        // Solo establecemos la cookie de referrer si no existe o si hay un nuevo referrer
        if (!empty($referrer)) {
            setcookie('rt_referrer', $referrer, $expire, $path);
            if ($debug) {
                error_log('RT Debug: Setting rt_referrer cookie with value: ' . $referrer);
            }
        } else if ($debug) {
            error_log('RT Debug: Not setting rt_referrer cookie because referrer is empty');
        }
        
        // Also set in $_COOKIE for immediate use in this request
        $_COOKIE['rt_source'] = $source;
        $_COOKIE['rt_medium'] = $medium;
        $_COOKIE['rt_campaign'] = $campaign;
        if (!empty($referrer)) {
            $_COOKIE['rt_referrer'] = $referrer;
        }
        
        if ($debug) {
            error_log('RT Debug: Cookies set successfully');
            error_log('RT Debug: $_COOKIE after setting: ' . print_r($_COOKIE, true));
            error_log('RT Debug: rt_referrer cookie value: ' . (isset($_COOKIE['rt_referrer']) ? $_COOKIE['rt_referrer'] : 'not set'));
        }
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
        // Get debug mode
        $debug = get_option('referrer_tracker_debug', 'no') === 'yes';
        
        $parsed = array(
            'source' => 'direct',
            'medium' => 'none',
            'campaign' => ''
        );

        if (empty($referrer)) {
            if ($debug) {
                error_log('RT Debug: Empty referrer, using default values');
            }
            return $parsed;
        }

        // Analizar la URL del referrer
        $parsed_url = wp_parse_url($referrer);
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';

        // Si no hay host, devolver valores por defecto
        if (empty($host)) {
            if ($debug) {
                error_log('RT Debug: No host in referrer URL, using default values');
            }
            return $parsed;
        }
        
        // Obtener el host actual
        $current_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        
        if ($debug) {
            error_log('RT Debug: Referrer host: ' . $host);
            error_log('RT Debug: Current host: ' . $current_host);
        }
        
        // Comprobar si el referrer es el mismo sitio
        if ($host === $current_host) {
            $parsed['source'] = 'direct';
            $parsed['medium'] = 'none';
            
            if ($debug) {
                error_log('RT Debug: Referrer is from the same site, setting source to "direct" and medium to "none"');
            }
            
            // Incluso si es interno, revisamos si hay parámetros UTM en la URL del referrer
            if (isset($parsed_url['query'])) {
                parse_str($parsed_url['query'], $query_params);
                
                // Extraer parámetros UTM del referrer si existen
                if (isset($query_params['utm_campaign']) && !empty($query_params['utm_campaign'])) {
                    $parsed['campaign'] = sanitize_text_field($query_params['utm_campaign']);
                    if ($debug) {
                        error_log('RT Debug: Found utm_campaign in referrer URL: ' . $parsed['campaign']);
                    }
                }
            }
            
            return $parsed;
        }
        
        // Comprobar parámetros de campaña pagada en la URL actual
        $query_params = $_GET;
        
        // Google Ads (gclid)
        if (isset($query_params['gclid'])) {
            $parsed['source'] = 'google';
            $parsed['medium'] = 'cpc';
            
            if ($debug) {
                error_log('RT Debug: Found gclid parameter, setting source to "google" and medium to "cpc"');
            }
            
            // Extraer campaña de UTM si existe
            if (isset($query_params['utm_campaign']) && !empty($query_params['utm_campaign'])) {
                $parsed['campaign'] = sanitize_text_field($query_params['utm_campaign']);
                if ($debug) {
                    error_log('RT Debug: Found utm_campaign parameter with gclid: ' . $parsed['campaign']);
                }
            }
            
            return $parsed;
        }
        
        // Facebook Ads (fbclid)
        if (isset($query_params['fbclid'])) {
            $parsed['source'] = 'facebook';
            $parsed['medium'] = 'paid-social';
            
            if ($debug) {
                error_log('RT Debug: Found fbclid parameter, setting source to "facebook" and medium to "paid-social"');
            }
            
            // Extraer campaña de UTM si existe
            if (isset($query_params['utm_campaign']) && !empty($query_params['utm_campaign'])) {
                $parsed['campaign'] = sanitize_text_field($query_params['utm_campaign']);
                if ($debug) {
                    error_log('RT Debug: Found utm_campaign parameter with fbclid: ' . $parsed['campaign']);
                }
            }
            
            return $parsed;
        }
        
        // Microsoft Ads (msclkid)
        if (isset($query_params['msclkid'])) {
            $parsed['source'] = 'bing';
            $parsed['medium'] = 'cpc';
            
            if ($debug) {
                error_log('RT Debug: Found msclkid parameter, setting source to "bing" and medium to "cpc"');
            }
            
            // Extraer campaña de UTM si existe
            if (isset($query_params['utm_campaign']) && !empty($query_params['utm_campaign'])) {
                $parsed['campaign'] = sanitize_text_field($query_params['utm_campaign']);
                if ($debug) {
                    error_log('RT Debug: Found utm_campaign parameter with msclkid: ' . $parsed['campaign']);
                }
            }
            
            return $parsed;
        }
        
        // TikTok Ads (ttclid)
        if (isset($query_params['ttclid'])) {
            $parsed['source'] = 'tiktok';
            $parsed['medium'] = 'paid-social';
            
            if ($debug) {
                error_log('RT Debug: Found ttclid parameter, setting source to "tiktok" and medium to "paid-social"');
            }
            
            // Extraer campaña de UTM si existe
            if (isset($query_params['utm_campaign']) && !empty($query_params['utm_campaign'])) {
                $parsed['campaign'] = sanitize_text_field($query_params['utm_campaign']);
                if ($debug) {
                    error_log('RT Debug: Found utm_campaign parameter with ttclid: ' . $parsed['campaign']);
                }
            }
            
            return $parsed;
        }
        
        // Extraer campaña de UTM de la URL actual si existe
        if (isset($query_params['utm_campaign']) && !empty($query_params['utm_campaign'])) {
            $parsed['campaign'] = sanitize_text_field($query_params['utm_campaign']);
            if ($debug) {
                error_log('RT Debug: Found utm_campaign parameter in current URL: ' . $parsed['campaign']);
            }
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
                    
                    if ($debug) {
                        error_log('RT Debug: Referrer identified as search engine: ' . $engine . ', medium: ' . $medium);
                    }
                    
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
                    
                    if ($debug) {
                        error_log('RT Debug: Referrer identified as social network: ' . $network . ', medium: ' . $medium);
                    }
                    
                    return $parsed;
                }
            }
        }
        
        // Si llegamos aquí, es un referrer genérico
        $parsed['source'] = $host;
        $parsed['medium'] = 'referral';
        
        if ($debug) {
            error_log('RT Debug: Generic referrer, setting source to host: ' . $host . ' and medium to "referral"');
        }

        return $parsed;
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Get debug mode
        $debug = get_option('referrer_tracker_debug', 'no') === 'yes';
        
        if ($debug) {
            error_log('RT Debug: Enqueuing scripts');
        }
        
        // Enqueue the JavaScript file
        wp_enqueue_script(
            'rt-referrer-tracker',
            plugins_url('js/referrer-tracker.js', RT_PLUGIN_FILE),
            array('jquery'),
            RT_VERSION,
            true
        );
        
        // Get tracking values from cookies
        $source = isset($_COOKIE['rt_source']) ? $_COOKIE['rt_source'] : '';
        $medium = isset($_COOKIE['rt_medium']) ? $_COOKIE['rt_medium'] : '';
        $campaign = isset($_COOKIE['rt_campaign']) ? $_COOKIE['rt_campaign'] : '';
        $referrer = isset($_COOKIE['rt_referrer']) ? $_COOKIE['rt_referrer'] : '';
        
        // Pass values to JavaScript
        wp_localize_script(
            'rt-referrer-tracker',
            'rtValues',
            array(
                'source' => $source,
                'medium' => $medium,
                'campaign' => $campaign,
                'referrer' => $referrer,
                'debug' => $debug ? 'yes' : 'no'
            )
        );
        
        if ($debug) {
            error_log('RT Debug: Values passed to JavaScript - source: ' . $source . ', medium: ' . $medium . ', campaign: ' . $campaign . ', referrer: ' . $referrer);
        }
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
            'rt-referrer-tracker',
            plugins_url('js/referrer-tracker.js', RT_PLUGIN_FILE),
            array('jquery'),
            RT_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'rt-referrer-tracker',
            'rtValues',
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
        if (!isset($_COOKIE['rt_source'])) {
            return '';
        }
        return sanitize_text_field(wp_unslash($_COOKIE['rt_source']));
    }

    /**
     * Get medium value from the cookie
     *
     * @return string The medium value
     */
    private function get_medium() {
        if (!isset($_COOKIE['rt_medium'])) {
            return '';
        }
        return sanitize_text_field(wp_unslash($_COOKIE['rt_medium']));
    }

    /**
     * Get campaign value from the cookie
     *
     * @return string The campaign value
     */
    private function get_campaign() {
        if (!isset($_COOKIE['rt_campaign'])) {
            return '';
        }
        return sanitize_text_field(wp_unslash($_COOKIE['rt_campaign']));
    }

    /**
     * Get referrer value from the cookie
     *
     * @return string The referrer value
     */
    private function get_referrer() {
        if (!isset($_COOKIE['rt_referrer'])) {
            return '';
        }
        return esc_url_raw(wp_unslash($_COOKIE['rt_referrer']));
    }

    /**
     * Debug logging
     */
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG === true && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG === true) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('RT: ' . $message);
        }
    }
}
