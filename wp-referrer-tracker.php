<?php
/**
 * Plugin Name: WP Referrer Tracker
 * Plugin URI: 
 * Description: Track referrer information and parse it into source, medium and campaign for any form plugin. Supports WPForms, Contact Form 7, Gravity Forms, and generic HTML forms.
 * Version: 1.4.2
 * Author: WMS
 * Author URI: https://www.webmanagerservice.es
 * License: GPL v2 or later
 * Text Domain: wp-referrer-tracker
 * 
 * This plugin tracks and categorizes referrer information, including:
 * - Traffic sources (Google, Facebook, Twitter, etc.)
 * - Traffic mediums (organic, cpc, social, email, referral)
 * - Campaign tracking via UTM parameters
 * - Paid vs Organic traffic detection
 * 
 * Key Features:
 * - Automatic field population
 * - UTM parameter tracking
 * - Multiple form plugin support
 * - Easy configuration options
 * - Debug logging
 * 
 * Form Plugin Support:
 * - Contact Form 7 (with auto-insert)
 * - WPForms
 * - Gravity Forms
 * - Generic HTML Forms
 * 
 * Technical Features:
 * - Cookie-based tracking
 * - Real-time field updates
 * - Automatic value detection
 * - Error prevention
 * - Debug logging
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Referrer_Tracker {
    private static $instance = null;
    private $field_prefix = 'wrt_';

    /**
     * Get the plugin instance
     *
     * @return WP_Referrer_Tracker The plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * Initialize the plugin by adding hooks and setting up cookies
     */
    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('init', array($this, 'init'));
        
        // Add settings page
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Initialize the plugin
     *
     * Set up cookies and initialize the plugin
     */
    public function init() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Insertar el código de tracking
        add_action('wp_footer', array($this, 'insert_tracking_code'));

        // Si está activada la inserción automática de campos
        if (get_option('wrt_settings')['auto_fields']) {
            add_filter('wpcf7_form_elements', array($this, 'add_hidden_fields_cf7'));
        }
        
        // Initialize tracking
        $this->init_tracking();
    }

    /**
     * Añadir campos ocultos a Contact Form 7
     */
    public function add_hidden_fields_cf7($elements) {
        $prefix = get_option('wrt_settings')['field_prefix'];
        
        $hidden_fields = "
[hidden {$prefix}source class:js-wrt-source \"\"]
[hidden {$prefix}medium class:js-wrt-medium \"\"]
[hidden {$prefix}campaign class:js-wrt-campaign \"\"]
[hidden {$prefix}referrer class:js-wrt-referrer \"\"]
";
        
        return $elements . $hidden_fields;
    }

    /**
     * Insertar el código de tracking
     */
    public function insert_tracking_code() {
        // Obtener los valores de las cookies
        $source = isset($_COOKIE['wrt_source']) ? $_COOKIE['wrt_source'] : '';
        $medium = isset($_COOKIE['wrt_medium']) ? $_COOKIE['wrt_medium'] : '';
        $campaign = isset($_COOKIE['wrt_campaign']) ? $_COOKIE['wrt_campaign'] : '';
        $referrer = isset($_COOKIE['wrt_referrer']) ? $_COOKIE['wrt_referrer'] : '';

        ?>
        <script>
        // Valores de referrer
        var wrtValues = {
            source: <?php echo json_encode($source); ?>,
            medium: <?php echo json_encode($medium); ?>,
            campaign: <?php echo json_encode($campaign); ?>,
            referrer: <?php echo json_encode($referrer); ?>
        };

        // Función para actualizar campos
        function wrtUpdateFields() {
            console.log('WRT: Actualizando campos con valores:', wrtValues);
            
            // Actualizar campos por clase
            document.querySelectorAll('.js-wrt-source').forEach(function(el) {
                el.value = wrtValues.source;
                console.log('WRT: Campo source actualizado:', el.value);
            });
            
            document.querySelectorAll('.js-wrt-medium').forEach(function(el) {
                el.value = wrtValues.medium;
                console.log('WRT: Campo medium actualizado:', el.value);
            });
            
            document.querySelectorAll('.js-wrt-campaign').forEach(function(el) {
                el.value = wrtValues.campaign;
                console.log('WRT: Campo campaign actualizado:', el.value);
            });
            
            document.querySelectorAll('.js-wrt-referrer').forEach(function(el) {
                el.value = wrtValues.referrer;
                console.log('WRT: Campo referrer actualizado:', el.value);
            });
        }

        // Actualizar campos cuando el DOM está listo
        document.addEventListener('DOMContentLoaded', function() {
            console.log('WRT: DOM cargado, actualizando campos...');
            wrtUpdateFields();
        });

        // Actualizar campos antes de enviar el formulario CF7
        document.addEventListener('wpcf7submit', function() {
            console.log('WRT: Formulario enviado, actualizando campos...');
            wrtUpdateFields();
        });
        </script>
        <?php
    }

    /**
     * Add settings page
     *
     * Add a settings page to the WordPress admin dashboard
     */
    public function add_admin_menu() {
        add_options_page(
            'WP Referrer Tracker Settings',
            'Referrer Tracker',
            'manage_options',
            'wp-referrer-tracker',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Display the plugin settings page
     */
    public function display_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php
            settings_errors('wp_referrer_tracker');
            ?>

            <form action="options.php" method="post">
                <?php
                settings_fields('wp_referrer_tracker');
                do_settings_sections('wp-referrer-tracker');
                submit_button();
                ?>
            </form>

            <?php
            $options = get_option('wrt_settings');
            $this->display_implementation_instructions($options['form_plugin']);
            ?>
        </div>
        <?php
    }

    /**
     * Display implementation instructions based on selected form plugin
     */
    private function display_implementation_instructions($plugin) {
        echo '<div class="wrt-instructions" style="margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
        echo '<h2>Implementation Instructions</h2>';
        
        switch ($plugin) {
            case 'cf7':
                ?>
                <h3>Contact Form 7 Implementation</h3>
                <p>Add these hidden fields to your Contact Form 7 form:</p>
                <pre style="background: #f5f5f5; padding: 10px; overflow: auto;">
[hidden wrt_source class:js-wrt-source ""]
[hidden wrt_medium class:js-wrt-medium ""]
[hidden wrt_campaign class:js-wrt-campaign ""]
[hidden wrt_referrer class:js-wrt-referrer ""]</pre>
                <p><strong>Important notes:</strong></p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li>The field names must use underscore (e.g., <code>wrt_source</code>)</li>
                    <li>The classes must use hyphen (e.g., <code>js-wrt-source</code>)</li>
                    <li>Leave the default value empty (<code>""</code>)</li>
                    <li>Do not add any additional classes or attributes</li>
                </ul>
                <?php
                break;

            case 'wpforms':
                ?>
                <h3>WPForms Implementation</h3>
                <p>Add these hidden fields to your WPForms form:</p>
                <ol style="list-style-type: decimal; margin-left: 20px;">
                    <li>Go to your form editor</li>
                    <li>Drag and drop 4 "Hidden Field" elements from the "Fancy Fields" section</li>
                    <li>Configure each hidden field:</li>
                </ol>
                <table class="wp-list-table widefat striped" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th>Field Label</th>
                            <th>Field Name</th>
                            <th>CSS Classes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Source</td>
                            <td>wrt_source</td>
                            <td>js-wrt-source</td>
                        </tr>
                        <tr>
                            <td>Medium</td>
                            <td>wrt_medium</td>
                            <td>js-wrt-medium</td>
                        </tr>
                        <tr>
                            <td>Campaign</td>
                            <td>wrt_campaign</td>
                            <td>js-wrt-campaign</td>
                        </tr>
                        <tr>
                            <td>Referrer</td>
                            <td>wrt_referrer</td>
                            <td>js-wrt-referrer</td>
                        </tr>
                    </tbody>
                </table>
                <?php
                break;

            case 'gravity':
                ?>
                <h3>Gravity Forms Implementation</h3>
                <p>Add these hidden fields to your Gravity Forms form:</p>
                <ol style="list-style-type: decimal; margin-left: 20px;">
                    <li>Go to your form editor</li>
                    <li>Add 4 "Hidden" fields from the "Advanced Fields" section</li>
                    <li>Configure each hidden field:</li>
                </ol>
                <table class="wp-list-table widefat striped" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th>Field Label</th>
                            <th>Field Name</th>
                            <th>CSS Class Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Source</td>
                            <td>wrt_source</td>
                            <td>js-wrt-source</td>
                        </tr>
                        <tr>
                            <td>Medium</td>
                            <td>wrt_medium</td>
                            <td>js-wrt-medium</td>
                        </tr>
                        <tr>
                            <td>Campaign</td>
                            <td>wrt_campaign</td>
                            <td>js-wrt-campaign</td>
                        </tr>
                        <tr>
                            <td>Referrer</td>
                            <td>wrt_referrer</td>
                            <td>js-wrt-referrer</td>
                        </tr>
                    </tbody>
                </table>
                <p><em>Note: Add the CSS Class Name in the "Advanced" tab of each field.</em></p>
                <?php
                break;

            case 'generic':
                ?>
                <h3>Generic HTML Forms Implementation</h3>
                <p>Add these hidden fields to your HTML form:</p>
                <pre style="background: #f5f5f5; padding: 10px; overflow: auto;">
&lt;input type="hidden" name="wrt_source" class="js-wrt-source" value=""&gt;
&lt;input type="hidden" name="wrt_medium" class="js-wrt-medium" value=""&gt;
&lt;input type="hidden" name="wrt_campaign" class="js-wrt-campaign" value=""&gt;
&lt;input type="hidden" name="wrt_referrer" class="js-wrt-referrer" value=""&gt;</pre>
                <p><strong>Important notes:</strong></p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li>The name attributes must use underscore (e.g., <code>wrt_source</code>)</li>
                    <li>The class attributes must use hyphen (e.g., <code>js-wrt-source</code>)</li>
                    <li>Leave the value attribute empty (<code>value=""</code>)</li>
                </ul>
                <?php
                break;

            default:
                echo '<p>Please select a form plugin to see implementation instructions.</p>';
                break;
        }
        
        echo '</div>';
    }

    /**
     * Register settings
     *
     * Register the plugin settings and add fields to the settings page
     */
    public function register_settings() {
        register_setting('wp_referrer_tracker', 'wrt_settings', array(
            'type' => 'array',
            'default' => array(
                'form_plugin' => 'cf7',
                'field_prefix' => 'wrt_',
                'auto_fields' => false
            ),
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));

        add_settings_section(
            'wrt_main_section',
            'Main Settings',
            array($this, 'section_text'),
            'wp-referrer-tracker'
        );

        add_settings_field(
            'form_plugin',
            'Form Plugin',
            array($this, 'form_plugin_field'),
            'wp-referrer-tracker',
            'wrt_main_section'
        );

        add_settings_field(
            'field_prefix',
            'Field Prefix',
            array($this, 'field_prefix_field'),
            'wp-referrer-tracker',
            'wrt_main_section'
        );

        add_settings_field(
            'auto_fields',
            'Auto-insert Hidden Fields',
            array($this, 'auto_fields_field'),
            'wp-referrer-tracker',
            'wrt_main_section'
        );
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Form Plugin
        if (isset($input['form_plugin'])) {
            $sanitized['form_plugin'] = sanitize_text_field($input['form_plugin']);
        }
        
        // Field Prefix
        if (isset($input['field_prefix'])) {
            $sanitized['field_prefix'] = sanitize_text_field($input['field_prefix']);
        }
        
        // Auto Fields
        $sanitized['auto_fields'] = isset($input['auto_fields']) ? true : false;
        
        return $sanitized;
    }

    /**
     * Section text
     */
    public function section_text() {
        echo '<p>Configure your referrer tracking settings here.</p>';
    }

    /**
     * Form plugin field
     */
    public function form_plugin_field() {
        $options = get_option('wrt_settings');
        $current = isset($options['form_plugin']) ? $options['form_plugin'] : 'cf7';
        ?>
        <select name="wrt_settings[form_plugin]">
            <option value="cf7" <?php selected($current, 'cf7'); ?>>Contact Form 7</option>
            <option value="wpforms" <?php selected($current, 'wpforms'); ?>>WPForms</option>
            <option value="gravity" <?php selected($current, 'gravity'); ?>>Gravity Forms</option>
            <option value="generic" <?php selected($current, 'generic'); ?>>Generic HTML Forms</option>
        </select>
        <?php
    }

    /**
     * Field prefix field
     */
    public function field_prefix_field() {
        $options = get_option('wrt_settings');
        $current = isset($options['field_prefix']) ? $options['field_prefix'] : 'wrt_';
        ?>
        <input type="text" name="wrt_settings[field_prefix]" value="<?php echo esc_attr($current); ?>" />
        <p class="description">Prefix for the hidden fields (e.g., wrt_)</p>
        <?php
    }

    /**
     * Auto fields field
     */
    public function auto_fields_field() {
        $options = get_option('wrt_settings');
        $checked = isset($options['auto_fields']) ? $options['auto_fields'] : false;
        ?>
        <input type="checkbox" name="wrt_settings[auto_fields]" value="1" <?php checked($checked, true); ?> />
        <p class="description">Automatically insert hidden fields into Contact Form 7 forms</p>
        <?php
    }

    /**
     * Set up cookies
     *
     * Set up cookies to track referrer information
     */
    private function set_cookies() {
        // Si ya existen las cookies, no las sobreescribimos
        if (isset($_COOKIE['wrt_source']) && !empty($_COOKIE['wrt_source'])) {
            error_log('WRT: Las cookies ya existen, no se sobreescriben');
            return;
        }

        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        error_log('WRT: Referrer detectado: ' . $referrer);

        // Obtener los parámetros UTM
        $utm_source = isset($_GET['utm_source']) ? $_GET['utm_source'] : '';
        $utm_medium = isset($_GET['utm_medium']) ? $_GET['utm_medium'] : '';
        $utm_campaign = isset($_GET['utm_campaign']) ? $_GET['utm_campaign'] : '';
        
        error_log('WRT: UTM params - source: ' . $utm_source . ', medium: ' . $utm_medium . ', campaign: ' . $utm_campaign);

        // Si hay parámetros UTM, los usamos
        if (!empty($utm_source)) {
            $source = $utm_source;
            $medium = !empty($utm_medium) ? $utm_medium : 'referral';
            $campaign = $utm_campaign;
        } 
        // Si no hay UTM, analizamos el referrer
        else if (!empty($referrer)) {
            $parsed_url = parse_url($referrer);
            $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
            
            // Google
            if (strpos($host, 'google') !== false) {
                $source = 'google';
                $medium = isset($_GET['gclid']) ? 'cpc' : 'organic';
            }
            // Facebook
            else if (strpos($host, 'facebook') !== false || strpos($host, 'fb') !== false) {
                $source = 'facebook';
                $medium = 'social';
            }
            // Otros casos
            else {
                $source = $host;
                $medium = 'referral';
            }
            $campaign = '';
        }
        // Si no hay referrer ni UTM
        else {
            $source = 'direct';
            $medium = 'none';
            $campaign = '';
        }

        error_log('WRT: Valores finales - source: ' . $source . ', medium: ' . $medium . ', campaign: ' . $campaign);

        // Establecer las cookies con una duración de 30 días
        $expire = time() + (30 * 24 * 60 * 60);
        $path = '/';
        
        setcookie('wrt_source', $source, $expire, $path);
        setcookie('wrt_medium', $medium, $expire, $path);
        setcookie('wrt_campaign', $campaign, $expire, $path);
        setcookie('wrt_referrer', $referrer, $expire, $path);

        error_log('WRT: Cookies establecidas con éxito');

        // También guardamos los valores en la sesión actual
        $_COOKIE['wrt_source'] = $source;
        $_COOKIE['wrt_medium'] = $medium;
        $_COOKIE['wrt_campaign'] = $campaign;
        $_COOKIE['wrt_referrer'] = $referrer;
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

        $referrer_host = parse_url($referrer, PHP_URL_HOST);
        $current_host = parse_url(get_site_url(), PHP_URL_HOST);
        $referrer_path = parse_url($referrer, PHP_URL_PATH);
        $query = parse_url($referrer, PHP_URL_QUERY);
        parse_str($query ?? '', $params);

        // Check if it's an internal referrer
        if ($referrer_host === $current_host) {
            return $parsed;
        }

        // Check for UTM parameters first
        if (!empty($params['utm_source'])) {
            $parsed['source'] = sanitize_text_field($params['utm_source']);
            $parsed['medium'] = sanitize_text_field($params['utm_medium'] ?? '');
            $parsed['campaign'] = sanitize_text_field($params['utm_campaign'] ?? '');
            return $parsed;
        }

        // Check for paid traffic parameters
        $paid_parameters = array(
            'gclid',  // Google Ads
            'fbclid', // Facebook Ads
            'msclkid', // Microsoft Ads
            'dclid',  // DoubleClick
            'ttclid', // TikTok Ads
            'twclid'  // Twitter Ads
        );

        foreach ($paid_parameters as $param) {
            if (isset($params[$param])) {
                switch ($param) {
                    case 'gclid':
                        $parsed['source'] = 'google';
                        $parsed['medium'] = 'cpc';
                        break;
                    case 'fbclid':
                        $parsed['source'] = 'facebook';
                        $parsed['medium'] = 'cpc';
                        break;
                    case 'msclkid':
                        $parsed['source'] = 'bing';
                        $parsed['medium'] = 'cpc';
                        break;
                    case 'dclid':
                        $parsed['source'] = 'doubleclick';
                        $parsed['medium'] = 'cpc';
                        break;
                    case 'ttclid':
                        $parsed['source'] = 'tiktok';
                        $parsed['medium'] = 'cpc';
                        break;
                    case 'twclid':
                        $parsed['source'] = 'twitter';
                        $parsed['medium'] = 'cpc';
                        break;
                }
                return $parsed;
            }
        }

        // Search Engines with specific checks for paid traffic
        $search_engines = array(
            'google' => array(
                'domains' => array('google', 'google.com', 'google.es', 'google.co.uk'),
                'paid_indicators' => array(
                    'params' => array('gclid'),
                    'paths' => array('/aclk', '/pagead'),
                    'refs' => array('adwords', 'googleads')
                )
            ),
            'bing' => array(
                'domains' => array('bing', 'bing.com'),
                'paid_indicators' => array(
                    'params' => array('msclkid'),
                    'paths' => array('/bing/ck.php'),
                    'refs' => array('msn', 'bingads')
                )
            ),
            'yahoo' => array(
                'domains' => array('yahoo', 'yahoo.com'),
                'paid_indicators' => array(
                    'paths' => array('/cbclk'),
                    'refs' => array('yahoo_sem')
                )
            ),
            'duckduckgo' => array(
                'domains' => array('duckduckgo.com'),
                'paid_indicators' => array()
            )
        );

        // Social Networks with paid indicators
        $social_networks = array(
            'facebook' => array(
                'domains' => array('facebook.com', 'fb.com', 'fb.me', 'm.facebook.com'),
                'paid_indicators' => array(
                    'params' => array('fbclid'),
                    'paths' => array('/ads/')
                )
            ),
            'instagram' => array(
                'domains' => array('instagram.com', 'l.instagram.com'),
                'paid_indicators' => array(
                    'paths' => array('/ads/')
                )
            ),
            'twitter' => array(
                'domains' => array('twitter.com', 't.co'),
                'paid_indicators' => array(
                    'params' => array('twclid'),
                    'paths' => array('/promote')
                )
            ),
            'linkedin' => array(
                'domains' => array('linkedin.com', 'lnkd.in'),
                'paid_indicators' => array(
                    'paths' => array('/ads/')
                )
            ),
            'youtube' => array(
                'domains' => array('youtube.com', 'youtu.be'),
                'paid_indicators' => array(
                    'paths' => array('/ads/')
                )
            ),
            'pinterest' => array(
                'domains' => array('pinterest.com', 'pin.it'),
                'paid_indicators' => array(
                    'paths' => array('/ads/')
                )
            ),
            'tiktok' => array(
                'domains' => array('tiktok.com', 'vm.tiktok.com'),
                'paid_indicators' => array(
                    'params' => array('ttclid'),
                    'paths' => array('/ads/')
                )
            )
        );

        // Email Providers
        $email_providers = array(
            'outlook' => array(
                'domains' => array('outlook.com', 'outlook.live.com'),
                'medium' => 'email'
            ),
            'gmail' => array(
                'domains' => array('mail.google.com'),
                'medium' => 'email'
            ),
            'yahoo-mail' => array(
                'domains' => array('mail.yahoo.com'),
                'medium' => 'email'
            )
        );

        // Check search engines first
        foreach ($search_engines as $engine => $data) {
            foreach ($data['domains'] as $domain) {
                if (strpos($referrer_host, $domain) !== false) {
                    $parsed['source'] = $engine;
                    
                    // Check for paid indicators
                    $is_paid = false;
                    if (isset($data['paid_indicators'])) {
                        // Check URL parameters
                        if (isset($data['paid_indicators']['params'])) {
                            foreach ($data['paid_indicators']['params'] as $param) {
                                if (isset($params[$param])) {
                                    $is_paid = true;
                                    break;
                                }
                            }
                        }
                        
                        // Check paths
                        if (!$is_paid && isset($data['paid_indicators']['paths'])) {
                            foreach ($data['paid_indicators']['paths'] as $path) {
                                if (strpos($referrer_path, $path) !== false) {
                                    $is_paid = true;
                                    break;
                                }
                            }
                        }
                        
                        // Check referrer strings
                        if (!$is_paid && isset($data['paid_indicators']['refs'])) {
                            foreach ($data['paid_indicators']['refs'] as $ref) {
                                if (strpos(strtolower($referrer), $ref) !== false) {
                                    $is_paid = true;
                                    break;
                                }
                            }
                        }
                    }
                    
                    $parsed['medium'] = $is_paid ? 'cpc' : 'organic';
                    return $parsed;
                }
            }
        }

        // Check social networks
        foreach ($social_networks as $network => $data) {
            foreach ($data['domains'] as $domain) {
                if (strpos($referrer_host, $domain) !== false) {
                    $parsed['source'] = $network;
                    
                    // Check for paid indicators
                    $is_paid = false;
                    if (isset($data['paid_indicators'])) {
                        // Check URL parameters
                        if (isset($data['paid_indicators']['params'])) {
                            foreach ($data['paid_indicators']['params'] as $param) {
                                if (isset($params[$param])) {
                                    $is_paid = true;
                                    break;
                                }
                            }
                        }
                        
                        // Check paths
                        if (!$is_paid && isset($data['paid_indicators']['paths'])) {
                            foreach ($data['paid_indicators']['paths'] as $path) {
                                if (strpos($referrer_path, $path) !== false) {
                                    $is_paid = true;
                                    break;
                                }
                            }
                        }
                    }
                    
                    $parsed['medium'] = $is_paid ? 'cpc' : 'social';
                    return $parsed;
                }
            }
        }

        // Check email providers
        foreach ($email_providers as $provider => $data) {
            foreach ($data['domains'] as $domain) {
                if (strpos($referrer_host, $domain) !== false) {
                    $parsed['source'] = $provider;
                    $parsed['medium'] = 'email';
                    return $parsed;
                }
            }
        }

        // If no match found, use referrer host as source
        $parsed['source'] = $referrer_host;
        $parsed['medium'] = 'referral';

        return $parsed;
    }

    /**
     * Enqueue scripts
     *
     * Enqueue the plugin's JavaScript file and pass cookie values and settings to it
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'wp-referrer-tracker',
            plugins_url('js/wp-referrer-tracker.js', __FILE__),
            array('jquery'),
            '1.0.0',
            true
        );

        $options = get_option('wrt_settings', array(
            'auto_fields' => true,
            'field_prefix' => 'wrt_',
            'form_plugin' => 'none',
            'auto_insert' => false
        ));

        // Pass cookie values and settings to JavaScript
        $referrer_data = array(
            'source' => isset($_COOKIE['wrt_source']) ? $_COOKIE['wrt_source'] : '',
            'medium' => isset($_COOKIE['wrt_medium']) ? $_COOKIE['wrt_medium'] : '',
            'campaign' => isset($_COOKIE['wrt_campaign']) ? $_COOKIE['wrt_campaign'] : '',
            'referrer' => isset($_COOKIE['wrt_referrer']) ? $_COOKIE['wrt_referrer'] : '',
            'settings' => array(
                'autoFields' => $options['auto_fields'],
                'fieldPrefix' => $options['field_prefix']
            )
        );

        wp_localize_script('wp-referrer-tracker', 'wpReferrerTracker', $referrer_data);
    }

    /**
     * Get source
     *
     * Get the source value from the cookie
     *
     * @return string The source value
     */
    private function get_source() {
        return $_COOKIE['wrt_source'] ?? '';
    }

    /**
     * Get medium
     *
     * Get the medium value from the cookie
     *
     * @return string The medium value
     */
    private function get_medium() {
        return $_COOKIE['wrt_medium'] ?? '';
    }

    /**
     * Get campaign
     *
     * Get the campaign value from the cookie
     *
     * @return string The campaign value
     */
    private function get_campaign() {
        return $_COOKIE['wrt_campaign'] ?? '';
    }

    /**
     * Get referrer
     *
     * Get the referrer value from the cookie
     *
     * @return string The referrer value
     */
    private function get_referrer() {
        return $_COOKIE['wrt_referrer'] ?? '';
    }
}

// Initialize the plugin
function wp_referrer_tracker_init() {
    return WP_Referrer_Tracker::get_instance();
}

add_action('plugins_loaded', 'wp_referrer_tracker_init');
