<?php
/**
 * GeneratePress Child Theme bootstrap.
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('GP_CHILD_VERSION')) {
    $theme = wp_get_theme(get_stylesheet());
    define('GP_CHILD_VERSION', $theme->get('Version') ?: '1.0.0');
}

if (! defined('GP_CHILD_DIR')) {
    define('GP_CHILD_DIR', get_stylesheet_directory());
}

if (! defined('GP_CHILD_URI')) {
    define('GP_CHILD_URI', get_stylesheet_directory_uri());
}

// ── Brand identity (slug used for block/pattern categories) ──────────────────
if (! defined('GP_CHILD_BRAND_SLUG')) {
    define('GP_CHILD_BRAND_SLUG', 'blueflamingo');
}
if (! defined('GP_CHILD_BRAND')) {
    define('GP_CHILD_BRAND', 'Blue Flamingo');
}

$includes = [
    '/inc/setup.php',
    '/inc/enqueue.php',
    '/inc/admin-settings.php',
    '/inc/animations.php',
    '/inc/parallax.php',
    '/inc/search_result/index.php',
    '/inc/modal.php',
    '/inc/helpers.php',
    '/inc/patterns.php',
    '/inc/popup-block.php',
];

foreach ($includes as $file) {
    $path = GP_CHILD_DIR . $file;

    if (file_exists($path)) {
        require_once $path;
    }
}
