<?php
/* This is called when Blueflamingo -> Plugins menu is displayed */
?>
<div class="wrap toplevel_page_blueflamingo">
	<h1 class="wp-heading-inline"><?php echo get_admin_page_title(); ?></h1>
	<hr class="wp-header-end">
	<?php settings_errors(); ?>
	<p></p>
	<ul class="nav nav-tabs" data-tab="plugins">
		<!-- Plugin Manager tab Trigger -->
		<li id="tab-7" data-tab="Plugin Manager" class="<?php echo bf_active_tab('Plugin Manager', 'plugins'); ?>">Plugin Manager</li> <!-- One Click Plugin Install tab Trigger -->
		<li id="tab-8" data-tab="One Click Plugin Install" class="<?php echo bf_active_tab('One Click Plugin Install', 'plugins'); ?>">One Click Plugin Install</li>
	</ul>
	<div class="tab-content">
		<!-- Plugin Manager tab -->
		<div class="tab-pane tab-7 <?php echo bf_active_tab('Plugin Manager', 'plugins'); ?>">
			<form method="post" action="options.php">
				<?php settings_fields('blueflamingo_plugin_plugin_manager_group'); ?>
				<?php $bfp_pm = get_option('blueflamingo_plugin_plugin_manager'); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Activate Plugin Manager</th>
						<td><input type="checkbox" name="blueflamingo_plugin_plugin_manager[activate_plugin_manager]" value="1" <?php checked(1, isset($bfp_pm['activate_plugin_manager'])) ?> /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Deactivate Plugin on Stage and Dev environments</th>
						<td>
							<?php
							$all_plugins = get_plugins();
							$html = '';
							foreach ($all_plugins as $name => $slug) {
								if ($slug['Name'] != 'Blue Flamingo') {
									$nm = $slug['Name'];
									$html .= "<label for='deactivate-plugin-on-stage-and-dev-environments-" . sanitize_title($nm) . "'><input id='deactivate-plugin-on-stage-and-dev-environments-" . sanitize_title($nm) . "' type='checkbox' name='blueflamingo_plugin_plugin_manager[deactivate_plugin_on_stage_and_dev_environments][$name]' value='1' " . checked(1, isset($bfp_pm['deactivate_plugin_on_stage_and_dev_environments'][$name]), false) . "/> $nm</label><br/>";
								} else {
									$html .= '';
								}
							}
							if ((count($all_plugins) - 1) != 0) {
								echo $html;
							} else {
								echo 'No Plugins to display';
							}
							?>
						</td>
					</tr>
					<tr valign="top" class="seperator">
						<th scope="row">Hide Plugin Update</th>
						<td id="plugin_hide_update">
							<?php
							$all_plugins = get_plugins();
							$all_plugins_updates = get_plugin_updates();
							$html = '';
							foreach ($all_plugins as $name => $slug) {

								if ($slug['Name'] != 'Blue Flamingo') {
									$nm = $slug['Name'];
									$html .= "<label for='hide-plugin-update-" . sanitize_title($nm) . "'><input id='hide-plugin-update-" . sanitize_title($nm) . "' type='checkbox' name='blueflamingo_plugin_plugin_manager[hide_plugin_update][$name]' value='1' " . checked(1, isset($bfp_pm['hide_plugin_update'][$name]), false) . "/> $nm</label><br/>";
								} else {
									$html .= '';
								}
							}
							if ((count($all_plugins) - 1) != 0) {
								echo $html;
							} else {
								echo 'No Plugins to display';
							}
							?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Note</th>
						<td>Plugin Manager will use same staging and developement website url from General tab</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="Save" />
				</p>
			</form>
		</div> <!-- One Click Plugin Install tab -->
		<div class="tab-pane tab-8 <?php echo bf_active_tab('One Click Plugin Install', 'plugins'); ?>">
			<div class="all_plugins_list"><?php
				$plug = get_plugins();
				$style = '<style>';
				$numItems = count($plug);
				$i = 0;
				foreach ($plug as $xx => $yy) {
					if (!is_plugin_active($xx)) {
						$style .= '.all_plugins_list span[basename="' . $xx . '"]{border-bottom: 1px dashed;}';
					}
				}
				// Prevent underline styling for licensed plugins loaded via AJAX
				$style .= '.all_plugins_list span[class*="licensed-used-plugins"][class*="-title"] { border-bottom: none !important; }';
				$style .= '
						.unified-plugins-container {
							margin-bottom: 20px;
						}
						.plugin-type-badge {
							display: inline-block;
							padding: 1px 5px;
							border-radius: 2px;
							font-size: 10px;
							font-weight: normal;
							margin-left: 8px;
							opacity: 0.8;
						}
						.plugin-item {
							margin-bottom: 5px;
							padding: 8px;
							border-radius: 3px;
							border: 1px solid #e5e5e5;
						}
						.plugin-item.free {
							background: #fafafa;
						}
						.plugin-item.licensed {
							background: #f9f9f9;
						}
						@keyframes rotation {
							from {
								transform: rotate(0deg);
							}
							to {
								transform: rotate(359deg);
							}
						}
						';
				$style .= '</style>';
				echo $style;
				?> <!-- Core Plugins Section -->
				<div class="core-plugins-container" style="margin-bottom: 30px;">
					<h3 style="margin-bottom: 15px; font-size: 16px; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 5px;">
						<span class="dashicons dashicons-star-filled" style="color: #3498db; margin-right: 8px;"></span>
						Core Plugins
					</h3>
					<div id="core-plugins-list" style="background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #e9ecef;">
						<?php
						// Define core plugins that go in the top section
						$corePlugins = BF_Core_Plugins_Manager::get_core_plugins();

						// Sort core plugins alphabetically by name
						ksort($corePlugins);

						// Display core plugins
						foreach ($corePlugins as $name => $val) {
							$row = bf_active_plugins($name, $val['slug'], $val['basename'], $val['type']);
							echo $row;
						}
						?>
						<!-- Core licensed plugins will be loaded here via AJAX -->
						<div id="core-licensed-plugins-container"></div>
					</div>
				</div>

				<!-- All Plugins Section -->
				<div class="all-plugins-container">
					<h3 style="margin-bottom: 15px; font-size: 16px; color: #2c3e50; border-bottom: 2px solid #27ae60; padding-bottom: 5px;">
						<span class="dashicons dashicons-admin-plugins" style="color: #27ae60; margin-right: 8px;"></span>
						All Plugins
					</h3>
					<div id="all-plugins-list">
						<?php
						$commonPlugins = BF_Core_Plugins_Manager::get_common_plugins();

						// Sort all plugins alphabetically by name
						ksort($commonPlugins);

						// Display free plugins (licensed will be added via JavaScript in alphabetical order)
						foreach ($commonPlugins as $name => $val) {
							$row = bf_active_plugins($name, $val['slug'], $val['basename'], $val['type']);
							echo $row;
						}
						?>
					</div>

					<!-- Licensed plugins loading message -->
					<div id="licensed-plugins-loading" style="padding: 15px; text-align: center; color: #666; font-style: italic;">
						<p><span class="dashicons dashicons-update" style="animation: rotation 2s infinite linear;"></span> Loading licensed plugins from GitHub repository...</p>
					</div>

					<div id="licensed-plugins-container" style="display: none;">
						<!-- Licensed plugins will be loaded here via AJAX and inserted alphabetically -->
					</div>
				</div>
			</div>

			<!-- Unified Actions Section -->
			<p class="submit">
				<input type="submit" class="button-primary OC_PLUGIN_INSTALL" value="Install Selected Plugins" />
				<span class="add_loader_gif"></span>
			<div class="plugin-log"></div>
			</p>

			<script type="text/javascript">
				jQuery(document).ready(function($) {
					// Load licensed plugins on page load
					loadLicensedPluginsInOneClick();

					function loadLicensedPluginsInOneClick() {
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'bf_get_licensed_plugins',
								nonce: '<?php echo wp_create_nonce('blueflamingo_licensed_nonce'); ?>'
							},
							success: function(response) {
								$('#licensed-plugins-loading').hide();
								if (response.success && response.data.plugins.length > 0) {
									// Sort licensed plugins alphabetically by name
									var sortedPlugins = response.data.plugins.sort(function(a, b) {
										return a.name.localeCompare(b.name);
									});
									// Define core plugin names for categorization
									// These plugins should NOT appear in the GitHub licensed plugins list
									var corePluginNames = ['Duplicate Page', 'Imsanity', 'Ninja Forms', 'SEOPress', 'Termly'];

									// Define plugins that should be excluded from GitHub loading (already in core)
									var excludeFromGitHub = ['ninja-forms', 'duplicate-page', 'imsanity', 'wp-seopress', 'uk-cookie-consent'];
									// Insert each licensed plugin in its correct section
									$.each(sortedPlugins, function(index, plugin) {
										// Skip plugins that are already in core plugins section
										if (excludeFromGitHub.indexOf(plugin.slug) !== -1) {
											return true; // Continue to next iteration
										}

										var statusIcon = '';

										if (plugin.active) {
											statusIcon = '<span class="dashicons dashicons-yes" style="color: #46b450;"></span>';
										} else if (plugin.installed) {
											statusIcon = '<span class="dashicons dashicons-yes" style="color: orange;"></span>';
										} else {
											statusIcon = '<input type="checkbox" name="LicensedPluginInstall" value="' + plugin.slug + '" basename="' + plugin.basename + '" pluginname="' + plugin.name + '" data-install-type="licensed">';
										}

										var pluginHtml = '<div class="' + plugin.slug + '" style="margin-bottom: 5px; padding: 8px; border: 1px solid #e5e5e5; border-radius: 3px; background: #f9f9f9;">';
										pluginHtml += '<label for="licensed-used-plugins-' + plugin.slug + '">';
										pluginHtml += statusIcon;
										pluginHtml += '<span class="licensed-used-plugins-' + plugin.slug + '-title" basename="' + plugin.basename + '">' + plugin.name + ' <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: 600; margin-left: 8px; text-transform: uppercase; letter-spacing: 0.5px;">LICENSED</span></span>';
										pluginHtml += '</label>';
										pluginHtml += '</div>';

										// Check if this is a core plugin based on name matching
										var isCore = corePluginNames.some(function(coreName) {
											return plugin.name.toLowerCase().includes(coreName.toLowerCase()) ||
												coreName.toLowerCase().includes(plugin.name.toLowerCase());
										});

										if (isCore) {
											// Add to core plugins section
											$('#core-licensed-plugins-container').append(pluginHtml);
										} else {
											// Add to all plugins section in alphabetical order
											var inserted = false;
											$('#all-plugins-list > div').each(function() {
												var currentPluginName = $(this).find('span[class*="-title"]').text().replace(/ (FREE|LICENSED)$/, '').trim();
												if (plugin.name.localeCompare(currentPluginName) < 0) {
													$(this).before(pluginHtml);
													inserted = true;
													return false; // break out of loop
												}
											});

											// If not inserted (comes last alphabetically), append to end
											if (!inserted) {
												$('#all-plugins-list').append(pluginHtml);
											}
										}
									});

									$('#licensed-plugins-container').hide();
								} else {
									$('#licensed-plugins-container').html('<p>No licensed plugins available from GitHub repository.</p>').show();
								}
							},
							error: function() {
								$('#licensed-plugins-loading').hide();
								$('#licensed-plugins-container').html('<p style="color: red;">Error loading licensed plugins. Please try again.</p>').show();
							}
						});
					}
				});
			</script>
		</div>
	</div>

	<!-- PIN Modal for Licensed Plugin Installation -->
	<div id="bf-pin-modal" class="bf-modal" style="display: none;">
		<div class="bf-modal-content">
			<div class="bf-modal-header">
				<h3>Licensed Plugin Installation</h3>
				<span class="bf-modal-close">&times;</span>
			</div>
			<div class="bf-modal-body">
				<p>Enter PIN to install licensed/premium plugins:</p>
				<input type="password" id="bf-pin-input" placeholder="Enter PIN" maxlength="4" />
				<div id="bf-pin-error" style="color: red; margin-top: 10px; display: none;"></div>
			</div>
			<div class="bf-modal-footer">
				<button type="button" id="bf-pin-submit" class="button-primary">Install Plugins</button>
				<button type="button" id="bf-pin-cancel" class="button">Cancel</button>
			</div>
		</div>
	</div>

	<style>
		.bf-modal {
			position: fixed;
			z-index: 9999;
			left: 0;
			top: 0;
			width: 100%;
			height: 100%;
			background-color: rgba(0, 0, 0, 0.5);
		}

		.bf-modal-content {
			background-color: #fff;
			margin: 15% auto;
			padding: 0;
			border: 1px solid #ccc;
			border-radius: 4px;
			width: 400px;
			max-width: 90%;
			box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
		}

		.bf-modal-header {
			padding: 15px 20px;
			border-bottom: 1px solid #e5e5e5;
			position: relative;
		}

		.bf-modal-header h3 {
			margin: 0;
			font-size: 16px;
		}

		.bf-modal-close {
			position: absolute;
			right: 15px;
			top: 15px;
			font-size: 24px;
			font-weight: bold;
			cursor: pointer;
			color: #999;
		}

		.bf-modal-close:hover {
			color: #000;
		}

		.bf-modal-body {
			padding: 20px;
		}

		.bf-modal-body p {
			margin: 0 0 15px 0;
		}

		#bf-pin-input {
			width: 100%;
			padding: 8px 12px;
			font-size: 14px;
			border: 1px solid #ddd;
			border-radius: 3px;
			text-align: center;
			letter-spacing: 2px;
		}

		.bf-modal-footer {
			padding: 15px 20px;
			border-top: 1px solid #e5e5e5;
			text-align: right;
		}

		.bf-modal-footer button {
			margin-left: 10px;
		}
	</style>
</div>