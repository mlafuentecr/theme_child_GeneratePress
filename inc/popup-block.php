<?php
/**
 * GP Popup — Custom Gutenberg Block registration.
 *
 * Registers the block category for the block inserter and enqueues
 * the editor-side JS. Front-end CSS/JS are handled by inc/modal.php.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Block category (block inserter) ──────────────────────────────────────────
// Separate from the pattern category registered in inc/patterns.php.

add_filter( 'block_categories_all', function ( array $categories ): array {
    $slugs = array_column( $categories, 'slug' );
    if ( in_array( GP_CHILD_BRAND_SLUG, $slugs, true ) ) {
        return $categories;
    }
    array_unshift( $categories, [
        'slug'  => GP_CHILD_BRAND_SLUG,
        'title' => '⭐ ' . GP_CHILD_BRAND . ' ⭐',
        'icon'  => null,
    ] );
    return $categories;
} );

// ── Editor JS ────────────────────────────────────────────────────────────────

add_action( 'enqueue_block_editor_assets', function (): void {
    $js = GP_CHILD_DIR . '/assets/js/popup-block-editor.js';

    wp_enqueue_script(
        'gp-popup-block-editor',
        GP_CHILD_URI . '/assets/js/popup-block-editor.js',
        [ 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ],
        file_exists( $js ) ? (string) filemtime( $js ) : GP_CHILD_VERSION,
        true
    );

    // Pass brand data so JS block registration uses the correct category slug
    wp_localize_script( 'gp-popup-block-editor', 'gpPopupBlockData', [
        'brandSlug' => GP_CHILD_BRAND_SLUG,
        'brandName' => GP_CHILD_BRAND,
    ] );
} );
