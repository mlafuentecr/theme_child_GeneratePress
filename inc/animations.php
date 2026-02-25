<?php
/**
 * Animation utilities — ease-out-expo scroll-triggered animations.
 *
 * Usage in HTML:
 *   <div data-animate="fade-up">...</div>
 *   <div data-animate="fade-left" data-animate-delay="200">...</div>
 *
 * Available values for data-animate:
 *   fade-up | fade-down | fade-left | fade-right | zoom-in | zoom-out | none
 *
 * data-animate-delay  — milliseconds before the animation triggers (optional)
 * data-animate-duration — override animation duration in ms (optional)
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function (): void {
    $css = GP_CHILD_DIR . '/assets/css/gp-animations.css';
    $js  = GP_CHILD_DIR . '/assets/js/gp-animations.js';

    wp_enqueue_style(
        'gp-animations',
        GP_CHILD_URI . '/assets/css/gp-animations.css',
        [],
        file_exists($css) ? (string) filemtime($css) : GP_CHILD_VERSION
    );

    wp_enqueue_script(
        'gp-animations',
        GP_CHILD_URI . '/assets/js/gp-animations.js',
        [],
        file_exists($js) ? (string) filemtime($js) : GP_CHILD_VERSION,
        true
    );
});
