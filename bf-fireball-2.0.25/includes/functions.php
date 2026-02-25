<?php

// Exit if accessed directly
if (!defined('ABSPATH')) exit;


/* Escape attributes */
function blueflamingo_esc_attr($data)
{
	return esc_attr($data);
}


/* Checks if variable is set or not */
function blueflamingo_issetor(&$var)
{
	return isset($var) ? $var : '';
}


/* Check if Pro is there */
function blueflamingo_is_maintained()
{
	if ('free' == apply_filters('blueflamingo_is_maintained', 'free')) {
		return false;
	} else {
		return true;
	}
}


/* Checks Current blueflamingo admin page */
function blueflamingo_admin_page_check()
{
	$blueflamingo_menu = get_current_screen();
	if (

		$blueflamingo_menu->base == 'toplevel_page_blueflamingo_settings' ||
		$blueflamingo_menu->base == 'blue-flamingo_page_blueflamingo_plugins' ||
		$blueflamingo_menu->base == 'blue-flamingo_page_blueflamingo_notes' ||
		$blueflamingo_menu->base == 'blue-flamingo_page_blueflamingo_shortcodes'

	) {
		return true;
	}
}

/* Custom encryption */
function blueflamingo_encryption($value = '')
{
	$simple_string = $value;

	// Display the original string
	//echo "Original String: " . $simple_string . "<br/>";

	// Store the cipher method
	$ciphering = "AES-128-CTR";

	// Store the encryption key
	$encryption_key = "BlueFlamingo";

	// Use OpenSSl Encryption method
	//$iv_length = openssl_cipher_iv_length($ciphering);
	$options = 0;

	// Non-NULL Initialization Vector for encryption
	$encryption_iv = '1234567891011121';

	// Use openssl_encrypt() function to encrypt the data
	$encryption = openssl_encrypt($simple_string, $ciphering, $encryption_key, $options, $encryption_iv);

	return $encryption;
}

/* Custom decryption */
function blueflamingo_decryption($encrypt_value = '')
{

	// Display the encrypted string
	//echo "Encrypted String: " . $encrypt_value . "\n";

	// Store the cipher method
	$ciphering = "AES-128-CTR";

	// Non-NULL Initialization Vector for decryption
	$decryption_iv = '1234567891011121';

	// Store the decryption key
	$decryption_key = "BlueFlamingo";

	// Use OpenSSl Encryption method
	$options = 0;

	// Use openssl_decrypt() function to decrypt the data
	$decryption = openssl_decrypt($encrypt_value, $ciphering, $decryption_key, $options, $decryption_iv);

	// Display the decrypted string
	return $decryption;
}

/* Free plugin input points */
function bf_active_plugins($name, $value, $basename, $type = 'free'){ 
	// Handle dual categorization: licensed-display shows as licensed but installs as free
	$display_type = ($type === 'licensed-display') ? 'licensed' : $type;
	$install_type = ($type === 'licensed-display') ? 'free' : $type;
	
	$input_name = ($install_type === 'licensed') ? 'LicensedPluginInstall' : 'pluginInstall';
	$css_class = ($install_type === 'licensed') ? 'licensed-used-plugins' : 'commonly-used-plugins';
	$id_prefix = ($display_type === 'licensed') ? 'licensed-used-plugins' : 'commonly-used-plugins';
	$type_badge = ($display_type === 'licensed') ? ' <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: 600; margin-left: 8px; text-transform: uppercase; letter-spacing: 0.5px;">LICENSED</span>' : ' <span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: 600; margin-left: 8px; text-transform: uppercase; letter-spacing: 0.5px;">FREE</span>';
	?>
	<div class="<?php echo $value; ?>" style="margin-bottom: 5px; padding: 8px; border: 1px solid #e5e5e5; border-radius: 3px; background: <?php echo ($display_type === 'licensed') ? '#f9f9f9' : '#fafafa'; ?>;">
		<label for="<?php echo $id_prefix; ?>-<?php echo $value; ?>">
		<?php if ( ! is_plugin_active( $basename ) ) { ?>
			<input id="<?php echo $id_prefix; ?>-<?php echo $value; ?>" type="checkbox" name="<?php echo $input_name; ?>" value="<?php echo $value; ?>" basename="<?php echo $basename; ?>" pluginname="<?php echo $name; ?>" data-install-type="<?php echo $install_type; ?>" >
		<?php }else{ ?>
			<span class="dashicons dashicons-yes" style="color: #46b450;"></span>
		<?php } ?>
		<span class="<?php echo $css_class; ?>-<?php echo $value; ?>-title" basename="<?php echo $basename; ?>"><?php echo $name; ?><?php echo $type_badge; ?></span></label>
	</div>
<?php }


