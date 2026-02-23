<?php
/**
 * WordPress head cleanup.
 *
 * Removes meta tags and links that are unnecessary on most
 * production sites and can expose information about the stack.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', static function (): void {
    remove_action( 'wp_head', 'wp_generator' );          // WordPress version number.
    remove_action( 'wp_head', 'rsd_link' );              // Really Simple Discovery link.
    remove_action( 'wp_head', 'feed_links', 2 );         // Post + comment feed links.
    remove_action( 'wp_head', 'feed_links_extra', 3 );   // Category / tag / author feed links.
    remove_action( 'wp_head', 'wlwmanifest_link' );      // Windows Live Writer manifest.
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );  // Shortlink tag.
} );
