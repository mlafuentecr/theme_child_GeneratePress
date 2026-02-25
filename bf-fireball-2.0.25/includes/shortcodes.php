<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'Blue_Flamingo_Shortcode' ) ) :

class Blue_Flamingo_Shortcode {

	function __construct() {

		// Get data added into post via post id
		add_shortcode('bf-shortcode', array($this, 'bf_shortcode_function') );

	}

	function bf_shortcode_function( $atts ) {

		extract(
			shortcode_atts(
				array('id' => ''),
				$atts
			)
		);

		$content_post =	 get_post($id);
		$content 	  =	 $content_post->post_content;
		$content	  =	 apply_filters('the_content', $content);
		$content	  =	 str_replace(']]>', ']]&gt;', $content);

		return $content;

	}

}

new Blue_Flamingo_Shortcode();

endif;