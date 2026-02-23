<?php
/**
 * Theme shortcodes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ============================================================
 * Current year
 * Usage: [current_year]
 * ============================================================ */
add_shortcode( 'current_year', static function (): string {
    return date( 'Y' );
} );