/* Checks for current active tab */
function bf_active_tab($tab, $parent)
{
	$active = get_option('blueflamingo_plugin_all_settings');
	if (!empty($tab)) {
		return ($tab == $active['current_tab_page'][$parent . '_tab']) ? 'active' : '';
	} else {
		return;
	}
}

/* If not empty function */
function bf_ifnotempty(&$var)
{
	return (!empty($var)) ? $var : '';
}

/*Description:Move the Yoast SEO Meta Box to the Bottom of the edit screen in WordPress */
function bfyoasttobottom()
{
	return 'low';
}

/*geting database size in Health check tab*/
function getDatabaseSize()
{
	global $wpdb;

	$schema = esc_sql(DB_NAME);
	$query = "
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
        FROM information_schema.TABLES
        WHERE table_schema = '{$schema}'
    ";
	$result = $wpdb->get_results($query, ARRAY_A);

	if (!empty($result) && isset($result[0]['size_mb'])) {
		$size_mb = floatval($result[0]['size_mb']);
		$size_formatted = ($size_mb > 1024)
			? round($size_mb / 1024, 2) . ' GB'
			: $size_mb . ' MB';

		// Determine status
		if ($size_mb > 500) {
			$status_class = 'health-status-error';
			$status_text = ' - Very large database';
		} elseif ($size_mb > 100) {
			$status_class = 'health-status-warning';
			$status_text = ' - Large database';
		} else {
			$status_class = 'health-status-good';
			$status_text = ' - Normal size';
		}

		return $size_formatted . ' <span class="' . esc_attr($status_class) . '">' . esc_html($status_text) . '</span>';
	}

	return '<span class="health-status-error">Unable to determine database size</span>';
}

/*geting file size in Health check tab*/
function getDirectorySize($directory)
{
	$size = 0;

	if (!is_dir($directory)) {
		return 0; // directory doesn't exist
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
	);

	foreach ($iterator as $file) {
		if ($file->isFile()) {
			$size += $file->getSize();
		}
	}

	return $size;
}

// Format the size in a human-readable format
function formatSize($size)
{
	$units = array('B', 'KB', 'MB', 'GB', 'TB');
	$i = 0;

	while ($size >= 1024) {
		$size /= 1024;
		$i++;
	}

	return round($size, 2) . ' ' . $units[$i];
}

