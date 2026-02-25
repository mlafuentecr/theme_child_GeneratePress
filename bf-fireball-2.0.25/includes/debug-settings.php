<?php

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Updates WordPress debug settings in wp-config.php based on plugin settings
 */
function bf_update_debug_settings() {
    try {
        // Get the plugin options
        $options = get_option('blueflamingo_plugin_options_settings');
        
        // Get wp-config.php path
        $config_file = ABSPATH . 'wp-config.php';
        
        if (!file_exists($config_file)) {
            throw new Exception('wp-config.php not found');
        }

        if (!is_readable($config_file)) {
            throw new Exception('wp-config.php is not readable');
        }

        if (!is_writable($config_file)) {
            throw new Exception('wp-config.php is not writable');
        }

        // Create backup first
        $backup_file = $config_file . '.backup-' . date('Y-m-d-His');
        if (!copy($config_file, $backup_file)) {
            throw new Exception('Failed to create backup file');
        }

        // Get current WordPress debug constants
        $debug_settings = array(
            'WP_DEBUG' => defined('WP_DEBUG') ? WP_DEBUG : false,
            'WP_DEBUG_LOG' => defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : false,
            'WP_DEBUG_DISPLAY' => defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY : false
        );

        // Override with plugin settings if they exist
        if (!empty($options)) {
            $debug_settings['WP_DEBUG'] = !empty($options['wp_debug']);
            $debug_settings['WP_DEBUG_LOG'] = !empty($options['wp_debug_log']);
            $debug_settings['WP_DEBUG_DISPLAY'] = !empty($options['wp_debug_display']);
        }

        // Read current config
        $config_content = file_get_contents($config_file);
        if ($config_content === false) {
            throw new Exception('Failed to read wp-config.php');
        }
    
    // Read the config file
    $config_content = file_get_contents($config_file);
    if ($config_content === false) {
        return false;
    }
    
    // Prepare debug constants
    $debug_settings = array(
        'WP_DEBUG' => !empty($options['wp_debug']),
        'WP_DEBUG_LOG' => !empty($options['wp_debug_log']),
        'WP_DEBUG_DISPLAY' => !empty($options['wp_debug_display'])
    );
    
    // Update each debug setting
    foreach ($debug_settings as $constant => $value) {
        $value_string = $value ? 'true' : 'false';
        
        // Check if constant exists
        $pattern = "/define\s*\(\s*['\"]" . $constant . "['\"]\s*,\s*(true|false)\s*\);/i";
        
        if (preg_match($pattern, $config_content)) {
            // Update existing constant
            $config_content = preg_replace(
                $pattern,
                "define('" . $constant . "', " . $value_string . ");",
                $config_content
            );
        } else {
            // Add new constant before /* That's all, stop editing! Happy publishing. */
            $insertion = "define('" . $constant . "', " . $value_string . ");\n";
            $config_content = str_replace(
                "/* That's all, stop editing! Happy publishing. */",
                $insertion . "/* That's all, stop editing! Happy publishing. */",
                $config_content
            );
        }
    }
    
    // Backup the original file
    $backup_file = $config_file . '.backup-' . date('Y-m-d-His');
    if (!copy($config_file, $backup_file)) {
        return false;
    }
    
        // Update each debug setting
        foreach ($debug_settings as $constant => $value) {
            $value_string = $value ? 'true' : 'false';
            
            // Check if constant exists
            $pattern = "/define\s*\(\s*['\"]" . $constant . "['\"]\s*,\s*(true|false)\s*\);/i";
            
            if (preg_match($pattern, $config_content)) {
                // Update existing constant
                $config_content = preg_replace(
                    $pattern,
                    "define('" . $constant . "', " . $value_string . ");",
                    $config_content
                );
            } else {
                // Add new constant before /* That's all, stop editing! Happy publishing. */
                $insertion = "define('" . $constant . "', " . $value_string . ");\n";
                $config_content = str_replace(
                    "/* That's all, stop editing! Happy publishing. */",
                    $insertion . "/* That's all, stop editing! Happy publishing. */",
                    $config_content
                );
            }
        }

        // Write the modified content back to wp-config.php
        if (file_put_contents($config_file, $config_content) === false) {
            throw new Exception('Failed to write to wp-config.php');
        }

        // Store the result in a transient to show admin notice
        set_transient('bf_debug_settings_updated', 'success', 30);
        return true;

    } catch (Exception $e) {
        // Store the error message in a transient
        set_transient('bf_debug_settings_error', $e->getMessage(), 30);
        return false;
    }
}

/**
 * Hook the debug settings update to the plugin options save action
 */
function bf_init_debug_settings() {
    // Hook into the options update with high priority (1)
    add_action('admin_init', 'bf_maybe_update_debug_settings', 1);
}

/**
 * Check if we need to update debug settings and handle the update
 */
function bf_maybe_update_debug_settings() {
    // Check if we're saving options and it's our plugin's options
    if (
        isset($_POST['option_page']) && 
        $_POST['option_page'] === 'blueflamingo_plugin_options_settings_group' &&
        isset($_POST['action']) && 
        $_POST['action'] === 'update'
    ) {
        // Verify nonce before proceeding
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'blueflamingo_plugin_options_settings_group-options')) {
            return;
        }

        // Get the new options being saved
        $new_options = isset($_POST['blueflamingo_plugin_options_settings']) ? $_POST['blueflamingo_plugin_options_settings'] : array();
        
        // Update debug settings before WordPress processes the option update
        $updated = bf_update_debug_settings();
        
        if ($updated) {
            // Store success message in transient
            set_transient('bf_debug_settings_updated', true, 30);
        }
        
        // Add custom query arg to redirect URL
        add_filter('wp_redirect', function($location) {
            return add_query_arg('bf_debug_updated', '1', $location);
        });
    }
}

add_action('admin_init', 'bf_init_debug_settings');

/**
 * Display an admin notice after settings update
 */
function bf_debug_settings_admin_notice() {
    // Only show messages on our plugin's pages
    $screen = get_current_screen();
    if ($screen->base !== 'toplevel_page_blueflamingo') {
        return;
    }

    // Check if we just updated debug settings
    if (isset($_GET['bf_debug_updated']) && $_GET['bf_debug_updated'] === '1') {
        if (get_transient('bf_debug_settings_updated')) {
            delete_transient('bf_debug_settings_updated');
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Debug settings have been updated and applied successfully.', 'blue-flamingo-text'); ?></p>
            </div>
            <?php
        }

        // Check for error message
        $error_message = get_transient('bf_debug_settings_error');
        if ($error_message) {
            delete_transient('bf_debug_settings_error');
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e('Failed to update debug settings: ', 'blue-flamingo-text'); echo esc_html($error_message); ?></p>
                <p><?php _e('Please check file permissions for wp-config.php or contact your server administrator.', 'blue-flamingo-text'); ?></p>
            </div>
            <?php
        }
    }
}
add_action('admin_notices', 'bf_debug_settings_admin_notice');