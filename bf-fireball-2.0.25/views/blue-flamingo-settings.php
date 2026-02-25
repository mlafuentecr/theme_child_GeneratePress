<?php //include( blueflamingo_DIR_VIEWS . '/blue-flamingo-other-info.php' ); ?>
<?php

/* This is called when Blueflamingo is displayed */

?>
<style>
	.bf-loading {
		position: fixed;
		width: 100%;
		height: 100%;
		top: 0;
		left: 0;
		background: rgba(255, 255, 255, 0.8);
		z-index: 9999;
		display: flex;
		justify-content: center;
		align-items: center;
	}
	.bf-loading-spinner {
		border: 8px solid rgba(255, 255, 255, 0.3);
		border-top: 8px solid #0073aa;
		border-radius: 50%;
		width: 40px;
		height: 40px;
		animation: spin 1s linear infinite;
	}
	@keyframes spin {
		0% { transform: rotate(0deg); }
		100% { transform: rotate(360deg); }
	}
</style>
<div class="wrap toplevel_page_blueflamingo">
	<h1 class="wp-heading-inline"><?php echo get_admin_page_title(); ?></h1>
	<hr class="wp-header-end">
	<?php /* Error display removed for production */ ?>
	<p></p>
	<ul class="nav nav-tabs" data-tab="settings">
		<!-- General tab Trigger -->
		<li id="tab-1" data-tab="General" class="<?php echo bf_active_tab('General', 'settings'); ?>">General</li>
		<!-- Options tab Trigger -->
		<li id="tab-2" data-tab="Options" class="<?php echo bf_active_tab('Options', 'settings'); ?>">Options</li>
		<!-- Email Redirect tab Trigger -->
		<li id="tab-3" data-tab="Email Redirect" class="<?php echo bf_active_tab('Email Redirect', 'settings'); ?>">Email Redirect</li>
		<!-- Google Analytics tab Trigger -->
		<li id="tab-4" data-tab="Google Analytics" class="<?php echo bf_active_tab('Google Analytics', 'settings'); ?>">Google Analytics</li>
		<!-- 404 Page tab Trigger -->
		<li id="tab-5" data-tab="404 Page" class="<?php echo bf_active_tab('404 Page', 'settings'); ?>">404 Page</li>
		<!-- Admin Display tab Trigger -->
		<li id="tab-6" data-tab="Admin Display" class="<?php echo bf_active_tab('Admin Display', 'settings'); ?>">Admin Display</li>
		<!-- Health Check tab Trigger -->
		<li id="tab-7" data-tab="Health Check" class="<?php echo bf_active_tab('Health Check', 'settings'); ?>">Health Check</li>
		<!-- Post Types tab Trigger -->
		<li id="tab-8" data-tab="Post Types" class="<?php echo bf_active_tab('Post Types', 'settings'); ?>">Post Types</li>
		<!-- Post Types tab Trigger -->
		<li id="tab-9" data-tab="Post Notice" class="<?php echo bf_active_tab('Post Notice', 'settings'); ?>">Post Notice</li>
		<!-- Security tab Trigger -->
		<li id="tab-11" data-tab="Security" class="<?php echo bf_active_tab('Security', 'settings'); ?>">Security</li>
	</ul>

	<div class="tab-content">

		<!-- General tab -->
		<div class="tab-pane tab-1 <?php echo bf_active_tab('General', 'settings'); ?>">
			<table class="form-table">
				<?php $bfps_g = get_option( 'blueflamingo_plugin_general_settings' ); ?>
				<tr valign="top"><th scope="row">Enter Live site url</th>
					<td>
					<input type="text" class="live_url" value="<?php echo blueflamingo_decryption( bf_ifnotempty( $bfps_g['live_url'] ) ); ?>"><br/><i>excluding https://www.</i>
					</td>
				</tr>
				<tr valign="top"><th scope="row">Enter staging site url</th>
					<td>
					<input type="text" class="staging_url" value="<?php echo blueflamingo_decryption( bf_ifnotempty( $bfps_g['staging_url'] ) ); ?>"><br/><i>excluding https://www.</i>
					</td>
				</tr>
				<tr valign="top"><th scope="row">Enter Developement site url</th>
					<td>
					<input type="text" class="dev_url" value="<?php echo blueflamingo_decryption( bf_ifnotempty( $bfps_g['dev_url'] ) ); ?>"><br/><i>excluding https://www.</i>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary BFSAVE" value="Save" />
				<span class="add_loader_gif_two"></span>
			</p>
		</div>

		<!-- Options tab -->
		<div class="tab-pane tab-2 <?php echo bf_active_tab('Options', 'settings'); ?>">
			<form method="post" action="options.php">
				<?php settings_fields( 'blueflamingo_plugin_options_settings_group' ); ?>
				<table class="form-table">
					<?php $bfps_o = get_option( 'blueflamingo_plugin_options_settings' ); ?>
					<tr valign="top">
						<th scope="row">Activate Stripe test mode <span class="dashicons dashicons-editor-help stripe-help-tooltip" title="Stripe will use same staging and development website URL from General tab"></span></th>
						<td><input type="checkbox" name="blueflamingo_plugin_options_settings[activate_stripe_test_mode]" value="1" <?php checked( 1, isset($bfps_o['activate_stripe_test_mode']) ) ?>/></td>
					</tr>
					<tr valign="top">
						<th scope="row">Activate test mode<br/>(WP Simple Pay)</th>
						<td><input type="checkbox" name="blueflamingo_plugin_options_settings[activate_wpsimplepay_testmode]" value="1" <?php checked( 1, isset($bfps_o['activate_wpsimplepay_testmode']) ) ?>/></td>
					</tr>
					<tr valign="top" class="seperator">
						<th scope="row">Hide Google Recaptcha logo</th>
						<td><input type="checkbox" name="blueflamingo_plugin_options_settings[hide_google_recaptcha_logo]" value="1" <?php checked( 1, isset($bfps_o['hide_google_recaptcha_logo']) ) ?>/></td>
					</tr>
					<tr valign="top" class="seperator">
						<th scope="row">Show all meta fields</th>
						<td><input type="checkbox" name="blueflamingo_plugin_options_settings[Show_all_meta_fields]" value="1" <?php checked( 1, isset($bfps_o['Show_all_meta_fields']) ) ?>/></td>
					</tr>
					<tr valign="top" class="seperator">
						<th scope="row">Disable admin notifications of password changes</th>
						<td><input type="checkbox" name="blueflamingo_plugin_options_settings[disable_admin_notifications_of_password_changes]" value="1" <?php checked( 1, isset($bfps_o['disable_admin_notifications_of_password_changes']) ) ?>/></td>
					</tr>
					<tr valign="top" class="seperator">
						<th scope="row">Enable User Registration Date</th>
						<td><input type="checkbox" name="blueflamingo_plugin_options_settings[admin_user_registration_date]" value="1" <?php checked( 1, isset($bfps_o['admin_user_registration_date']) ) ?>/></td>
					</tr>
					<tr valign="top" class="seperator">
						<th scope="row">Enable JSON Basic Authentication</th>
						<td><input type="checkbox" name="blueflamingo_plugin_options_settings[json_basic_authentication]" value="1" <?php checked( 1, isset($bfps_o['json_basic_authentication']) ) ?>/></td>
					</tr>
					<!-- <tr valign="top" class="seperator">
						<th scope="row">Sharpen Images</th>
						<td><input type="checkbox" name="blueflamingo_plugin_options_settings[sharpen_images]" value="1" <?php checked( 1, isset($bfps_o['sharpen_images']) ) ?>/></td>
					</tr> -->

					<tr valign="top" class="seperator">
						<th scope="row">Default featured image</th>
						<td>
						<?php
							$DFI = new Blue_Flamingo_Default_Featured_Image();
							echo $DFI->settings_html();
						?>
						</td>
					</tr>

					<!--tr valign="top" class="seperator">
						<th scope="row">Move SEO Yoast Plugin to bottom</th>
						<td><input type="checkbox" name="blueflamingo_plugin_options_settings[move_yoast_bottom]" value="1" <?php checked( 1, isset($bfps_o['move_yoast_bottom']) ) ?>/></td>
					</tr-->

					<tr valign="top" class="seperator">
						<th scope="row">Auto Delete Standard Theme</th>
						<td><input type="checkbox" name="blueflamingo_plugin_options_settings[auto_delete_standard_theme]" value="1" <?php checked( 1, isset($bfps_o['auto_delete_standard_theme']) ) ?>/></td>
					</tr>

					<tr valign="top" class="seperator">
						<th scope="row">Log Plugin Upgrade in table</th>
						<td><input type="checkbox" name="blueflamingo_plugin_options_settings[log_plugin_upgrade]" value="1" <?php checked( 1, isset($bfps_o['log_plugin_upgrade']) ) ?>/></td>
					</tr>

					<tr valign="top" class="seperator">
						<th scope="row">Limit ability to add new plugins</th>
						<td><input type="checkbox" name="blueflamingo_plugin_options_settings[limit_ability_to_add_new_plugin]" value="1" <?php checked( 1, isset($bfps_o['limit_ability_to_add_new_plugin']) ) ?>/></td>
					</tr>

					<tr valign="top" class="seperator">
						<th scope="row">WhatConverts ID</th>
						<td><input type="text" name="blueflamingo_plugin_options_settings[id_whatConverts]" value="<?php echo bf_ifnotempty( $bfps_o['id_whatConverts'] ); ?>"></td>
					</tr>

					<!-- WordPress Debug Options -->
					<tr valign="top" class="seperator">
						<th scope="row" colspan="2"><h3 style="margin: 0;">WordPress Debug Options</h3></th>
					</tr>

					<?php

					// Helper to determine effective state
					function bf_effective_debug_option($key, $constant, $default = false, $options = []) {
						if (isset($options[$key])) {
							return (bool)$options[$key];
						} elseif (defined($constant)) {
							return constant($constant);
						}
						return $default;
					}
					?>

					<tr valign="top">
						<th scope="row">Enable WP_DEBUG</th>
						<td>
							<input type="hidden" name="blueflamingo_plugin_options_settings[wp_debug]" value="0">
							<input type="checkbox"
								name="blueflamingo_plugin_options_settings[wp_debug]"
								value="1"
								<?php checked(1, bf_effective_debug_option('wp_debug', 'WP_DEBUG', false, $bfps_o)); ?> />
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							Enable WP_DEBUG_LOG
							<p class="description">(Logs debug messages to wp-content/debug.log)</p>
						</th>
						<td>
							<input type="hidden" name="blueflamingo_plugin_options_settings[wp_debug_log]" value="0">
							<input type="checkbox"
								name="blueflamingo_plugin_options_settings[wp_debug_log]"
								value="1"
								<?php checked(1, bf_effective_debug_option('wp_debug_log', 'WP_DEBUG_LOG', false, $bfps_o)); ?> />
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							Enable WP_DEBUG_DISPLAY
							<p class="description">(Shows debug messages on the page)</p>
						</th>
						<td>
							<input type="hidden" name="blueflamingo_plugin_options_settings[wp_debug_display]" value="0">
							<input type="checkbox"
								name="blueflamingo_plugin_options_settings[wp_debug_display]"
								value="1"
								<?php checked(1, bf_effective_debug_option('wp_debug_display', 'WP_DEBUG_DISPLAY', true, $bfps_o)); ?> />
						</td>
					</tr>

				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="Save" />
				</p>
			</form>

		</div>

		<!-- Email Redirect tab -->
		<div class="tab-pane tab-3 <?php echo bf_active_tab('Email Redirect', 'settings'); ?>">
			<form method="post" action="options.php">
				<?php settings_fields( 'blueflamingo_plugin_email_redirect_settings_group' ); ?>
				<table class="form-table">
					<?php $bfps_er = get_option( 'blueflamingo_plugin_email_redirect_settings' ); ?>
					<tr valign="top">
						<th scope="row">Activate Mail Redirect<br/>(Staging or Development)</th>
						<td><input type="checkbox" class="special-checkbox" name="blueflamingo_plugin_email_redirect_settings[activate_email_redirect_staging_or_development]" value="1" <?php checked( 1, isset($bfps_er['activate_email_redirect_staging_or_development']) ) ?>/></td>
					</tr>
					<tr valign="top">
						<th scope="row">Activate Mail Redirect<br/>(Production)</th>
						<td><input type="checkbox" class="special-checkbox" name="blueflamingo_plugin_email_redirect_settings[activate_email_redirect_production]" value="1" <?php checked( 1, isset($bfps_er['activate_email_redirect_production']) ) ?>/></td>
					</tr>
					<tr valign="top">
						<th scope="row">Enter Redirect Email id</th>
						<td><input type="text" name="blueflamingo_plugin_email_redirect_settings[redirect_email_id]" value="<?php echo bf_ifnotempty( $bfps_er['redirect_email_id'] ); ?>"></td>
					</tr>
					<tr valign="top">
						<th scope="row">Note</th>
						<td>Email redirect will use same staging and developement website url from General tab</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="Save" />
				</p>
			</form>
		</div>

		<!-- Google Analytics tab -->
		<div class="tab-pane tab-4 <?php echo bf_active_tab('Google Analytics', 'settings'); ?>">
			<form method="post" action="options.php">
				<?php settings_fields( 'blueflamingo_plugin_google_analytics_settings_group' ); ?>
				<table class="form-table">
					<?php $bfps_ga = get_option( 'blueflamingo_plugin_google_analytics_settings' ); ?>
					<tr valign="top">
						<th scope="row">Activate Google Analytics</th>
						<td><input type="checkbox" name="blueflamingo_plugin_google_analytics_settings[activate_google_analytics]" value="1" <?php checked( 1, isset($bfps_ga['activate_google_analytics']) ) ?>/></td>
					</tr>

					<tr valign="top">
						<th scope="row">Google Analytics ID</th>
						<td>
							<input type="text" name="blueflamingo_plugin_google_analytics_settings[google_analytics_id]" value="<?php echo bf_ifnotempty( $bfps_ga['google_analytics_id'] ); ?>">
						</td>
					</tr>

					<!--tr valign="top" id="showUAID" <?php if( bf_ifnotempty( $bfps_ga['google_analytics_tracking_method'] ) != 3){ echo 'style="display:none;"'; }?>>
						<th scope="row">Universal Analytics ID</th>
						<td>
							<input type="text" name="blueflamingo_plugin_google_analytics_settings[universal_analytics_id]" value="<?php echo bf_ifnotempty( $bfps_ga['universal_analytics_id'] ); ?>">
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">Tracking Method</th>
						<td>
							<?php if( false ): ?>
							<input type="radio" class="tracking_method" id="analyticsjs" name="blueflamingo_plugin_google_analytics_settings[google_analytics_tracking_method]" value="1" <?php echo ( bf_ifnotempty( $bfps_ga['google_analytics_tracking_method'] ) == 1)? 'checked="checked"' : ''; ?> ><label for="analyticsjs" style="position: relative;bottom: 2px;"><code>analytics.js</code> <span class="gap-note">:</span> <a target="_blank" rel="noopener noreferrer" href="https://developers.google.com/analytics/devguides/collection/analyticsjs/">Universal Analytics</a> - Default</label><br/>
							<?php endif; ?>
							<p style="margin-bottom: 5px;"></p>
							<input type="radio" class="tracking_method" id="gtagjs" name="blueflamingo_plugin_google_analytics_settings[google_analytics_tracking_method]" value="2" <?php echo ( bf_ifnotempty( $bfps_ga['google_analytics_tracking_method'] ) == 2)? 'checked="checked"' : ''; ?> ><label for="gtagjs" style="position: relative;bottom: 2px;"><code>gtag.js</code> <span class="gap-note">:</span> <a target="_blank" rel="noopener noreferrer" href="https://developers.google.com/analytics/devguides/collection/gtagjs/">Global Site Tag</a></label><br/><p style="margin-bottom: 11px;"></p>
							<?php if( false ): ?>
							<input type="radio" class="tracking_method" id="bothtmjs" name="blueflamingo_plugin_google_analytics_settings[google_analytics_tracking_method]" value="3" <?php echo ( bf_ifnotempty( $bfps_ga['google_analytics_tracking_method'] ) == 3)? 'checked="checked"' : ''; ?> ><label for="bothtmjs" style="position: relative;bottom: 2px;">Both(<code>analytics.js</code> and <code>gtag.js</code> )</label><br/>
							<?php endif; ?>
						</td>
					</tr-->

					<tr valign="top">
						<th scope="row">Position</th>
						<td>
							<label><input type="radio" name="blueflamingo_plugin_google_analytics_settings[google_analytics_position]" value="Head" <?php echo ( bf_ifnotempty( $bfps_ga['google_analytics_position'] ) == 'Head')? 'checked="checked"' : ''; ?>/> Header - Default</label><p></p>
							<label><input type="radio" name="blueflamingo_plugin_google_analytics_settings[google_analytics_position]" value="Footer" <?php echo ( bf_ifnotempty( $bfps_ga['google_analytics_position'] ) == 'Footer')? 'checked="checked"' : ''; ?>/> Footer</label>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">Include logged-in users.</th>
						<td><input type="checkbox" name="blueflamingo_plugin_google_analytics_settings[google_analytics_logged_in]" value="1" <?php checked( 1, isset($bfps_ga['google_analytics_logged_in']) ) ?>/></td>
					</tr>

					<tr valign="top">
						<th scope="row">Extra Meta Tag (optional)</th>
						<td>
							<textarea name="blueflamingo_plugin_google_analytics_settings[google_analytics_metatag]" rows="8" cols="70"><?php echo bf_ifnotempty( $bfps_ga['google_analytics_metatag'] ); ?></textarea>
						</td>
					</tr>

				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="Save" />
				</p>
			</form>
		</div>

		<!-- 404 Page tab -->
		<div class="tab-pane tab-5 <?php echo bf_active_tab('404 Page', 'settings'); ?>">
			<form method="post" action="options.php">
				<?php settings_fields( 'blueflamingo_plugin_error_page_settings_group' ); ?>
				<table class="form-table">
					<?php $bfps_ep = get_option( 'blueflamingo_plugin_error_page_settings' ); ?>
					<tr valign="top">
						<th scope="row">Activate 404 page</th>
						<td><input type="checkbox" name="blueflamingo_plugin_error_page_settings[activate_404]" value="1" <?php checked( 1, isset($bfps_ep['activate_404']) ) ?>/></td>
					</tr>
					<tr valign="top">
						<th scope="row">Select Page</th>
						<td>
							<select name='blueflamingo_plugin_error_page_settings[custom_404_page]'>
								<option value='0'><?php _e('Select a Page', 'textdomain'); ?></option>
								<?php $pages = get_pages(); ?>
								<?php foreach( $pages as $page ) { ?>
									<option value='<?php echo $page->ID; ?>' <?php selected( bf_ifnotempty($bfps_ep['custom_404_page']), $page->ID ); ?> ><?php echo $page->post_title; ?></option>
								<?php } ?>
							</select>
						</td>
					</tr>

				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="Save" />
				</p>
			</form>
		</div>

		<!-- Admin Display tab -->
		<div class="tab-pane tab-6 <?php echo bf_active_tab('Admin Display', 'settings'); ?>">
			<form method="post" action="options.php">
				<?php settings_fields( 'blueflamingo_plugin_admin_display_settings_group' ); ?>
				<table class="form-table">
					<?php $bfps_ad = get_option( 'blueflamingo_plugin_admin_display_settings' ); ?>
					<tr valign="top">
						<th scope="row">Users</th>
						<td><input type="checkbox" name="blueflamingo_plugin_admin_display_settings[users_admin_display_options]" value="1" <?php checked( 1, isset($bfps_ad['users_admin_display_options']) ) ?>/></td>
					</tr>
					<tr valign="top">
						<th scope="row">Post and Page Metaboxes</th>
						<td><input type="checkbox" name="blueflamingo_plugin_admin_display_settings[post_and_page_metaboxes_admin_display_options]" value="1" <?php checked( 1, isset($bfps_ad['post_and_page_metaboxes_admin_display_options']) ) ?>/></td>
					</tr>

					<!--tr valign="top" class="seperator">
						<th scope="row">Website Feedback :-</th>
					</tr-->
					<tr valign="top" class="seperator">
						<th scope="row">Enable Marker.io</th>
						<td><input type="checkbox" id="enable_website_feedback" name="blueflamingo_plugin_admin_display_settings[enable_website_feedback]" value="1" <?php checked( 1, isset($bfps_ad['enable_website_feedback']) ) ?>/>
							<div class="markerio_config" style="display: <?= !empty($bfps_ad['enable_website_feedback']) ? 'block' : 'none'; ?>;">
								<table class="form-table">
									<tr valign="top" class="seperator">
										<th scope="row">Marker.io Destination</th>
										<td>
											<select id="feedback_destination" name='blueflamingo_plugin_admin_display_settings[feedback_destination]'>
												<?php
												$feedback_destination_IDs = array(
													'696178330024dcf967112bc4'  => 'ClickUp Marker.io Connector',
													'6659bf8d13a1d8a3810631f3'  => 'BF S',
													'66ffdf357cd29779d701143bv' => 'BF DEV',
													'6422f8f994f76ddbdd4f4676'  => 'BF M',
													'66ffe5fbf80d380c2bfba484'  => 'BF B',
													'66ffe623979b8eee4ca7dfc6'  => 'BF R',
													'6691283bda3ecfae79b72379'  => 'Reiser iShotIt',
												);
												$current = bf_ifnotempty($bfps_ad['feedback_destination']);
												$first = true;
												foreach( $feedback_destination_IDs as $IDMain => $feedbackName ) {
													$selected = ($first && empty($current)) ? 'selected="selected"' : selected($current, $IDMain, false);
													echo '<option value="' . esc_attr($IDMain) . '" ' . $selected . '>' . esc_html($feedbackName) . '</option>';
													$first = false;
												}
												?>
												<option value="other" <?php selected($current, 'other'); ?>>Other</option>
											</select>
											<input type="text" id="other_destination" name="blueflamingo_plugin_admin_display_settings[feedback_destination_other]" value="<?= $bfps_ad['feedback_destination_other'] ?>" style="width:300px; display: <?= $current == 'other' ? 'inline' : 'none'; ?>;" placeholder="Enter your Marker.io Destination ID here"/>
										</td>
									</tr>
									<tr valign="top" class="seperator">
										<th scope="row">Only Display for Admin Users</th>
										<td><input type="checkbox" name="blueflamingo_plugin_admin_display_settings[feedback_only_for_admin]" value="1" <?php checked( 1, isset($bfps_ad['feedback_only_for_admin']) ) ?>/></td>
									</tr>
								</table>
								<p>When using ClickUp Marker.io Connector as the destination, create a ClickUp automation on the Marker.io Connector ClickUp board that triggers when the Project Website URL custom field is set to a specific website URL such as https://stg-mysite-staging.kinsta.cloud/. The automation will then move the task to the appropriate client project board.</p>
							</div>
						</td>
					</tr>

					<!--tr valign="top" class="seperator">
						<th scope="row">Email Testing :-</th>
					</tr-->
					<tr valign="top" class="seperator">
						<th scope="row">Email Testing</th>
						<td><input type="checkbox" name="blueflamingo_plugin_admin_display_settings[enable_email_testing]" value="1" <?php checked( 1, isset($bfps_ad['enable_email_testing']) ) ?>/></td>
					</tr>
					<tr valign="top" class="seperator">
						<th scope="row">Testing Email Address</th>
						<td><input type="text" name="blueflamingo_plugin_admin_display_settings[testing_email_address]" value="<?php  if(!empty( $bfps_ad['testing_email_address'])){ echo $bfps_ad['testing_email_address'];} ?>"></td>
					</tr>
					<!--tr valign="top" class="seperator">
						<th scope="row" >Enable SPM</th>
						<td><input type="checkbox" name="blueflamingo_plugin_admin_display_settings[enable_spm]" value="1" <?php checked( 1, isset($bfps_ad['enable_spm']) ) ?>/></td>
					</tr-->
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="Save" />
				</p>
			</form>
		</div>

		<!-- Health Check tab -->
		<div class="tab-pane tab-7 <?php echo bf_active_tab('Health Check', 'settings'); ?>">
			<div id="health-check-loading" class="health-check-placeholder">
				<div class="health-check-loader">
					<div class="loader-spinner"></div>
					<p>Loading Health Check data... This may take a moment.</p>
					<p style="font-size: 12px; color: #666; margin-top: 10px;">Analyzing system performance, database health, and security status...</p>
				</div>
			</div>
			<div id="health-check-content" style="display: none;"></div>
		</div>

		<!-- Post Types Tab -->
		<div class="tab-pane tab-8 <?php echo bf_active_tab('Post Types', 'settings'); ?>">
			<form method="post" action="options.php">
				<?php settings_fields( 'blueflamingo_plugin_post_types_settings_group' ); ?>
				<table class="form-table">
					<?php $bfps_er = get_option( 'blueflamingo_plugin_post_types_settings' );
					?>
					<tr>
						<th>Post Type</th>
						<th>Description / Note</th>
					</tr>
					<tr valign="top">
						<?php
						$args = array(
							'public'   => true,
							'_builtin' => false,
						);
						$post_types = get_post_types($args, 'objects');

						if(!empty($post_types)){
							foreach ($post_types as $post_type) {
								//$typename = $post_type->rewrite['slug']; 	?>
								<tr valign="top" class="seperator">
									<th scope="row" style="font-weight: normal;"><?=$post_type->labels->menu_name;?></th>
									<td>
										<textarea name="blueflamingo_plugin_post_types_settings[<?=$post_type->name;?>]" rows="3" cols="70"><?php if(!empty( $bfps_er[$post_type->name])){ echo $bfps_er[$post_type->name]; } ?></textarea>
									</td>
								</tr>
								<?php
							}
						}?>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="Save" />
				</p>
			</form>
		</div>

		<!-- Post Types Tab -->
		<div class="tab-pane tab-9 <?php echo bf_active_tab('Post Notice', 'settings'); ?>">
			<form method="post" action="options.php">
			<?php settings_fields( 'blueflamingo_plugin_post_notice_settings_group' ); ?>
				<table class="form-table">
					<?php $bfpn_er = get_option( 'blueflamingo_plugin_post_notice_settings' );?>
					<tr>
						<th>Description / Note</th>
					</tr>
					<tr valign="top">
						<td>
							<textarea name="blueflamingo_plugin_post_notice_settings[notice]" rows="3" cols="100"><?php if(!empty( $bfpn_er['notice'])){ echo $bfpn_er['notice']; } ?></textarea>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="Save" />
				</p>
			</form>
		</div>

		<!-- Security tab -->
		<div class="tab-pane tab-11 <?php echo bf_active_tab('Security', 'settings'); ?>">
			<form method="post" action="options.php">
			<?php settings_fields( 'blueflamingo_plugin_security_settings_group' ); ?>
				<table class="form-table">
					<?php $bfps_security = get_option( 'blueflamingo_plugin_security_settings' ); ?>

					<!-- Referrer-Policy -->
					<tr valign="top">
						<th scope="row">Referrer-Policy</th>
						<td>
							<input type="hidden" name="blueflamingo_plugin_security_settings[referrer_policy_enabled]" value="0">
							<input type="checkbox" name="blueflamingo_plugin_security_settings[referrer_policy_enabled]" value="1" <?php checked(1, isset($bfps_security['referrer_policy_enabled']) ? $bfps_security['referrer_policy_enabled'] : 1); ?>>
							<input type="text" name="blueflamingo_plugin_security_settings[referrer_policy]" value="<?php echo isset($bfps_security['referrer_policy']) ? esc_attr($bfps_security['referrer_policy']) : 'no-referrer-when-downgrade'; ?>" class="regular-text">
						</td>
					</tr>

					<!-- Strict-Transport-Security -->
					<tr valign="top">
						<th scope="row">Strict-Transport-Security</th>
						<td>
							<input type="hidden" name="blueflamingo_plugin_security_settings[hsts_enabled]" value="0">
							<input type="checkbox" name="blueflamingo_plugin_security_settings[hsts_enabled]" value="1" <?php checked(1, isset($bfps_security['hsts_enabled']) ? $bfps_security['hsts_enabled'] : 1); ?>>
							<input type="text" name="blueflamingo_plugin_security_settings[hsts]" value="<?php echo isset($bfps_security['hsts']) ? esc_attr($bfps_security['hsts']) : 'max-age=2592000'; ?>" class="regular-text">
						</td>
					</tr>

					<!-- X-Content-Type-Options -->
					<tr valign="top">
						<th scope="row">X-Content-Type-Options</th>
						<td>
							<input type="hidden" name="blueflamingo_plugin_security_settings[content_type_options_enabled]" value="0">
							<input type="checkbox" name="blueflamingo_plugin_security_settings[content_type_options_enabled]" value="1" <?php checked(1, isset($bfps_security['content_type_options_enabled']) ? $bfps_security['content_type_options_enabled'] : 1); ?>>
							<input type="text" name="blueflamingo_plugin_security_settings[content_type_options]" value="<?php echo isset($bfps_security['content_type_options']) ? esc_attr($bfps_security['content_type_options']) : 'nosniff'; ?>" class="regular-text">
						</td>
					</tr>

					<!-- X-Frame-Options -->
					<tr valign="top">
						<th scope="row">X-Frame-Options</th>
						<td>
							<input type="hidden" name="blueflamingo_plugin_security_settings[frame_options_enabled]" value="0">
							<input type="checkbox" name="blueflamingo_plugin_security_settings[frame_options_enabled]" value="1" <?php checked(1, isset($bfps_security['frame_options_enabled']) ? $bfps_security['frame_options_enabled'] : 1); ?>>
							<input type="text" name="blueflamingo_plugin_security_settings[frame_options]" value="<?php echo isset($bfps_security['frame_options']) ? esc_attr($bfps_security['frame_options']) : 'SAMEORIGIN'; ?>" class="regular-text">
						</td>
					</tr>

					<!-- X-XSS-Protection -->
					<tr valign="top">
						<th scope="row">X-XSS-Protection</th>
						<td>
							<input type="hidden" name="blueflamingo_plugin_security_settings[xss_protection_enabled]" value="0">
							<input type="checkbox" name="blueflamingo_plugin_security_settings[xss_protection_enabled]" value="1" <?php checked(1, isset($bfps_security['xss_protection_enabled']) ? $bfps_security['xss_protection_enabled'] : 1); ?>>
							<input type="text" name="blueflamingo_plugin_security_settings[xss_protection]" value="<?php echo isset($bfps_security['xss_protection']) ? esc_attr($bfps_security['xss_protection']) : '1; mode=block'; ?>" class="regular-text">
						</td>
					</tr>

					<!-- Permissions-Policy -->
					<tr valign="top">
						<th scope="row">Permissions-Policy</th>
						<td>
							<input type="hidden" name="blueflamingo_plugin_security_settings[permissions_policy_enabled]" value="0">
							<input type="checkbox" name="blueflamingo_plugin_security_settings[permissions_policy_enabled]" value="1" <?php checked(1, isset($bfps_security['permissions_policy_enabled']) ? $bfps_security['permissions_policy_enabled'] : 1); ?>>
							<input type="text" name="blueflamingo_plugin_security_settings[permissions_policy]" value="<?php echo isset($bfps_security['permissions_policy']) ? esc_attr($bfps_security['permissions_policy']) : 'autoplay=*,geolocation=(self),camera=(self),fullscreen=(self),geolocation=(self),microphone=(self)'; ?>" class="regular-text">
						</td>
					</tr>

					<!-- Content-Security-Policy -->
					<tr valign="top">
						<th scope="row">Content-Security-Policy</th>
						<td>
							<input type="hidden" name="blueflamingo_plugin_security_settings[csp_enabled]" value="0">
							<input type="checkbox" name="blueflamingo_plugin_security_settings[csp_enabled]" value="1" <?php checked(1, isset($bfps_security['csp_enabled']) ? $bfps_security['csp_enabled'] : 0); ?> style="vertical-align: top;">
							<textarea name="blueflamingo_plugin_security_settings[csp]" rows="15" cols="105"><?php echo isset($bfps_security['csp']) ? esc_attr($bfps_security['csp']) : "default-src 'self' https: data: blob:;
