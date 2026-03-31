<?php
/**
 * Runtime handlers for the Options tab.
 */

if (! defined('ABSPATH')) {
    exit;
}

function gp_child_get_options_settings(): array
{
    return (array) get_option('blueflamingo_plugin_options_settings', []);
}

function gp_child_get_support_email(): string
{
    return (string) apply_filters('gp_child_support_email', 'support@blueflamingo.solutions');
}

function gp_child_is_support_user(?WP_User $user = null): bool
{
    $user = $user ?: wp_get_current_user();

    if (! ($user instanceof WP_User) || ! $user->exists()) {
        return false;
    }

    return strtolower($user->user_email) === strtolower(gp_child_get_support_email());
}

add_action('wp_head', function (): void {
    $opts = gp_child_get_options_settings();

    if (! empty($opts['hide_google_recaptcha_logo'])) {
        echo '<style>.grecaptcha-badge{visibility:collapse!important;}</style>' . "\n";
    }
}, 99);

add_action('init', function (): void {
    $opts = gp_child_get_options_settings();

    if (empty($opts['admin_user_registration_date'])) {
        return;
    }

    add_filter('manage_users_columns', function (array $cols): array {
        $cols['registration_date'] = __('Registered', 'generatepress-child');
        return $cols;
    });

    add_filter('manage_users_custom_column', function (string $out, string $col, int $uid): string {
        if ($col !== 'registration_date') {
            return $out;
        }

        $user = get_userdata($uid);
        return $user ? esc_html(date_i18n('j M Y', strtotime($user->user_registered))) : '—';
    }, 10, 3);

    add_filter('manage_users_sortable_columns', function (array $cols): array {
        return wp_parse_args(['registration_date' => 'registered'], $cols);
    });
});

add_filter('send_password_change_email', function (bool $send): bool {
    $opts = gp_child_get_options_settings();
    return ! empty($opts['disable_admin_notifications_of_password_changes']) ? false : $send;
});

