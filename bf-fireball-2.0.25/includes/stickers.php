<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'Blue_Flamingo_Stickies' ) ) :

class Blue_Flamingo_Stickies{
    private $cpt;
    private $ids;

    public function init( $cpt = 'post' , $ids = array() ) {
        $this->cpt = $cpt;
        $this->ids = $ids;
        add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
    }

    public function pre_get_posts( $q ) {
        if( is_admin() && 'edit.php' === $GLOBALS['pagenow']&& $q->is_main_query() && $this->cpt === $q->get( 'post_type' ) ) {
            add_filter( 'post_class', array( $this, 'post_class' ), 10, 3 );
            add_filter( 'option_sticky_posts', array( $this, 'custom_stickies' ) );
            $q->is_home = 1; # <-- We must use this "hack" to support sticky posts
            $q->set( 'ignore_sticky_posts', 0 );
        }
    }

    public function custom_stickies( $data ){
        // remove_filter( current_filter(), array( $this, __FUNCTION__ ) );
        if( isset( $this->ids ) ){
			if( count( $this->ids ) > 0 ){
				$data = $this->ids;
			}
		}
        return $data;
    }

    public function post_class( $classes, $class, $post_ID ) {
        // Append the sticky CSS class to the corresponding row:
        if( in_array( $post_ID, $this->ids, true ) )
            $classes[] = 'is-admin-sticky';

        return $classes;
    }

}

endif;