script-src 'self' https: 'unsafe-inline' 'unsafe-eval' blob: data: https://*.googletagmanager.com https://*.google-analytics.com https://*.g.doubleclick.net https://*.marker.io https://*.linkedin.com https://*.licdn.com https://*.hs-scripts.com https://*.hscollectedforms.net https://*.hubspot.com https://*.termly.io;
style-src 'self' https: 'unsafe-inline';
img-src 'self' https: data: blob: https://*.google-analytics.com https://*.googletagmanager.com https://*.linkedin.com https://*.licdn.com https://*.marker.io https://*.hubspot.com https://*.termly.io;
font-src 'self' https: data:;
connect-src 'self' https: wss: https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com https://*.marker.io https://*.linkedin.com https://*.licdn.com https://*.hubapi.com https://*.hubspot.com https://*.termly.io;
frame-src 'self' https: https://*.termly.io https://*.googletagmanager.com https://*.marker.io https://*.hubspot.com https://*.linkedin.com https://*.licdn.com;
media-src 'self' https: blob:;
object-src 'none';
base-uri 'self';
form-action 'self' https://*.hubspot.com;
frame-ancestors 'none';
upgrade-insecure-requests;"; ?></textarea>
							<br>
							<label style="margin-top: 10px; display: block;">
								<input type="checkbox" name="blueflamingo_plugin_security_settings[csp_report_only]" value="1" <?php checked(1, isset($bfps_security['csp_report_only']) ? $bfps_security['csp_report_only'] : 0); ?>>
								Enable Report-Only Mode (Content-Security-Policy-Report-Only)
							</label>
							<div style="margin-top: 10px;">
								<a href="#" class="button view-csp-logs">View CSP Violation Logs</a>
								<a href="#" class="button delete-csp-logs">Delete Logs</a>
							</div>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="Save" />
				</p>
			</form>
		</div>

	</div>
</div>

<script>
	jQuery(document).ready(function($) {
		$('#enable_website_feedback').change(function() {
			if($(this).is(':checked')) {
				$('.markerio_config').show();
				// Select the first option in feedback_destination
				$('#feedback_destination').prop('selectedIndex', 0).trigger('change');
			} else {
				$('.markerio_config').hide();
				// Clear Marker.io config fields when hiding
				$('#feedback_destination').val('');
				$('#other_destination').val('').hide();
				$('input[name="blueflamingo_plugin_admin_display_settings[feedback_destination_other]"]').val('');
				$('input[name="blueflamingo_plugin_admin_display_settings[feedback_only_for_admin]"]').prop('checked', false);
			}
		})
		$('#feedback_destination').change(function() {
			if($(this).val() === 'other') {
				$('#other_destination').show();
			} else {
				$('#other_destination').val('').hide();
			}
		});
	});
</script>