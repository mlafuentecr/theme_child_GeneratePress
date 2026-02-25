<?php
/**
 * Runtime Debug Settings Handler
 * Controls debug settings without modifying wp-config.php
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class BF_Debug_Settings {
    private static $instance = null;
    private $options;

    private function __construct() {
        $this->options = get_option('blueflamingo_plugin_options_settings', array());
        $this->init_hooks();
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_hooks() {
        // Hook early into WordPress loading
        add_action('plugins_loaded', array($this, 'setup_debug_constants'), 1);
        
        // Hook into error reporting
        add_action('init', array($this, 'setup_error_reporting'), 1);
        
        // Filter for debug display
        add_filter('wp_debug_display', array($this, 'filter_debug_display'), 1);
    }

    public function setup_debug_constants() {
        // No constants needed, handle via ini_set instead
        $this->setup_error_reporting();
    }

    public function setup_error_reporting() {
        $debug_enabled = !empty($this->options['wp_debug']);
        $log_enabled = !empty($this->options['wp_debug_log']);
        $display_enabled = !empty($this->options['wp_debug_display']);

        error_reporting($debug_enabled ? E_ALL : 0);
        ini_set('display_errors', $display_enabled ? '1' : '0');
        ini_set('log_errors', $log_enabled ? '1' : '0');

        if ($log_enabled) {
            ini_set('error_log', WP_CONTENT_DIR . '/debug.log');
        }
    }

    public function filter_debug_display($display) {
        return !empty($this->options['wp_debug_display']);
    }

    /**
     * Get the current debug status
     */
    public function get_debug_status() {
        return array(
            'wp_debug' => defined('WP_DEBUG') ? WP_DEBUG : false,
            'wp_debug_log' => defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : false,
            'wp_debug_display' => defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY : false
        );
    }

    /**
     * Toggle debug mode on/off
     * 
     * @param bool $enable Whether to enable or disable debug mode
     * @param array $options Optional. Specific debug options to set
     * @return bool True if successful, false otherwise
     */
    public function toggle_wp_debug($enable = true, $options = array()) {
        try {
            // Update options
            $current_options = get_option('blueflamingo_plugin_options_settings', array());
            
            // Set debug options
            $current_options['wp_debug'] = $enable;
            
            // Handle specific debug options if provided
            if (!empty($options)) {
                if (isset($options['wp_debug_log'])) {
                    $current_options['wp_debug_log'] = $options['wp_debug_log'];
                }
                if (isset($options['wp_debug_display'])) {
                    $current_options['wp_debug_display'] = $options['wp_debug_display'];
                }
            } else {
                // Default behavior when enabling debug
                if ($enable) {
                    $current_options['wp_debug_log'] = true;
                    $current_options['wp_debug_display'] = false; // Safer default for production
                } else {
                    // Disable all debug options when turning off debug mode
                    $current_options['wp_debug_log'] = false;
                    $current_options['wp_debug_display'] = false;
                }
            }

            // Save updated options
            update_option('blueflamingo_plugin_options_settings', $current_options);

            // Update runtime settings
            $this->options = $current_options;
            $this->setup_debug_constants();
            $this->setup_error_reporting();

            return true;
        } catch (Exception $e) {
            error_log('Blue Flamingo Debug Toggle Error: ' . $e->getMessage());
            return false;
        }
    }
}

// Initialize the debug settings handler
add_action('plugins_loaded', array('BF_Debug_Settings', 'get_instance'), 0);

/**
 * Helper function to check debug status
 */
function bf_is_debug_enabled() {
    return BF_Debug_Settings::get_instance()->get_debug_status();
}

/**
 * Helper function to toggle debug mode
 * 
 * @param bool $enable Whether to enable or disable debug mode
 * @param array $options Optional. Specific debug options to set
 * @return bool True if successful, false otherwise
 */
function bf_toggle_debug($enable = true, $options = array()) {
    return BF_Debug_Settings::get_instance()->toggle_wp_debug($enable, $options);
}