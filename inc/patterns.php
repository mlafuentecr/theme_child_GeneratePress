<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// what patterns are available
// js and css are handled separately in enqueue-patterns.php (loaded via functions.php)

// Register brand pattern category (slug + label driven by GP_CHILD_BRAND_SLUG / GP_CHILD_BRAND)
add_action( 'init', function () {
    register_block_pattern_category(
        GP_CHILD_BRAND_SLUG,
        [ 'label' => '⭐ ' . GP_CHILD_BRAND . ' ⭐' ]
    );
} );

// Popup is now a custom Gutenberg block — see inc/popup-block.php
