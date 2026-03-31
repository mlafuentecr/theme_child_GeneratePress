<?php
/**
 * WordPress/GeneratePress cleanup toggles controlled from theme settings.
 */

if (! defined('ABSPATH')) {
    exit;
}

function gp_child_cleanup_options(): array
{
    return (array) get_option('blueflamingo_plugin_options_settings', []);
}

function gp_child_cleanup_is_enabled(string $key): bool
{
    $options = gp_child_cleanup_options();
    return ! empty($options[$key]);
}

add_action('init', static function (): void {
    if (gp_child_cleanup_is_enabled('disable_emojis')) {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('emoji_svg_url', '__return_false');
    }

    if (gp_child_cleanup_is_enabled('disable_embeds')) {
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
        remove_action('rest_api_init', 'wp_oembed_register_route');
        remove_filter('embed_oembed_discover', '__return_true');
        add_filter('embed_oembed_discover', '__return_false');
        remove_filter('the_content', [$GLOBALS['wp_embed'], 'autoembed'], 8);
        remove_filter('widget_text_content', [$GLOBALS['wp_embed'], 'autoembed'], 8);
        add_filter('tiny_mce_plugins', static function (array $plugins): array {
            return array_values(array_diff($plugins, ['wpembed']));
        });
        add_filter('rewrite_rules_array', static function (array $rules): array {
            foreach ($rules as $rule => $rewrite) {
                if (str_contains($rewrite, 'embed=true')) {
                    unset($rules[$rule]);
                }
            }

            return $rules;
        });
    }

    if (gp_child_cleanup_is_enabled('remove_global_styles')) {
        remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
        remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
        remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
        remove_action('in_admin_header', 'wp_global_styles_render_svg_filters');
    }

    if (gp_child_cleanup_is_enabled('disable_xml_rpc')) {
        add_filter('xmlrpc_enabled', '__return_false');
    }

    if (gp_child_cleanup_is_enabled('hide_wp_version')) {
        remove_action('wp_head', 'wp_generator');
        add_filter('the_generator', '__return_empty_string');
    }

    if (gp_child_cleanup_is_enabled('remove_rsd_link')) {
        remove_action('wp_head', 'rsd_link');
    }

    if (gp_child_cleanup_is_enabled('remove_shortlink')) {
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('template_redirect', 'wp_shortlink_header', 11);
    }

    if (gp_child_cleanup_is_enabled('remove_rss_feed_links')) {
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
    }
});

add_filter('should_load_separate_core_block_assets', static function (bool $load): bool {
    if (gp_child_cleanup_is_enabled('load_separate_block_styles')) {
        return true;
    }

    return $load;
});

add_action('wp_enqueue_scripts', static function (): void {
    if (is_admin()) {
        return;
    }

    if (gp_child_cleanup_is_enabled('disable_dashicons') && ! is_user_logged_in()) {
        wp_deregister_style('dashicons');
    }
}, 100);

add_filter('wp_default_scripts', static function (WP_Scripts $scripts): void {
    if (is_admin() || ! gp_child_cleanup_is_enabled('remove_jquery_migrate')) {
        return;
    }

    if (! isset($scripts->registered['jquery']) || empty($scripts->registered['jquery']->deps)) {
        return;
    }

    $scripts->registered['jquery']->deps = array_values(
        array_diff($scripts->registered['jquery']->deps, ['jquery-migrate'])
    );
});

add_action('template_redirect', static function (): void {
    if (is_admin()) {
        return;
    }

    if (gp_child_cleanup_is_enabled('disable_rss_feeds') && is_feed()) {
        wp_die(
            esc_html__('RSS feeds are disabled on this site.', 'generatepress-child'),
            '',
            ['response' => 403]
        );
    }

    if (gp_child_cleanup_is_enabled('remove_generatepress_header')) {
        remove_action('generate_before_header', 'generate_top_bar', 5);
        remove_action('generate_header', 'generate_construct_header');
    }

    if (gp_child_cleanup_is_enabled('remove_generatepress_footer')) {
        remove_action('generate_footer', 'generate_construct_footer_widgets', 5);
        remove_action('generate_footer', 'generate_construct_footer');
        remove_action('generate_after_footer', 'generate_back_to_top');
    }
}, 1);

add_action('pre_ping', static function (&$links): void {
    if (! gp_child_cleanup_is_enabled('disable_self_pingbacks') || ! is_array($links)) {
        return;
    }

    $home = wp_parse_url(home_url(), PHP_URL_HOST);
    if (! is_string($home) || $home === '') {
        return;
    }

    foreach ($links as $key => $link) {
        $host = wp_parse_url($link, PHP_URL_HOST);
        if (is_string($host) && $host === $home) {
            unset($links[$key]);
        }
    }
});

add_filter('rest_authentication_errors', static function ($result) {
    if (! empty($result) || is_admin()) {
        return $result;
    }

    $mode = gp_child_cleanup_options()['disable_rest_api'] ?? '';
    if ($mode === '') {
        return $result;
    }

    $user = wp_get_current_user();
    $is_admin_user = $user instanceof WP_User && $user->exists() && user_can($user, 'manage_options');

    if ($mode === 'non_admins' && ! $is_admin_user) {
        return new WP_Error(
            'rest_disabled',
            __('The REST API is restricted on this site.', 'generatepress-child'),
            ['status' => rest_authorization_required_code()]
        );
    }

    if ($mode === 'logged_out' && ! is_user_logged_in()) {
        return new WP_Error(
            'rest_disabled',
            __('The REST API is restricted on this site.', 'generatepress-child'),
            ['status' => rest_authorization_required_code()]
        );
    }

    return $result;
});

add_action('after_setup_theme', static function (): void {
    if (! gp_child_cleanup_is_enabled('remove_rest_api_links')) {
        return;
    }

    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    remove_action('template_redirect', 'rest_output_link_header', 11);
    remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');
});
