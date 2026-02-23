<?php
/**
 * Core helper functions for the BlueFlamingo child theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ============================================================
 * Body classes
 * ============================================================ */

// Add custom body class for the front page
add_filter( 'body_class', function ( $classes ) {
    if ( is_front_page() ) {
        $classes[] = 'is-home';
    }
    return $classes;
} );


// "My patterns" label is hardcoded in block-editor.js via wp.i18n.__().
// PHP gettext cannot intercept pre-compiled JS translations, so we use
// wp.i18n.setLocaleData() to override the string at runtime in the editor.
add_action( 'enqueue_block_editor_assets', function () {
    $label = '⭐ ' . GP_CHILD_BRAND . ' ⭐';
    wp_add_inline_script(
        'wp-i18n',
        'wp.i18n.setLocaleData( { "My patterns": [' . wp_json_encode( $label ) . '] }, "default" );',
        'after'
    );
} );

// Filter blockPatterns to keep only patterns in the brand category.
add_filter( 'block_editor_settings_all', function ( $settings ) {
    if ( isset( $settings['blockPatterns'] ) && is_array( $settings['blockPatterns'] ) ) {
        $settings['blockPatterns'] = array_values( array_filter(
            $settings['blockPatterns'],
            function ( $pattern ) {
                return isset( $pattern['categories'] )
                    && in_array( GP_CHILD_BRAND_SLUG, $pattern['categories'], true );
            }
        ) );
    }
    return $settings;
} );



/* ============================================================
 * Gutenberg block styles
 * ============================================================ */

// Register custom block style category
add_action( 'init', function () {
    if ( function_exists( 'register_block_style_category' ) ) {
        register_block_style_category(
            GP_CHILD_BRAND_SLUG,
            [
                'label' => __( GP_CHILD_BRAND, 'generatepress-child' ),
            ]
        );
    }
} );

/* ============================================================
 * Footer
 * ============================================================ */

// Remove GeneratePress footer output
add_action( 'after_setup_theme', function () {
    remove_action( 'generate_footer', 'generate_construct_footer' );
} );

/* ============================================================
 * Content defaults
 * ============================================================ */

// Set global excerpt length
add_filter( 'excerpt_length', function () {
    return 15;
}, 999 );

// Disable comments support for posts and pages
add_action( 'init', function () {
    remove_post_type_support( 'post', 'comments' );
    remove_post_type_support( 'page', 'comments' );
} );

// Disable comments output completely
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_filter( 'comments_array', '__return_empty_array', 10 );

/* ============================================================
 * GeneratePress – Frontend layout (GLOBAL)
 * ============================================================ */

// Force full-width content container
add_filter( 'generate_page_container', fn() => 'full' );
add_filter( 'generate_page_container', fn() => 'full' );


// Disable sidebar everywhere
add_filter( 'generate_sidebar_layout', fn() => 'no-sidebar' );

// Hide post/page titles globally
add_filter( 'generate_show_title', '__return_false' );

// Disable featured images globally
add_filter( 'generate_show_featured_image', '__return_false' );

// Remove post meta (date, author, categories)
add_action( 'init', function () {
    remove_action( 'generate_after_entry_title', 'generate_post_meta' );
    remove_action( 'generate_after_entry_content', 'generate_footer_meta' );
} );


/* ============================================================
 * Gutenberg – Clean Patterns UI (BlueFlamingo only)
 * ============================================================ */

// Remove core pattern categories
add_action( 'init', function () {
    if ( ! function_exists( 'unregister_block_pattern_category' ) ) return;

    foreach ( [
        'about',
        'banners',
        'call-to-action',
        'footer',
        'gallery',
        'header',
        'posts',
        'text',
        'banner',
    ] as $category ) {
        unregister_block_pattern_category( $category );
    }
}, 20 );

/* ============================================================
   Add Content to post empty
============================================================ */

add_filter( 'default_content', function( $content, $post ) {

    if ( $post->post_type !== 'post' ) {
        return $content;
    }

    if ( ! empty( $content ) ) {
        return $content;
    }

    return '<!-- wp:pattern {"slug":"generatepress-child/ui-post-resources"} /-->';

}, 10, 2 );

/* ============================================================
   allow_svg_upload
============================================================ */
function allow_svg_upload($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'allow_svg_upload');
