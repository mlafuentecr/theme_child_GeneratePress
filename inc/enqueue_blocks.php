<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Enqueue patterns PHP
require_once 'blocks/blocks-btns.php';

// =====================================
// Blocks config
// =====================================
$blocks = [
    'buttons' => [
        'css' => true,
        'js'  => false,
    ],
    'react-buttons' => [
        'css' => true,
        'js'  => true,
    ]
];

// =====================================
// Editor assets (Gutenberg)
// =====================================
add_action( 'enqueue_block_editor_assets', function () use ( $blocks ) {

    foreach ( $blocks as $name => $assets ) {

        // Editor CSS
        if ( ! empty( $assets['css'] ) ) {
            wp_enqueue_style(
                "signifi-{$name}-editor",
                ML_CHILD_URI . "/assets/css/blocks/{$name}.css",
                [],
                ML_CHILD_VER
            );
        }

        // Editor JS (SOLO si existe)
        if ( ! empty( $assets['js'] ) ) {
            wp_enqueue_script(
                "signifi-{$name}-editor",
                ML_CHILD_URI . "/assets/js/blocks/{$name}.js",
                [
                    'wp-blocks',
                    'wp-element',
                    'wp-editor',
                    'wp-block-editor'
                ],
                ML_CHILD_VER,
                true
            );
        }
    }

} );

// =====================================
// Frontend CSS (opcional)
// =====================================
add_action( 'wp_enqueue_scripts', function () use ( $blocks ) {

    foreach ( $blocks as $name => $assets ) {

        if ( ! empty( $assets['css'] ) ) {
            wp_enqueue_style(
                "signifi-{$name}",
                ML_CHILD_URI . "/assets/css/blocks/{$name}.css",
                [],
                ML_CHILD_VER
            );
        }
    }

} );

// =====================================
// Enable editor styles
// =====================================
add_action( 'after_setup_theme', function () {
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/css/blocks/buttons.css' );
});
