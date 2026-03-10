<?php
/**
 * Parallax scroll effect.
 *
 * Usage in HTML:
 *   <div data-parallax data-parallax-speed="0.3">...</div>
 *
 * data-parallax-speed  — positive = moves up slower than scroll (default 0.3)
 *                        negative = moves in opposite direction
 * data-parallax-axis   — "y" (default) | "x"
 *
 * Wrap images that need a visible overflow-hidden parent:
 *   <div class="parallax-container">
 *       <img data-parallax data-parallax-speed="0.4" src="...">
 *   </div>
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function (): void {
    $css = GP_CHILD_DIR . '/assets/css/gp-parallax.css';
    $js  = GP_CHILD_DIR . '/assets/js/gp-parallax.js';

    wp_enqueue_style(
        'gp-parallax',
        GP_CHILD_URI . '/assets/css/gp-parallax.css',
        [],
        file_exists($css) ? (string) filemtime($css) : GP_CHILD_VERSION
    );

    wp_enqueue_script(
        'gp-parallax',
        GP_CHILD_URI . '/assets/js/gp-parallax.js',
        [],
        file_exists($js) ? (string) filemtime($js) : GP_CHILD_VERSION,
        true
    );
});
