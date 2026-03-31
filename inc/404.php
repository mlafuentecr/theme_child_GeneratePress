<?php
/**
 * Custom 404 page runtime.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', function (): void {
    $ep = (array) get_option('blueflamingo_plugin_error_page_settings', []);

    if (! empty($ep['activate_404'])) {
        add_filter('404_template', 'gp_child_custom_404_template');
    }
});

function gp_child_custom_404_template(string $template): string
{
    global $wp_query, $post;

    $ep          = (array) get_option('blueflamingo_plugin_error_page_settings', []);
    $custom_page = get_post(intval($ep['custom_404_page'] ?? 0));

    if (! ($custom_page instanceof WP_Post)) {
        return $template;
    }

    $post = $custom_page; // phpcs:ignore WordPress.WP.GlobalVariablesOverride

    $wp_query->posts             = [$post];
    $wp_query->queried_object_id = $post->ID;
    $wp_query->queried_object    = $post;
    $wp_query->post_count        = 1;
    $wp_query->found_posts       = 1;
    $wp_query->is_404            = false;
    $wp_query->is_page           = true;
    $wp_query->is_singular       = true;

    return get_page_template();
}