/*geting wp option data in table in Health check tab*/
function optionTableDetails()
{
	global $wpdb;

	// Get autoloaded data statistics
	$autoload_stats = $wpdb->get_results("
		SELECT 
			'Autoloaded Data Size (KB)' as metric,
			ROUND(SUM(LENGTH(option_value)) / 1024, 2) as value,
			'autoload_size' as type
		FROM {$wpdb->options} 
		WHERE autoload = 'yes'
		
		UNION ALL
		
		SELECT 
			'Autoloaded Options Count' as metric,
			COUNT(*) as value,
			'autoload_count' as type
		FROM {$wpdb->options} 
		WHERE autoload = 'yes'
		
		UNION ALL
		
		SELECT 
			'Total Options Count' as metric,
			COUNT(*) as value,
			'total_count' as type
		FROM {$wpdb->options}
	", ARRAY_A);

	// Get largest autoloaded options
	$large_options = $wpdb->get_results("
		SELECT 
			option_name as metric,
			ROUND(LENGTH(option_value) / 1024, 2) as value,
			'large_option' as type
		FROM {$wpdb->options} 
		WHERE autoload = 'yes' 
		ORDER BY LENGTH(option_value) DESC 
		LIMIT 10
	", ARRAY_A);

	// Combine results
	$results = array_merge($autoload_stats, $large_options);

	// Add status information to results
	foreach ($results as &$result) {
		$result['status_class'] = '';
		$result['status_text'] = '';

		switch ($result['type']) {
			case 'autoload_size':
				if ($result['value'] > 1000) { // > 1MB
					$result['status_class'] = 'health-status-error';
					$result['status_text'] = ' - Critical: Very large autoloaded data';
				} elseif ($result['value'] > 500) { // > 500KB
					$result['status_class'] = 'health-status-warning';
					$result['status_text'] = ' - Warning: Large autoloaded data';
				} else {
					$result['status_class'] = 'health-status-good';
					$result['status_text'] = ' - Good';
				}
				$result['value'] = $result['value'] . ' KB';
				break;

			case 'autoload_count':
				if ($result['value'] > 1000) {
					$result['status_class'] = 'health-status-warning';
					$result['status_text'] = ' - Many autoloaded options';
				} elseif ($result['value'] > 500) {
					$result['status_class'] = 'health-status-warning';
					$result['status_text'] = ' - High number of autoloaded options';
				} else {
					$result['status_class'] = 'health-status-good';
					$result['status_text'] = ' - Normal';
				}
				break;

			case 'total_count':
				if ($result['value'] > 5000) {
					$result['status_class'] = 'health-status-warning';
					$result['status_text'] = ' - Large options table';
				} else {
					$result['status_class'] = 'health-status-good';
					$result['status_text'] = ' - Normal';
				}
				break;

			case 'large_option':
				if ($result['value'] > 100) { // > 100KB
					$result['status_class'] = 'health-status-error';
					$result['status_text'] = ' - Very large option!';
				} elseif ($result['value'] > 50) { // > 50KB
					$result['status_class'] = 'health-status-warning';
					$result['status_text'] = ' - Large option';
				} else {
					$result['status_class'] = 'health-status-good';
					$result['status_text'] = 'Normal';
				}
				$result['value'] = $result['value'] . ' KB';
				break;
		}
	}
	return $results;
}

/*geting lateest $limit plugin updates in table in Health check tab*/
function getLatestPluginUpdates($limit = 15)
{
	global $wpdb;

	$table_name = $wpdb->prefix . 'bf_plugin_log';

	// Check if table exists
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		return array(); // Return empty array if table doesn't exist
	}

	$querystr = "SELECT * FROM $table_name ORDER BY upgraded_datetime DESC LIMIT $limit";
	$results = $wpdb->get_results($querystr, ARRAY_A);

	return $results ? $results : array();
}

/*Get PHP memory limit for Health check tab*/
function getPHPMemoryLimit()
{
	$memory_limit = ini_get('memory_limit');
	$memory_limit_bytes = wp_convert_hr_to_bytes($memory_limit);
	$memory_usage = memory_get_usage(true);
	$memory_usage_percentage = round(($memory_usage / $memory_limit_bytes) * 100, 2);

	$status_class = '';
	if ($memory_usage_percentage < 70) {
		$status_class = 'health-status-good';
	} elseif ($memory_usage_percentage < 90) {
		$status_class = 'health-status-warning';
	} else {
		$status_class = 'health-status-error';
	}

	return array(
		'limit' => $memory_limit,
		'usage' => size_format($memory_usage),
		'usage_percentage' => $memory_usage_percentage . '%',
		'status_class' => $status_class
	);
}

/*Get PHP max execution time for Health check tab*/
function getPHPMaxExecutionTime()
{
	$max_execution_time = ini_get('max_execution_time');
	if ($max_execution_time == 0) {
		return 'Unlimited';
	}
	return $max_execution_time . ' seconds';
}

/*Check for SSL certificate for Health check tab*/
function checkSSLCertificate()
{
	$site_url = get_site_url();
	if (strpos($site_url, 'https://') === 0) {
		// Site is using HTTPS
		$domain = parse_url($site_url, PHP_URL_HOST);
		$ssl_info = @file_get_contents("https://api.ssllabs.com/api/v3/analyze?host=" . $domain . "&publish=off&all=done&ignoreMismatch=on");

		if ($ssl_info) {
			$ssl_data = json_decode($ssl_info, true);
			if (isset($ssl_data['endpoints'][0]['grade'])) {
				return '<span class="health-status-good">HTTPS Enabled - Grade: ' . $ssl_data['endpoints'][0]['grade'] . '</span>';
			}
		}
		return '<span class="health-status-good">HTTPS Enabled</span>';
	} else {
		return '<span class="health-status-error">No SSL (HTTP only)</span>';
	}
}

/*Check for outdated plugins for Health check tab*/
function getOutdatedPlugins()
{
	if (!function_exists('get_plugin_updates')) {
		require_once(ABSPATH . 'wp-admin/includes/update.php');
	}

	$plugin_updates = get_plugin_updates();
	$outdated_count = count($plugin_updates);

	if ($outdated_count > 0) {
		$status_class = $outdated_count > 5 ? 'health-status-error' : 'health-status-warning';
		return '<span class="' . $status_class . '">' . $outdated_count . ' plugin(s) need updates</span>';
	} else {
		return '<span class="health-status-good">All plugins up to date</span>';
	}
}

/*Check for inactive plugins for Health check tab*/
function getInactivePlugins()
{
	$all_plugins = get_plugins();
	$active_plugins = get_option('active_plugins');
	$inactive_plugins = array();

	foreach ($all_plugins as $plugin_file => $plugin_data) {
		if (!in_array($plugin_file, $active_plugins)) {
			$inactive_plugins[] = $plugin_data['Name'];
		}
	}

	$inactive_count = count($inactive_plugins);
	if ($inactive_count > 0) {
		$status_class = $inactive_count > 10 ? 'health-status-warning' : '';
		return '<span class="' . $status_class . '">' . $inactive_count . ' inactive plugin(s)</span>';
	} else {
		return '<span class="health-status-good">No inactive plugins</span>';
	}
}

/*Check WordPress core updates for Health check tab*/
function checkWordPressCoreUpdates()
{
	if (!function_exists('get_core_updates')) {
		require_once(ABSPATH . 'wp-admin/includes/update.php');
	}

	$core_updates = get_core_updates();
	if (!empty($core_updates) && $core_updates[0]->response == 'upgrade') {
		return '<span class="health-status-warning">Update available: ' . $core_updates[0]->version . '</span>';
	} else {
		return '<span class="health-status-good">WordPress is up to date</span>';
	}
}

/*Check debug mode status for Health check tab*/
function getDebugModeStatus()
{
    $debug_status = array();
    $options = get_option('blueflamingo_plugin_options_settings', array());

    // Helper: get effective status
    $get_status = function($constant, $option_key, $label, $default = false) use ($options) {
        // Determine constant value or fallback default
        $const_value = defined($constant) ? constant($constant) : $default;

        // Option value takes precedence if defined
        $option_value = isset($options[$option_key]) ? (bool)$options[$option_key] : null;
        $is_enabled = !is_null($option_value) ? $option_value : $const_value;

        $status_class = $is_enabled ? 'health-status-error' : 'health-status-good';
        $status_text = $is_enabled ? 'Enabled' : 'Disabled';

        return sprintf(
            '<span class="%s">%s: %s</span>',
            esc_attr($status_class),
            esc_html($label),
            esc_html($status_text)
        );
    };

    // Default WP behavior when constants are undefined
    $debug_status[] = $get_status('WP_DEBUG', 'wp_debug', 'WP_DEBUG', false);
    $debug_status[] = $get_status('WP_DEBUG_LOG', 'wp_debug_log', 'WP_DEBUG_LOG', false);
    $debug_status[] = $get_status('WP_DEBUG_DISPLAY', 'wp_debug_display', 'WP_DEBUG_DISPLAY', true);

    return implode('<br>', $debug_status);
}


/*Check theme updates for Health check tab*/
function getThemeUpdates()
{
	if (!function_exists('get_theme_updates')) {
		require_once(ABSPATH . 'wp-admin/includes/update.php');
	}

	$theme_updates = get_theme_updates();
	$outdated_count = count($theme_updates);

	if ($outdated_count > 0) {
		$status_class = $outdated_count > 3 ? 'health-status-error' : 'health-status-warning';
		return '<span class="' . $status_class . '">' . $outdated_count . ' theme(s) need updates</span>';
	} else {
		return '<span class="health-status-good">All themes up to date</span>';
	}
}

/*Check disk space usage for Health check tab*/
function getDiskSpaceUsage()
{
	// Check if disk space functions are available (they might be disabled on some hosting providers)
	if (!function_exists('disk_total_space') || !function_exists('disk_free_space')) {
		return array(
			'total' => 'N/A',
			'used' => 'N/A',
			'free' => 'N/A',
			'usage_percentage' => 'N/A',
			'status_class' => 'health-status-warning',
			'error' => 'Disk space functions not available on this server'
		);
	}

	$total_space = @disk_total_space(ABSPATH);
	$free_space = @disk_free_space(ABSPATH);

	// Check if the functions returned valid values
	if ($total_space === false || $free_space === false) {
		return array(
			'total' => 'N/A',
			'used' => 'N/A',
			'free' => 'N/A',
			'usage_percentage' => 'N/A',
			'status_class' => 'health-status-warning',
			'error' => 'Unable to retrieve disk space information'
		);
	}

	$used_space = $total_space - $free_space;
	$usage_percentage = round(($used_space / $total_space) * 100, 2);

	$status_class = '';
	if ($usage_percentage < 80) {
		$status_class = 'health-status-good';
	} elseif ($usage_percentage < 95) {
		$status_class = 'health-status-warning';
	} else {
		$status_class = 'health-status-error';
	}

	return array(
		'total' => size_format($total_space),
		'used' => size_format($used_space),
		'free' => size_format($free_space),
		'usage_percentage' => $usage_percentage . '%',
		'status_class' => $status_class
	);
}

/*Get MySQL version for Health check tab*/
function getMySQLVersion()
{
	global $wpdb;
	$mysql_version = $wpdb->get_var("SELECT VERSION()");
	return $mysql_version;
}

/*Check file permissions for Health check tab*/
function checkFilePermissions()
{
	$issues = [];

	// Check wp-content
	if (!file_exists(WP_CONTENT_DIR)) {
		$issues[] = 'wp-content directory not found';
	} else {
		$wp_content_perms = str_pad(substr(sprintf('%o', fileperms(WP_CONTENT_DIR)), -4), 4, '0', STR_PAD_LEFT);
		if (!in_array($wp_content_perms, ['0755', '0775'], true)) {
			$issues[] = 'wp-content: ' . $wp_content_perms;
		}
	}

	// Check uploads
	$uploads_dir = wp_upload_dir();
	if (!file_exists($uploads_dir['basedir'])) {
		$issues[] = 'uploads directory not found';
	} else {
		$uploads_perms = str_pad(substr(sprintf('%o', fileperms($uploads_dir['basedir'])), -4), 4, '0', STR_PAD_LEFT);
		if (!in_array($uploads_perms, ['0755', '0775'], true)) {
			$issues[] = 'uploads: ' . $uploads_perms;
		}
	}

	if (empty($issues)) {
		return '<span class="health-status-good">File permissions OK</span>';
	} else {
		return '<span class="health-status-warning">Check permissions: ' . implode(', ', $issues) . '</span>';
	}
}


/*Check WordPress auto-updates status*/
function getAutoUpdatesStatus()
{
	$status_items = array();
	$core_enabled = false;
	$plugins_enabled = false;
	$themes_enabled = false;

	// Check WordPress core auto-updates
	if (defined('WP_AUTO_UPDATE_CORE')) {
		if (WP_AUTO_UPDATE_CORE === true || WP_AUTO_UPDATE_CORE === 'minor') {
			$status_items[] = '<span class="health-status-good">Core: Enabled</span>';
			$core_enabled = true;
		} else {
			$status_items[] = '<span class="health-status-error">Core: Disabled</span>';
		}
	} else {
		// Default WordPress behavior for auto-updates (minor updates enabled)
		$status_items[] = '<span class="health-status-good">Core: Enabled (default)</span>';
		$core_enabled = true;
	}

	// Check plugin auto-updates - look for actual enabled plugins
	$auto_update_plugins = get_option('auto_update_plugins', array());
	if (!empty($auto_update_plugins)) {
		$plugins_enabled = true;
		$status_items[] = '<span class="health-status-error">Plugins: ' . count($auto_update_plugins) . ' enabled</span>';
	} else {
		$status_items[] = '<span class="health-status-good">Plugins: None enabled</span>';
	}

	// Check theme auto-updates - look for actual enabled themes
	$auto_update_themes = get_option('auto_update_themes', array());
	if (!empty($auto_update_themes)) {
		$themes_enabled = true;
		$status_items[] = '<span class="health-status-error">Themes: ' . count($auto_update_themes) . ' enabled</span>';
	} else {
		$status_items[] = '<span class="health-status-good">Themes: None enabled</span>';
	}

	return implode('<br>', $status_items);
}

/*Check for broken plugins for Health check tab*/
function checkBrokenPlugins()
{
	$all_plugins = get_plugins();
	$broken_plugins = array();

	foreach ($all_plugins as $plugin_file => $plugin_data) {
		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
		if (!file_exists($plugin_path)) {
			$broken_plugins[] = $plugin_data['Name'];
		}
	}

	if (empty($broken_plugins)) {
		return '<span class="health-status-good">No broken plugins detected</span>';
	} else {
		return '<span class="health-status-error">' . count($broken_plugins) . ' broken plugin(s) detected</span>';
	}
}

/*Check WordPress constants*/
function checkWordPressConstants()
{
	$constants = array();

	// Check important WordPress constants
	if (defined('WP_MEMORY_LIMIT')) {
		$constants[] = 'WP_MEMORY_LIMIT: ' . WP_MEMORY_LIMIT;
	}

	if (defined('WP_MAX_MEMORY_LIMIT')) {
		$constants[] = 'WP_MAX_MEMORY_LIMIT: ' . WP_MAX_MEMORY_LIMIT;
	}

	if (defined('WP_POST_REVISIONS')) {
		$revisions = WP_POST_REVISIONS === true ? 'Unlimited' : (int)WP_POST_REVISIONS;
		$constants[] = 'WP_POST_REVISIONS: ' . $revisions;
	}

	if (defined('AUTOSAVE_INTERVAL')) {
		$constants[] = 'AUTOSAVE_INTERVAL: ' . AUTOSAVE_INTERVAL . 's';
	}

	if (defined('WP_CRON_LOCK_TIMEOUT')) {
		$constants[] = 'WP_CRON_LOCK_TIMEOUT: ' . WP_CRON_LOCK_TIMEOUT . 's';
	}

	return implode('<br>', $constants);
}

/************codes for view plugin updates file with vality url blueflamingo/plugin-updates/************/
// Define custom rewrite rule 
function bf_custom_rewrite_rule()
{
	add_rewrite_rule('^blueflamingo/plugin-updates/?', 'index.php?bf_plugin_log=1', 'top');
}
add_action('init', 'bf_custom_rewrite_rule');

// Set query variable for custom URL
function bf_custom_query_vars($vars)
{
	$vars[] = 'bf_plugin_log';
	return $vars;
}
add_filter('query_vars', 'bf_custom_query_vars');

// Load custom PHP file
function bf_custom_template_include($template)
{
	if (get_query_var('bf_plugin_log')) {
		return ABSPATH . "/bf_plugin_log.php";
	}
	return $template;
}
add_filter('template_include', 'bf_custom_template_include');


/*************notes view and category codes*********************/
function add_blue_flamingo_notes_view_button($actions, $post)
{
	if ('blue-flamingo-notes' === $post->post_type) {
		$view_url = admin_url('admin.php?page=blue_flamingo_view_notes&note_id=' . $post->ID);
		$actions['view'] = '<a href="' . esc_url($view_url) . '" title="' . esc_attr__('View this item') . '">' . __('View') . '</a>';
	}

	return $actions;
}

function register_bf_note_view_page()
{
	add_submenu_page(
		null, // parent_slug set to null hides the submenu from the admin menu
		__('View Notes'), // page_title
		__('View Notes'), // menu_title
		'manage_options', // capability
		'blue_flamingo_view_notes', // menu_slug
		'display_blue_flamingo_notes_view_page' // function
	);
}

// Display custom page for viewing admin notes
function display_blue_flamingo_notes_view_page()
{
	if (!isset($_GET['note_id']) || !current_user_can('manage_options')) {
		wp_die(__('Invalid note ID or insufficient permissions.'));
	}

	$note_id = intval($_GET['note_id']);
	$note = get_post($note_id);

	if (!$note || 'blue-flamingo-notes' !== $note->post_type) {
		wp_die(__('Invalid note ID or post type.'));
	}

	echo '<div class="wrap">';
	echo '<h1>' . esc_html(get_the_title($note)) . '</h1><a href="' . admin_url('edit.php?post_type=blue-flamingo-notes') . '">Back to Notes</a>';
	echo '<div class="note-content">' . apply_filters('the_content', $note->post_content) . '</div>';
	echo '</div>';
}

function custom_notes_category()
{
	register_taxonomy(
		'blue_flamingonotes_category',
		'blue-flamingo-notes',
		array(
			'hierarchical' => true,
			'label' => 'Notes Categories',
			'query_var' => true,
			'rewrite' => array(
				'slug' => 'blue-flamingo-notes-category',
				'with_front' => true
			),
			'show_admin_column' => true,
		)
	);
}

/* Automatically log plugin updates to bf_plugin_log table */
add_action('upgrader_process_complete', function($upgrader, $hook_extra) {
	// Only log plugin updates
	if (
		isset($hook_extra['type']) && $hook_extra['type'] === 'plugin' &&
		isset($hook_extra['action']) && $hook_extra['action'] === 'update'
	) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'bf_plugin_log';
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
			$plugins = isset($hook_extra['plugins']) ? $hook_extra['plugins'] : array();
			foreach ($plugins as $plugin_file) {
				// Get plugin data
				if (!function_exists('get_plugin_data')) {
					require_once(ABSPATH . 'wp-admin/includes/plugin.php');
				}
				$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
				$plugin_name = isset($plugin_data['Name']) ? $plugin_data['Name'] : $plugin_file;
				$new_version = isset($plugin_data['Version']) ? $plugin_data['Version'] : '';
				// Try to get old version from transient
				$update_plugins = get_site_transient('update_plugins');
				$old_version = isset($update_plugins->checked[$plugin_file]) ? $update_plugins->checked[$plugin_file] : '';
				$wpdb->insert(
					$table_name,
					array(
						'plugin_name' => $plugin_name,
						'old_version' => $old_version,
						'new_version' => $new_version,
						'upgraded_datetime' => current_time('mysql')
					),
					array('%s', '%s', '%s', '%s')
				);
			}
		}
	}
}, 10, 2);


// Load plugins from JSON file
function load_plugins_by_type($type) {
    $json_path = blueflamingo_DIR . '/json/blueflamingo-plugins.json';
    $plugins = array();

    if (file_exists($json_path)) {
        $json = file_get_contents($json_path);
        $all_plugins = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON decode error: ' . json_last_error_msg());
            return $plugins;
        }

        // Loop through each section (core, common, etc.)
        foreach ($all_plugins as $section => $plugin_list) {
            if (!is_array($plugin_list) || $section !== $type) {
                continue;
            }

            foreach ($plugin_list as $plugin) {
				$plugins[$plugin['name']] = array(
					'slug' => $plugin['slug'],
					'basename' => $plugin['basename'],
					'type' => $plugin['type']
				);
            }
        }
    }
    return $plugins;
}

