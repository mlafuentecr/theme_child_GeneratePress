<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class Blue_Flamingo_Scripts_Styles {


	function __construct() {

		// JS
		add_action( 'wp_enqueue_scripts', array($this, 'blueflamingo_front_script') );
		add_action( 'admin_enqueue_scripts', array($this, 'blueflamingo_admin_script') );

		// CSS
		add_action( 'wp_enqueue_scripts', array($this, 'blueflamingo_front_style') );
		add_action( 'admin_enqueue_scripts', array($this, 'blueflamingo_admin_style') );

	}


	/* Function to add script at front side */
	function blueflamingo_front_script() {
		//wp_enqueue_script( 'blueflamingo-frontend', blueflamingo_URL_JS . '/frontend.js', array('jquery'), blueflamingo_VERSION, true );
	}


	/* Function to add script at admin side */
	function blueflamingo_admin_script() {

		if( blueflamingo_admin_page_check() ) {
			wp_enqueue_script('blueflamingo-admin', blueflamingo_URL_JS . '/admin.js', array('jquery'), blueflamingo_VERSION, true);
			wp_localize_script( 'blueflamingo-admin', 'blueflamingo', array(
				'ajaxurl' 			=> admin_url( 'admin-ajax.php' ),
				'nonce' 			=> wp_create_nonce('blueflamingo_nonce'),
				'licensed_nonce'		=> wp_create_nonce('blueflamingo_licensed_nonce'),
				'OCPI' 				=> wp_create_nonce('OCPI'),
				'ajax_plugin' 		=> wp_create_nonce('updates'),
				'ajax_bulk_plugins' => wp_create_nonce('bulk-plugins'),
				'bloginfoURL' 		=> get_bloginfo('url'),
				'plugindir' 		=> blueflamingo_URL
			));
			
			wp_localize_script( 'blueflamingo-admin', 'BF', array(
				'ajaxurl' 			=> admin_url( 'admin-ajax.php' ),
				'nonce' 			=> wp_create_nonce('blueflamingo_nonce'),
				'licensed_nonce'		=> wp_create_nonce('blueflamingo_licensed_nonce'),
				'OCPI' 				=> wp_create_nonce('OCPI'),
				'ajax_plugin' 		=> wp_create_nonce('updates'),
				'ajax_bulk_plugins' => wp_create_nonce('bulk-plugins'),
				'bloginfoURL' 		=> get_bloginfo('url'),
				'plugindir' 		=> blueflamingo_URL
			));

			wp_localize_script('blueflamingo-admin', 'bfCspAdmin', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('blueflamingo_csp')
			));

			wp_enqueue_media(); // scripts used for uploader.
			
			// Scripts used for Default Feature Image.
			wp_enqueue_script( 'blueflamingo-dfi', blueflamingo_URL_JS . '/default-featured-image.js', array('jquery'), blueflamingo_VERSION, true);
			wp_localize_script(
				'blueflamingo-dfi',
				'bfdfi',
				array(
					'manager_title'  => __( 'Select default featured image', 'default-featured-image' ),
					'manager_button' => __( 'Set default featured image', 'default-featured-image' ),
				)
			);
		}

		wp_enqueue_script('blueflamingo-all-admin', blueflamingo_URL_JS . '/all-admin.js', array('jquery'), blueflamingo_VERSION, true);
		wp_localize_script( 'blueflamingo-all-admin', 'blueflamingoALL', array(
			'imgurl'	=>	blueflamingo_URL_IMAGES
		));

	}


	/* Function to add style at front side */
	function blueflamingo_front_style() {
		wp_register_style( 'blueflamingo-frontend', blueflamingo_URL_CSS . '/frontend.css', null, blueflamingo_VERSION );
		wp_enqueue_style( 'blueflamingo-frontend' );
	}


	/* Enqueue admin styles */
	function blueflamingo_admin_style() {
		if( blueflamingo_admin_page_check() ) {
			wp_register_style( 'blueflamingo-admin', blueflamingo_URL_CSS . '/admin.css', null, blueflamingo_VERSION );
			wp_enqueue_style( 'blueflamingo-admin' );
		}
		wp_register_style( 'blueflamingo-all-admin', blueflamingo_URL_CSS . '/all-admin.css', null, blueflamingo_VERSION );
		wp_enqueue_style( 'blueflamingo-all-admin' );
	}

}

new Blue_Flamingo_Scripts_Styles();