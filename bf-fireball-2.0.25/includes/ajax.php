<?php

// Exit if accessed directly
if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('Blue_Flamingo_Ajax')):

	class Blue_Flamingo_Ajax
	{

		public function __construct()
		{

			// Saves Production, Staging and Dev environment URLs
			add_action('wp_ajax_blueflamingo_save_url', [$this, 'blueflamingo_save_url']);

			// Dismisses Stripe notice
			add_action('wp_ajax_blueflamingo_wc_stripe_notice_dismiss', [$this, 'blueflamingo_wc_stripe_notice_dismiss']);

			// Saves current page, to be used when page is refreshed
			add_action('wp_ajax_blueflamingo_current_page', [$this, 'blueflamingo_current_page']);

			// shows a preview for Default Featured Image functionality
			add_action('wp_ajax_dfi_change_preview', [$this, 'blueflamingo_dfi']);

			// AJAX handler for lazy loading Health Check content
			add_action('wp_ajax_blueflamingo_load_health_check', [$this, 'blueflamingo_load_health_check']);

		// AJAX handler for installing core plugins
		add_action('wp_ajax_bf_install_core_plugins', function() {
			if (!isset($_POST['nonce'])) {
				wp_send_json_error(array('message' => 'Missing nonce.'));
			}
			if (!wp_verify_nonce($_POST['nonce'], 'blueflamingo_core_plugins')) {
				wp_send_json_error(array('message' => 'Invalid nonce.'));
			}
			if (!current_user_can('activate_plugins')) {
				wp_send_json_error(array('message' => 'You do not have permission to install plugins.'));
			}
			if (!class_exists('BF_Core_Plugins_Manager')) {
				require_once dirname(__FILE__) . '/class-core-plugins-manager.php';
			}
			try {
				BF_Core_Plugins_Manager::install_core_plugins();
				wp_send_json_success();
			} catch (Throwable $e) {
				$msg = 'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
				wp_send_json_error(array('message' => $msg));
			}
		});

		// AJAX handler for bulk plugin activation
		add_action('wp_ajax_bf_bulk_activate_plugins', [$this, 'bf_bulk_activate_plugins']);
	}		
	
	public function blueflamingo_dfi()
		{
			global $BlueFlamingoPlugin;
			if (! empty($_POST['image_id']) && absint($_POST['image_id'])) {
				$img_id = absint($_POST['image_id']);
				$DFI    = new Blue_Flamingo_Default_Featured_Image();
				echo $DFI->preview_image($img_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			die(); // ajax call..
		}

		public function blueflamingo_current_page()
		{
			$check = sanitize_text_field($_POST['nonce']);
			if (! wp_verify_nonce($check, 'blueflamingo_nonce')) {
				exit(__('Wrong Nonce', ''));
			}

			$tab   = sanitize_text_field($_POST['tab']);
			$value = sanitize_text_field($_POST['value']);

			$cp       = ! empty(get_option("blueflamingo_plugin_all_settings")) ? get_option("blueflamingo_plugin_all_settings") : [];
			$main_arr = $cp['current_tab_page'];

			if (! empty($tab) && ! empty($value)) {
				$cp['current_tab_page'][$tab] = $value;
				update_option("blueflamingo_plugin_all_settings", $cp);
			}
			die();
		}

		public function blueflamingo_save_url()
		{
			$check = sanitize_text_field($_POST['nonce']);
			if (! wp_verify_nonce($check, 'blueflamingo_nonce')) {
				exit("Wrong nonce");
			}

			$bfps_g                = ! empty(get_option('blueflamingo_plugin_general_settings')) ? get_option('blueflamingo_plugin_general_settings') : [];
			$bfps_g['live_url']    = blueflamingo_encryption($_REQUEST["live_url"]);
			$bfps_g['staging_url'] = blueflamingo_encryption($_REQUEST["staging_url"]);
			$bfps_g['dev_url']     = blueflamingo_encryption($_REQUEST["dev_url"]);
			update_option('blueflamingo_plugin_general_settings', $bfps_g);

			die();
		}

		public function blueflamingo_wc_stripe_notice_dismiss()
		{
			$check = sanitize_text_field($_POST['nonce']);
			if (! wp_verify_nonce($check, 'blueflamingo_nonce')) {
				exit("Wrong nonce");
			}

			$bfps_all                  = get_option('blueflamingo_plugin_all_settings');
			$bfps_all['custom_notice'] = 1;
			update_site_option('blueflamingo_plugin_all_settings', $bfps_all);

			die();
		}

		public function blueflamingo_load_health_check()
		{
			// Check if nonce is provided
			if (! isset($_POST['nonce'])) {
				wp_send_json_error('No security nonce provided');
				return;
			}

			$check = sanitize_text_field($_POST['nonce']);
			if (! wp_verify_nonce($check, 'blueflamingo_nonce')) {
				wp_send_json_error('Security check failed');
				return;
			}

			// Ensure functions file is loaded
			if (! function_exists('getMySQLVersion')) {
				include_once plugin_dir_path(__FILE__) . 'functions.php';
			}

			// Wrap content generation in try-catch to handle any errors gracefully
			try {
				// Generate Health Check content
				ob_start();
?>
				<h3>System Information</h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">PHP version</th>
						<td><?php echo phpversion(); ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">Wordpress Version</th>
						<td><?php global $wp_version;
							echo $wp_version; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">MySQL Version</th>
						<td><?php echo function_exists('getMySQLVersion') ? getMySQLVersion() : 'Function not available'; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">PHP Memory Limit</th>
						<td><?php
							if (function_exists('getPHPMemoryLimit')) {
								$memory_info = getPHPMemoryLimit();
								echo 'Limit: ' . $memory_info['limit'] . ' | Usage: ' . $memory_info['usage'] . ' (<span class="' . $memory_info['status_class'] . '">' . $memory_info['usage_percentage'] . '</span>)';
							} else {
								echo 'Function not available';
							}
							?></td>
					</tr>
					<tr valign="top">
						<th scope="row">PHP Max Execution Time</th>
						<td><?php echo function_exists('getPHPMaxExecutionTime') ? getPHPMaxExecutionTime() : ini_get('max_execution_time') . ' seconds'; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">Website File Size</th>
						<td><?php
							// Set the path to your WordPress installation
							if (function_exists('getDirectorySize') && function_exists('formatSize')) {
								$total_size = getDirectorySize(ABSPATH);
								if ($total_size > 0) {
									$formatted_size = formatSize($total_size);

									// Add status indicator based on size
									$status_class = '';
									$status_text  = '';
									$size_gb      = $total_size / (1024 * 1024 * 1024);

									if ($size_gb > 10) {
										$status_class = 'health-status-error';
										$status_text  = ' - Very large website files';
									} elseif ($size_gb > 5) {
										$status_class = 'health-status-warning';
										$status_text  = ' - Large website files';
									} else {
										$status_class = 'health-status-good';
										$status_text  = ' - Normal size';
									}
									echo $formatted_size . ' <span class="' . $status_class . '">' . $status_text . '</span>';
								} else {
									echo '<span class="health-status-error">Unable to calculate file size - Check directory permissions</span>';
								}
							} else {
								echo '<span class="health-status-error">Directory size functions not available</span>';
							}
							?></td>
					</tr>
					<tr valign="top">
						<th scope="row">Database Size</th>
						<td><?php echo function_exists('getDatabaseSize') ? getDatabaseSize() : 'Function not available'; ?></td>
					</tr>
				</table>
				<h3>Security & Updates</h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">SSL Certificate</th>
						<td><?php echo function_exists('checkSSLCertificate') ? checkSSLCertificate() : 'Function not available'; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">WordPress Core Updates</th>
						<td><?php echo function_exists('checkWordPressCoreUpdates') ? checkWordPressCoreUpdates() : 'Function not available'; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">Plugin Updates</th>
						<td><?php echo function_exists('getOutdatedPlugins') ? getOutdatedPlugins() : 'Function not available'; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">Theme Updates</th>
						<td><?php echo function_exists('getThemeUpdates') ? getThemeUpdates() : 'Function not available'; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">Auto-Updates Status</th>
						<td><?php echo function_exists('getAutoUpdatesStatus') ? getAutoUpdatesStatus() : 'Function not available'; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">File Permissions</th>
						<td><?php echo function_exists('checkFilePermissions') ? checkFilePermissions() : 'Function not available'; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">Broken Plugins</th>
						<td><?php echo function_exists('checkBrokenPlugins') ? checkBrokenPlugins() : 'Function not available'; ?></td>
					</tr>
				</table>
				<h3>Configuration</h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Inactive Plugins</th>
						<td><?php echo function_exists('getInactivePlugins') ? getInactivePlugins() : 'Function not available'; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">Debug Mode Status</th>
						<td><?php echo function_exists('getDebugModeStatus') ? getDebugModeStatus() : 'Function not available'; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">WordPress Constants</th>
						<td><?php echo function_exists('checkWordPressConstants') ? checkWordPressConstants() : 'Function not available'; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">WP Options Table Analysis</th>
						<td>
							<?php if (function_exists('optionTableDetails')): ?>
								<div class="wp-options-analysis">
									<h4 style="margin: 0 0 10px 0; color: #333;">Database Performance Metrics</h4>
									<table class="wp-options-table">
										<thead>
											<tr>
												<th>Metric</th>
												<th>Value</th>
												<th>Status</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$optionTableDetails = optionTableDetails();
											$summary_count      = 0;
											foreach ($optionTableDetails as $opt) {
												// Show summary metrics first (first 3 items)
												if ($summary_count < 3) {
											?>
													<tr class="summary-row">
														<td><strong><?php echo esc_html($opt['metric']); ?></strong></td>
														<td><strong><?php echo esc_html($opt['value']); ?></strong></td>
														<td><span class="<?php echo esc_attr($opt['status_class']); ?>"><?php echo esc_html($opt['status_text']); ?></span></td>
													</tr>
											<?php
													$summary_count++;
												}
											}
											?>
										</tbody>
									</table>

									<h4 style="margin: 20px 0 10px 0; color: #333;">Largest Autoloaded Options</h4>
									<p style="margin: 0 0 10px 0; font-size: 12px; color: #666;">These options are loaded on every page request. Large values can slow down your site.</p>
									<table class="wp-options-table large-options">
										<thead>
											<tr>
												<th>Option Name</th>
												<th>Size</th>
												<th>Status</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$detail_count = 0;
											foreach ($optionTableDetails as $opt) {
												// Show detailed options (items 4+)
												if ($detail_count >= 3) {
											?>
													<tr>
														<td><code><?php echo esc_html($opt['metric']); ?></code></td>
														<td><?php echo esc_html($opt['value']); ?></td>
														<td><span class="<?php echo esc_attr($opt['status_class']); ?>"><?php echo esc_html($opt['status_text']); ?></span></td>
													</tr>
											<?php
												}
												$detail_count++;
											}
											?>
										</tbody>
									</table>

									<div class="wp-options-help" style="margin-top: 15px; padding: 10px; background: #f9f9f9; border-left: 4px solid #0073aa; font-size: 12px;">
										<strong>Performance Tips:</strong>
										<ul style="margin: 5px 0 0 20px;">
											<li>Autoloaded data over 500KB can slow down your site</li>
											<li>Consider disabling unused plugins that create large autoloaded options</li>
											<li>Some cache plugins may create large autoloaded options - check if they can be optimized</li>
											<li>Contact your developer if you see critical issues highlighted in red</li>
										</ul>
									</div>
								</div>
							<?php else: ?>
								<span class="health-status-error">WP Options analysis function not available</span>
							<?php endif; ?>
						</td>
					</tr>
				</table>

				<h3>Recent Plugin Updates</h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Latest Plugin Updates</th>
						<td>
							<?php
							if (function_exists('getLatestPluginUpdates')) {
								$latest_updates = getLatestPluginUpdates(10);
								if (! empty($latest_updates)) {
									echo '<table class="latest-plugin-updates">';
									echo '<thead><tr><th>Plugin</th><th>Version</th><th>Date</th></tr></thead>';
									echo '<tbody>';
									foreach ($latest_updates as $update) {
										echo '<tr>';
										echo '<td>' . esc_html($update['plugin_name']) . '</td>';
										echo '<td>' . esc_html($update['new_version']) . '</td>';
										echo '<td>' . esc_html($update['upgraded_datetime']) . '</td>';
										echo '</tr>';
									}
									echo '</tbody></table>';
								} else {
									echo '<span class="health-status-good">No recent plugin updates recorded</span>';
								}
							} else {
								echo '<span class="health-status-error">Latest plugin updates function not available</span>';
							}
							?>
						</td>
					</tr>
				</table>
<?php

				$content = ob_get_clean();
				wp_send_json_success($content);
			} catch (Exception $e) {
				// Clean any output buffer
				if (ob_get_level()) {
					ob_end_clean();
				}
				wp_send_json_error('An error occurred while generating Health Check data: ' . $e->getMessage());
			} catch (Error $e) {
				// Handle PHP 7+ fatal errors
				if (ob_get_level()) {
					ob_end_clean();
				}
				wp_send_json_error('A fatal error occurred while generating Health Check data. Please check server logs.');
			}

		die();
	}

	/**
	 * AJAX handler for bulk plugin activation
	 */
	public function bf_bulk_activate_plugins()
	{
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'blueflamingo_nonce')) {
			wp_send_json_error('Invalid nonce');
		}

		// Check permissions
		if (!current_user_can('activate_plugins')) {
			wp_send_json_error('You do not have permission to activate plugins');
		}

		// Get plugins to activate
		$plugins = isset($_POST['plugins']) ? $_POST['plugins'] : array();
		
		if (empty($plugins) || !is_array($plugins)) {
			wp_send_json_error('No plugins specified');
		}

		// Load required WordPress functions
		if (!function_exists('activate_plugin')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$activated = array();
		$errors = array();

		foreach ($plugins as $plugin) {
			$plugin = sanitize_text_field($plugin);
			
			// Check if plugin exists
			$plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
			if (!file_exists($plugin_file)) {
				$plugin_name = basename($plugin, '.php');
				$errors[] = "Plugin file not found: {$plugin_name}";
				continue;
			}

			// Activate the plugin
			$result = activate_plugin($plugin, '', false, true);
			
			if (is_wp_error($result)) {
				$plugin_data = get_plugin_data($plugin_file);
				$plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;
				$errors[] = $plugin_name . ': ' . $result->get_error_message();
			} else {
				$activated[] = $plugin;
			}
		}

		if (!empty($errors)) {
			wp_send_json_error(array(
				'message' => 'Some plugins failed to activate',
				'activated' => $activated,
				'errors' => $errors
			));
		}

		wp_send_json_success(array(
			'message' => 'Plugins activated successfully',
			'activated' => $activated
		));
	}
}

new Blue_Flamingo_Ajax();

endif;