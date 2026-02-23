<?php
/**
 * Theme filters.
 *
 * Presentation-layer filters: body classes, title, image sizes, etc.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ============================================================
 * Body classes
 * ============================================================ */
add_filter( 'body_class', static function ( array $classes ): array {
    if ( is_front_page() ) {
        $classes[] = 'page-template-home-page';
    }

    return $classes;
} );
