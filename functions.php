<?php
/**
 * Bootstrap file for the Cypress Lakes child theme.
 *
 * Sets global references, detects the active environment and loads the
 * modular PHP files stored inside the /inc directory.
 */

/* -------------------------------------------------------------------------
 * 1. Global references
 * ------------------------------------------------------------------------- */
$GLOBALS['THEME_MLM_PATH'] = get_template_directory_uri();
$GLOBALS['THEME_MLM_VER']  = '1.1.1';
$GLOBALS['THEME_MLM_ENV']  = 'dist';

// Child theme constants.
if (!defined('ML_CHILD_DIR')) {
    define('ML_CHILD_DIR', get_stylesheet_directory());
}

if (!defined('ML_CHILD_URI')) {
    define('ML_CHILD_URI', get_stylesheet_directory_uri());
}

if (!defined('ML_CHILD_VER')) {
    $child_theme = wp_get_theme(get_stylesheet());
    define('ML_CHILD_VER', $child_theme->get('Version'));
}

/* -------------------------------------------------------------------------
 * 2. Environment detection (local / staging / production)
 * ------------------------------------------------------------------------- */
$http_host  = $_SERVER['HTTP_HOST'] ?? '';
$local      = 'signifi2026.local';
$staging    = 'stg-signifi2026-staging.kinsta.cloud';
$production = 'signifi2026.kinsta.cloud';

$environments = array(
    'local'      => $local,
    'staging'    => $staging,
    'production' => $production,
);

foreach ($environments as $environment => $hostname) {
    if ($hostname !== '' && stripos($http_host, $hostname) !== false) {
        $GLOBALS['THEME_MLM_ENV'] = ($environment === 'local') ? 'src' : 'dist';
        break;
    }
}

/* -------------------------------------------------------------------------
 * 3. List of files to include.
 * ------------------------------------------------------------------------- */
$theme_directory = get_stylesheet_directory();

$theme_includes = array(

    // Assets
    '/inc/enqueue.php',
    '/inc/enqueue_blocks.php',
    
   // Core
    '/inc/cleanup.php',
    '/inc/helpers.php',

    // Theme structure
    '/inc/menus.php',
    '/inc/patterns.php',
    '/inc/top-bar.php',

    // CPTs & taxonomies
    '/inc/custom-post-types.php',
    '/inc/product-meta-boxes.php',

    // Shortcodes (use case is in here)
    '/inc/shortcode.php',
    '/inc/search_result/index.php',

    // AJAX
    '/inc/ajax/use-cases.php',

    //WEbp Converter
    '/inc/webp-converter.php',



);


foreach ($theme_includes as $file) {
    $path = $theme_directory . $file;

    if (file_exists($path)) {
        require_once $path;
    }
}

