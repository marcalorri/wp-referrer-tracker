<?php
/**
 * Plugin Name: WP Referrer Tracker
 * Plugin URI: 
 * Description: Track referrer information and parse it into source, medium and campaign for any form plugin. Supports WPForms, Contact Form 7, Gravity Forms, and generic HTML forms with automatic code insertion.
 * Version: 1.3.0
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
 * Supports major form plugins with automatic implementation:
 * - WPForms
 * - Contact Form 7
 * - Gravity Forms
 * - Generic HTML Forms
 * 
 * Features:
 * - Automatic form field insertion
 * - Custom field prefix configuration
 * - Plugin-specific implementation code generation
 * - Automatic code insertion in functions.php
 * - Automatic backup creation
 * - Comprehensive paid traffic detection
 * - International search engine support
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
        $this->setup_cookies();
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
            array($this, 'settings_page')
        );
    }

    /**
     * Register settings
     *
     * Register the plugin settings and add fields to the settings page
     */
    public function register_settings() {
        register_setting('wp_referrer_tracker', 'wrt_settings', array($this, 'validate_settings'));
        
        // Main Settings Section
        add_settings_section(
            'wrt_main_section',
            'Main Settings',
            null,
            'wp-referrer-tracker'
        );

        // Auto Fields Setting
        add_settings_field(
            'wrt_auto_fields',
            'Auto-insert Hidden Fields',
            array($this, 'auto_fields_callback'),
            'wp-referrer-tracker',
            'wrt_main_section'
        );

        // Field Prefix Setting
        add_settings_field(
            'wrt_field_prefix',
            'Field Prefix',
            array($this, 'field_prefix_callback'),
            'wp-referrer-tracker',
            'wrt_main_section'
        );

        // Form Plugin Selection
        add_settings_field(
            'wrt_form_plugin',
            'Form Plugin',
            array($this, 'form_plugin_callback'),
            'wp-referrer-tracker',
            'wrt_main_section'
        );

        // Code Generation Section
        add_settings_section(
            'wrt_code_section',
            'Code Generation',
            array($this, 'code_section_callback'),
            'wp-referrer-tracker'
        );

        // Generate Code Setting
        add_settings_field(
            'wrt_generate_code',
            'Generate Code for functions.php',
            array($this, 'generate_code_callback'),
            'wp-referrer-tracker',
            'wrt_code_section'
        );

        // Auto Insert Code Setting
        add_settings_field(
            'wrt_auto_insert_code',
            'Auto Insert Code in functions.php',
            array($this, 'auto_insert_code_callback'),
            'wp-referrer-tracker',
            'wrt_code_section'
        );
    }

    /**
     * Auto fields callback
     *
     * Display the auto fields setting
     */
    public function auto_fields_callback() {
        $options = get_option('wrt_settings', array(
            'auto_fields' => true,
            'field_prefix' => 'wrt_',
            'form_plugin' => 'none',
            'generate_code' => false,
            'auto_insert_code' => false
        ));
        ?>
        <input type="checkbox" name="wrt_settings[auto_fields]" 
               <?php checked($options['auto_fields'], 1); ?> value="1">
        <p class="description">If checked, hidden fields will be automatically inserted into forms</p>
        <?php
    }

    /**
     * Field prefix callback
     *
     * Display the field prefix setting
     */
    public function field_prefix_callback() {
        $options = get_option('wrt_settings', array(
            'auto_fields' => true,
            'field_prefix' => 'wrt_',
            'form_plugin' => 'none',
            'generate_code' => false,
            'auto_insert_code' => false
        ));
        ?>
        <input type="text" name="wrt_settings[field_prefix]" 
               value="<?php echo esc_attr($options['field_prefix']); ?>">
        <p class="description">Prefix for automatically inserted field names (e.g., wrt_source)</p>
        <?php
    }

    /**
     * Form plugin callback
     *
     * Display the form plugin selection
     */
    public function form_plugin_callback() {
        $options = get_option('wrt_settings', array(
            'auto_fields' => true,
            'field_prefix' => 'wrt_',
            'form_plugin' => 'none',
            'generate_code' => false,
            'auto_insert_code' => false
        ));

        $plugins = array(
            'none' => 'None (Manual Implementation)',
            'wpforms' => 'WPForms',
            'cf7' => 'Contact Form 7',
            'gravity' => 'Gravity Forms',
            'generic' => 'Generic HTML Forms'
        );

        echo '<select name="wrt_settings[form_plugin]">';
        foreach ($plugins as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . 
                 selected($options['form_plugin'], $value, false) . '>' . 
                 esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Select your form plugin to get specific implementation code</p>';
    }

    /**
     * Code section callback
     *
     * Display the code generation section
     */
    public function code_section_callback() {
        echo '<p>Generate implementation code for your selected form plugin.</p>';
    }

    /**
     * Generate code callback
     *
     * Display the generate code setting
     */
    public function generate_code_callback() {
        $options = get_option('wrt_settings', array(
            'auto_fields' => true,
            'field_prefix' => 'wrt_',
            'form_plugin' => 'none',
            'generate_code' => false,
            'auto_insert_code' => false
        ));

        echo '<input type="checkbox" name="wrt_settings[generate_code]" ' . 
             checked($options['generate_code'], 1, false) . ' value="1">';
        echo '<p class="description">If checked, the plugin will display the code to add to your functions.php</p>';
    }

    /**
     * Auto insert code callback
     *
     * Display the auto insert code setting
     */
    public function auto_insert_code_callback() {
        $options = get_option('wrt_settings', array(
            'auto_fields' => true,
            'field_prefix' => 'wrt_',
            'form_plugin' => 'none',
            'generate_code' => false,
            'auto_insert_code' => false
        ));

        echo '<input type="checkbox" name="wrt_settings[auto_insert_code]" ' . 
             checked($options['auto_insert_code'], 1, false) . ' value="1">';
        echo '<p class="description">If checked, the plugin will automatically insert/update the code in your theme\'s functions.php file</p>';
        
        // Mostrar advertencia si el archivo functions.php no es escribible
        if (!$this->is_functions_writable()) {
            echo '<p class="notice notice-warning" style="padding: 10px;">Warning: Your theme\'s functions.php file is not writable. Please check the file permissions.</p>';
        }
    }

    /**
     * Validate settings
     *
     * Validate the plugin settings and perform actions based on changes
     */
    public function validate_settings($input) {
        $old_options = get_option('wrt_settings');
        
        // Si se activa la inserción automática y no estaba activada antes
        if (!empty($input['auto_insert_code']) && empty($old_options['auto_insert_code'])) {
            $this->insert_code_in_functions($input['form_plugin'], $input['field_prefix']);
        }
        // Si se cambia el plugin o el prefijo y la inserción automática está activa
        elseif (!empty($input['auto_insert_code']) && 
                ($input['form_plugin'] !== $old_options['form_plugin'] || 
                 $input['field_prefix'] !== $old_options['field_prefix'])) {
            $this->update_code_in_functions($input['form_plugin'], $input['field_prefix']);
        }
        // Si se desactiva la inserción automática
        elseif (empty($input['auto_insert_code']) && !empty($old_options['auto_insert_code'])) {
            $this->remove_code_from_functions();
        }

        return $input;
    }

    /**
     * Check if functions.php is writable
     *
     * Check if the theme's functions.php file is writable
     */
    private function is_functions_writable() {
        $functions_file = get_template_directory() . '/functions.php';
        return file_exists($functions_file) && is_writable($functions_file);
    }

    /**
     * Insert code in functions.php
     *
     * Insert the implementation code in the theme's functions.php file
     */
    private function insert_code_in_functions($plugin, $prefix) {
        $functions_file = get_template_directory() . '/functions.php';
        
        if (!$this->is_functions_writable()) {
            add_settings_error(
                'wp_referrer_tracker',
                'functions_not_writable',
                'Could not write to functions.php. Please check file permissions.',
                'error'
            );
            return false;
        }

        // Leer el contenido actual
        $current_content = file_get_contents($functions_file);
        
        // Verificar si el código ya existe
        if (strpos($current_content, '// WP Referrer Tracker Implementation Code') !== false) {
            return false;
        }

        // Preparar el nuevo código
        $new_code = "\n\n" . $this->get_implementation_code($plugin, $prefix);

        // Hacer backup del archivo original
        $backup_file = $functions_file . '.backup-' . date('Y-m-d-His');
        copy($functions_file, $backup_file);

        // Añadir el nuevo código al final del archivo
        $success = file_put_contents($functions_file, $current_content . $new_code);

        if (!$success) {
            add_settings_error(
                'wp_referrer_tracker',
                'code_insert_failed',
                'Failed to insert code in functions.php',
                'error'
            );
            return false;
        }

        add_settings_error(
            'wp_referrer_tracker',
            'code_inserted',
            'Code successfully inserted in functions.php',
            'success'
        );
        return true;
    }

    /**
     * Update code in functions.php
     *
     * Update the implementation code in the theme's functions.php file
     */
    private function update_code_in_functions($plugin, $prefix) {
        $functions_file = get_template_directory() . '/functions.php';
        
        if (!$this->is_functions_writable()) {
            add_settings_error(
                'wp_referrer_tracker',
                'functions_not_writable',
                'Could not write to functions.php. Please check file permissions.',
                'error'
            );
            return false;
        }

        // Leer el contenido actual
        $current_content = file_get_contents($functions_file);
        
        // Hacer backup del archivo original
        $backup_file = $functions_file . '.backup-' . date('Y-m-d-His');
        copy($functions_file, $backup_file);

        // Eliminar el código antiguo
        $pattern = '/\/\/ WP Referrer Tracker Implementation Code.*?\}\);/s';
        $new_content = preg_replace($pattern, '', $current_content);

        // Añadir el nuevo código
        $new_code = $this->get_implementation_code($plugin, $prefix);
        $new_content .= "\n\n" . $new_code;

        // Guardar los cambios
        $success = file_put_contents($functions_file, $new_content);

        if (!$success) {
            add_settings_error(
                'wp_referrer_tracker',
                'code_update_failed',
                'Failed to update code in functions.php',
                'error'
            );
            return false;
        }

        add_settings_error(
            'wp_referrer_tracker',
            'code_updated',
            'Code successfully updated in functions.php',
            'success'
        );
        return true;
    }

    /**
     * Remove code from functions.php
     *
     * Remove the implementation code from the theme's functions.php file
     */
    private function remove_code_from_functions() {
        $functions_file = get_template_directory() . '/functions.php';
        
        if (!$this->is_functions_writable()) {
            add_settings_error(
                'wp_referrer_tracker',
                'functions_not_writable',
                'Could not write to functions.php. Please check file permissions.',
                'error'
            );
            return false;
        }

        // Leer el contenido actual
        $current_content = file_get_contents($functions_file);
        
        // Hacer backup del archivo original
        $backup_file = $functions_file . '.backup-' . date('Y-m-d-His');
        copy($functions_file, $backup_file);

        // Eliminar el código
        $pattern = '/\n*\/\/ WP Referrer Tracker Implementation Code.*?\}\);\n*/s';
        $new_content = preg_replace($pattern, '', $current_content);

        // Guardar los cambios
        $success = file_put_contents($functions_file, $new_content);

        if (!$success) {
            add_settings_error(
                'wp_referrer_tracker',
                'code_remove_failed',
                'Failed to remove code from functions.php',
                'error'
            );
            return false;
        }

        add_settings_error(
            'wp_referrer_tracker',
            'code_removed',
            'Code successfully removed from functions.php',
            'success'
        );
        return true;
    }

    /**
     * Settings page
     *
     * Display the settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h2>WP Referrer Tracker Settings</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_referrer_tracker');
                do_settings_sections('wp-referrer-tracker');
                submit_button();
                ?>
            </form>

            <?php
            $options = get_option('wrt_settings');
            if (!empty($options['generate_code']) && $options['form_plugin'] !== 'none') {
                $this->display_implementation_code($options['form_plugin'], $options['field_prefix']);
            }
            ?>
        </div>
        <?php
    }

    /**
     * Display implementation code
     *
     * Display the implementation code for the selected form plugin
     */
    private function display_implementation_code($plugin, $prefix) {
        $code = $this->get_implementation_code($plugin, $prefix);
        if (!empty($code)) {
            echo '<div class="wrt-code-section" style="margin-top: 20px;">';
            echo '<h3>Implementation Code</h3>';
            echo '<p>Add this code to your theme\'s functions.php file:</p>';
            echo '<pre style="background: #f1f1f1; padding: 15px; overflow: auto;">';
            echo esc_html($code);
            echo '</pre>';
            echo '</div>';
        }
    }

    /**
     * Get implementation code
     *
     * Get the implementation code for the selected form plugin
     */
    private function get_implementation_code($plugin, $prefix) {
        $code = "// WP Referrer Tracker Implementation Code\n";
        $code .= "add_action('wp_footer', function() {\n";
        
        switch ($plugin) {
            case 'wpforms':
                $code .= $this->get_wpforms_code($prefix);
                break;
            case 'cf7':
                $code .= $this->get_cf7_code($prefix);
                break;
            case 'gravity':
                $code .= $this->get_gravity_forms_code($prefix);
                break;
            case 'generic':
                $code .= $this->get_generic_forms_code($prefix);
                break;
        }
        
        $code .= "});\n";
        return $code;
    }

    /**
     * Get WPForms code
     *
     * Get the implementation code for WPForms
     */
    private function get_wpforms_code($prefix) {
        return "    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof wpforms !== 'undefined') {
            wpforms.on('wpformsBeforeFormSubmit', function(form) {
                const fields = {
                    'source': getReferrerValue('source'),
                    'medium': getReferrerValue('medium'),
                    'campaign': getReferrerValue('campaign'),
                    'referrer': getReferrerValue('referrer')
                };
                
                for (let [key, value] of Object.entries(fields)) {
                    const fieldName = '{$prefix}' + key;
                    const hiddenField = form.querySelector('input[name=\"' + fieldName + '\"]');
                    if (hiddenField) {
                        hiddenField.value = value;
                    }
                }
            });
        }
    });
    </script>
    <?php";
    }

    /**
     * Get Contact Form 7 code
     *
     * Get the implementation code for Contact Form 7
     */
    private function get_cf7_code($prefix) {
        return "    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function updateHiddenField(className, valueType) {
            const fields = document.getElementsByClassName('wpcf7-form-control-wrap ' + className);
            for (let field of fields) {
                const input = field.querySelector('input[type=\"hidden\"]');
                if (input) {
                    input.value = getReferrerValue(valueType);
                }
            }
        }

        document.addEventListener('wpcf7submit', function() {
            updateHiddenField('{$prefix}source', 'source');
            updateHiddenField('{$prefix}medium', 'medium');
            updateHiddenField('{$prefix}campaign', 'campaign');
            updateHiddenField('{$prefix}referrer', 'referrer');
        });
    });
    </script>
    <?php";
    }

    /**
     * Get Gravity Forms code
     *
     * Get the implementation code for Gravity Forms
     */
    private function get_gravity_forms_code($prefix) {
        return "    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof gform !== 'undefined') {
            gform.addFilter('gform_form_pre_render', function(form) {
                const fields = {
                    'source': getReferrerValue('source'),
                    'medium': getReferrerValue('medium'),
                    'campaign': getReferrerValue('campaign'),
                    'referrer': getReferrerValue('referrer')
                };
                
                for (let [key, value] of Object.entries(fields)) {
                    const fieldName = '{$prefix}' + key;
                    jQuery('input[name=\"input_' + fieldName + '\"]').val(value);
                }
                return form;
            });
        }
    });
    </script>
    <?php";
    }

    /**
     * Get generic forms code
     *
     * Get the implementation code for generic HTML forms
     */
    private function get_generic_forms_code($prefix) {
        return "    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.getElementsByTagName('form');
        for (let form of forms) {
            const fields = {
                'source': getReferrerValue('source'),
                'medium': getReferrerValue('medium'),
                'campaign': getReferrerValue('campaign'),
                'referrer': getReferrerValue('referrer')
            };
            
            for (let [key, value] of Object.entries(fields)) {
                const fieldName = '{$prefix}' + key;
                const input = form.querySelector('input[name=\"' + fieldName + '\"]');
                if (input) {
                    input.value = value;
                }
            }
        }
    });
    </script>
    <?php";
    }

    /**
     * Set up cookies
     *
     * Set up cookies to track referrer information
     */
    public function setup_cookies() {
        if (!isset($_COOKIE['wrt_referrer']) && isset($_SERVER['HTTP_REFERER'])) {
            $referrer = $_SERVER['HTTP_REFERER'];
            $parsed = $this->parse_referrer($referrer);
            
            // Set cookies for 30 days
            setcookie('wrt_source', $parsed['source'], time() + (86400 * 30), '/');
            setcookie('wrt_medium', $parsed['medium'], time() + (86400 * 30), '/');
            setcookie('wrt_campaign', $parsed['campaign'], time() + (86400 * 30), '/');
            setcookie('wrt_referrer', $referrer, time() + (86400 * 30), '/');
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
            'generate_code' => false,
            'auto_insert_code' => false
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
}

// Initialize the plugin
function wp_referrer_tracker_init() {
    return WP_Referrer_Tracker::get_instance();
}

add_action('plugins_loaded', 'wp_referrer_tracker_init');
