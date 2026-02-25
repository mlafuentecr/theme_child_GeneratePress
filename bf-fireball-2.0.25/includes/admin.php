<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'Blue_Flamingo_Admin' ) ) :

class Blue_Flamingo_Admin {

	function __construct() {
        // Create CSP violations table
        $this->create_csp_violations_table();

		// Plugin links in plugin row
		add_filter('plugin_action_links_' . blueflamingo_PLUGIN_BASENAME, array($this, 'blueflamingo_link') );
		add_filter('plugin_row_meta', array($this, 'prefix_append_blueflamingo_row'), 10, 4 );

        // Add AJAX handlers for CSP logs
        add_action('wp_ajax_view_csp_logs', array($this, 'ajax_view_csp_logs'));
        add_action('wp_ajax_delete_csp_logs', array($this, 'ajax_delete_csp_logs'));

		// Admin menu
		add_action('admin_menu', array($this, 'blueflamingo_register_menu'), 12 );

		// This hides plugin updates
		add_filter('site_transient_update_plugins', array( $this, 'filter_plugin_updates' ));

		// Code added to admin head section
		add_action('admin_head', array( $this, 'plugin_color_border' ) );

		// This hides unneccessary post and page meta box
		add_action('admin_head', array( $this, 'post_and_page_metaboxes' ) );

		// This hides plugin update notice, which is not hidden by {filter_plugin_updates}
		add_action('admin_head', array( $this, 'hide_plugin_other_notice' ) );

		// Code added to user admin head section only
		add_action('admin_head-user-edit.php', array( $this, 'bf_admin_display' ) );

		// Code added to profile admin head section only
		add_action('admin_head-profile.php',   array( $this, 'bf_admin_display' ) );

		// This will deactivate plugin based on evironment ( Production, Staging, Development )
		$this->blueflamingo_activate_deactivate_plugin();

		// This will hide Google Recaptcha V3 logo in frontend via css
		$this->hide_google_recaptcha_logo();

		// This removes Contact form 7 Google Recaptcha V3 in Staging and Dev environments for form Testing
		$this->remove_recaptcha();

		// This disables password change notification for users
		$this->wp_password_change_notification_bf();

		// This is for debug purpose, this shows all the post meta within that post
		$this->show_all_meta_value();

		// This is used for testing purpose, All mails are redirected for mentioned email, only when its Staging or Dev environment
		$this->mail_filter();

		// Assigns one of wordpress pages to 404 template
		$this->bf_404_page();

		// This adds in a "registration_date" in user table
		$this->wp_add_user_colomn();

		// Settings for Google Analytics tab
		$this->bf_google_analytics();

		// Adds a crisp to images
		$this->bf_sharpen_images();

		// This function executed as soon as Free / Premium plugin are added in
		$this->bf_reload();

		// convert the "username: password" pair to a Base64 encoded string and pass it in the authorization request header
		$this->bf_json_basic_authentication();

		// This function pushes wpsimplepay to test mode for Staging or Dev environment
		$this->blueflamingo_wpsimplepay_settings();

		// Initiates stickers for notes
		$this->blueflamingo_admin_init();

		// This registes a get_option to save all settings
		add_action('admin_init', array($this, 'register_stripe_fields'));

		// Blueflamingo Notes Page URL
		add_action('admin_init', array($this, 'blueflamingo_notes') );

		// Blueflamingo Shortcodes Page URL
		add_action('admin_init', array($this, 'blueflamingo_shortcodes') );

		// Set Stripe to test mode for Staging or Dev environment
		add_action('admin_init', array($this, 'blueflamingo_prefix_env_settings') );

		// Add security headers on init
		add_action('send_headers', [$this, 'add_security_headers'], 99999);

		// This will change the logo and footer text in wp login page
		$this->bf_login_logo();

		// This will move SEO Yoast plugin to bottom of posts and pages
		//$this->move_yoast_bottom();

		// If enabled user/admin get the widget to add feedback to the BF
		$this->website_feedback();

		//if enabled Automatic updates column in plugin page and WP Engine Smart Plugin Manager fromplugin list will be hidden
		//$this->smart_plugin_manager_hide();

		// If enabled contact from 7 "TO" email or any other email with enterted email address will be redirected to same.
		$this->email_testing();

		// If enabled all standard theme like 2022,2021,2019 etc should delete
		add_action('admin_init', array($this, 'auto_delete_standard_theme') );

		// If enabled then a table will be created to plugin updates i.e name, old, new version and datetime and displayed in stand alone page with url -  "siteurl/blueflamingo/plugin-updates"
		$this->log_plugin_upgrade();

		// This will disable and hide Add new plugin functionality
		$this->limit_ability_to_add_new_plugin();

		// This will add script for WhatsConverts
		$this->bf_whatConverts();

		// This is to display post type description message, this shows message on particular post type if description is added.
		$this->show_description_for_post_types();

		//this is to disply post notice on top of post page
		$this->show_description_for_post();
	}

	function blueflamingo_admin_init() {
		if( file_exists(blueflamingo_DIR_INCLUDES . '/stickers.php') ) {
			$stickies = new Blue_Flamingo_Stickies;
			$stickies->init( 'blue-flamingo-notes', $this->ret_sticky_array() );
		}
	}

	function ret_sticky_array() {
		$events = get_posts( array ( 'post_type' => 'blue-flamingo-notes' ) );
		$arrayFormat = array();
		if ( $events ) {
			foreach ( $events as $event ) {
				$poststicky = get_post_meta( $event->ID, '_bf_notes_data', true );
				if( !empty($poststicky['sticky']['enable']) && $poststicky['sticky']['enable'] == 'on' ){
					$arrayFormat[] = $event->ID;
				}
			}
			return $arrayFormat;
		}
	}

