<?php
// =====================================
// Enqueue patterns
// =====================================
$patterns = [

    'headline' => [
        'css' => true,
    ],
    'industries' => [
        'css' => true,
    ],
    'hero-two-section' => [
        'css' => true,
        'js'  => true,
    ],
    'ui-post' => [
        'css' => true,
    ],
    'ui-highlights' => [
        'css' => true,
    ],
    'icon-grid' => [
        'css' => true,
    ],
    'ui-arrow' => [
        'css' => true,
    ],
    'logo-strip' => [
        'css' => true,
        'js'  => true,
    ],
    'seamless-integration' => [
        'css' => true,
        'js'  => true,
    ],
    'comparison' => [
        'css' => true,
    ],
     'image-grid' => [
        'css' => true,
    ],
    'use-cases' => [
        'css' => true,
    ],
    'benefit-stack' => [
        'css' => true,
    ],
    'product-image' => [
        'css' => true,
        'js'  => true,
    ],
    'video-and-text' => [
        'css' => true,
        'js'  => true,
    ],
    'title-and-text' => [
        'css' => true,
    ],
    'stats' => [
        'css' => true,
    ],
    'home-headline' => [
        'css' => true,
    ],
    'product-hero' => [
        'css' => true,
    ]

];

// =====================================
//
// =====================================
add_action( 'wp_enqueue_scripts', function () use ( $patterns ) {

    foreach ( $patterns as $name => $assets ) {

      //CSS
        if ( ! empty( $assets['css'] ) ) {
            wp_enqueue_style(
                "signifi-$name",
                ML_CHILD_URI . "/assets/css/patterns/$name.css", [], ML_CHILD_VER
            );
        }
      //JS
        if ( ! empty( $assets['js'] ) ) {
            wp_enqueue_script(
                "signifi-$name", ML_CHILD_URI . "/assets/js/patterns/$name.js", [], ML_CHILD_VER, true
            );
        }
    }

} );

// =====================================
// Editor styles
// =====================================
add_action( 'after_setup_theme', function () use ( $patterns ) {
    add_theme_support( 'editor-styles' );
    foreach ( $patterns as $name => $assets ) {
            add_editor_style( [  "/assets/css/patterns/$name.css"  ] );
      }
} );
