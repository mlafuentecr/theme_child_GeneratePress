<?php
/**
 * Asset cache versioning helpers.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_filter('wp_get_attachment_url', function (string $url): string {
    if (get_option('gp_child_version_images', '0') !== '1') {
        return $url;
    }

    $v = intval(get_option('gp_child_css_version', 1));
    return add_query_arg('v', $v, $url);
});
