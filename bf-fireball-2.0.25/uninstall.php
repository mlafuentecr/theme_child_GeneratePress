<?php
/**
 * Uninstall Event
 *
 * Runs when blueflamingo is Uninstalled
 */

// Exit if not uninstalling from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Clean up all plugin options
$options_to_delete = array(
    'blueflamingo_plugin_general_settings',
    'blueflamingo_plugin_options_settings',
    'blueflamingo_plugin_email_redirect_settings',
    'blueflamingo_plugin_google_analytics_settings',
    'blueflamingo_plugin_error_page_settings',
    'blueflamingo_plugin_admin_display_settings',
    'blueflamingo_plugin_plugin_manager',
    'blueflamingo_plugin_all_settings',
    'blueflamingo_plugin_post_types_settings',
    'blueflamingo_plugin_db_updater',
    'blueflamingo_plugin_security_settings',
    'bf_plugin_version',
    'bf_chatbot_db_version'
);

// License-related and other transients to clean up
$transients_to_delete = array(
    'bf_licensed_plugins_files',
    'bf_licensed_plugins_github_urls',
    'bf_licensed_plugins_github_info',
    'bf_plugin_meta_*', // Will be handled separately for wildcard
    'bf_csp_limit_*' // Will be handled separately for wildcard
);

// Delete options and transients for single site
foreach ($options_to_delete as $option) {
    delete_option($option);
}

// Delete regular transients
foreach ($transients_to_delete as $transient) {
    if (strpos($transient, '*') === false) {
        delete_transient($transient);
    }
}

// Handle wildcard transients using direct database query
global $wpdb;
foreach ($transients_to_delete as $transient) {
    if (strpos($transient, '*') !== false) {
        $like = str_replace('*', '%', $transient);
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $wpdb->esc_like($like),
                '_transient_timeout_' . $wpdb->esc_like($like)
            )
        );
    }
}

// If this is a multisite installation, clean up options and transients for all sites
if (is_multisite()) {
    $sites = get_sites();
    foreach ($sites as $site) {
        switch_to_blog($site->blog_id);
        
        // Delete options
        foreach ($options_to_delete as $option) {
            delete_option($option);
        }
        
        // Delete regular transients
        foreach ($transients_to_delete as $transient) {
            if (strpos($transient, '*') === false) {
                delete_transient($transient);
            }
        }

        // Handle wildcard transients using direct database query
        foreach ($transients_to_delete as $transient) {
            if (strpos($transient, '*') !== false) {
                $like = str_replace('*', '%', $transient);
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                        '_transient_' . $wpdb->esc_like($like),
                        '_transient_timeout_' . $wpdb->esc_like($like)
                    )
                );
            }
        }
        
        restore_current_blog();
    }
}

// Remove only licensed plugins (not free/core plugins) when uninstalling Blue Flamingo
if (!class_exists('BF_Core_Plugins_Manager')) {
    require_once dirname(__FILE__) . '/includes/class-core-plugins-manager.php';
}
$core_plugins = BF_Core_Plugins_Manager::get_core_plugins();
foreach ($core_plugins as $plugin) {
    // Only delete plugins with type 'licensed' or 'licensed-display'
    if (isset($plugin['type']) && ($plugin['type'] === 'licensed' || $plugin['type'] === 'licensed-display')) {
        $plugin_folder = explode('/', $plugin['basename'])[0];
        $plugin_dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_folder;
        if (is_dir($plugin_dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($plugin_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($plugin_dir);
        }
    }
}

// Remove all licensed plugins (from repo/API) when uninstalling Blue Flamingo
if (!class_exists('Blue_Flamingo_Premium_Plugin_Install')) {
    require_once dirname(__FILE__) . '/includes/premium-plugin-install.php';
}
$installer = new Blue_Flamingo_Premium_Plugin_Install();
$licensed_plugins = $installer->get_licensed_plugin_info();
foreach ($licensed_plugins as $plugin) {
    if (isset($plugin['basename'])) {
        $plugin_folder = explode('/', $plugin['basename'])[0];
        $plugin_dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_folder;
        if (is_dir($plugin_dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($plugin_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($plugin_dir);
        }
    }
}