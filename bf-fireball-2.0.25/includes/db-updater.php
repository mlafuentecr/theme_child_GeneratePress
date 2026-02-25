<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class Blue_Flamingo_DB_Updater {


	function __construct() {
		
		// Update saving option for this plugin
		$this->bf_values_db_updater();
		
		// Delete old database
		$this->delete_old_db();

	}


	function bf_values_db_updater() {

		$db_updater = get_option('blueflamingo_plugin_db_updater'); // done or not-done
		$bfall_s 	= get_option('blueflamingo_plugin_all_settings');
		$bfps_g  	= get_option('blueflamingo_plugin_general_settings');
		$bfps_o  	= get_option('blueflamingo_plugin_options_settings');
		$bfps_er 	= get_option('blueflamingo_plugin_email_redirect_settings');
		$bfps_ga 	= get_option('blueflamingo_plugin_google_analytics_settings');
		$bfps_ep 	= get_option('blueflamingo_plugin_error_page_settings');
		$bfps_ad 	= get_option('blueflamingo_plugin_admin_display_settings');
		$bfp_pm 	= get_option('blueflamingo_plugin_plugin_manager');
		$bfps_pt    = get_option('blueflamingo_plugin_post_types_settings');
		$bfpn_pt    = get_option('blueflamingo_plugin_post_notice_settings');

		// Check
		$db_updater = ( !empty( $db_updater ) )	? $db_updater 	: '' ;
		$bfall_s  	= ( !empty( $bfall_s ) )	? $bfall_s  	: array();
		$bfps_g  	= ( !empty( $bfps_g ) )		? $bfps_g  		: array();
		$bfps_o  	= ( !empty( $bfps_o ) )		? $bfps_o		: array();
		$bfps_er 	= ( !empty( $bfps_er ) )	? $bfps_er 		: array();
		$bfps_ga 	= ( !empty( $bfps_ga ) )	? $bfps_ga		: array();
		$bfps_ep 	= ( !empty( $bfps_ep ) )	? $bfps_ep		: array();
		$bfps_ad 	= ( !empty( $bfps_ad ) )	? $bfps_ad		: array();
		$bfp_pm 	= ( !empty( $bfp_pm ) )		? $bfp_pm		: array();

		$bfps_pt 	= ( !empty( $bfps_pt ) )    ? $bfps_pt		: array();
		$bfpn_pt 	= ( !empty( $bfpn_pt ) )    ? $bfpn_pt		: array();

		if( !empty( get_option('fireball_live_url') ) ){
			$bfps_g['live_url'] = get_option('fireball_live_url');
		}

		if( !empty( get_option('fireball_staging_url') ) ){
			$bfps_g['staging_url'] = get_option('fireball_staging_url');
		}

		if( !empty( get_option('fireball_dev_url') ) ){
			$bfps_g['dev_url'] = get_option('fireball_dev_url');
		}


		$options = get_option('fireball_activate_stripe');

		if( !empty( $options['fireball_checkbox'] ) ){
			$bfps_o['activate_stripe_test_mode'] = $options['fireball_checkbox'];
		}

		if( !empty( $options['activate_wpsimplepay_testmode'] ) ){
			$bfps_o['activate_wpsimplepay_testmode'] = $options['activate_wpsimplepay_testmode'];
		}

		if( !empty( $options['hide_google_recaptcha_logo'] ) ){
			$bfps_o['hide_google_recaptcha_logo'] = $options['hide_google_recaptcha_logo'];
		}

		if( !empty( $options['Show_all_meta_fields'] ) ){
			$bfps_o['Show_all_meta_fields'] = $options['Show_all_meta_fields'];
		}

		if( !empty( $options['disable_admin_notifications_of_password_changes'] ) ){
			$bfps_o['disable_admin_notifications_of_password_changes'] = $options['disable_admin_notifications_of_password_changes'];
		}

		if( !empty( $options['admin_user_registration_date'] ) ){
			$bfps_o['admin_user_registration_date'] = $options['admin_user_registration_date'];
		}

		if( !empty( $options['json_basic_authentication'] ) ){
			$bfps_o['json_basic_authentication'] = $options['json_basic_authentication'];
		}

		if( !empty( $options['sharpen_images'] ) ){
			$bfps_o['sharpen_images'] = $options['sharpen_images'];
		}
		
		if( !empty( $options['move_yoast_bottom'] ) ){
			$bfps_o['move_yoast_bottom'] = $options['move_yoast_bottom'];
		}

		if( !empty( $options['auto_delete_standard_theme'] ) ){
			$bfps_o['auto_delete_standard_theme'] = $options['auto_delete_standard_theme'];
		}
		
		if( !empty( $options['log_plugin_upgrade'] ) ){
			$bfps_o['log_plugin_upgrade'] = $options['log_plugin_upgrade'];
		}
		
		if( !empty( $options['limit_ability_to_add_new_plugin'] ) ){
			$bfps_o['limit_ability_to_add_new_plugin'] = $options['limit_ability_to_add_new_plugin'];
		} 
		
		

		$fireball_activate_email_redirect = get_option('fireball_activate_email_redirect');
		if( !empty( $fireball_activate_email_redirect['fireball_checkbox'] ) ){
			$bfps_er['activate_email_redirect_staging_or_development'] = $fireball_activate_email_redirect['fireball_checkbox'];
		}

		$fireball_activate_email_redirect_production = get_option('fireball_activate_email_redirect_production');
		if( !empty( $fireball_activate_email_redirect_production['fireball_checkbox'] ) ){
			$bfps_er['activate_email_redirect_production'] = $fireball_activate_email_redirect_production['fireball_checkbox'];
		}

		if( !empty( get_option('fireball_email_redirect_field_dev_email') ) ){
			$bfps_er['redirect_email_id'] = get_option('fireball_email_redirect_field_dev_email');
		}




		$fireball_activate_google_analytics = get_option('fireball_activate_google_analytics');
		if( !empty( $fireball_activate_google_analytics['fireball_checkbox'] ) ){
			$bfps_ga['activate_google_analytics'] = $fireball_activate_google_analytics['fireball_checkbox'];
		}

		if( !empty( get_option('fireball_google_analytics_id') ) ){
			$bfps_ga['google_analytics_id'] = get_option('fireball_google_analytics_id');
		}

		if( !empty( get_option('fireball_universal_analytics_id') ) ){
			$bfps_ga['universal_analytics_id'] = get_option('fireball_universal_analytics_id');
		}

		$fireball_google_analytics_tracking_method = get_option('fireball_google_analytics_tracking_method');
		if( !empty( $fireball_google_analytics_tracking_method['ga_method'] ) ){
			$bfps_ga['google_analytics_tracking_method'] = $fireball_google_analytics_tracking_method['ga_method'];
		}

		if( !empty( get_option('fireball_google_analytics_position') ) ){
			$bfps_ga['google_analytics_position'] = get_option('fireball_google_analytics_position');
		}



		$fireball_google_analytics_logged_in = get_option('fireball_google_analytics_logged_in');
		if( !empty( $fireball_google_analytics_logged_in['fireball_checkbox'] ) ){
			$bfps_ga['google_analytics_logged_in'] = $fireball_google_analytics_logged_in['fireball_checkbox'];
		}

		if( !empty( get_option('fireball_google_analytics_metatag') ) ){
			$bfps_ga['google_analytics_metatag'] = get_option('fireball_google_analytics_metatag');
		}




		$error_page = get_option('fireball_404_activate');
		if( !empty( $error_page['activate'] ) ){
			$bfps_ep['activate_404'] = $error_page['activate'];
		}

		if( !empty( $error_page['custom_404_page'] ) ){
			$bfps_ep['custom_404_page'] = $error_page['custom_404_page'];
		}




		$admin_display_options = get_option('fireball_admin_display_options');
		if( !empty( $admin_display_options['users'] ) ){
			$bfps_ad['users_admin_display_options'] = $admin_display_options['users'];
		}

		if( !empty( $admin_display_options['ppm'] ) ){
			$bfps_ad['post_and_page_metaboxes_admin_display_options'] = $admin_display_options['ppm'];
		}

		if( !empty( $admin_display_options['enable_website_feedback'] ) ){
			$bfps_ad['enable_website_feedback'] = $admin_display_options['enable_website_feedback'];
		}
		if( !empty( $admin_display_options['feedback_destination'] ) ){
			$bfps_ad['feedback_destination'] = $admin_display_options['feedback_destination'];
		}
		if( !empty( $admin_display_options['feedback_only_for_admin'] ) ){
			$bfps_ad['feedback_only_for_admin'] = $admin_display_options['feedback_only_for_admin'];
		}
		if( !empty( $admin_display_options['enable_spm'] ) ){
			$bfps_ad['enable_spm'] = $admin_display_options['enable_spm'];
		}
		if( !empty( $admin_display_options['enable_email_testing'] ) ){
			$bfps_ad['enable_email_testing'] = $admin_display_options['enable_email_testing'];
		}
		if( !empty( $admin_display_options['testing_email_address'] ) ){
			$bfps_ad['testing_email_address'] = $admin_display_options['testing_email_address'];
		}
		


		$fireball_activate_plugin_manager = get_option('fireball_activate_plugin_manager');
		if( !empty( $fireball_activate_plugin_manager['fireball_checkbox'] ) ){
			$bfp_pm['activate_plugin_manager'] = $fireball_activate_plugin_manager['fireball_checkbox'];
		}

		if( !empty( get_option('fireball_plugin_control') ) ){
			$bfp_pm['deactivate_plugin_on_stage_and_dev_environments'] = get_option('fireball_plugin_control');
		}

		if( !empty( get_option('fireball_plugin_hide_update') ) ){
			$bfp_pm['hide_plugin_update'] = get_option('fireball_plugin_hide_update');
		}



		if( !empty( get_option('blueflamingo_current_page') ) ){
			$bfall_s['current_tab_page'] = get_option('blueflamingo_current_page');
		}else{
			$bfall_s['current_tab_page'] = array('settings_tab' => 'General', 'plugins_tab' => 'Plugin Manager');
		}

		if( !empty( get_option('fireball_plugin_hide_update') ) ){
			$bfall_s['custom_notice'] = get_option('woocommerce_stripe_notice_dismiss');
		}else{
			$bfall_s['custom_notice'] = 0;
		}

		if( empty($db_updater) || $db_updater == 'not-done' ){
			update_option('blueflamingo_plugin_general_settings', $bfps_g);
			update_option('blueflamingo_plugin_options_settings', $bfps_o);
			update_option('blueflamingo_plugin_email_redirect_settings', $bfps_er);
			update_option('blueflamingo_plugin_google_analytics_settings', $bfps_ga);
			update_option('blueflamingo_plugin_error_page_settings', $bfps_ep);
			update_option('blueflamingo_plugin_admin_display_settings', $bfps_ad);
			update_option('blueflamingo_plugin_plugin_manager', $bfp_pm);
			update_option('blueflamingo_plugin_all_settings', $bfall_s );
			update_option('blueflamingo_plugin_post_types_settings',$bfps_pt);
			update_option('blueflamingo_plugin_post_notice_settings',$bfpn_pt);
			update_option('blueflamingo_plugin_db_updater', 'done');
		}
	}

	function delete_old_db() {

		if( !empty($db_updater) && $db_updater == 'done' ){

			//General
			delete_option('fireball_live_url');
			delete_option('fireball_staging_url');
			delete_option('fireball_dev_url');

			//Stripe
			delete_option('fireball_activate_stripe');

			//Email Redirect
			delete_option('fireball_activate_email_redirect');
			delete_option('fireball_activate_email_redirect_production');
			delete_option('fireball_email_redirect_field_dev_email');

			//Plugin Manager
			delete_option('fireball_activate_plugin_manager');
			delete_option('fireball_plugin_control');
			delete_option('fireball_plugin_hide_update');

			//Google Analytics
			delete_option('fireball_activate_google_analytics');
			delete_option('fireball_google_analytics_id');
			delete_option('fireball_google_analytics_tracking_method');
			delete_option('fireball_google_analytics_position');
			delete_option('fireball_google_analytics_logged_in');
			delete_option('fireball_google_analytics_metatag');

			//Admin Display
			delete_option('fireball_admin_display_options');

			//404 Page
			delete_option('fireball_404_activate');
			delete_option('fireball_404_url');

			//All Settings
			delete_option('blueflamingo_current_page');
			delete_option('woocommerce_stripe_notice_dismiss');

			// Not used
			delete_option('fireball_plugin_slug');

		}
	}

}

new Blue_Flamingo_DB_Updater();