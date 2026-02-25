jQuery(document).ready(function () {

	// Tab JS
	jQuery('.nav-tabs li').click(function (e) {
		e.preventDefault();
		var fetchId = jQuery(this).attr('id');
		var attrId = jQuery(this).attr('data-tab');
		var parentId = jQuery(this).parent().attr('data-tab');
		var data = { 'action': 'blueflamingo_current_page', 'tab': parentId + '_tab', 'value': attrId, 'nonce': blueflamingo.nonce };
		jQuery.post(ajaxurl, data, function (response) { });
		jQuery('.nav-tabs li').removeClass('active');
		jQuery(this).addClass('active')
		jQuery('.tab-content div').removeClass('active');
		jQuery('.' + fetchId).addClass('active');
		// Handle Health Check lazy loading
		if (attrId === 'Health Check' && !jQuery('#health-check-content').hasClass('loaded')) {
			loadHealthCheckContent();
		}
	});

	// Function to load Health Check content via AJAX
	function loadHealthCheckContent() {
		// Show loading state
		jQuery('#health-check-loading').show();
		jQuery('#health-check-content').hide();

		var data = {
			'action': 'blueflamingo_load_health_check',
			'nonce': blueflamingo.nonce
		};

		jQuery.post(ajaxurl, data, function (response) {
			if (response.success) {
				// Hide loading and show content
				jQuery('#health-check-loading').hide();
				jQuery('#health-check-content').html(response.data).show().addClass('loaded');
			} else {
				// Show error message with more detail if available
				var errorMsg = response.data || 'Unknown error occurred';
				jQuery('#health-check-loading').hide();
				jQuery('#health-check-content').html('<div class="notice notice-error"><p><strong>Error loading Health Check data:</strong> ' + errorMsg + '</p><p>Please refresh the page and try again. If the problem persists, contact your site administrator.</p></div>').show();
			}
		}).fail(function (xhr, status, error) {
			// Handle AJAX failure with more detailed error info
			var errorDetails = '';
			if (xhr.status) {
				errorDetails = ' (HTTP ' + xhr.status + ': ' + xhr.statusText + ')';
			}
			if (xhr.responseText && xhr.responseText.indexOf('Fatal error') !== -1) {
				errorDetails += ' - A server error occurred. Please check the error logs.';
			}

			jQuery('#health-check-loading').hide();
			jQuery('#health-check-content').html('<div class="notice notice-error"><p><strong>Failed to load Health Check data.</strong>' + errorDetails + '</p><p>This may be due to server configuration issues. Please contact your hosting provider if the problem persists.</p></div>').show();
		});
	}

	// Load Health Check content immediately if tab is initially active
	jQuery(document).ready(function () {
		if (jQuery('.tab-7').hasClass('active') && !jQuery('#health-check-content').hasClass('loaded')) {
			loadHealthCheckContent();
		}
	});

	function woo_notice_dismiss() {
		var data = {
			'action': 'blueflamingo_wc_stripe_notice_dismiss', 'nonce': blueflamingo.nonce
		};
		jQuery.post(ajaxurl, data, function (response) {
			jQuery('#woobf-post-notice').hide();
		});
	}

	jQuery(document).ready(function () {
		jQuery('body').on('click', '#woobf-post-notice .notice-dismiss', function () {
			woo_notice_dismiss();
		});
	});

	jQuery(".BFSAVE").click(function (e) {
		e.preventDefault();
		jQuery(".add_loader_gif_two").append('<img src="images/spinner.gif">');
		var live_url = jQuery('.tab-pane.tab-1.active .live_url').val();
		var staging_url = jQuery('.tab-pane.tab-1.active .staging_url').val();
		var dev_url = jQuery('.tab-pane.tab-1.active .dev_url').val();
		var data = {
			'action': 'blueflamingo_save_url', 'live_url': live_url, 'staging_url': staging_url, 'dev_url': dev_url, 'nonce': blueflamingo.nonce
		};
		jQuery.post(ajaxurl, data, function (response) {
			location.reload();
		});
	});

	jQuery("input[name='checkALL']").click(function () {
		jQuery("input[name='pluginInstall']").prop('checked', this.checked);
	});

	jQuery("input[name='checkALLPremiumPlugins']").click(function () {
		jQuery("input[name='PremiumPluginInstall']").prop('checked', this.checked);
	});

	jQuery("input[name='checkALLAFIPT']").click(function () {
		jQuery("input[class='checkALL_AFIPT']").prop('checked', this.checked);
	});

	function load_url(url) {
		var href = window.location.href;
		lastIndex = href.substr(href.lastIndexOf('/') + 1);
		href = href.replace(lastIndex, url);
		window.location.href = href;
	}

	function bf_custom_active_plugin(data) {
		jQuery.ajax({
			type: "post",
			dataType: "json",
			url: blueflamingo.ajaxurl,
			data: {
				action: "bf_bulk_activate_plugins",
				plugins: data,
				nonce: blueflamingo.nonce
			},
			success: function (response) {
				if (response.success) {
					console.log('Plugins activated successfully', response);
					load_url('admin.php?page=blueflamingo_plugins&action=activate_plugins');
				} else {
					console.error('Plugin activation failed:', response.data);
					response.data.errors.forEach(function (error) {
						jQuery(".plugin-log").append('<span style="background: #ff0000a1;color: white;">Plugin activation failed: ' + error + '</span>');
					});
					setTimeout(function () {
						load_url('admin.php?page=blueflamingo_plugins&action=activate_plugins');
					}, 5000);
				}
			},
			error: function (xhr, status, error) {
				console.error('Plugin activation failed:', error);
				jQuery(".plugin-log").append('<span style="background: #ff0000a1;color: white;">Plugin activation failed: ' + error + '</span>');
			}
		});
	}

	jQuery(".OC_PLUGIN_INSTALL").click(function (e) {
		e.preventDefault();
		// Collect both free and licensed plugins
		var freePlugins = [];
		var freePluginActivate = [];
		var licensedPlugins = [];
		var licensedPluginActivate = [];

		// Collect free plugins (including licensed-display plugins that install as free)
		jQuery.each(jQuery("input[name='pluginInstall']:checked"), function () {
			freePlugins.push(jQuery(this).val());
			freePluginActivate.push(jQuery(this).attr('basename'));
		});

		// Collect true licensed plugins (excluding licensed-display)
		jQuery.each(jQuery("input[name='LicensedPluginInstall']:checked"), function () {
			var installType = jQuery(this).attr('data-install-type');
			if (installType === 'free') {
				// This is a licensed-display plugin that should install as free
				freePlugins.push(jQuery(this).val());
				freePluginActivate.push(jQuery(this).attr('basename'));
			} else {
				// This is a true licensed plugin that requires PIN
				licensedPlugins.push(jQuery(this).val());
				licensedPluginActivate.push(jQuery(this).attr('basename'));
			}
		});

		// Check if any plugins are selected
		if (freePlugins.length === 0 && licensedPlugins.length === 0) {
			jQuery(".plugin-log").append('<span style="background: #ff0000a1;color: white;"> No plugins selected from the list </span>');
			return;
		}

		jQuery(".add_loader_gif").append('<img src="images/spinner.gif">');
		var totalPlugins = freePlugins.length + licensedPlugins.length;
		var completedCount = 0;
		var allActivationBasenames = freePluginActivate.concat(licensedPluginActivate);

		// Function to check if all installations are complete
		function checkAllComplete() {
			completedCount++;
			if (completedCount >= totalPlugins) {
				jQuery(".add_loader_gif").remove();
				if (allActivationBasenames.length > 0) {
					jQuery(".plugin-log").append('<span style="background: #2aa932;color:#fff">Activating added plugins</span>').slideDown("slow");
					bf_custom_active_plugin(allActivationBasenames);
				}
			}
		}

		// Install free plugins
		if (freePlugins.length > 0) {
			jQuery.each(freePlugins, function (index, value) {
				var PluginName = jQuery("input[value='" + value + "']").attr('pluginname');
				jQuery.ajax({
					type: "post",
					dataType: "json",
					url: blueflamingo.ajaxurl,
					data: { action: "install-plugin", slug: value, _ajax_nonce: blueflamingo.ajax_plugin },
					success: function (response) {
						//console.log(response); // Debug removed
						if (response['success'] == false) {
							jQuery(".plugin-log").append('<span style="background: #ff0000a1;color: white;"><strong>' + PluginName + '</strong> plugin already exists </span>');
						} else {
							jQuery(".plugin-log").append('<span><strong>' + PluginName + '</strong> plugin added </span>');
						}
						checkAllComplete();
					},
					error: function (data) {
						jQuery(".plugin-log").append('<span style="background: #ff0000a1;color: white;"><strong>' + PluginName + '</strong> plugin not added, ' + data['status'] + ' : ' + data['statusText'] + '</span>');
						checkAllComplete();
					}
				});
			});
		}

		// Install licensed plugins sequentially to avoid conflicts
		if (licensedPlugins.length > 0) {
			// Show PIN modal before installing licensed plugins
			showPinModal(function (pin) {
				// Proceed with sequential installation if PIN was provided
				var currentIndex = 0;

				function installNextLicensedPlugin() {
					if (currentIndex >= licensedPlugins.length) {
						// All licensed plugins processed
						return;
					}

					var value = licensedPlugins[currentIndex];
					var PluginName = jQuery("input[value='" + value + "']").attr('pluginname');

					jQuery.ajax({
						type: "post",
						dataType: "json",
						url: blueflamingo.ajaxurl,
						data: {
							action: 'bf_install_licensed_plugin',
							slug: value,
							nonce: blueflamingo.licensed_nonce,
							pin: pin
						},
						success: function (response) {
							if (response && response.success === false) {
								var errorMessage = response.data && response.data.message ? response.data.message : 'Installation failed';
								jQuery(".plugin-log").append('<span style="background: #ff0000a1;color: white;"><strong>' + PluginName + '</strong> plugin error: ' + errorMessage + '</span>');
							} else {
								jQuery(".plugin-log").append('<span><strong>' + PluginName + '</strong> plugin added </span>');
							}
							checkAllComplete();
							currentIndex++;
							// Add 500ms delay between installations to prevent race conditions
							setTimeout(installNextLicensedPlugin, 500);
						},
						error: function (data) {
							jQuery(".plugin-log").append('<span style="background: #ff0000a1;color: white;"><strong>' + PluginName + '</strong> plugin not added, ' + data['status'] + ' : ' + data['statusText'] + '</span>');
							checkAllComplete();
							currentIndex++;
							// Add 500ms delay even on error to maintain stable processing
							setTimeout(installNextLicensedPlugin, 500);
						}
					});
				}

				// Start sequential installation
				installNextLicensedPlugin();
			}, function () {
				// User cancelled PIN entry
				totalPlugins -= licensedPlugins.length;
				jQuery(".plugin-log").append('<span style="background: #ff0000a1;color: white;"> Licensed plugin installation cancelled - PIN required </span>');
			});
		}
	});

	jQuery('input.special-checkbox').on('change', function () {
		jQuery('input.special-checkbox').not(this).prop('checked', false);
	});

	jQuery('input.tracking_method').on('change', function () {
		var value = jQuery('input[name="blueflamingo_plugin_google_analytics_settings[google_analytics_tracking_method]"]:checked').val();
		if (value == 3) {
			jQuery('#showUAID').css("display", "contents");
		} else {
			jQuery('#showUAID').css("display", "none");
		}
	});

	// PIN Modal functionality for licensed plugin installation
	function showPinModal(onSuccess, onCancel) {
		var modal = jQuery('#bf-pin-modal');
		var input = jQuery('#bf-pin-input');
		var error = jQuery('#bf-pin-error');

		// Clear previous state
		input.val('');
		error.hide();

		// Show modal
		modal.show();
		input.focus();

		// Handle submit
		jQuery('#bf-pin-submit').off('click').on('click', function () {
			var pin = input.val().trim();
			if (!pin) {
				error.text('Please enter a PIN').show();
				return;
			}
			modal.hide();
			onSuccess(pin);
		});

		// Handle cancel
		jQuery('#bf-pin-cancel, .bf-modal-close').off('click').on('click', function () {
			modal.hide();
			if (onCancel) onCancel();
		});

		// Handle Enter key
		input.off('keypress').on('keypress', function (e) {
			if (e.which === 13) { // Enter key
				jQuery('#bf-pin-submit').click();
			}
		});

		// Handle Escape key
		jQuery(document).off('keyup.pin-modal').on('keyup.pin-modal', function (e) {
			if (e.which === 27) { // Escape key
				modal.hide();
				if (onCancel) onCancel();
			}
		});

		// Close on outside click
		modal.off('click').on('click', function (e) {
			if (e.target === modal[0]) {
				modal.hide();
				if (onCancel) onCancel();
			}
		});
	}
	// View CSP Logs
	jQuery('.view-csp-logs').on('click', function (e) {
		e.preventDefault();
		//show loading indicator full page add styles bf-loading and bf-loading-spinner

		jQuery('body').append('<div class="bf-loading"><div class="bf-loading-spinner"></div></div>');
		jQuery.ajax({
			url: bfCspAdmin.ajaxurl,
			type: 'POST',
			data: {
				action: 'view_csp_logs',
				nonce: bfCspAdmin.nonce
			},
			success: function (response) {
				if (response.success && response.data) {
					//remove loading indicator
					jQuery('.bf-loading').remove();
					showLogsModal(response.data);
				}
			}
		});
	});

	// Delete CSP Logs
	jQuery('.delete-csp-logs').on('click', function (e) {
		e.preventDefault();
		if (!confirm('Are you sure you want to delete all CSP violation logs?')) {
			return;
		}

		jQuery.ajax({
			url: bfCspAdmin.ajaxurl,
			type: 'POST',
			data: {
				action: 'delete_csp_logs',
				nonce: bfCspAdmin.nonce
			},
			success: function (response) {
				if (response.success) {
					alert('CSP violation logs have been deleted.');
				}
			}
		});
	});

	// Function to display logs in a modal
	function showLogsModal(logs) {
		// Remove existing modal if any
		jQuery('#csp-logs-modal').remove();

		// Create modal HTML
		var modalHtml = '<div id="csp-logs-modal" class="bf-modal">' +
			'<div class="bf-modal-content">' +
			'<div class="bf-modal-header">' +
			'<h2>CSP Violation Logs</h2>' +
			'<span class="bf-modal-close">&times;</span>' +
			'</div>' +
			'<div class="bf-modal-body">';

		if (Object.keys(logs).length === 0) {
			modalHtml += '<p>No CSP violations logged.</p>';
		} else {
			modalHtml += '<table class="wp-list-table widefat fixed striped">' +
				'<thead><tr>' +
				'<th>First Seen</th>' +
				'<th>Blocked URI</th>' +
				'<th>Violated Directive</th>' +
				'<th>Total Count</th>' +
				'<th>Last Seen</th>' +
				'<th>Actions</th>' +
				'</tr></thead><tbody>';

			for (var key in logs) {
				var log = logs[key];
				var pageCount = log.document_uris ? log.document_uris.length : 0;

				modalHtml += '<tr>' +
					'<td>' + log.first_occurrence + '</td>' +
					'<td style="word-break: break-all; max-width: 300px;">' + log.blocked_uri + '</td>' +
					'<td>' + log.violated_directive + '</td>' +
					'<td>' + log.total_count + '</td>' +
					'<td>' + log.last_occurrence + '</td>' +
					'<td>' +
					'<button class="button button-small view-pages-btn" data-index="' + key + '">' +
					'View Pages (' + pageCount + ')' +
					'</button>' +
					'</td>' +
					'</tr>';
			}

			modalHtml += '</tbody></table>';
		}

		modalHtml += '</div></div></div>';

		// Add modal styles
		var modalStyles = '<style>' +
			'.bf-modal { display: block; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); }' +
			'.bf-modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 1200px; max-height: 80%; overflow: auto; }' +
			'.bf-modal-header { position: relative; margin-bottom: 20px; }' +
			'.bf-modal-close { position: absolute; right: 0; top: 0; font-size: 28px; font-weight: bold; cursor: pointer; }' +
			'.bf-modal-close:hover { color: #666; }' +
			'.bf-modal table { width: 100%; border-collapse: collapse; }' +
			'.bf-modal th, .bf-modal td { padding: 8px; text-align: left; vertical-align: top; }' +
			'.view-pages-btn { white-space: nowrap; }' +
			'#csp-pages-modal .bf-modal-content { max-width: 800px; }' +
			'#csp-pages-modal ul { list-style: none; padding: 0; margin: 0; }' +
			'#csp-pages-modal li { padding: 10px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }' +
			'#csp-pages-modal li:last-child { border-bottom: none; }' +
			'#csp-pages-modal li:hover { background-color: #f5f5f5; }' +
			'#csp-pages-modal .page-url { flex: 1; word-break: break-all; margin-right: 10px; }' +
			'</style>';

		// Add modal to page
		jQuery('body').append(modalStyles + modalHtml);

		// Handle view pages button
		jQuery('.view-pages-btn').on('click', function () {
			var index = jQuery(this).data('index');
			var log = logs[index];
			showPagesModal(log);
		});

		// Handle close button and outside click
		jQuery('.bf-modal-close, .bf-modal').on('click', function (e) {
			if (e.target === this) {
				jQuery('#csp-logs-modal').remove();
			}
		});
	}

	// Function to display pages in a separate modal
	function showPagesModal(log) {
		// Remove existing pages modal if any
		jQuery('#csp-pages-modal').remove();

		var modalHtml = '<div id="csp-pages-modal" class="bf-modal">' +
			'<div class="bf-modal-content">' +
			'<div class="bf-modal-header">' +
			'<h2>Pages with CSP Violations</h2>' +
			'<span class="bf-modal-close">&times;</span>' +
			'</div>' +
			'<div class="bf-modal-body">' +
			'<div style="margin-bottom: 15px; padding: 10px; background: #f0f0f0; border-radius: 4px;">' +
			'<strong>Blocked URI:</strong> <span style="word-break: break-all;">' + log.blocked_uri + '</span><br>' +
			'<strong>Violated Directive:</strong> ' + log.violated_directive + '<br>' +
			'<strong>Total Violations:</strong> ' + log.total_count +
			'</div>';

		if (!log.document_uris || log.document_uris.length === 0) {
			modalHtml += '<p>No page information available.</p>';
		} else {
			modalHtml += '<ul>';
			for (var i = 0; i < log.document_uris.length; i++) {
				var pageUrl = log.document_uris[i];
				modalHtml += '<li>' +
					'<span class="page-url">' + pageUrl + '</span>' +
					'<a href="' + pageUrl + '" target="_blank" class="button button-small">Open Page</a>' +
					'</li>';
			}
			modalHtml += '</ul>';
		}

		modalHtml += '</div></div></div>';

		// Add modal to page
		jQuery('body').append(modalHtml);

		// Handle close button and outside click
		jQuery('#csp-pages-modal .bf-modal-close, #csp-pages-modal').on('click', function (e) {
			if (e.target === this) {
				jQuery('#csp-pages-modal').remove();
			}
		});
	}
});