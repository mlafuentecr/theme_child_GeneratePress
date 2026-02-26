<?php
/**
 * Default Featured Image (DFI) — fallback when bf-fireball plugin is not active.
 *
 * When the plugin IS active its class (Blue_Flamingo_Default_Featured_Image) exists
 * and handles everything — all hooks below bail out immediately via the guard.
 *
 * Option key : blueflamingo_plugin_options_settings[default_featured_image]
 * Post types : blueflamingo_plugin_options_settings[default_featured_image_post_types]
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Inject DFI into the post-meta cache when a post has no thumbnail.
 * Equivalent to Blue_Flamingo_Default_Featured_Image::set_dfi_meta_key().
 *
 * @param  mixed  $null
 * @param  int    $object_id
 * @param  string $meta_key
 * @param  bool   $single
 * @return mixed
 */
add_filter('get_post_metadata', function ($null, int $object_id, string $meta_key, bool $single) {

    // Bail if plugin is active — it handles DFI.
    if (class_exists('Blue_Flamingo_Default_Featured_Image')) {
        return $null;
    }

    // Only on frontend (AJAX is allowed).
    if (is_admin() && ! (defined('DOING_AJAX') && DOING_AJAX)) {
        return $null;
    }

    // Only intercept _thumbnail_id requests.
    if (! empty($meta_key) && '_thumbnail_id' !== $meta_key) {
        return $null;
    }

    // Post type must support thumbnails.
    if (! post_type_supports((string) get_post_type($object_id), 'thumbnail')) {
        return $null;
    }

    $options      = (array) get_option('blueflamingo_plugin_options_settings', []);
    $dfi_id       = ! empty($options['default_featured_image']) ? absint($options['default_featured_image']) : 0;

    if (! $dfi_id) {
        return $null;
    }

    // Respect per-post-type restrictions.
    $allowed_types = $options['default_featured_image_post_types'] ?? [];
    if (! empty($allowed_types) && is_array($allowed_types)) {
        $allowed_slugs = array_keys(array_filter($allowed_types));
        if (! in_array((string) get_post_type($object_id), $allowed_slugs, true)) {
            return $null;
        }
    }

    // Read (or warm) the meta cache for this post.
    $meta_cache = wp_cache_get($object_id, 'post_meta');
    if (! $meta_cache) {
        $meta_cache = update_meta_cache('post', [$object_id]);
        $meta_cache = $meta_cache[$object_id] ?? [];
    }

    // Post already has its own thumbnail → leave it alone.
    if (! empty($meta_cache['_thumbnail_id'][0])) {
        return $null;
    }

    // Inject DFI into cache so subsequent calls don't re-run this filter.
    $meta_cache['_thumbnail_id'][0] = apply_filters('dfi_thumbnail_id', $dfi_id, $object_id);
    wp_cache_set($object_id, $meta_cache, 'post_meta');

    return $null;

}, 10, 4);

/**
 * Add CSS class 'default-featured-img' when the DFI is being displayed.
 * Equivalent to Blue_Flamingo_Default_Featured_Image::show_dfi().
 *
 * @param  string $html
 * @param  int    $post_id
 * @param  int    $post_thumbnail_id
 * @param  mixed  $size
 * @param  array  $attr
 * @return string
 */
add_filter('post_thumbnail_html', function (string $html, int $post_id, $post_thumbnail_id, $size, array $attr): string {

    // Bail if plugin is active.
    if (class_exists('Blue_Flamingo_Default_Featured_Image')) {
        return $html;
    }

    $options = (array) get_option('blueflamingo_plugin_options_settings', []);
    $dfi_id  = ! empty($options['default_featured_image']) ? absint($options['default_featured_image']) : 0;

    if (! $dfi_id || (int) $dfi_id !== (int) $post_thumbnail_id) {
        return $html;
    }

    // Add the identifying class.
    if (isset($attr['class'])) {
        $attr['class'] .= ' default-featured-img';
    } else {
        $size_class = is_array($size) ? 'size-' . implode('x', $size) : $size;
        $attr       = ['class' => "attachment-{$size_class} default-featured-img"];
    }

    $html = wp_get_attachment_image($dfi_id, $size, false, $attr);
    $html = apply_filters('dfi_thumbnail_html', $html, $post_id, $dfi_id, $size, $attr);

    return $html;

}, 10, 5);