add_action('all_admin_notices', function (): void {
    $opts = gp_child_get_options_settings();

    if (empty($opts['Show_all_meta_fields']) || empty($_GET['post'])) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (! $screen || ! in_array($screen->base, ['post', 'post-new'], true)) {
        return;
    }

    $post_id = absint(wp_unslash($_GET['post']));
    if (! $post_id) {
        return;
    }

    echo '<div class="notice notice-info"><h3>All post meta:</h3><pre style="white-space:pre-wrap;max-height:320px;overflow:auto;">';
    print_r(get_post_meta($post_id)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
    echo '</pre></div>';
});

add_action('admin_init', function (): void {
    $opts = gp_child_get_options_settings();

    if (empty($opts['activate_stripe_test_mode']) || ! gp_child_is_staging_environment() || ! class_exists('WC_Stripe')) {
        return;
    }

    $settings = get_option('woocommerce_stripe_settings', []);
    if (! is_array($settings)) {
        $settings = [];
    }

    if (($settings['testmode'] ?? '') !== 'yes') {
        $settings['testmode'] = 'yes';
        update_option('woocommerce_stripe_settings', $settings);
    }
});

add_action('admin_init', function (): void {
    $opts = gp_child_get_options_settings();

    if (! array_key_exists('activate_wpsimplepay_testmode', $opts) || ! gp_child_is_staging_environment()) {
        return;
    }

    $settings = get_option('simpay_settings_keys', []);
    if (! is_array($settings) || empty($settings)) {
        return;
    }

    $settings['mode'] = is_array($settings['mode'] ?? null) ? $settings['mode'] : [];
    $settings['mode']['test_mode'] = ! empty($opts['activate_wpsimplepay_testmode']) ? 'enabled' : 'disabled';
    update_option('simpay_settings_keys', $settings);
});

add_action('admin_init', function (): void {
    $opts = gp_child_get_options_settings();

    if (empty($opts['auto_delete_standard_theme']) || ! current_user_can('delete_themes')) {
        return;
    }

    $current_theme = wp_get_theme();
    foreach (wp_get_themes() as $theme) {
        $author = (string) $theme->get('Author');
        if (strcasecmp($author, 'the WordPress team') === 0 && $theme->get_stylesheet() !== $current_theme->get_stylesheet()) {
            delete_theme($theme->get_stylesheet());
        }
    }
});

add_filter('editable_roles', function (array $roles): array {
    $opts = gp_child_get_options_settings();

    if (empty($opts['restrict_admin_creation']) || gp_child_is_support_user()) {
        return $roles;
    }

    unset($roles['administrator']);
    return $roles;
});

add_filter('map_meta_cap', function (array $caps, string $cap, int $user_id, array $args): array {
    $opts = gp_child_get_options_settings();
    $user = get_userdata($user_id);

    if (! $user || gp_child_is_support_user($user)) {
        return $caps;
    }

    $requested_role = sanitize_text_field(wp_unslash($_POST['role'] ?? ''));

    if (! empty($opts['restrict_admin_creation']) && in_array($cap, ['create_users', 'promote_user'], true) && $requested_role === 'administrator') {
        return ['do_not_allow'];
    }

    if (! empty($opts['restrict_plugin_management']) && in_array($cap, ['install_plugins', 'delete_plugins', 'update_plugins', 'activate_plugins'], true)) {
        return ['do_not_allow'];
    }

    if (! empty($opts['limit_ability_to_add_new_plugin']) && $cap === 'install_plugins') {
        return ['do_not_allow'];
    }

    return $caps;
}, 10, 4);

add_filter('plugin_action_links', function (array $actions, string $plugin_file): array {
    $opts = gp_child_get_options_settings();

    if (empty($opts['restrict_plugin_management']) || gp_child_is_support_user()) {
        return $actions;
    }

    unset($actions['activate'], $actions['deactivate'], $actions['delete'], $actions['update-check']);
    return $actions;
}, 10, 2);

add_filter('bulk_actions-plugins', function (array $actions): array {
    $opts = gp_child_get_options_settings();

    if (empty($opts['restrict_plugin_management']) || gp_child_is_support_user()) {
        return $actions;
    }

    unset($actions['activate-selected'], $actions['deactivate-selected'], $actions['delete-selected'], $actions['update-selected']);
    return $actions;
});

add_action('admin_menu', function (): void {
    $opts = gp_child_get_options_settings();

    if (gp_child_is_support_user()) {
        return;
    }

    if (! empty($opts['limit_ability_to_add_new_plugin']) || ! empty($opts['restrict_plugin_management'])) {
        remove_submenu_page('plugins.php', 'plugin-install.php');
    }
}, 99);

add_action('admin_init', function (): void {
    global $pagenow;

    if (gp_child_is_support_user()) {
        return;
    }

    $opts   = gp_child_get_options_settings();
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $action = sanitize_key(wp_unslash($_GET['action'] ?? ''));

    if (! empty($opts['restrict_plugin_management']) && in_array($action, ['activate', 'deactivate', 'delete', 'activate-selected', 'deactivate-selected', 'delete-selected'], true)) {
        wp_die(esc_html__('Plugin management is restricted.', 'generatepress-child'));
    }

    if (! empty($opts['limit_ability_to_add_new_plugin'])) {
        if (($screen && in_array($screen->id, ['plugin-install', 'plugin-install-network'], true)) || $pagenow === 'plugin-install.php') {
            wp_die(esc_html__('Adding new plugins is restricted.', 'generatepress-child'));
        }
    }
});

add_action('admin_head', function (): void {
    if (gp_child_is_support_user()) {
        return;
    }

    $opts = gp_child_get_options_settings();
    $css  = [];

    if (! empty($opts['restrict_plugin_management'])) {
        $css[] = '.plugins-php .row-actions .activate, .plugins-php .row-actions .deactivate, .plugins-php .row-actions .delete, .plugins-php .row-actions .update, .plugins-php .open-plugin-details-modal { display:none !important; }';
    }

    if (! empty($opts['limit_ability_to_add_new_plugin']) || ! empty($opts['restrict_plugin_management'])) {
        $css[] = '.plugins-php .page-title-action, .plugins-php .upload-plugin, .plugins-php a[href*="plugin-install.php"] { display:none !important; }';
    }

    if ($css) {
        echo '<style>' . implode("\n", $css) . '</style>' . "\n";
    }
});
