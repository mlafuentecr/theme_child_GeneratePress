<?php

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class Blue_Flamingo_Premium_Plugin_Install
{
	function __construct()
	{
		// Installs Licensed Plugin
		add_action('wp_ajax_bf_install_licensed_plugin', array($this, 'bf_install_licensed_plugin'));

		// Fetch available licensed plugins list
		add_action('wp_ajax_bf_get_licensed_plugins', array($this, 'bf_get_licensed_plugins'));

		// Admin notice for GitHub integration
		add_action('admin_notices', array($this, 'show_github_integration_notice'));
	}

	public function bf_install_licensed_plugin()
	{
		// Ensure nonce is present
		if (! isset($_POST['nonce'])) {
			wp_send_json_error(array('message' => 'Missing security nonce.')); 
		}
		$check = sanitize_text_field(wp_unslash($_POST['nonce']));
		if (! wp_verify_nonce($check, 'blueflamingo_licensed_nonce')) {
			wp_send_json_error(array('message' => 'Invalid security nonce.'));
		}

		// Validate PIN for licensed plugin installation
		if (! isset($_POST['pin']) || $_POST['pin'] !== '2012') {
			wp_send_json_error(array('message' => 'Invalid PIN. Licensed plugin installation requires the correct PIN.'));
		}

		// Start Install
		$install = $this->install_plugin(isset($_POST['slug']) ? $_POST['slug'] : null);

		if (is_wp_error($install)) {
			// Log detailed error for debugging
			blueflamingo_debug_log('Blue Flamingo Licensed: Installation failed for ' . esc_html($_POST['slug']) . ' - ' . $install->get_error_message());
			// Send structured error response including messages and code
			return wp_send_json_error(array(
				'message' => $install->get_error_message(),
				'code' => $install->get_error_code(),
				'messages' => $install->get_error_messages(),
			));
		}

		wp_send_json_success(array('slug' => $_POST['slug']));
	}

	public function bf_get_licensed_plugins()
	{
		if (! isset($_POST['nonce'])) {
			wp_send_json_error(array('message' => 'Missing security nonce.'));
		}
		$check = sanitize_text_field(wp_unslash($_POST['nonce']));
		if (! wp_verify_nonce($check, 'blueflamingo_licensed_nonce')) {
			wp_send_json_error(array('message' => 'Invalid security nonce.'));
		}

		if (! current_user_can('install_plugins')) {
			wp_send_json_error(array('message' => 'You do not have permission to install plugins'));
		}
		
		// Get licensed plugins from GitHub repository
		$licensed_plugins = $this->get_licensed_plugin_info();
		

		// Add installation status for each plugin
		foreach ($licensed_plugins as &$plugin) {
			$plugin['installed'] = $this->is_plugin_installed($plugin['basename']);
			$plugin['active'] = is_plugin_active($plugin['basename']);
		}

		wp_send_json_success(array('plugins' => $licensed_plugins));
	}

	public function install_plugin($plugin)
	{
		if (! $plugin) {
			return new WP_Error('bf-integration', 'No plugin specified.');
		}

		if (! current_user_can('install_plugins')) {
			return new WP_Error('bf-integration', 'Your user account does not have permission to install plugins.');
		}

		// Nothing to do if already installed
		if ($this->is_plugin_installed($plugin)) {
			return new WP_Error('bf-integration', 'Plugin already installed.');
		}

		// Abort if file system not writable
		if (! $this->can_write_to_filesystem()) {
			return new WP_Error('bf-integration', 'Your WordPress file permissions do not allow plugins to be installed.');
		}		
		// Load WordPress Upgrader Skin
		bf_install_licensed_plugin_load_upgrader();

		// Create fresh instances for each installation to avoid state conflicts
		$skin = new BF_Plugin_Upgrader_Skin();
		$upgrader = new BF_Plugin_Upgrader($skin);

		// Resolve download URL and log for debugging
		$download_url = $this->get_download_url($plugin);
		if (is_wp_error($download_url)) {
			blueflamingo_debug_log('Blue Flamingo Licensed: Download URL resolution failed for ' . $plugin . ' - ' . $download_url->get_error_message());
			return $download_url;
		}

		// Clear any previous upgrade locks that might interfere
		global $wp_filesystem;
		delete_option('core_updater.lock');
		delete_site_transient('update_plugins');
		
		// Ensure we have a clean state
		if (isset($upgrader->skin->result)) {
			unset($upgrader->skin->result);
		}

		$result = $upgrader->install($download_url);

		if (is_wp_error($result)) {
			blueflamingo_debug_log('Blue Flamingo Licensed: Upgrader returned WP_Error: ' . $result->get_error_message());
			return $result;
		}

		// Check if result is null - this often means the upgrader encountered an issue
		if ($result === null) {
			// Check skin for errors first
			$skin_error = $skin->get_error();
			if (is_wp_error($skin_error)) {
				blueflamingo_debug_log('Blue Flamingo Licensed: Upgrader returned null, skin has error: ' . $skin_error->get_error_message());
				return $skin_error;
			}

			// If no skin error but still null, check if plugin was actually installed
			if ($this->is_plugin_installed($plugin)) {
				blueflamingo_debug_log('Blue Flamingo Licensed: Upgrader returned null but plugin appears to be installed successfully');
				return true;
			}

			blueflamingo_debug_log('Blue Flamingo Licensed: Upgrader returned null and plugin not found - installation failed');
			return new WP_Error('bf-upgrader-null', 'Plugin installation returned null. This may be due to concurrent installation attempts or temporary file system issues. Please try again.');
		}

		// The upgrader may return true on success, or false/other values on failure.
		if ($result !== true) {
			// Check if plugin was actually installed despite non-true return
			if ($this->is_plugin_installed($plugin)) {
				blueflamingo_debug_log('Blue Flamingo Licensed: Upgrader returned non-true value but plugin appears installed');
				return true;
			}
			return new WP_Error('bf-upgrader-failed', 'Plugin installer failed. Upgrader returned unexpected result: ' . var_export($result, true));
		}

		// Final check for skin errors
		$skin_error = $skin->get_error();
		if (is_wp_error($skin_error)) {
			blueflamingo_debug_log('Blue Flamingo Licensed: Upgrader skin error: ' . $skin_error->get_error_message());
			return $skin_error;
		}

		blueflamingo_debug_log('Blue Flamingo Licensed: Plugin ' . $plugin . ' installed successfully');
		return true;
	}

	public function is_plugin_installed($basename)
	{
		$installed_plugins = $this->get_plugins();
		return isset($installed_plugins[$basename]);
	}

	public function get_plugins($plugin_folder = '')
	{
		if (! function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugins($plugin_folder);
	}

	public function can_write_to_filesystem()
	{

		// Attempt to get credentials without output
		ob_start();
		$creds = request_filesystem_credentials('', '', false, false, null);
		ob_end_clean();

		// Return true/false if file system is available
		return (bool) WP_Filesystem($creds);
	}

	public function get_download_url($slug)
	{
		if (! $slug) {
			return new WP_Error('bf-invalid-slug', 'No plugin slug provided.');
		}

		$plugin_urls = get_transient('bf_licensed_plugins_github_urls');
		if ($plugin_urls && isset($plugin_urls[$slug])) {
			$url = $plugin_urls[$slug];
			// The API may include temporary token querystrings; strip token param if present when using BF_GITHUB_TOKEN
			// (we still pass Authorization header via upgrader)
			$url = preg_replace('/([&?])token=[^&]+/', '', $url);
			return $url;
		}

		// If we don't have a mapping at all, return an error indicating the plugin isn't in the repo
		return new WP_Error('bf-not-found', 'Requested plugin "' . esc_html($slug) . '" not found in licensed repository. Please refresh the plugin list.');
	}

	public function get_licensed_plugin_info() {
		$cache_key = 'bf_licensed_plugins_github_info';
		$cached_plugins = get_transient($cache_key);

		if ($cached_plugins !== false) {
			return $cached_plugins;
		}
		
		$json_file = plugin_dir_path(__FILE__) . '../json/premium-plugins.json';
		$json_data = json_decode(file_get_contents($json_file), true);
		$available_plugins = $this->get_available_licensed_plugins(); // returns array of [slug => download_url]
		$plugins_info = array();

		foreach ($available_plugins as $slug => $download_url) {
			$meta_cache_key = 'bf_plugin_meta_' . md5($slug);

			// Check individual plugin metadata cache
			$cached_meta = get_transient($meta_cache_key);
			if ($cached_meta !== false) {
				$plugins_info[] = $cached_meta;
				continue;
			}
			
			$matched_data = null;
			$slug_normalized = strtolower(preg_replace('/[^a-z0-9]+/', '-', $slug));
			$best_similarity = 0;

			foreach ($json_data as $plugin_meta) {
				if (empty($plugin_meta['slug'])) continue;

				$meta_slug_normalized = strtolower(preg_replace('/[^a-z0-9]+/', '-', $plugin_meta['slug']));

				// ✅ Exact match first
				if ($meta_slug_normalized === $slug_normalized) {
					$matched_data = $plugin_meta;
					blueflamingo_debug_log("Blue Flamingo Licensed: Exact match for '$slug' → '$meta_slug_normalized'");
					break;
				}

				// ✅ Partial / fuzzy match - check if JSON slug is contained in GitHub slug
				// Example: 'wonderplugin-3dcarousel' is in 'wonderplugin-3dcarousel-pro'
				if (strpos($slug_normalized, $meta_slug_normalized) !== false) {
					$matched_data = $plugin_meta;
					blueflamingo_debug_log("Blue Flamingo Licensed: Partial match for '$slug' → contains '$meta_slug_normalized'");
					break;
				}
				
				// Check reverse: GitHub slug is in JSON slug (less common but possible)
				if (strpos($meta_slug_normalized, $slug_normalized) !== false) {
					$matched_data = $plugin_meta;
					blueflamingo_debug_log("Blue Flamingo Licensed: Reverse partial match for '$slug' → found in '$meta_slug_normalized'");
					break;
				}

				// ✅ Fallback: find closest match if nothing exact/partial
				similar_text($slug_normalized, $meta_slug_normalized, $similarity);
				if ($similarity > $best_similarity && $similarity >= 70) {
					$best_similarity = $similarity;
					$matched_data = $plugin_meta;
				}
			}

			if ($matched_data) {
				// Use GitHub slug for consistency with download URL mapping
				$matched_data['slug'] = $slug;
				$plugins_info[] = $matched_data;
				set_transient($meta_cache_key, $matched_data, 2 * HOUR_IN_SECONDS);
				blueflamingo_debug_log("Blue Flamingo Licensed: Plugin slug '$slug' matched metadata: " . var_export($matched_data, true));
				continue;
			}

			// If no JSON match, try extracting from zip
			blueflamingo_debug_log("Blue Flamingo Licensed: No JSON match for '$slug', extracting from zip...");
			$metadata = $this->bf_get_plugin_metadata_from_zip($download_url);

			if ($metadata) {
				$plugins_info[] = $metadata;
				set_transient($meta_cache_key, $metadata, 2 * HOUR_IN_SECONDS);
			} else {
				// fallback if unable to read zip
				$plugins_info[] = array(
					'slug' => $slug,
					'name' => ucwords(str_replace('-', ' ', $slug)),
					'basename' => "$slug/$slug.php",
					'type' => 'licensed',
				);
			}
		}

		usort($plugins_info, function ($a, $b) {
			return strcmp($a['name'], $b['name']);
		});

		set_transient($cache_key, $plugins_info, 2 * HOUR_IN_SECONDS);

		return $plugins_info;
	}


	public function get_available_licensed_plugins() {
		$cache_key = 'bf_licensed_plugins_github_urls';
		$cached_plugins = get_transient($cache_key);

		if ($cached_plugins !== false) {
			return $cached_plugins;
		}

		$licensed_repo = 'blueflamingo-solutions/wp-premium-plugins';
		$all_files = array();
		$page = 1;
		$per_page = 100;

		do {
			$api_url = "https://api.github.com/repos/$licensed_repo/contents?per_page=$per_page&page=$page";
			$headers = array('User-Agent' => 'WordPress/Blue-Flamingo-Plugin');

			if (defined('BF_GITHUB_TOKEN') && BF_GITHUB_TOKEN) {
				$headers['Authorization'] = 'token ' . BF_GITHUB_TOKEN;
			}

			$response = wp_remote_get($api_url, array('headers' => $headers, 'timeout' => 30));

			if (is_wp_error($response)) {
				blueflamingo_debug_log("GitHub API error: " . $response->get_error_message());
				break;
			}

			if (wp_remote_retrieve_response_code($response) !== 200) {
				blueflamingo_debug_log("GitHub API failed with code: " . wp_remote_retrieve_response_code($response));
				break;
			}

			$data = json_decode(wp_remote_retrieve_body($response), true);
			if (!is_array($data) || empty($data)) break;

			$all_files = array_merge($all_files, $data);
			$page++;
		} while (count($data) === $per_page);

		if (empty($all_files)) {
			blueflamingo_debug_log("No files retrieved from GitHub API.");
			return array();
		}

		$plugin_urls = array();

		foreach ($all_files as $file) {
			if (isset($file['name'], $file['type']) && $file['type'] === 'file' && substr($file['name'], -4) === '.zip') {
				$filename = $file['name'];
				$slug = preg_replace('/[-_]?v?\d+(?:[-_.]\d+)*$/', '', str_replace('.zip', '', $filename));
				$slug = rtrim($slug, '.');

				if (!empty($slug) && !empty($file['download_url'])) {
					$plugin_urls[$slug] = $file['download_url'];
				}
			}
		}

		set_transient($cache_key, $plugin_urls, 2 * HOUR_IN_SECONDS);

		blueflamingo_debug_log("Blue Flamingo Licensed: Retrieved " . count($plugin_urls) . " licensed plugins from GitHub.");
		blueflamingo_debug_log("Plugin slugs: " . implode(', ', array_keys($plugin_urls)));
		return $plugin_urls;
	}


	private function bf_get_plugin_metadata_from_zip($download_url) {
		$tmp_zip = $this->bf_download_plugin_zip($download_url);
		if (is_wp_error($tmp_zip)) {
			return false;
		}

		$tmp_dir = wp_tempnam();
		if ($tmp_dir) {
			@unlink($tmp_dir);
			mkdir($tmp_dir);
		}

		$zip = new ZipArchive();
		if ($zip->open($tmp_zip) !== true) {
			@unlink($tmp_zip);
			return false;
		}
		$zip->extractTo($tmp_dir);
		$zip->close();

		$plugin_file = $this->bf_find_main_plugin_file($tmp_dir);
		if (!$plugin_file) {
			$this->bf_rrmdir($tmp_dir);
			@unlink($tmp_zip);
			return false;
		}

		if (!function_exists('get_plugin_data')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugin_data($plugin_file, false, false);

		$plugin_name = $plugin_data['Name'] ?? '';
		$plugin_slug = basename(dirname($plugin_file));
		$plugin_base_name = $plugin_slug . '/' . basename($plugin_file);

		$this->bf_rrmdir($tmp_dir);
		@unlink($tmp_zip);

		return array(
			'slug' => $plugin_slug,
			'name' => $plugin_name ?: ucwords(str_replace('-', ' ', $plugin_slug)),
			'basename' => $plugin_base_name,
			'type' => 'licensed',
		);
	}

	private function bf_download_plugin_zip($download_url) {
		$github_token = BF_GITHUB_TOKEN;

		$response = wp_remote_get($download_url, array(
			'headers' => array(
				'Authorization' => 'token ' . $github_token,
				'User-Agent'    => 'WordPress/' . get_bloginfo('version'),
			),
			'timeout' => 60,
		));

		if (is_wp_error($response)) {
			blueflamingo_debug_log('Download failed: ' . $response->get_error_message());
			return false;
		}

		$code = wp_remote_retrieve_response_code($response);
		if ($code !== 200) {
			blueflamingo_debug_log('Download failed, HTTP ' . $code);
			return false;
		}

		$body = wp_remote_retrieve_body($response);
		$tmp_zip = wp_tempnam($download_url);
		file_put_contents($tmp_zip, $body);

		return $tmp_zip;
	}

	private function bf_find_main_plugin_file($dir) {
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
		foreach ($iterator as $file) {
			if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
				$contents = file_get_contents($file->getPathname(), false, null, 0, 8192);
				if (preg_match('/Plugin\s+Name\s*:/i', $contents)) {
					return $file->getPathname();
				}
			}
		}
		return false;
	}

	private function bf_rrmdir($dir) {
		if (!is_dir($dir)) return;
		$files = array_diff(scandir($dir), array('.', '..'));
		foreach ($files as $file) {
			$path = "$dir/$file";
			is_dir($path) ? $this->bf_rrmdir($path) : unlink($path);
		}
		rmdir($dir);
	}

	public function show_github_integration_notice()
	{
		if (!current_user_can('manage_options')) {
			return;
		}

		// Only show on the plugins page
		$screen = get_current_screen();
		if (!$screen || $screen->id !== 'toplevel_page_blue-flamingo-plugins') {
			return;
		}
		echo '<div class="notice notice-info is-dismissible">';
		echo '<p><strong>Blue Flamingo Licensed Plugins:</strong> Licensed plugin downloads are now secured through GitHub repositories with enhanced authentication. All licensed plugins are automatically downloaded from our secure GitHub repository: <code>blueflamingo-solutions/wp-premium-plugins</code></p>';
		echo '</div>';
	}
}

function bf_install_licensed_plugin_load_upgrader()
{
	if (! class_exists('Plugin_Upgrader', false)) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}

	if (! class_exists('BF_Plugin_Upgrader')) {
		class BF_Plugin_Upgrader extends Plugin_Upgrader
		{

			/**
			 * Override download_package to add GitHub authentication
			 */
			public function download_package($package, $check_signatures = false, $hook_extra = array())
			{
				// Check if this is a GitHub URL that needs authentication
				if (strpos($package, 'github.com') !== false || strpos($package, 'githubusercontent.com') !== false) {
					return $this->download_github_package($package);
				}

				// For non-GitHub URLs, use parent method
				return parent::download_package($package, $check_signatures, $hook_extra);
			}

			/**
			 * Download package from GitHub with authentication
			 */
			private function download_github_package($package)
			{
				$headers = array(
					'User-Agent' => 'WordPress/Blue-Flamingo-Plugin'
				);
				if (defined('BF_GITHUB_TOKEN') && BF_GITHUB_TOKEN) {
					$headers['Authorization'] = 'token ' . BF_GITHUB_TOKEN;
				}
				$args = array(
					'timeout' => 300,
					'headers' => $headers
				);

				$response = wp_remote_get($package, $args);

				if (is_wp_error($response)) {
					blueflamingo_debug_log("Blue Flamingo Licensed: GitHub download error: " . $response->get_error_message());
					return $response;
				}
				$response_code = wp_remote_retrieve_response_code($response);
				if ($response_code !== 200) {
					blueflamingo_debug_log("Blue Flamingo Licensed: GitHub download failed with code: $response_code");
					return new WP_Error('download_failed', "Download failed with HTTP code: $response_code");
				}

				$body = wp_remote_retrieve_body($response);

				// Create temporary file
				$tmpfname = wp_tempnam();
				if (! $tmpfname) {
					return new WP_Error('temp_file_failed', 'Could not create temporary file.');
				}

				$handle = fopen($tmpfname, 'wb');
				if (! $handle) {
					return new WP_Error('temp_file_failed', 'Could not write to temporary file.');
				}
				fwrite($handle, $body);
				fclose($handle);

				return $tmpfname;
			}
		}
	}

	if (! class_exists('BF_Plugin_Upgrader_Skin')) {
		class BF_Plugin_Upgrader_Skin extends WP_Upgrader_Skin
		{
			public $error_messages = array();

			public function get_error()
			{
				return empty($this->error_messages) ? false : new WP_Error('bf-integration', implode(' | ', $this->error_messages));
			}

			public function error($errors)
			{
				if (is_string($errors)) {
					$this->error_messages[] = $errors;
				} elseif (is_wp_error($errors) && $errors->has_errors()) {
					foreach ($errors->get_error_messages() as $message) {
						if ($errors->get_error_data() && is_string($errors->get_error_data())) {
							$this->error_messages[] = $message . ' ' . esc_html(strip_tags($errors->get_error_data()));
						} else {
							$this->error_messages[] = $message;
						}
					}
				}
			}

			public function after() {}
			public function header() {}
			public function footer() {}
			public function feedback($string, ...$args) {}
		}
	}
}

new Blue_Flamingo_Premium_Plugin_Install();
