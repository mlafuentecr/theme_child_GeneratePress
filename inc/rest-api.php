<?php
/**
 * REST API runtime helpers.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', function (): void {
    $opts = (array) get_option('blueflamingo_plugin_options_settings', []);

    if (! empty($opts['json_basic_authentication'])) {
        add_filter('determine_current_user', 'gp_child_json_basic_auth_handler', 20);
        add_filter('rest_authentication_errors', 'gp_child_json_basic_auth_error');
    }
});

function gp_child_json_basic_auth_handler(mixed $user): mixed
{
    global $gp_child_json_basic_auth_error;
    $gp_child_json_basic_auth_error = null;

    if (! empty($user)) {
        return $user;
    }

    if (! isset($_SERVER['PHP_AUTH_USER'])) {
        return $user;
    }

    remove_filter('determine_current_user', 'gp_child_json_basic_auth_handler', 20);
    $auth_user = wp_authenticate(
        sanitize_user((string) $_SERVER['PHP_AUTH_USER']),
        (string) ($_SERVER['PHP_AUTH_PW'] ?? '')
    );
    add_filter('determine_current_user', 'gp_child_json_basic_auth_handler', 20);

    if (is_wp_error($auth_user)) {
        $gp_child_json_basic_auth_error = $auth_user;
        return null;
    }

    $gp_child_json_basic_auth_error = true;
    return $auth_user->ID;
}

function gp_child_json_basic_auth_error(mixed $error): mixed
{
    if (! empty($error)) {
        return $error;
    }

    global $gp_child_json_basic_auth_error;
    return $gp_child_json_basic_auth_error;
}