	//Stripe actual code start
	function blueflamingo_prefix_env_settings() {
		if ( class_exists( 'WC_Stripe' ) && is_admin() ) {
			$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );
			$bfps_g = get_option( 'blueflamingo_plugin_general_settings' );
			if( !empty( $bfps_o['activate_stripe_test_mode'] ) ){
				$test_site_staging_url 	= blueflamingo_decryption( $bfps_g['staging_url'] );
				$test_site_dev_url 		= blueflamingo_decryption( $bfps_g['dev_url'] );
				// If settings have already been updated, return early
				if ( 1 == get_transient( 'bf_staging-settings-updated' ) ) {
					return;
				}
				if ( !empty($test_site_staging_url) || !empty($test_site_dev_url) ) {
					if ( (strpos(get_site_url(), $test_site_staging_url) || strpos(get_site_url(), $test_site_dev_url)) !== false ) {
						// Use Stripe in test mode
						$woocommerce_stripe_settings = get_option( 'woocommerce_stripe_settings', array() );
						if ( 'yes' != $woocommerce_stripe_settings['testmode'] ) {
							$woocommerce_stripe_settings['testmode'] = 'yes';
							update_option( 'woocommerce_stripe_settings', $woocommerce_stripe_settings );
						}
						set_transient( 'bf_staging-settings-updated', 1, ( 60 * 60 * 24 ) );
					}
				}
			}else{
				if( 1 == get_transient( 'bf_staging-settings-updated' ) ){
					delete_transient( 'bf_staging-settings-updated' );
				}else{
					return;
				}
			}
		}else{
			add_action( 'stripe_error', array( $this, 'woocommerce_stripe_missing_wc_notice' ) );
		}
	}

	function woocommerce_stripe_missing_wc_notice() {
		$bfps_all = get_option( 'blueflamingo_plugin_all_settings' );
		if ( $bfps_all['custom_notice'] == 0 ){
			echo '<div id="woobf-post-notice" class="notice is-dismissible error"><p><strong style="display: flex;align-items: center;"><img src="'. blueflamingo_URL_IMAGES .'/bf-logo.svg" alt="" style="margin-right: 9px;width: 18px;">' . esc_html__( "Blue Flamingo Stripe Setting won't work until WooCommerce Stripe Payment Gateway Plugin is installed and activated." ) . '</strong></p></div>';
		}
	}

	//WP Simple Pay setting
	function blueflamingo_wpsimplepay_settings() {
		$bfps_o		= get_option( 'blueflamingo_plugin_options_settings' );
		$bfps_g 	= get_option( 'blueflamingo_plugin_general_settings' );
		$settings 	= get_option( 'simpay_settings_keys' );

		if(!empty($settings)){

			$test_site_live_url 	= blueflamingo_decryption( $bfps_g['live_url'] );
			$test_site_staging_url 	= blueflamingo_decryption( $bfps_g['staging_url'] );
			$test_site_dev_url 		= blueflamingo_decryption( $bfps_g['dev_url'] );

			if( !empty( $bfps_o['activate_wpsimplepay_testmode'] ) ){
				// If settings have already been updated, return early
				if ( !empty($test_site_staging_url) || !empty($test_site_dev_url) /*|| !empty($test_site_live_url)*/ ) {
					if ( (strpos(get_site_url(), $test_site_staging_url) || strpos(get_site_url(), $test_site_dev_url)/* || strpos(get_site_url(), $test_site_live_url)*/ ) !== false ) {
						// Use WP Simple Pay in test mode
						$settings['mode']['test_mode'] = 'enabled';
						update_option( 'simpay_settings_keys' , $settings );
					}
				}
			}else{
				if ( !empty($test_site_staging_url) || !empty($test_site_dev_url) ) {
					if ( (strpos(get_site_url(), $test_site_staging_url) || strpos(get_site_url(), $test_site_dev_url) ) !== false ) {
						// Use WP Simple Pay in test mode
						$settings['mode']['test_mode'] = 'disabled';
						update_option( 'simpay_settings_keys' , $settings );
					}
				}
				if ( !empty($test_site_live_url) ) {
					if ( strpos(get_site_url(), $test_site_live_url) !== false ) {
						// Use WP Simple Pay in test mode

					}
				}
			}
		}else{
			return;
		}
	}

	function blueflamingo_notes() {
		global $pagenow;
		# Check current admin page.
		if ( $pagenow == 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] == 'blueflamingo_notes' ) {
			wp_redirect( admin_url( 'edit.php?post_type=blue-flamingo-notes' ) );
			exit;
		}
	}

	function blueflamingo_shortcodes() {
		global $pagenow;
		# Check current admin page.
		if ( $pagenow == 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] == 'blueflamingo_shortcodes' ) {
			wp_redirect( admin_url( 'edit.php?post_type=bf-shortcodes' ) );
			exit;
		}
	}

	function bf_admin_display() {
		$bfps_ad = get_option( 'blueflamingo_plugin_admin_display_settings' );
		if( !empty( $bfps_ad['users_admin_display_options'] ) ){
			$styles_scripts = "
				<style>
					#your-profile .custom-personal-options-class tr, tr.user-rich-editing-wrap, tr.user-syntax-highlighting-wrap, tr.user-admin-color-wrap, tr.user-comment-shortcuts-wrap, tr.show-admin-bar.user-admin-bar-front-wrap, tr.user-description-wrap, tr.user-profile-picture, tr.user-url-wrap, tr.user-facebook-wrap, tr.user-instagram-wrap, tr.user-linkedin-wrap, tr.user-myspace-wrap, tr.user-pinterest-wrap, tr.user-soundcloud-wrap, tr.user-tumblr-wrap, tr.user-twitter-wrap, tr.user-youtube-wrap, tr.user-wikipedia-wrap, #your-profile .yoast-settings, #your-profile div#application-passwords-section{display:none;}tr.user-language-wrap{display:block!important;}
				</style>
			";
			echo $styles_scripts;
		}
	}

	function plugin_color_border() {
		$bfp_pm = get_option('blueflamingo_plugin_plugin_manager');
		$plug	= isset( $bfp_pm['hide_plugin_update'] ) ? $bfp_pm['hide_plugin_update'] : null;
		$style = '<style>';
		$i = 0;
		if( !empty( $plug ) ){
			$numItems = count( $plug );
			foreach( $plug as $xx => $yy ){
				if(++$i === $numItems) {
					$seperator = "";
				}else{
					$seperator = ",";
				}
				$style .= '[data-plugin="'.$xx.'"] th.check-column'.$seperator.'';
			}
		}
		$style .= '{border-left: 4px solid #F26522 !important;}';
		$j = 0;
		if( !empty( $plug ) ){
			foreach( $plug as $xx => $yy ){
				if(++$j === $numItems) {
					$seperator = "";
				}else{
					$seperator = ",";
				}
				$style .= '[data-plugin="'.$xx.'"] + tr.plugin-update-tr'.$seperator.'';
			}
		}
		$style .= '{display: none;}';
		if( !empty( $plug ) ){
			$style .= 'tr.plugin-update-tr + tr.plugin-update-tr{display: none;}';
		}
		$style .= '#toplevel_page_blue-flamingo li.wp-first-item,#menu-posts-blue-flamingo-notes, #toplevel_page_blue-flamingo li.wp-first-item,#menu-posts-bf-shortcodes{display:none;}';
		$style .= '</style>';
		echo $style;
	}

	function post_and_page_metaboxes() {
		$bfps_ad = get_option( 'blueflamingo_plugin_admin_display_settings' );
		if( !empty( $bfps_ad['post_and_page_metaboxes_admin_display_options'] ) ){
			$styles_scripts = "
				<style>
					.post-php div#x-meta-box-post,
					.post-php label[for='x-meta-box-post-hide'],
					.post-php div#slider_revolution_metabox,
					.post-php label[for='slider_revolution_metabox-hide'],
					.post-php div#pageparentdiv,
					.post-php label[for='pageparentdiv-hide'],
					.post-php div#x-meta-box-page,
					.post-php label[for='x-meta-box-page-hide'],
					.post-php div#eg-meta-box,
					.post-php label[for='eg-meta-box-hide'],
					.post-php div#x-meta-box-portfolio,
					.post-php label[for='x-meta-box-portfolio-hide'],
					.post-php div#x-meta-box-portfolio-item,
					.post-php label[for='x-meta-box-portfolio-item-hide'],
					.post-php div#trackbacksdiv,
					.post-php label[for='trackbacksdiv-hide'],
					.post-php div#commentsdiv,
					.post-php label[for='commentsdiv-hide'],
					.post-php div#commentstatusdiv,
					.post-php label[for='commentstatusdiv-hide'],
					.post-php div#authordiv,
					.post-php label[for='authordiv-hide'],
					.post-php div#formatdiv,
					.post-php label[for='formatdiv-hide'],
					.post-php div#tagsdiv-post_tag,
					.post-php label[for='tagsdiv-post_tag-hide'],

					.post-new-php div#x-meta-box-post,
					.post-new-php label[for='x-meta-box-post-hide'],
					.post-new-php div#slider_revolution_metabox,
					.post-new-php label[for='slider_revolution_metabox-hide'],
					.post-new-php div#pageparentdiv,
					.post-new-php label[for='pageparentdiv-hide'],
					.post-new-php div#x-meta-box-page,
					.post-new-php label[for='x-meta-box-page-hide'],
					.post-new-php div#eg-meta-box,
					.post-new-php label[for='eg-meta-box-hide'],
					.post-new-php div#x-meta-box-portfolio,
					.post-new-php label[for='x-meta-box-portfolio-hide'],
					.post-new-php div#x-meta-box-portfolio-item
					.post-new-php label[for='x-meta-box-portfolio-item-hide'],
					.post-new-php div#trackbacksdiv,
					.post-new-php label[for='trackbacksdiv-hide'],
					.post-new-php div#commentsdiv,
					.post-new-php label[for='commentsdiv-hide'],
					.post-new-php div#commentstatusdiv,
					.post-new-php label[for='commentstatusdiv-hide'],
					.post-new-php div#authordiv,
					.post-new-php label[for='authordiv-hide'],
					.post-new-php div#formatdiv,
					.post-new-php label[for='formatdiv-hide'],
					.post-new-php div#tagsdiv-post_tag,
					.post-new-php label[for='tagsdiv-post_tag-hide']{
						display: none !important;
					}
				</style>
			";
			echo $styles_scripts;
		}
	}

	function hide_plugin_other_notice() {
		$bfp_pm = get_option( 'blueflamingo_plugin_plugin_manager' );
		$styles = "";
		if( !empty( $bfp_pm['activate_plugin_manager'] ) ){
			if(isset( $bfp_pm['hide_plugin_update'] ) ){
				$blueflamingo_plugin_hide_update = $bfp_pm['hide_plugin_update'];
			}
			$styles .= '<style>';
			if(!empty($blueflamingo_plugin_hide_update)){
				foreach($blueflamingo_plugin_hide_update as $basename => $active){
					$styles .= 'tr[data-plugin="'. $basename .'"] .notice,tr[data-plugin="'. $basename .'"] + tr.plugin-update-tr .notice{display: none;}';
				}
			}
			$styles .= '</style>';
		}
		echo $styles;
	}

    /**
     * Create CSP violations table
     */
    private function create_csp_violations_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bf_csp_violations';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			blocked_uri VARCHAR(1024) NULL,
			blocked_uri_full TEXT NULL,
			document_uri VARCHAR(255) NULL,
			violated_directive VARCHAR(255) NULL,
			effective_directive VARCHAR(255) NULL,
			original_policy LONGTEXT NULL,
			referrer VARCHAR(255) NULL,
			status_code INT NULL,
			user_agent TEXT NULL,
			count INT DEFAULT 1,
			first_occurrence DATETIME NOT NULL,
			last_occurrence DATETIME NOT NULL,
			PRIMARY KEY (id),
            KEY blocked_uri (blocked_uri),
            KEY violated_directive (violated_directive)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

	function register_stripe_fields () {

		register_setting('blueflamingo_plugin_general_settings_group', 'blueflamingo_plugin_general_settings');
		register_setting('blueflamingo_plugin_options_settings_group', 'blueflamingo_plugin_options_settings');
		register_setting('blueflamingo_plugin_email_redirect_settings_group', 'blueflamingo_plugin_email_redirect_settings');
		register_setting('blueflamingo_plugin_google_analytics_settings_group', 'blueflamingo_plugin_google_analytics_settings');
		register_setting('blueflamingo_plugin_error_page_settings_group', 'blueflamingo_plugin_error_page_settings');
		register_setting('blueflamingo_plugin_admin_display_settings_group', 'blueflamingo_plugin_admin_display_settings');
		register_setting('blueflamingo_plugin_plugin_manager_group', 'blueflamingo_plugin_plugin_manager');
		register_setting('blueflamingo_plugin_post_types_settings_group', 'blueflamingo_plugin_post_types_settings');
		register_setting('blueflamingo_plugin_post_notice_settings_group', 'blueflamingo_plugin_post_notice_settings');
		register_setting('blueflamingo_plugin_security_settings_group', 'blueflamingo_plugin_security_settings',
		array(
			'sanitize_callback' => array($this, 'bf_sanitize_security_settings')
		));
	}

	function bf_sanitize_security_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return $input;
		}

		// Sanitize CSP field
		if ( isset( $input['csp'] ) ) {
			$csp = (string) $input['csp'];

			if ( ! empty( $input['csp_enabled'] ) ) {
				// Split by any newline, rtrim each line, drop empty lines, join with single space
				$lines = preg_split( '/\r\n|\r|\n/', $csp );
				$clean = array();

				foreach ( $lines as $line ) {
					$line = rtrim( $line );
					if ( $line === '' ) {
						continue;
					}
					$clean[] = $line;
				}

				$csp = implode( ' ', $clean );
				// Collapse multiple whitespace into single space and trim ends
				$csp = preg_replace( '/\s+/', ' ', $csp );
				$csp = trim( $csp );
			} else {
				// Keep newlines but remove trailing spaces at end of each line
				$lines = preg_split( '/\r\n|\r|\n/', $csp );
				foreach ( $lines as &$line ) {
					$line = rtrim( $line );
				}
				$csp = implode( PHP_EOL, $lines );
			}

			$input['csp'] = $csp;
		}

		// Optionally sanitize other known fields here if needed

		return $input;
	}

	// Helper function for basic header validation
	function bf_is_valid_header($value) {
		if (empty($value)) return false;
		// Disallow newlines and control characters
		if (preg_match('/[\r\n\x00-\x1F]/', $value)) return false;
		// Disallow overly long values
		if (strlen($value) > 5000) return false;
		return true;
	}

	function add_security_headers() {
		$security_settings = get_option('blueflamingo_plugin_security_settings');

		if (!is_array($security_settings)) {
			return;
		}

		$send_header = function($name, $value, $sanitize = true) {
			if (!empty($value)) {
				if ($sanitize) {
					header("$name: " . sanitize_text_field(trim($value)));
				} else {
					header("$name: " . trim($value));
				}
			}
		};

		header_remove('Content-Security-Policy');
		header_remove('X-Frame-Options');
		header_remove('X-Content-Type-Options');
		header_remove('Referrer-Policy');
		header_remove('Strict-Transport-Security');
		header_remove('X-XSS-Protection');
		header_remove('Permissions-Policy');

		// Referrer-Policy
		if (!empty($security_settings['referrer_policy_enabled'])) {
			$val = trim($security_settings['referrer_policy']);
			if ($this->bf_is_valid_header($val)) {
				$send_header('Referrer-Policy', $val);
			}
		}

		// Strict-Transport-Security
		if (!empty($security_settings['hsts_enabled'])) {
			$val = trim($security_settings['hsts']);
			if ($this->bf_is_valid_header($val)) {
				$send_header('Strict-Transport-Security', $val);
			}
		}

		// X-Content-Type-Options
		if (!empty($security_settings['content_type_options_enabled'])) {
			$val = trim($security_settings['content_type_options']);
			if ($this->bf_is_valid_header($val)) {
				$send_header('X-Content-Type-Options', $val);
			}
		}

		// X-Frame-Options
		if (!empty($security_settings['frame_options_enabled'])) {
			$val = trim($security_settings['frame_options']);
			if ($this->bf_is_valid_header($val)) {
				$send_header('X-Frame-Options', $val);
			}
		}

		// X-XSS-Protection
		if (!empty($security_settings['xss_protection_enabled'])) {
			$val = trim($security_settings['xss_protection']);
			if ($this->bf_is_valid_header($val)) {
				$send_header('X-XSS-Protection', $val);
			}
		}

		// Permissions-Policy
		if (!empty($security_settings['permissions_policy_enabled'])) {
			$val = trim($security_settings['permissions_policy']);
			if ($this->bf_is_valid_header($val)) {
				$send_header('Permissions-Policy', $val, false);
			}
		}

		// Content-Security-Policy
		if (!empty($security_settings['csp_enabled'])) {
			$val = trim(preg_replace('/\s+/', ' ', $security_settings['csp']));
			if ($val !== '' && substr($val, -1) !== ';') {
				$val .= ';';
			}

			if (!empty($val) && $this->bf_is_valid_header($val)) {

				// Add report-uri for violation reporting
				$report_uri = rest_url('blueflamingo/v1/csp-report');
				$val .= " report-uri $report_uri; report-to csp-endpoint";

				// Choose header name (report-only or enforce)
				$header_name = !empty($security_settings['csp_report_only'])
					? 'Content-Security-Policy-Report-Only'
					: 'Content-Security-Policy';

				// Send CSP header (no sanitization to preserve syntax)
				$send_header($header_name, $val, false);

				// Add Report-To header for browser reporting
				$report_to = json_encode([
					'group' => 'csp-endpoint',
					'max_age' => 86400,
					'endpoints' => [['url' => $report_uri]]
				], JSON_UNESCAPED_SLASHES);

				header('Report-To: ' . $report_to);
			}
		}
	}


	function filter_plugin_updates( $value ) {
		foreach($this->hide_plugin_update() as $num => $val){
			unset( $value->response[$val] );
		}
		return $value;
	}

    /**
     * Handle viewing CSP violation logs via AJAX
     */
    function ajax_view_csp_logs() {
		check_ajax_referer('blueflamingo_csp', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized');
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'bf_csp_violations';

		// Get grouped violations with document URIs as a list
		$violations = $wpdb->get_results(
			"SELECT
				blocked_uri,
				COALESCE(NULLIF(violated_directive, ''), effective_directive) AS violated_directive,
				SUM(count) AS total_count,
				GROUP_CONCAT(DISTINCT document_uri ORDER BY document_uri SEPARATOR '||') AS document_uris,
				MIN(first_occurrence) AS first_occurrence,
				MAX(last_occurrence) AS last_occurrence
			FROM $table_name
			GROUP BY blocked_uri, COALESCE(NULLIF(violated_directive, ''), effective_directive)
			ORDER BY last_occurrence DESC",
			ARRAY_A
		);

		// Convert document_uris string to array for each violation
		foreach ($violations as &$violation) {
			if (!empty($violation['document_uris'])) {
				$violation['document_uris'] = explode('||', $violation['document_uris']);
			} else {
				$violation['document_uris'] = array();
			}

			// Format dates
			$violation['first_occurrence'] = !empty($violation['first_occurrence'])
				? date('Y-m-d H:i:s', strtotime($violation['first_occurrence']))
				: '';
			$violation['last_occurrence'] = !empty($violation['last_occurrence'])
				? date('Y-m-d H:i:s', strtotime($violation['last_occurrence']))
				: '';
		}

		wp_send_json_success($violations);
	}


    /**
     * Handle deleting CSP violation logs via AJAX
     */
    function ajax_delete_csp_logs() {
        check_ajax_referer('blueflamingo_csp', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'bf_csp_violations';

        // Delete all violations
        $wpdb->query("TRUNCATE TABLE $table_name");

        wp_send_json_success();
    }

	function hide_plugin_update() {
		$no_update = array();
		$bfp_pm = get_option( 'blueflamingo_plugin_plugin_manager' );
		if( !empty( $bfp_pm['activate_plugin_manager'] ) ){
			if(isset( $bfp_pm['hide_plugin_update'] ) ){
				$blueflamingo_plugin_hide_update = $bfp_pm['hide_plugin_update'];
			}
			if(! empty($blueflamingo_plugin_hide_update)){
				foreach($blueflamingo_plugin_hide_update as $basename => $active){
					$no_update[] = $basename;
				}
			}
		}else{}
		return $no_update;
	}

	function blueflamingo_activate_deactivate_plugin() {
		$bfps_g 	= get_option( 'blueflamingo_plugin_general_settings' );
		$test_site_staging_url = blueflamingo_decryption( blueflamingo_issetor( $bfps_g['staging_url'] ) );
		$test_site_dev_url = blueflamingo_decryption( blueflamingo_issetor( $bfps_g['dev_url'] ) );
		if ( !empty($test_site_staging_url) ) {
			if ( ( strpos(get_site_url(), $test_site_staging_url) ) !== false ) {
				add_action( 'admin_init', array( $this, 'deactivate_plugin_conditional' ) );
			}
		}
		if ( !empty($test_site_dev_url) ) {
			if ( ( strpos(get_site_url(), $test_site_dev_url) ) !== false ) {
				add_action( 'admin_init', array( $this, 'deactivate_plugin_conditional' ) );
			}
		}
	}

	function deactivate_plugin_conditional() {
		$bfp_pm = get_option( 'blueflamingo_plugin_plugin_manager' );
		if( !empty( $bfp_pm['activate_plugin_manager'] ) ){
			$blueflamingo_plugin_control = ( isset($bfp_pm['deactivate_plugin_on_stage_and_dev_environments']) ) ? $bfp_pm['deactivate_plugin_on_stage_and_dev_environments'] : '';
			if( !empty( $blueflamingo_plugin_control ) ){
				foreach( $blueflamingo_plugin_control as $basename => $active ){
					if ( is_plugin_active($basename) ) {
						deactivate_plugins($basename);
					}
				}
			}
		}else{}
	}

	function bf_json_basic_authentication() {
		$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );
		if( !empty( $bfps_o['json_basic_authentication'] ) ){
			add_filter( 'determine_current_user', array($this, 'json_basic_auth_handler'), 20 );
			add_filter( 'rest_authentication_errors', array($this, 'json_basic_auth_error') );
		}
	}

	function json_basic_auth_handler( $user ) {
		global $wp_json_basic_auth_error;
		$wp_json_basic_auth_error = null;

		// Don't authenticate twice
		if ( ! empty( $user ) ) {
			return $user;
		}

		// Check that we're trying to authenticate
		if ( !isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			return $user;
		}

		$username = $_SERVER['PHP_AUTH_USER'];
		$password = $_SERVER['PHP_AUTH_PW'];

		/**
		 * In multi-site, wp_authenticate_spam_check filter is run on authentication. This filter calls
		 * get_currentuserinfo which in turn calls the determine_current_user filter. This leads to infinite
		 * recursion and a stack overflow unless the current function is removed from the determine_current_user
		 * filter during authentication.
		 */
		remove_filter( 'determine_current_user', array($this, 'json_basic_auth_handler'), 20 );
		$user = wp_authenticate( $username, $password );
		add_filter( 'determine_current_user', array($this, 'json_basic_auth_handler'), 20 );
		if ( is_wp_error( $user ) ) {
			$wp_json_basic_auth_error = $user;
			return null;
		}
		$wp_json_basic_auth_error = true;
		return $user->ID;
	}

	function json_basic_auth_error( $error ) {
		// Passthrough other errors
		if ( ! empty( $error ) ) {
			return $error;
		}
		global $wp_json_basic_auth_error;
		return $wp_json_basic_auth_error;
	}

	function bf_reload() {
		if( isset($_GET['action']) && $_GET['action'] == 'activate_plugins' ){
			sleep(10);
			$this->blueflamingo_main_redirect();
		}
	}

	function blueflamingo_main_redirect() {
		$child = admin_url( 'admin.php?page=blueflamingo_plugins' );
		echo "<script>window.location = '$child';</script>";
	}

	function bf_sharpen_images() {
		$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );
		if( !empty( $bfps_o['sharpen_images'] ) ){
			add_filter('image_make_intermediate_size', array($this, 'wps_sharpen_resized_file'),900);
		}
	}

	function wps_sharpen_resized_file( $resized_file ) {
		// Prevent infinite recursion
		remove_filter( 'image_make_intermediate_size', [ $this, 'wps_sharpen_resized_file' ], 900 );

		// Detect file type
		$mime = mime_content_type( $resized_file );
		if ( $mime !== 'image/jpeg' ) {
			add_filter( 'image_make_intermediate_size', [ $this, 'wps_sharpen_resized_file' ], 900 );
			return $resized_file;
		}

		// --- Preferred: Imagick ---
		if ( class_exists( 'Imagick' ) ) {
			try {
				$imagick = new Imagick( $resized_file );

				// (radius = 0, sigma = 1) => subtle sharpening
				$imagick->sharpenImage( 0, 1 );

				// Overwrite original file
				$imagick->writeImage( $resized_file );

				$imagick->clear();
				$imagick->destroy();

				add_filter( 'image_make_intermediate_size', [ $this, 'wps_sharpen_resized_file' ], 900 );
				return $resized_file;
			} catch ( Exception $e ) {
				// Fallback to GD if Imagick fails
			}
		}

		// --- Fallback: GD ---
		if ( function_exists( 'imagecreatefromjpeg' ) ) {
			$image = @imagecreatefromjpeg( $resized_file );

			if ( $image ) {
				$matrix = [
					[-1, -1, -1],
					[-1, 16, -1],
					[-1, -1, -1],
				];
				$divisor = array_sum( array_map( 'array_sum', $matrix ) );
				$offset  = 0;

				if ( function_exists( 'imageconvolution' ) ) {
					imageconvolution( $image, $matrix, $divisor, $offset );
				}

				imagejpeg( $image, $resized_file, apply_filters( 'jpeg_quality', 90, 'edit_image' ) );
				imagedestroy( $image );
			}
		}

		// Re-attach filter
		add_filter( 'image_make_intermediate_size', [ $this, 'wps_sharpen_resized_file' ], 900 );

		return $resized_file;
	}


	function bf_google_analytics(){
		$bfps_ga	= get_option('blueflamingo_plugin_google_analytics_settings');
		if ( isset( $bfps_ga['activate_google_analytics'] ) && !empty( $bfps_ga['activate_google_analytics'] ) ) {
			if( !empty( $bfps_ga['google_analytics_position'] ) && ( $bfps_ga['google_analytics_position'] == 'Head' ) ){

				add_action( 'wp_head', array( $this, 'bf_google_analytics_output' ), 1 );

			}elseif( !empty( $bfps_ga['google_analytics_position'] ) && ( $bfps_ga['google_analytics_position'] == 'Footer' ) ){

				add_action( 'wp_footer', array($this, 'bf_google_analytics_output'), 1 );

			}else{

				add_action( 'wp_head', array($this, 'bf_google_analytics_output'), 1 );

			}
		}
	}

	function bf_google_analytics_output($value = ''){
		$bfps_ga		 = get_option('blueflamingo_plugin_google_analytics_settings');
		$ga_id			 = $bfps_ga['google_analytics_id'];
		$ua_id			 = $bfps_ga['universal_analytics_id'];
		//$tracking_method = $bfps_ga['google_analytics_tracking_method'];
		$tracking_method = 2;
		if( !empty($ga_id) ){
			if ( (! ( is_user_logged_in() && current_user_can( 'update_core' ) ) ) || !empty( $bfps_ga['google_analytics_logged_in'] ) ) {
			?>
<?php if( empty($tracking_method) || $tracking_method == 1 ){ ?>
	<!-- Google Analytics -->
	<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

		ga('create', '<?php echo $ga_id; ?>', 'auto');
		ga('send', 'pageview');
	</script>
	<!-- End Google Analytics -->
<?php }elseif( $tracking_method == 2){ ?>
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $ga_id; ?>"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());
		gtag('config', '<?php echo $ga_id; ?>');
	</script>
	<!-- End Global site tag (gtag.js) - Google Analytics -->

<?php }elseif($tracking_method == 3 ){
if( !empty($ua_id) ){?>
	<!-- Google Analytics -->
	<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

		ga('create', '<?php echo $ua_id; ?>', 'auto');
		ga('send', 'pageview');
	</script>
	<!-- End Google Analytics -->
<?php } ?>
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $ga_id; ?>"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());
		gtag('config', '<?php echo $ga_id; ?>');
	</script>
	<!-- End Global site tag (gtag.js) - Google Analytics -->

<?php }else{?>
	<!-- Google Analytics -->
	<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

		ga('create', '<?php echo $ga_id; ?>', 'auto');
		ga('send', 'pageview');
	</script>
	<!-- End Google Analytics -->
<?php } ?><?php
				echo $bfps_ga['google_analytics_metatag'];
			}
		}

	}

	function wp_add_user_colomn() {
		$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );
		if( !empty( $bfps_o['admin_user_registration_date'] ) ){
			//Register column
			add_filter( 'manage_users_columns', array($this, 'rudr_modify_user_table' ));
			add_filter( 'manage_users_custom_column', array($this, 'rudr_modify_user_table_row'), 10, 3 );
			add_filter( 'manage_users_sortable_columns', array($this, 'rudr_make_registered_column_sortable' ));
		}
	}

	function rudr_modify_user_table( $columns ) {
		// unset( $columns['posts'] ); // maybe you would like to remove default columns
		$columns['registration_date'] = 'Registration date'; // add new
		return $columns;
	}

	function rudr_modify_user_table_row( $row_output, $column_id_attr, $user ) {
		$date_format = 'j M, Y H:i';
		switch ( $column_id_attr ) {
			case 'registration_date' :
				return date( $date_format, strtotime( get_the_author_meta( 'registered', $user ) ) );
				break;
			default:
		}
		return $row_output;
	}

	function rudr_make_registered_column_sortable( $columns ) {
		return wp_parse_args( array( 'registration_date' => 'registered' ), $columns );
	}

	function bf_404_page() {
		$bfps_ep = get_option( 'blueflamingo_plugin_error_page_settings' );
		if( !empty( $bfps_ep['activate_404'] ) ){
			add_filter( '404_template', array( $this, 'bf_custom_404_template') );
		}
	}

	function bf_custom_404_template( $template ) {
		global $wp_query, $post;

		$bfps_ep 	 = get_option( 'blueflamingo_plugin_error_page_settings' );
		$url 		 = $bfps_ep['custom_404_page'];
		$custom_page = get_post( (int) $url );

		if ( ! is_a( $custom_page, 'WP_Post' ) ) {
			return $template;
		}

		$post = $custom_page;

		$wp_query->posts             = array( $post );
		$wp_query->queried_object_id = $post->ID;
		$wp_query->queried_object    = $post;
		$wp_query->post_count        = 1;
		$wp_query->found_posts       = 1;
		$wp_query->max_num_pages     = 0;
		$wp_query->is_404            = false;
		$wp_query->is_page           = true;
		$wp_query->is_singular	     = true;

		return get_page_template();
	}

	function mail_filter(){
		$bfps_er	= get_option( 'blueflamingo_plugin_email_redirect_settings' );
		if( !empty( $bfps_er['activate_email_redirect_staging_or_development'] ) || !empty( $bfps_er['activate_email_redirect_production'] ) ){
			add_filter('wp_mail', array($this, 'blueflamingo_override_mail_recipient'));
		}else{}
	}

	function blueflamingo_override_mail_recipient ( $args ) {
		$bfps_er	= get_option( 'blueflamingo_plugin_email_redirect_settings' );
		$bfps_g		= get_option( 'blueflamingo_plugin_general_settings' );
		if( !empty( $bfps_er['activate_email_redirect_staging_or_development'] ) ){

			$test_site_staging_url  = blueflamingo_decryption( $bfps_g['staging_url'] );
			$test_site_dev_url  	= blueflamingo_decryption( $bfps_g['dev_url'] );
			$test_site_email_id 	= $bfps_er['redirect_email_id'];
			$to      				= $args['to'];
			$html    				= $args['html'];
			$subject 				= $args['subject'];
			$message 				= $args['message'];

			if ( !empty($test_site_staging_url) || !empty($test_site_dev_url) ) {

				if ( (strpos(get_site_url(), $test_site_staging_url) || strpos(get_site_url(), $test_site_dev_url)) !== false ) {

					$subject  = '[TEST] ' . $subject;
					$to 	  = $test_site_email_id; //get_option('admin_email');
					$message  = 'DEVELOPMENT ENVIRONMENT.  THIS MESSAGE WOULD NORMALLY HAVE BEEN SENT TO: ' . $args['to'];
					$message .= PHP_EOL . $args['message'];
					$html 	 .= '<strong><em>DEVELOPMENT ENVIRONMENT. THIS MESSAGE WOULD NORMALLY HAVE BEEN SENT TO: ' . $args['to'] . '</em></strong>';

				}

			}

		}elseif( !empty( $bfps_er['activate_email_redirect_production'] ) ){

			$test_site_live_url = blueflamingo_decryption( $bfps_g['live_url'] );
			$test_site_email_id = $bfps_er['redirect_email_id'];
			$to      			= $args['to'];
			$html    			= $args['html'];
			$subject 			= $args['subject'];
			$message 			= $args['message'];
			if ( !empty($test_site_live_url) ) {

				if ( (strpos(get_site_url(), $test_site_live_url)) !== false ) {

					$subject  = '[TEST] ' . $subject;
					$to 	  = $test_site_email_id; //get_option('admin_email');
					$message  = 'DEVELOPMENT ENVIRONMENT.  THIS MESSAGE WOULD NORMALLY HAVE BEEN SENT TO: ' . $args['to'];
					$message .= PHP_EOL . $args['message'];
					$html 	 .= '<strong><em>DEVELOPMENT ENVIRONMENT. THIS MESSAGE WOULD NORMALLY HAVE BEEN SENT TO: ' . $args['to'] . '</em></strong>';

				}

			}

		}else{
			return;
		}

		$new_wp_mail = array(
			'to' => $to,
			'subject' => $subject,
			'message' => $message,
			'html' => $html,
			'headers' => $args['headers'],
			'attachments' => $args['attachments']
		);

		return $new_wp_mail;
	}

	function hide_google_recaptcha_logo() {
		$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );
		if( !empty( $bfps_o['hide_google_recaptcha_logo'] ) ){
			add_action( 'wp_head', function (){ ?><style>.grecaptcha-badge{visibility: collapse !important;}</style><?php } );
		}
	}

	function remove_recaptcha() {
		$bfps_g 	= get_option( 'blueflamingo_plugin_general_settings' );
		$test_site_staging_url = blueflamingo_decryption( blueflamingo_issetor( $bfps_g['staging_url'] ) );
		$test_site_dev_url = blueflamingo_decryption( blueflamingo_issetor( $bfps_g['dev_url'] ) );
		if ( !empty($test_site_staging_url) ) {
			if ( ( strpos(get_site_url(), $test_site_staging_url) ) !== false ) {
				$wpcf = get_option('wpcf7');
				if( !empty($wpcf['recaptcha']) ){
					unset($wpcf['recaptcha']);
					unset($wpcf['recaptcha_v2_v3_warning']);
					update_option('wpcf7',$wpcf);
				}else{
					return;
				}
			}
		}elseif ( !empty($test_site_dev_url) ) {
			if ( strpos( get_site_url(), $test_site_dev_url ) !== false ) {
				$wpcf = get_option('wpcf7');
				if( !empty($wpcf['recaptcha']) ){
					unset($wpcf['recaptcha']);
					unset($wpcf['recaptcha_v2_v3_warning']);
					update_option('wpcf7',$wpcf);
				}else{
					return;
				}
			}
		}else{
			return;
		}
	}

	function wp_password_change_notification_bf() {
		$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );
		if( !empty( $bfps_o['disable_admin_notifications_of_password_changes'] ) ){
			if ( !function_exists('wp_password_change_notification') ) :
				function wp_password_change_notification($user) {}
			endif;
		}
	}

	function show_all_meta_value() {
		$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );
		if( !empty( $bfps_o['Show_all_meta_fields'] ) ){
			add_action( 'all_admin_notices', array( $this, 'wpsnipp_show_all_custom_fields' ) );
		}
	}

	function wpsnipp_show_all_custom_fields() {
		if ( isset( $_GET['post'] ) ) {
			$post_id = absint( $_GET['post'] );
			?>
			<div id="message" class="updated">
				<h3>All post meta:</h3>
				<xmp><?php print_r( get_post_meta( $post_id ) ); ?></xmp>
			</div>
			<?php
		}
	}

	function show_description_for_post_types(){
		$bfps_pt = get_option( 'blueflamingo_plugin_post_types_settings' );
		if ( isset( $_GET['post_type'] )  && isset($bfps_pt[$_GET['post_type']]) && $bfps_pt[$_GET['post_type']]!='') { ?>
			<div class="notice notice-info" style="display:flex; align-items: center; border-left-color: #F06C4C;">
				<img src="<?=plugins_url( 'blueflamingo-logo-profile.png', dirname(__FILE__) );?>" width= "39px;" style="padding-right: 10px; margin: 3px 0;"/>
				<p><?=$bfps_pt[$_GET['post_type']]?></p>
			</div>
			<?php
		}
	}

	function show_description_for_post(){
		global $pagenow, $typenow;
		$bfpn_pt = get_option( 'blueflamingo_plugin_post_notice_settings');

		if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI']=="/wp-admin/edit.php" && isset($bfpn_pt['notice']) && $bfpn_pt['notice']!='') { ?>
			<div class="notice notice-info" style="display:flex; align-items: center; border-left-color: #F06C4C;">
				<img src="<?=plugins_url( 'blueflamingo-logo-profile.png', dirname(__FILE__) );?>" width= "39px;" style="padding-right: 10px; margin: 3px 0;"/>
				<p><?=$bfpn_pt['notice'];?></p>
			</div>
			<?php
		}
	}

	function blueflamingo_link( $links ) {
		$arr 	    = $links;
		$main_arr   = array();
		$main_arr[] = '<a href="'. admin_url( 'admin.php?page=blueflamingo_settings' ) .'">' . __('Settings', 'blue-flamingo-new-sdfdfr') . '</a>';
		return array_merge( $main_arr, $arr );
	}

	function prefix_append_blueflamingo_row( $links_array, $plugin_file_name, $plugin_data, $status ) {

		if ( strpos( $plugin_file_name, plugin_basename( blueflamingo_FILE ) ) !== false ) {
			//$links_array[] = '<a href="https://www.blueflamingo.co.uk/" target="_blank">'. __( 'UK', 'blue-flamingo-new-sdfdfr' ) .'</a>';
			//$links_array[] = '<a href="https://www.blueflamingo.ca/" target="_blank">'. __( 'Canada', 'blue-flamingo-new-sdfdfr' ) .'</a>';
		}

		return $links_array;
	}


	function blueflamingo_register_menu() {

		//Main menu defined
		add_menu_page(
			__( 'Blue Flamingo : Main', 'blue-flamingo-new-sdfdfr' ),
			__( 'Blue Flamingo', 'blue-flamingo-new-sdfdfr' ),
			'manage_options',
			'blueflamingo_settings',
			array( $this, 'blueflamingo_settings' ),
			blueflamingo_URL_IMAGES . '/bf-logo.svg',
			1
		);

		//Side menu defined
		add_submenu_page(
			'blueflamingo_settings',
			__( 'Settings', 'blue-flamingo-new-sdfdfr' ),
			__( 'Settings', 'blue-flamingo-new-sdfdfr' ),
			'manage_options',
			'blueflamingo_settings',
			array( $this, 'blueflamingo_settings' )
		);

		//Side menu defined
		add_submenu_page(
			'blueflamingo_settings',
			__( 'Plugins', 'blue-flamingo-new-sdfdfr' ),
			__( 'Plugins', 'blue-flamingo-new-sdfdfr' ),
			'manage_options',
			'blueflamingo_plugins',
			array( $this, 'blueflamingo_plugins' )
		);

		//Side menu defined
		add_submenu_page(
			'blueflamingo_settings',
			__( 'Notes', 'blue-flamingo-new-sdfdfr' ),
			__( 'Notes', 'blue-flamingo-new-sdfdfr' ),
			'manage_options',
			'blueflamingo_notes',
			array( $this, 'blueflamingo_notes_page' )
		);

		//Side menu defined
		add_submenu_page(
			'blueflamingo_settings',
			__( 'Shortcodes', 'blue-flamingo-new-sdfdfr' ),
			__( 'Shortcodes', 'blue-flamingo-new-sdfdfr' ),
			'manage_options',
			'blueflamingo_shortcodes',
			array( $this, 'blueflamingo_shortcodes_page' )
		);

	}


	function blueflamingo_settings() {
		include( blueflamingo_DIR_VIEWS . '/blue-flamingo-settings.php' );
	}


	function blueflamingo_plugins() {
		include( blueflamingo_DIR_VIEWS . '/blue-flamingo-plugins.php' );
	}


	function blueflamingo_notes_page() {
		echo 'Hello';
	}


	function blueflamingo_shortcodes_page() {
		echo 'Hello';
	}

	function bf_login_logo() {
		add_action( 'login_head', function (){
		$logo_url = blueflamingo_URL_IMAGES.'/bf-logo.png';
			echo '<style type="text/css">'.
				'h1 a {
					background-image:url('.$logo_url.') !important;
					line-height:inherit !important;
				}'.
			'</style>';
		});//.login .privacy-policy-page-link {display: none;}
		add_action( 'login_footer', function (){
			echo '<p style="text-align:center;padding-bottom:20px">For support, please contact <a href="mailto:support@blueflamingo.solutions">support@blueflamingo.solutions</a></p>';
		} );
	}

	function move_yoast_bottom(){
		$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );
		if( !empty( $bfps_o['move_yoast_bottom'] ) ){
			add_filter( 'wpseo_metabox_prio', 'bfyoasttobottom');
		}
	}

	function website_feedback(){

		$bfps_ad = get_option('blueflamingo_plugin_admin_display_settings');
		if ( isset( $bfps_ad['enable_website_feedback'] ) && !empty( $bfps_ad['feedback_destination'] ) ) {

			if(  isset( $bfps_ad['feedback_only_for_admin'] )){
				include_once(ABSPATH . 'wp-includes/pluggable.php');
				$current_user = wp_get_current_user();
				if ( isset($current_user->roles[0]) && "administrator" == $current_user->roles[0] ) {
                    add_action( 'admin_head', array( $this, 'bf_website_feedback_output' ), 1 );
                    add_action( 'wp_head', array( $this, 'bf_website_feedback_output' ), 1 );
                }

			}else{
				add_action( 'wp_head', array( $this, 'bf_website_feedback_output' ), 1 );
				add_action( 'admin_head', array( $this, 'bf_website_feedback_output' ), 1 );
			}

		}

	}

	function bf_website_feedback_output(){
		$bfps_ad	= get_option('blueflamingo_plugin_admin_display_settings');
		$destination	= $bfps_ad['feedback_destination'];
		if($destination == 'other'){
			$destination = $bfps_ad['feedback_destination_other'];
		}
		if( $destination == '66ffdf357cd29779d701143bv' ){
			$destination = '66ffdf357cd29779d701143b';
		}
	    $current_user = wp_get_current_user();
		$email	= $current_user->user_email;
		$fullName = $current_user->user_firstname.' '.$current_user->user_lastname ;

	?>

	<script>
		window.markerConfig = {
			project: '<?php echo $destination;?>',
			source: 'snippet',
			reporter: {
				email: '<?php echo $email;?>',
				fullName: '<?php echo $fullName;?>',
			},
		};
	</script>

	<script>
		!function(e,r,a){if(!e.__Marker){e.__Marker={};var t=[],n={__cs:t};["show","hide","isVisible","capture","cancelCapture","unload","reload","isExtensionInstalled","setReporter","setCustomData","on","off"].forEach(function(e){n[e]=function(){var r=Array.prototype.slice.call(arguments);r.unshift(e),t.push(r)}}),e.Marker=n;var s=r.createElement("script");s.async=1,s.src="https://edge.marker.io/latest/shim.js";var i=r.getElementsByTagName("script")[0];i.parentNode.insertBefore(s,i)}}(window,document);
	</script>

	<?php }

	function smart_plugin_manager_hide(){
		$bfps_ad = get_option('blueflamingo_plugin_admin_display_settings');
		if ( isset( $bfps_ad['enable_spm'] ) ) {

			// Disable plugins auto-update UI elements.
			add_filter( 'plugins_auto_update_enabled', '__return_false' );

			// Disable themes auto-update UI elements.
			add_filter( 'themes_auto_update_enabled', '__return_false' );

			//Hide pluhin from list
			add_filter( 'all_plugins',
				function ( $plugins ) {
					$shouldHide = ! array_key_exists( 'show_all', $_GET );
					if ( $shouldHide ) {
						$hiddenPlugins = [ 'autoupdater/autoupdater.php', 'autoupdater.php', ];
						foreach ( $hiddenPlugins as $hiddenPlugin ) {
							unset( $plugins[ $hiddenPlugin ] );
						}
					}
					return $plugins;
			});
		}
	}

	function email_testing(){

		$bfps_ad = get_option('blueflamingo_plugin_admin_display_settings');

		if ( isset( $bfps_ad['enable_email_testing'] ) && !empty( $bfps_ad['testing_email_address'] ) ) {

			add_filter('wp_mail', array($this, 'email_override_mail_recipient'));

		}

	}

	function email_override_mail_recipient ( $args ) {

		$bfps_ad = get_option('blueflamingo_plugin_admin_display_settings');
		if(strpos($args['message'], $bfps_ad['testing_email_address']) === false){
			$to      = $args['to'];
		}else{
			$to      = $bfps_ad['testing_email_address'];
		}
			$html    = $args['html'];
			$subject = $args['subject'];
			$message = $args['message'];

		$new_wp_mail = array(
			'to' => $to,
			'subject' => $subject,
			'message' => $message,
			'html' => $html,
			'headers' => $args['headers'],
			'attachments' => $args['attachments']
		);

		return $new_wp_mail;
	}

	function auto_delete_standard_theme(){
		$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );
		if( !empty( $bfps_o['auto_delete_standard_theme'] ) ){
			$all_themes = wp_get_themes();
			$my_theme = wp_get_theme();

			foreach($all_themes as $theme){
				$themeAuthor = esc_html( $theme->get( 'Author' ) );
				if($themeAuthor == "the WordPress team" && $my_theme != $theme->name){
					delete_theme($theme->stylesheet);
				}
			}
		}
	}

	function log_plugin_upgrade(){
		$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );
		if( !empty( $bfps_o['log_plugin_upgrade'] ) ){
			add_action( 'upgrader_pre_install', array( $this,'bf_upgrader_pre_install'), 10, 2 );
			add_action( 'upgrader_process_complete', array( $this, 'bf_plugins_update_completed' ), 10,2 );

		}
	}

	function bf_upgrader_pre_install( $return, $plugin ){
		if ( ! is_wp_error( $return ) ) {


				$plugins = get_plugin_data( ABSPATH . 'wp-content/plugins/'. $plugin['plugin'],true,true);
				$plugin_current_version = $plugins['Version'];
				// does not work
				$option_name = $plugin['plugin'] . '_plugin_version_before_update';

				$r = update_option( $option_name, $plugin_current_version );

		}
		remove_action( current_filter(), __FUNCTION__, 99 );
		return $return;
	}

	function bf_plugins_update_completed( $upgrader_object, $options ) {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name = 'bf_plugin_version_details_table';
		$sql = "CREATE TABLE $table_name (
		  id bigint(50) NOT NULL AUTO_INCREMENT,
		  plugin_name varchar(100) DEFAULT '' NOT NULL,
		  plugin_filepath varchar(200) DEFAULT '' NOT NULL,
		  plugin_new_version varchar(20) DEFAULT '' NOT NULL,
		  plugin_old_version varchar(20) DEFAULT '' NOT NULL,
		  upgraded_datetime datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		  PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		//codes for SPM update starts
		// If an update has taken place and the updated type is plugins and the plugins element exists
		if ( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugin'] ) ) {

			$plugin_data = get_plugin_data( ABSPATH . 'wp-content/plugins/'. $options['plugin'],true,true);
			$plugin_new_version = $plugin_data['Version'];
			$plugin_name = $plugin_data['Name'];

			// Get the old version of the plugin
			$old_version = get_option($options['plugin'] . '_plugin_version_before_update');

			$wpdb->insert(
				$table_name,
				array(
					'plugin_name' => $plugin_name,
					'plugin_filepath' => $options['plugin'],
					'plugin_new_version' => $plugin_new_version,
					'plugin_old_version' => $old_version,

				)
			);
			delete_option($options['plugin'] . '_plugin_version_before_update');

		}
		//codes for SPM update ends

		// If an update has taken place and the updated type is plugins and the plugins element exists
		if ( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
			foreach( $options['plugins'] as $plugin ) {
				// Check to ensure it's my plugin
				// Get the old version of the plugin
        		$plugin_old_version = get_option($plugin . '_plugin_version_before_update');

				// do stuff here
				$plugin_data = get_plugin_data( ABSPATH . 'wp-content/plugins/'. $plugin,true,true);
				$plugin_new_version = $plugin_data['Version'];
				$plugin_name = $plugin_data['Name'];
				$wpdb->insert(
					$table_name,
					array(
						'plugin_name' => $plugin_name,
						'plugin_filepath' => $plugin,
						'plugin_new_version' => $plugin_new_version,
						'plugin_old_version' => $plugin_old_version,

					)
				);
				delete_option($plugin . '_plugin_version_before_update');
			}
		}

		$querystr = "SELECT * FROM bf_plugin_version_details_table WHERE upgraded_datetime >= DATE_SUB(NOW(), INTERVAL 45 DAY) order by upgraded_datetime desc";
		$lt_plugin_name = $wpdb->get_results($querystr, ARRAY_A);

		$post_content .= '<!DOCTYPE html>
		<html>
		<head>
		<link href="https://fonts.googleapis.com/css2?family=Oxygen:wght@400;700&display=swap" rel="stylesheet">
		<style>
			body{
				background-color:#FBFBFB;
				font-family: "Oxygen", sans-serif;
			}
			#outer-container{
				width:700px;
				margin:0 auto;
				color:#666666;
			}
			#logo{
				width:45px;
				margin:10px auto;
			}
			#container{
				background-color:#fff;
				padding:15px;
				min-height:550px;
			}
			h1{
				font-size: 20px;
				font-weight: 400;
				padding-top: 20px;
				padding-bottom: 10px;
			}
			th, td {
				padding: 8px 20px 5px 0px;
				font-weight: normal;
				text-align: left;
				margin-left: 20px;
				font-size: 14px;
			}
			p{
				text-align:center;
				font-size: 14px;
				padding:20px;
			}
			.plgname{
				width:150px;
			}
			</style>
			</head>';
		$post_content.= '<body><div id="outer-container">
		<div id="logo"><img src="'.plugin_dir_url( __DIR__ ) .'assets/images/bf-logo.png" alt="Blueflamingo" width="35px"></div>
		<div id="container">
		<h1>Plugin Update History</h1>
			<table id="plugins">
			  <tr>
				<th>Sl No.</th>
				<th class="plgname">Plugin Name</th>
				<th>Old Version</th>
				<th>New Version</th>
				<th>Date</th>
			  </tr>';
			  $i = 1;
			foreach($lt_plugin_name as $plg){
				$post_content.= '<tr>
					<td>'.$i.'</td>
					<td class="plgname">'.$plg['plugin_name'].'</td>
					<td>'.$plg['plugin_old_version'].'</td>
					<td>'.$plg['plugin_new_version'].'</td>
					<td>'.date("d-m-Y H:i", strtotime($plg['upgraded_datetime'])).'</td>
				 </tr>';
				$i++;
			}

		$post_content.= '</table></div>
		<p>&copy; Blue Flamingo</p>
		</div>
		</body>
		</html>';

		$fp = fopen(ABSPATH . "/bf_plugin_log.php","w");
		fwrite($fp,$post_content);
		fclose($fp);

	}

	function limit_ability_to_add_new_plugin(){
		$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );
		if( !empty( $bfps_o['limit_ability_to_add_new_plugin'] ) ){

			add_filter( 'site_transient_update_plugins',array($this, 'bf_custom_disable_plugin_updates' ) );// Disable plugin updates
			add_action( 'admin_footer', array($this,'bf_custom_add_new_plugin_alert' ) );// Show an alert when clicking "Add New" plugin button
			add_action( 'admin_head', array($this,'bf_custom_hide_plugin_menu_items' ) );// Hide all menu items within the plugins section in the WordPress admin
		}
	}

	function bf_custom_disable_plugin_updates( $value ) {
		if ( isset( $value ) && is_object( $value ) ) {
			$value->no_update = array();
		}
		return $value;
	}
	function bf_custom_add_new_plugin_alert() {
		$screen = get_current_screen();

		if ( $screen->id === 'plugins' || $screen->id === 'plugins-network' ) {  ?>
			<script>
				document.addEventListener('DOMContentLoaded', function() {
					var addButton = document.querySelector('.page-title-action');
					if (addButton) {
						addButton.addEventListener('click', function(e) {
							e.preventDefault();
							alert('Plugin updates on this website are managed by Blue Flamingo. To avoid conflicts with existing site functionality we have disabled plugin updates for our maintenance clients. We kindly ask you to contact us at support@blueflamingo.solutions should you wish to add a new plugin.');
						});
					}
				});
			</script>
			<?php
		}
	}
	function bf_custom_hide_plugin_menu_items() { ?>
		<style>
			#menu-plugins ul.wp-submenu li {
				display: none;
			}
		</style>
		<?php
	}

	function bf_whatConverts(){
		$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );
		if(!empty( $bfps_o['id_whatConverts'])){

			add_action( 'wp_footer', function() {
				$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );?>
				<script src="//scripts.iconnode.com/<?php echo $bfps_o['id_whatConverts'];?>.js"></script>
			<?php

			});
		}
	}

}

new Blue_Flamingo_Admin();

endif;