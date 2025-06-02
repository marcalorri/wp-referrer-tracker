<?php
/**
 * Admin functionality for Referrer Tracker
 *
 * @package Referrer_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class RT_Admin
 * 
 * Handles all admin-related functionality for the Referrer Tracker plugin
 */
class RT_Admin {
    /**
     * Constructor
     */
    public function __construct() {
        // Add settings page
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add settings page
     *
     * Add a settings page to the WordPress admin dashboard
     */
    public function add_admin_menu() {
        add_options_page(
            'Referrer Tracker for Forms and CMS Settings',
            'Referrer Tracker',
            'manage_options',
            'referrer-tracker',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Display the plugin settings page
     */
    public function display_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo get_admin_page_title(); ?></h1>

            <?php
            settings_errors('referrer_tracker');
            ?>

            <form action="options.php" method="post">
                <?php
                settings_fields('rt_referrer_tracker');
                do_settings_sections('rt_referrer_tracker');
                submit_button();
                ?>
            </form>

            <?php
            $options = get_option('rt_settings');
            $this->display_implementation_instructions($options['rt_form_plugin']);
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
[hidden rt_source class:js-rt-source ""]
[hidden rt_medium class:js-rt-medium ""]
[hidden rt_campaign class:js-rt-campaign ""]
[hidden rt_referrer class:js-rt-referrer ""]</pre>
                <p><strong>Important notes:</strong></p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li>The field names must use underscore (e.g., <code>rt_source</code>)</li>
                    <li>The classes must use hyphen (e.g., <code>js-rt-source</code>)</li>
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
                    <li>Configure each hidden field with the following settings:</li>
                </ol>
                <table class="wp-list-table widefat striped" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th>Field Label</th>
                            <th>Field Name</th>
                            <th>Default Value</th>
                            <th>CSS Classes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Source</td>
                            <td>rt_source</td>
                            <td><em>Leave empty</em></td>
                            <td>js-rt-source</td>
                        </tr>
                        <tr>
                            <td>Medium</td>
                            <td>rt_medium</td>
                            <td><em>Leave empty</em></td>
                            <td>js-rt-medium</td>
                        </tr>
                        <tr>
                            <td>Campaign</td>
                            <td>rt_campaign</td>
                            <td><em>Leave empty</em></td>
                            <td>js-rt-campaign</td>
                        </tr>
                        <tr>
                            <td>Referrer</td>
                            <td>rt_referrer</td>
                            <td><em>Leave empty</em></td>
                            <td>js-rt-referrer</td>
                        </tr>
                    </tbody>
                </table>
                <p><strong>Important notes:</strong></p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li>Make sure to set the <strong>Field Name</strong> exactly as shown above (e.g., <code>rt_source</code>)</li>
                    <li>Add the <strong>CSS Classes</strong> exactly as shown above (e.g., <code>js-rt-source</code>)</li>
                    <li>Leave the <strong>Default Value</strong> empty - the plugin will populate it automatically</li>
                    <li>If you have enabled "Auto Fields" in the settings, the plugin will automatically handle these fields for you</li>
                </ul>
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
                            <td>rt_source</td>
                            <td>js-rt-source</td>
                        </tr>
                        <tr>
                            <td>Medium</td>
                            <td>rt_medium</td>
                            <td>js-rt-medium</td>
                        </tr>
                        <tr>
                            <td>Campaign</td>
                            <td>rt_campaign</td>
                            <td>js-rt-campaign</td>
                        </tr>
                        <tr>
                            <td>Referrer</td>
                            <td>rt_referrer</td>
                            <td>js-rt-referrer</td>
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
&lt;input type="hidden" name="rt_source" class="js-rt-source" value=""&gt;
&lt;input type="hidden" name="rt_medium" class="js-rt-medium" value=""&gt;
&lt;input type="hidden" name="rt_campaign" class="js-rt-campaign" value=""&gt;
&lt;input type="hidden" name="rt_referrer" class="js-rt-referrer" value=""&gt;</pre>
                <p><strong>Important notes:</strong></p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li>The name attributes must use underscore (e.g., <code>rt_source</code>)</li>
                    <li>The class attributes must use hyphen (e.g., <code>js-rt-source</code>)</li>
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
        register_setting(
            'rt_referrer_tracker',
            'rt_settings',
            array(
                'type' => 'array',
                'default' => array(
                    'rt_form_plugin' => 'cf7',
                    'rt_field_prefix' => 'rt_',
                    'rt_auto_fields' => false
                ),
                'sanitize_callback' => array($this, 'sanitize_settings')
            )
        );

        add_settings_section(
            'rt_referrer_tracker_section',
            __('General Settings', 'referrer-tracker'),
            array($this, 'section_text'),
            'rt_referrer_tracker'
        );

        add_settings_field(
            'rt_form_plugin',
            __('Form Plugin', 'referrer-tracker'),
            array($this, 'form_plugin_field'),
            'rt_referrer_tracker',
            'rt_referrer_tracker_section'
        );

        add_settings_field(
            'rt_field_prefix',
            __('Field Prefix', 'referrer-tracker'),
            array($this, 'field_prefix_field'),
            'rt_referrer_tracker',
            'rt_referrer_tracker_section'
        );

        // Solo mostrar la opción de auto fields para Gravity Forms y Generic HTML Forms
        $options = get_option('rt_settings');
        $form_plugin = isset($options['rt_form_plugin']) ? $options['rt_form_plugin'] : 'cf7';
        if ($form_plugin === 'gravity' || $form_plugin === 'generic') {
            add_settings_field(
                'rt_auto_fields',
                __('Auto Fields', 'referrer-tracker'),
                array($this, 'auto_fields_field'),
                'rt_referrer_tracker',
                'rt_referrer_tracker_section'
            );
        }
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Form Plugin
        if (isset($input['rt_form_plugin'])) {
            $sanitized['rt_form_plugin'] = sanitize_text_field($input['rt_form_plugin']);
        }
        
        // Field Prefix
        if (isset($input['rt_field_prefix'])) {
            $sanitized['rt_field_prefix'] = sanitize_text_field($input['rt_field_prefix']);
        }
        
        // Auto Fields
        $sanitized['rt_auto_fields'] = isset($input['rt_auto_fields']) ? true : false;
        
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
        $options = get_option('rt_settings');
        $current = isset($options['rt_form_plugin']) ? $options['rt_form_plugin'] : 'cf7';
        ?>
        <select name="rt_settings[rt_form_plugin]">
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
        $options = get_option('rt_settings');
        $current = isset($options['rt_field_prefix']) ? $options['rt_field_prefix'] : 'rt_';
        ?>
        <input type="text" name="rt_settings[rt_field_prefix]" value="<?php echo esc_attr($current); ?>" />
        <p class="description">Prefix for the hidden fields (e.g., rt_)</p>
        <?php
    }

    /**
     * Auto fields field
     */
    public function auto_fields_field() {
        $options = get_option('rt_settings');
        $checked = isset($options['rt_auto_fields']) ? $options['rt_auto_fields'] : false;
        $form_plugin = isset($options['rt_form_plugin']) ? $options['rt_form_plugin'] : 'cf7';
        
        // Determinar el texto según el plugin de formulario seleccionado
        $plugin_text = 'forms';
        switch ($form_plugin) {
            case 'cf7':
                $plugin_text = 'Contact Form 7 forms';
                break;
            case 'wpforms':
                $plugin_text = 'WPForms forms';
                break;
            case 'gravity':
                $plugin_text = 'Gravity Forms forms';
                break;
            case 'generic':
                $plugin_text = 'HTML forms';
                break;
        }
        ?>
        <input type="checkbox" name="rt_settings[rt_auto_fields]" value="1" <?php checked($checked, true); ?> />
        <p class="description">Automatically insert hidden fields into <?php echo $plugin_text; ?></p>
        <?php
    }
}
