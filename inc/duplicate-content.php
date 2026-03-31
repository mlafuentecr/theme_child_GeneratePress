<?php
/**
 * Duplicate posts/pages from the admin when enabled in theme settings.
 */

if (! defined('ABSPATH')) {
    exit;
}

function gp_child_duplicate_content_enabled(): bool
{
    $opts = (array) get_option('blueflamingo_plugin_options_settings', []);
    return ! empty($opts['enable_duplicate_content']);
}

function gp_child_get_duplicate_link(int $post_id): string
{
    return wp_nonce_url(
        admin_url('admin.php?action=gp_child_duplicate_post&post=' . $post_id),
        'gp_child_duplicate_post_' . $post_id
    );
}

add_filter('post_row_actions', function (array $actions, WP_Post $post): array {
    if (! gp_child_duplicate_content_enabled()) {
        return $actions;
    }

    if (! current_user_can('edit_posts') || ! current_user_can('edit_post', $post->ID)) {
        return $actions;
    }

    $actions['gp_child_duplicate'] = sprintf(
        '<a href="%1$s">%2$s</a>',
        esc_url(gp_child_get_duplicate_link($post->ID)),
        esc_html__('Duplicate', 'generatepress-child')
    );

    return $actions;
}, 10, 2);

add_filter('page_row_actions', function (array $actions, WP_Post $post): array {
    if (! gp_child_duplicate_content_enabled()) {
        return $actions;
    }

    if (! current_user_can('edit_pages') || ! current_user_can('edit_post', $post->ID)) {
        return $actions;
    }

    $actions['gp_child_duplicate'] = sprintf(
        '<a href="%1$s">%2$s</a>',
        esc_url(gp_child_get_duplicate_link($post->ID)),
        esc_html__('Duplicate', 'generatepress-child')
    );

    return $actions;
}, 10, 2);

add_action('admin_action_gp_child_duplicate_post', function (): void {
    if (! gp_child_duplicate_content_enabled()) {
        wp_die(esc_html__('Duplicate content is disabled.', 'generatepress-child'));
    }

    $post_id = absint($_GET['post'] ?? 0);
    if (! $post_id) {
        wp_die(esc_html__('Invalid post ID.', 'generatepress-child'));
    }

    check_admin_referer('gp_child_duplicate_post_' . $post_id);

    $post = get_post($post_id);
    if (! ($post instanceof WP_Post)) {
        wp_die(esc_html__('The requested content could not be found.', 'generatepress-child'));
    }

    if (! current_user_can('edit_post', $post_id)) {
        wp_die(esc_html__('You are not allowed to duplicate this content.', 'generatepress-child'));
    }

    $new_post_id = wp_insert_post([
        'post_type' => $post->post_type,
        'post_status' => 'draft',
        'post_title' => sprintf(__('%s (Copy)', 'generatepress-child'), $post->post_title),
        'post_content' => $post->post_content,
        'post_excerpt' => $post->post_excerpt,
        'post_parent' => $post->post_parent,
        'menu_order' => $post->menu_order,
        'comment_status' => $post->comment_status,
        'ping_status' => $post->ping_status,
        'post_password' => $post->post_password,
        'post_author' => get_current_user_id(),
    ], true);

    if (is_wp_error($new_post_id)) {
        wp_die(esc_html($new_post_id->get_error_message()));
    }

    $taxonomies = get_object_taxonomies($post->post_type);
    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
        if (! is_wp_error($terms) && ! empty($terms)) {
            wp_set_object_terms($new_post_id, $terms, $taxonomy);
        }
    }

    $meta = get_post_meta($post_id);
    foreach ($meta as $meta_key => $values) {
        if ($meta_key === '_edit_lock' || $meta_key === '_edit_last') {
            continue;
        }

        foreach ($values as $value) {
            add_post_meta($new_post_id, $meta_key, maybe_unserialize($value));
        }
    }

    wp_safe_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
    exit;
});
