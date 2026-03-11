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

    $vars_file       = GP_CHILD_DIR . '/assets/css/variables.css';
    $css_file        = GP_CHILD_DIR . '/assets/css/theme.css';
    $layout_file     = GP_CHILD_DIR . '/assets/css/layout.css';
    $nav_file        = GP_CHILD_DIR . '/assets/css/nav.css';
    $components_file = GP_CHILD_DIR . '/assets/css/components.css';
    $js_file         = GP_CHILD_DIR . '/assets/js/theme.js';

    // 0. Google Fonts — font families.
    wp_enqueue_style(
        'gp-child-fonts-manrope',
        'https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap',
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

    // 3. Layout helpers (spacing, grid, containers).
    wp_enqueue_style(
        'gp-child-layout',
        GP_CHILD_URI . '/assets/css/layout.css',
        ['gp-child-variables'],
        file_exists($layout_file) ? $version : GP_CHILD_VERSION
    );

    // 4. Navigation styles.
    wp_enqueue_style(
        'gp-child-nav',
        GP_CHILD_URI . '/assets/css/nav.css',
        ['gp-child-variables'],
        file_exists($nav_file) ? $version : GP_CHILD_VERSION
    );

    // 5. Component styles (buttons, cards, forms, etc.).
    wp_enqueue_style(
        'gp-child-components',
        GP_CHILD_URI . '/assets/css/components.css',
        ['gp-child-variables'],
        file_exists($components_file) ? $version : GP_CHILD_VERSION
    );

    // 6. Utility classes.
    $utilities_file = GP_CHILD_DIR . '/assets/css/utilities.css';
    wp_enqueue_style(
        'gp-child-utilities',
        GP_CHILD_URI . '/assets/css/utilities.css',
        ['gp-child-variables'],
        file_exists($utilities_file) ? $version : GP_CHILD_VERSION
    );

    // 7. Footer styles.
    $footer_file = GP_CHILD_DIR . '/assets/css/footer.css';
    wp_enqueue_style(
        'gp-child-footer',
        GP_CHILD_URI . '/assets/css/footer.css',
        ['gp-child-variables'],
        file_exists($footer_file) ? $version : GP_CHILD_VERSION
    );

    wp_enqueue_script(
        'gp-child-theme',
        GP_CHILD_URI . '/assets/js/theme.js',
        [],
        file_exists($js_file) ? $version : GP_CHILD_VERSION,
        true
    );
});

// Google Fonts preconnect hints (avoids CORB from loading the domain root as CSS).
add_filter('wp_resource_hints', function (array $urls, string $relation_type): array {
    if ($relation_type === 'preconnect') {
        $urls[] = 'https://fonts.googleapis.com';
        $urls[] = 'https://fonts.gstatic.com';
    }
    return $urls;
}, 10, 2);

// Load variables.css in the block editor so var(--*) tokens work in patterns.
add_action('after_setup_theme', function (): void {
    add_editor_style( 'assets/css/variables.css' );
});


//enqueue css for patterns frontend
require_once 'enqueue_patterns.php';
