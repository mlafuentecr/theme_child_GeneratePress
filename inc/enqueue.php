<?php
/**
 * Enqueue child theme assets.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function (): void {
    $css_file = GP_CHILD_DIR . '/assets/css/theme.css';
    $js_file  = GP_CHILD_DIR . '/assets/js/theme.js';

    wp_enqueue_style(
        'gp-child-theme',
        GP_CHILD_URI . '/assets/css/theme.css',
        ['generate-style'],
        file_exists($css_file) ? (string) filemtime($css_file) : GP_CHILD_VERSION
    );

    wp_enqueue_script(
        'gp-child-theme',
        GP_CHILD_URI . '/assets/js/theme.js',
        [],
        file_exists($js_file) ? (string) filemtime($js_file) : GP_CHILD_VERSION,
        true
    );
});
