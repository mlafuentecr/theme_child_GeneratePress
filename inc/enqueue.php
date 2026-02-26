<?php
/**
 * Enqueue child theme assets.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function (): void {
    // Cache-buster counter set via Appearance > Child Theme Settings.
    $bust    = intval(get_option('gp_child_css_version', 1));
    $version = GP_CHILD_VERSION . '.' . $bust;

    $vars_file = GP_CHILD_DIR . '/assets/css/variables.css';
    $css_file  = GP_CHILD_DIR . '/assets/css/theme.css';
    $js_file   = GP_CHILD_DIR . '/assets/js/theme.js';

    // 0. Google Fonts — preconnect + font families.
    wp_enqueue_style(
        'gp-child-fonts-preconnect',
        'https://fonts.googleapis.com',
        [],
        null
    );
    wp_enqueue_style(
        'gp-child-font-raleway',
        'https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap',
        [],
        null
    );
    wp_enqueue_style(
        'gp-child-font-barlow-condensed',
        'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&display=swap',
        [],
        null
    );
    wp_enqueue_style(
        'gp-child-font-barlow',
        'https://fonts.googleapis.com/css2?family=Barlow:wght@300;400;500;600;700&display=swap',
        [],
        null
    );

    // 1. Design tokens — must load first so all var(--*) work everywhere.
    wp_enqueue_style(
        'gp-child-variables',
        GP_CHILD_URI . '/assets/css/variables.css',
        ['generate-style'],
        file_exists($vars_file) ? $version : GP_CHILD_VERSION
    );

    // 2. Main theme stylesheet — depends on variables.
    wp_enqueue_style(
        'gp-child-theme',
        GP_CHILD_URI . '/assets/css/theme.css',
        ['gp-child-variables'],
        file_exists($css_file) ? $version : GP_CHILD_VERSION
    );

    wp_enqueue_script(
        'gp-child-theme',
        GP_CHILD_URI . '/assets/js/theme.js',
        [],
        file_exists($js_file) ? $version : GP_CHILD_VERSION,
        true
    );
});

// Load variables.css in the block editor so var(--*) tokens work in patterns.
add_action('after_setup_theme', function (): void {
    add_editor_style( 'assets/css/variables.css' );
});


//enqueue css for patterns frontend
require_once 'enqueue_patterns.php';
