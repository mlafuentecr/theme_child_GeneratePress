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

    //Core Theme Setup
    '/inc/supported.php',
    '/inc/enqueue.php',
    '/inc/helpers.php',


    //Admin / Back Office
    '/inc/admin-settings.php',


    //Frontend Features
    '/inc/404.php',
    '/inc/animations.php',
    '/inc/parallax.php',
    '/inc/webp-converter.php',
    '/inc/search_result/index.php',


    //Gutenberg / Patterns / blocks
    '/inc/patterns.php',
    //(Modal & Popup) work Together
    '/inc/modal.php',
    '/inc/popup-block.php',
];

foreach ($includes as $file) {
    $path = GP_CHILD_DIR . $file;

    if (file_exists($path)) {
        require_once $path;
    }
}
