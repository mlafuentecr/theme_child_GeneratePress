<?php
/**
 * Core Plugins Manager
 * Handles the installation and activation of required core plugins
 */

class BF_Core_Plugins_Manager {

    public static function get_core_plugins() {
        return load_plugins_by_type('core');
    }

    public static function get_common_plugins() {
        return load_plugins_by_type('common');
    }

    /**
     * Install and activate all core plugins
     */
    public static function install_core_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Required for plugin installation
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        $installed_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', array());

        foreach (self::get_core_plugins() as $name => $plugin) {
            // Skip if plugin is already installed and activated
            if (isset($installed_plugins[$plugin['basename']]) && in_array($plugin['basename'], $active_plugins)) {
                continue;
            }

            // Install plugin if not installed
            if (!isset($installed_plugins[$plugin['basename']])) {
                $install_result = self::install_plugin($plugin);
                if (is_wp_error($install_result)) {
                    $msg = $install_result->get_error_message();
                    blueflamingo_debug_log(sprintf('[Blueflamingo] install_core_plugins: installation failed for %s: %s', $name, $msg));
                    // continue to next plugin
                    continue;
                }
            }

            // Activate plugin if not activated
            if (!in_array($plugin['basename'], $active_plugins)) {
                $activation_result = activate_plugin($plugin['basename']);
                if (is_wp_error($activation_result)) {
                    $msg = $activation_result->get_error_message();
                    blueflamingo_debug_log(sprintf('[Blueflamingo] Failed to activate plugin %s: %s', $name, $msg));
                }
            }
        }
    }

    /**
     * Install a single plugin
     */
    private static function install_plugin($plugin) {
        $install_type = ($plugin['type'] === 'licensed-display') ? 'free' : $plugin['type'];
        if ($install_type === 'free') {
            // Install free plugin from WordPress.org
            $api = plugins_api('plugin_information', array(
                'slug' => $plugin['slug'],
                'fields' => array(
                    'short_description' => false,
                    'sections' => false,
                    'requires' => false,
                    'rating' => false,
                    'ratings' => false,
                    'downloaded' => false,
                    'last_updated' => false,
                    'added' => false,
                    'tags' => false,
                    'compatibility' => false,
                    'homepage' => false,
                    'donate_link' => false,
                ),
            ));

            if (!is_wp_error($api)) {
                $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
                $install_result = $upgrader->install($api->download_link);

                    if (is_wp_error($install_result)) {
                    blueflamingo_debug_log(sprintf('[Blueflamingo] Failed to install plugin %s: %s', 
                        $plugin['slug'], 
                        $install_result->get_error_message()
                    ));
                    return $install_result;
                }

                // Verify installation
                $installed_after = get_plugins();
                if (!isset($installed_after[$plugin['basename']])) {
                    blueflamingo_debug_log(sprintf('[Blueflamingo] Plugin installer reported success but %s not found after install', $plugin['basename']));
                    return new WP_Error('install_failed', 'Installation reported success but plugin not found: ' . $plugin['basename']);
                }

                return true;
            }

            return new WP_Error('plugin_api_error', 'Could not fetch plugin information for ' . $plugin['slug']);
        } else {
            // For licensed plugins, use the existing premium installer class
            if (class_exists('Blue_Flamingo_Premium_Plugin_Install')) {
                $installer = new Blue_Flamingo_Premium_Plugin_Install();
                $result = $installer->install_plugin($plugin['slug']);
                if (is_wp_error($result)) {
                    blueflamingo_debug_log(sprintf('[Blueflamingo] Failed to install premium plugin %s: %s', 
                        $plugin['slug'], 
                        $result->get_error_message()
                    ));
                    return $result;
                }
                return true;
            } else {
                blueflamingo_debug_log('[Blueflamingo] Premium installer class not available to install ' . $plugin['slug']);
                return new WP_Error('installer_missing', 'Premium installer not available');
            }
        }
    }

    /**
     * Check if all core plugins are installed and activated
     */
    public static function verify_core_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $installed_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', array());
        $missing_plugins = array();

        foreach (self::get_core_plugins() as $name => $plugin) {
            if ($plugin['type'] === 'licensed' || $plugin['type'] === 'licensed-display') {
                if (!isset($installed_plugins[$plugin['basename']]) || !in_array($plugin['basename'], $active_plugins)) {
                    // For premium plugins, store the slug instead of name for lookup
                    $missing_plugins[] = $plugin['slug'];
                }
            } else {
                if (!isset($installed_plugins[$plugin['basename']]) || !in_array($plugin['basename'], $active_plugins)) {
                    $missing_plugins[] = $name;
                }
            }
        }

        return empty($missing_plugins) ? true : $missing_plugins;
    }

    public static function get_plugin_name($plugin_slug) {
        foreach (self::get_core_plugins() as $plugin_name => $plugin_data) {
            if (isset($plugin_data['slug']) && $plugin_data['slug'] === $plugin_slug) {
                return $plugin_name;
            }
        }

        // fallback if slug not found
        return $plugin_slug;
    }
}