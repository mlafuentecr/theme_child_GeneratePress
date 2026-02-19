<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
 * Register Use Cases filter query vars so WP doesn't strip them
 * ============================================================ */
add_filter( 'query_vars', function ( $vars ) {
    $vars[] = 'uc_industry';
    $vars[] = 'uc_solution';
    $vars[] = 'uc_product';
    $vars[] = 'uc_search';
    return $vars;
} );

/* ============================================================
 * CPT – Industry
 * ============================================================ */
add_action( 'init', function () {

    register_post_type( 'industry', [
        'labels' => [
            'name'          => 'Industries',
            'singular_name' => 'Industry',
        ],
        'public'        => true,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'menu_icon'     => 'dashicons-building',
        'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
        'has_archive'   => true,
        'rewrite'       => [ 'slug' => 'industry', 'with_front' => false ],
        'show_in_rest'  => true,
    ] );

} );


/* ============================================================
 * CPT – Solution
 * ============================================================ */
add_action( 'init', function () {

    register_post_type( 'solution', [
        'labels' => [
            'name'          => 'Solutions',
            'singular_name' => 'Solution',
        ],
        'public'        => true,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'menu_icon'     => 'dashicons-lightbulb',
        'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
        'has_archive'   => true,
        'rewrite'       => [ 'slug' => 'solution', 'with_front' => false ],
        'show_in_rest'  => true,
    ] );

} );


/* ============================================================
 * CPT – Product
 * ============================================================ */
add_action( 'init', function () {

    register_post_type( 'product', [
        'labels' => [
            'name'          => 'Products',
            'singular_name' => 'Product',
        ],
        'public'        => true,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'menu_icon'     => 'dashicons-products',
        'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
        'has_archive'   => true,
        'rewrite'       => [ 'slug' => 'product', 'with_front' => false ],
        'show_in_rest'  => true,
    ] );

} );


/* ============================================================
 * CPT – Use Case
 * ============================================================ */
add_action( 'init', function () {

    register_post_type( 'use_case', [
        'labels' => [
            'name'          => 'Use Cases',
            'singular_name' => 'Use Case',
        ],
        'public'        => true,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'menu_icon'     => 'dashicons-portfolio',
        'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
        'has_archive'   => true,
        'rewrite'       => [ 'slug' => 'use-cases', 'with_front' => false ],
        'show_in_rest'  => true,

        /* ======================================
           DEFAULT BLOCK TEMPLATE
        ====================================== */
        'template' => [
            [
                'core/gallery',
                [
                    'columns'   => 3,
                    'imageCrop' => true,
                    'linkTo'    => 'none'
                ]
            ],
            [
                'core/paragraph',
                [
                    'placeholder' => 'Write the use case description here...'
                ]
            ],
        ],

        'template_lock' => false,
    ] );

});


/* ============================================================
 * TAXONOMY – Industry (for Use Cases only)
 * ============================================================ */
add_action( 'init', function () {

    register_taxonomy( 'use_case_industry', [ 'use_case' ], [
        'labels' => [
            'name'          => 'Industries ',
            'singular_name' => 'Industry',
        ],
        'public'              => true,
        'hierarchical'        => true,
        'show_ui'             => true,
        'show_admin_column'   => true,
        'show_in_rest'        => true,
        'rewrite'             => [ 'slug' => 'use-case-industry' ],
    ] );

} );


/* ============================================================
 * TAXONOMY – Solution (for Use Cases only)
 * ============================================================ */
add_action( 'init', function () {

    register_taxonomy( 'use_case_solution', [ 'use_case' ], [
        'labels' => [
            'name'          => 'Solutions ',
            'singular_name' => 'Solution',
        ],
        'public'              => true,
        'hierarchical'        => true,
        'show_ui'             => true,
        'show_admin_column'   => true,
        'show_in_rest'        => true,
        'rewrite'             => [ 'slug' => 'use-case-solution' ],
    ] );

} );


/* ============================================================
 * TAXONOMY – Product (for Use Cases only)
 * ============================================================ */
add_action( 'init', function () {

    register_taxonomy( 'use_case_product', [ 'use_case' ], [
        'labels' => [
            'name'          => 'Products',
            'singular_name' => 'Product',
        ],
        'public'              => true,
        'hierarchical'        => true,
        'show_ui'             => true,
        'show_admin_column'   => true,
        'show_in_rest'        => true,
        'rewrite'             => [ 'slug' => 'use-case-product' ],
    ] );

} );
