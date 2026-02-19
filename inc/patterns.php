<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// what patterns are available
// js and css are handled separately enqueue_patterns.php

// Register Signifi pattern category
add_action( 'init', function () {
    register_block_pattern_category( 'signifi', [ 'label' => '⭐ Signifi ⭐' ] );
} );


//enqueue css for patterns frontend
require_once 'enqueue_patterns.php';


