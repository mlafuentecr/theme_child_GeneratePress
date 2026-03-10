<?php
/**
 * Animation Block Extension — enqueues the editor-side JS that adds
 * an "Animation" panel to every block's sidebar in the Gutenberg editor.
 *
 * The extension uses four WordPress filters:
 *   blocks.registerBlockType      — adds gpAnimation + gpAnimationDelay attributes
 *   editor.BlockEdit               — injects the Animation panel into the sidebar
 *   blocks.getSaveContent.extraProps — writes the class + data-animate-delay to saved HTML
 *   editor.BlockListBlock          — mirrors the class in the editor canvas
 *
 * The chosen animation class (fade-up, fade-down, etc.) and data-animate-delay
 * are consumed by the existing gp-animations.js (IntersectionObserver).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'enqueue_block_editor_assets', function (): void {

    $js = GP_CHILD_DIR . '/assets/js/animation-block-extension.js';

    wp_enqueue_script(
        'gp-animation-block-extension',
        GP_CHILD_URI . '/assets/js/animation-block-extension.js',
        [
            'wp-hooks',        // addFilter
            'wp-compose',      // createHigherOrderComponent
            'wp-blocks',       // registerBlockType filter
            'wp-block-editor', // InspectorControls, BlockListBlock
            'wp-components',   // PanelBody, SelectControl, RangeControl
            'wp-element',      // createElement, Fragment
            'wp-i18n',         // __()
        ],
        gp_child_asset_version( $js ),
        true
    );

    // Pass the animation list from PHP so labels can be translated here if needed.
    wp_localize_script( 'gp-animation-block-extension', 'gpAnimations', [
        'animations' => [
            [ 'value' => '',           'label' => __( '— None —',   'generatepress-child' ) ],
            [ 'value' => 'fade-up',    'label' => __( 'Fade Up',    'generatepress-child' ) ],
            [ 'value' => 'fade-down',  'label' => __( 'Fade Down',  'generatepress-child' ) ],
            [ 'value' => 'fade-left',  'label' => __( 'Fade Left',  'generatepress-child' ) ],
            [ 'value' => 'fade-right', 'label' => __( 'Fade Right', 'generatepress-child' ) ],
            [ 'value' => 'zoom-in',    'label' => __( 'Zoom In',    'generatepress-child' ) ],
            [ 'value' => 'zoom-out',   'label' => __( 'Zoom Out',   'generatepress-child' ) ],
        ],
    ] );
} );
