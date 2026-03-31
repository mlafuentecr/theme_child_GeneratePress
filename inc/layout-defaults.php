<?php
/**
 * Global GeneratePress layout defaults controlled from theme settings.
 */

if (! defined('ABSPATH')) {
    exit;
}

function gp_child_layout_defaults_options(): array
{
    return (array) get_option('blueflamingo_plugin_options_settings', []);
}

function gp_child_layout_default_value(string $key): string
{
    $options = gp_child_layout_defaults_options();
    return isset($options[$key]) ? (string) $options[$key] : '';
}

function gp_child_layout_default_enabled(string $key): bool
{
    $options = gp_child_layout_defaults_options();
    return ! empty($options[$key]);
}

function gp_child_layout_default_applies_to_post(int $post_id): bool
{
    $post = get_post($post_id);
    return $post instanceof WP_Post
        && $post->post_type !== 'attachment'
        && is_post_type_viewable($post->post_type);
}

add_filter('default_post_metadata', function ($value, $object_id, $meta_key, $single, $meta_type) {
    if ($meta_type !== 'post' || ! $single || ! gp_child_layout_default_applies_to_post((int) $object_id)) {
        return $value;
    }

    $map = [
        '_generate-sidebar-layout-meta' => gp_child_layout_default_value('default_sidebar_layout'),
        '_generate-footer-widget-meta' => gp_child_layout_default_value('default_footer_widgets'),
        '_generate-full-width-content' => gp_child_layout_default_value('default_content_container'),
        '_generate-disable-headline' => gp_child_layout_default_enabled('default_disable_content_title') ? 'true' : '',
    ];

    if (! array_key_exists($meta_key, $map)) {
        return $value;
    }

    return $map[$meta_key];
}, 10, 5);

add_filter('generate_sidebar_layout', function (string $layout): string {
    if (! is_singular()) {
        return $layout;
    }

    $default = gp_child_layout_default_value('default_sidebar_layout');
    if ($default === '' || get_post_meta(get_the_ID(), '_generate-sidebar-layout-meta', true) !== '') {
        return $layout;
    }

    return $default;
});

add_filter('generate_footer_widgets', function ($widgets) {
    if (! is_singular()) {
        return $widgets;
    }

    $default = gp_child_layout_default_value('default_footer_widgets');
    if ($default === '' || get_post_meta(get_the_ID(), '_generate-footer-widget-meta', true) !== '') {
        return $widgets;
    }

    return $default;
});

add_filter('body_class', function (array $classes): array {
    if (! is_singular()) {
        return $classes;
    }

    if (get_post_meta(get_the_ID(), '_generate-full-width-content', true) !== '') {
        return $classes;
    }

    $default = gp_child_layout_default_value('default_content_container');
    if ($default === 'true' && ! in_array('full-width-content', $classes, true)) {
        $classes[] = 'full-width-content';
    }

    if ($default === 'contained' && ! in_array('contained-content', $classes, true)) {
        $classes[] = 'contained-content';
    }

    return $classes;
});

add_filter('generate_show_title', function (bool $show): bool {
    if (! is_singular()) {
        return $show;
    }

    if (get_post_meta(get_the_ID(), '_generate-disable-headline', true) !== '') {
        return $show;
    }

    if (gp_child_layout_default_enabled('default_disable_content_title')) {
        return false;
    }

    return $show;
}, 20);

add_action('save_post', function (int $post_id, WP_Post $post, bool $update): void {
    if (! gp_child_layout_default_applies_to_post($post_id) || wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    $defaults = [
        '_generate-sidebar-layout-meta' => gp_child_layout_default_value('default_sidebar_layout'),
        '_generate-footer-widget-meta' => gp_child_layout_default_value('default_footer_widgets'),
        '_generate-full-width-content' => gp_child_layout_default_value('default_content_container'),
        '_generate-disable-headline' => gp_child_layout_default_enabled('default_disable_content_title') ? 'true' : '',
    ];

    foreach ($defaults as $meta_key => $meta_value) {
        $current_value = get_post_meta($post_id, $meta_key, true);
        if ($current_value !== '' || $meta_value === '') {
            continue;
        }

        update_post_meta($post_id, $meta_key, $meta_value);
    }
}, 20, 3);

add_action('admin_head', function (): void {
    $screen = get_current_screen();
    if (! $screen || ! in_array($screen->base, ['post', 'post-new'], true)) {
        return;
    }

    if (! gp_child_layout_default_enabled('hide_generatepress_layout_box')) {
        return;
    }
    ?>
<style>
  #generate_layout_options_meta_box,
  .edit-post-meta-boxes-area #generate_layout_options_meta_box {
    display: none !important;
  }
</style>
<?php
});
