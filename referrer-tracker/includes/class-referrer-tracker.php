<?php
/**
 * Main plugin class
 *
 * @package Referrer_Tracker
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if WordPress functions are available
if (!function_exists('add_action') || !function_exists('plugin_basename')) {
    return;
}

/**
 * Class Refetrfo_Referrer_Tracker
 * 
 * Main plugin class that initializes all components
 * 
 * Note: This class uses WordPress functions like get_option(), add_action(), 
 * load_plugin_textdomain(), and plugin_basename() which may trigger linting errors
 * in some environments but will work correctly within WordPress.
 */
class Refetrfo_Referrer_Tracker {
    /**
     * Plugin instance
     *
     * @var Refetrfo_Referrer_Tracker
     */
    private static $instance = null;

    /**
     * Admin instance
     *
     * @var Refetrfo_Admin
     */
    public $admin;

    /**
     * Tracker instance
     *
     * @var Refetrfo_Tracker
     */
    public $tracker;

    /**
     * CF7 integration instance
     *
     * @var Refetrfo_Integration_CF7
     */
    public $cf7;

    /**
     * WPForms integration instance
     *
     * @var Refetrfo_Integration_WPForms
     */
    public $wpforms;

    /**
     * Gravity Forms integration instance
     *
     * @var Refetrfo_Integration_Gravity
     */
    public $gravity;

    /**
     * Get the plugin instance
     *
     * @return Refetrfo_Referrer_Tracker The plugin instance
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
     * Initialize the plugin by loading dependencies and setting up hooks
     */
    private function __construct() {
        // Make sure WordPress functions are available
        if (!function_exists('get_option') || !function_exists('add_action') || !function_exists('plugin_basename')) {
            return;
        }
        
        // Constants are already defined in the main plugin file
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once dirname(__FILE__) . '/core/class-tracker.php';
        
        // Admin classes
        require_once dirname(__FILE__, 2) . '/admin/class-admin.php';
        
        // Integration classes
        $options = get_option('refetrfo_settings');
        $form_plugin = isset($options['refetrfo_form_plugin']) ? $options['refetrfo_form_plugin'] : 'cf7';
        
        // Load all integrations
        require_once dirname(__FILE__, 2) . '/integrations/class-cf7.php';
        require_once dirname(__FILE__, 2) . '/integrations/class-wpforms.php';
        require_once dirname(__FILE__, 2) . '/integrations/class-gravity.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize components
        $this->init_components();
        
        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Initialize components
     */
    private function init_components() {
        // Make sure WordPress functions are available
        if (!function_exists('get_option')) {
            return;
        }
        
        // Initialize admin
        $this->admin = new Refetrfo_Admin();
        
        // Initialize tracker
        $this->tracker = new Refetrfo_Tracker();
        
        // Initialize integrations based on settings
        $options = get_option('refetrfo_settings');
        $form_plugin = isset($options['refetrfo_form_plugin']) ? $options['refetrfo_form_plugin'] : 'cf7';
        
        // Initialize all integrations
        $this->cf7 = new Refetrfo_Integration_CF7();
        $this->wpforms = new Refetrfo_Integration_WPForms();
        $this->gravity = new Refetrfo_Integration_Gravity();
    }

    /**
     * Load plugin text domain
     * 
     * Note: Since WordPress 4.6, load_plugin_textdomain() is automatically handled
     * by WordPress for plugins hosted on WordPress.org. This method is no longer needed.
     */
    public function load_textdomain() {
        // WordPress automatically loads translations for plugins hosted on WordPress.org
        // No manual call to load_plugin_textdomain() is needed since WordPress 4.6
    }
}
