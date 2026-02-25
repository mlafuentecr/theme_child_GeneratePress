<?php
/**
 * Child Theme Settings page under Appearance.
 *
 * Preserves Fireball option keys so data survives plugin removal:
 *   blueflamingo_plugin_general_settings          → move notes, live/staging URLs
 *   blueflamingo_plugin_options_settings           → toggles, default image, WhatConverts
 *   blueflamingo_plugin_google_analytics_settings  → GA4 / GTM
 *   blueflamingo_plugin_error_page_settings        → custom 404
 *   gp_child_css_version                           → cache buster counter
 */

if (! defined('ABSPATH')) {
    exit;
}

// ── 1. Register the Appearance sub-page ──────────────────────────────────────

add_action('admin_menu', function (): void {
    add_theme_page(
        __('Child Theme Settings', 'generatepress-child'),
        __('Child Theme Settings', 'generatepress-child'),
        'manage_options',
        'gp-child-settings',
        'gp_child_render_settings_page'
    );
});

// ── 2. Enqueue WP media + admin JS on our page only ──────────────────────────

add_action('admin_enqueue_scripts', function (string $hook): void {
    if ($hook !== 'appearance_page_gp-child-settings') {
        return;
    }
    wp_enqueue_media();
    // Use 'jquery' (not 'jquery-core') so the script runs after jQuery is ready
    wp_enqueue_script('jquery');
    wp_add_inline_script('jquery', gp_child_admin_js());
});

function gp_child_admin_js(): string
{
    return <<<'JS'
(function($){
    $(function(){
        var STORAGE_KEY = 'gp_child_active_tab';

        // ── Activate a tab by its data-tab value ───────────────────────────
        function activateTab(tabId) {
            // Update buttons
            $('.gp-tab-btn').removeClass('is-active').attr('aria-selected', 'false');
            var $btn = $('.gp-tab-btn[data-tab="' + tabId + '"]');
            if (!$btn.length) {
                $btn = $('.gp-tab-btn').first();
                tabId = $btn.data('tab');
            }
            $btn.addClass('is-active').attr('aria-selected', 'true');

            // Update panels
            $('.gp-tab-panel').removeClass('is-active');
            $('#gp-tab-' + tabId).addClass('is-active');

            // Remember choice
            try { sessionStorage.setItem(STORAGE_KEY, tabId); } catch(e) {}
        }

        // ── Determine initial tab ──────────────────────────────────────────
        var saved = '';
        try { saved = sessionStorage.getItem(STORAGE_KEY) || ''; } catch(e) {}
        var initial = saved || $('.gp-tab-btn').first().data('tab');
        activateTab(initial);

        // ── Click handler ──────────────────────────────────────────────────
        $(document).on('click', '.gp-tab-btn', function(){
            activateTab($(this).data('tab'));
        });

        // ── Default Featured Image media picker ────────────────────────────
        var frame;
        $(document).on('click', '#gp-dfi-select', function(e){
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({
                title: 'Select Default Featured Image',
                multiple: false,
                library: { type: 'image' },
                button: { text: 'Use this image' }
            });
            frame.on('select', function(){
                var att = frame.state().get('selection').first().toJSON();
                $('#gp-dfi-id').val(att.id);
                var src = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
                $('#gp-dfi-preview').attr('src', src).show();
                $('#gp-dfi-remove').show();
            });
            frame.open();
        });
        $(document).on('click', '#gp-dfi-remove', function(e){
            e.preventDefault();
            $('#gp-dfi-id').val('');
            $('#gp-dfi-preview').attr('src', '').hide();
            $(this).hide();
        });
    });
}(jQuery));
JS;
}

// ── 3. Register every settings group ─────────────────────────────────────────

add_action('admin_init', function (): void {

    // Site Info (preserves Fireball general settings key)
    register_setting('gp_child_site_info_group',  'blueflamingo_plugin_general_settings',         ['sanitize_callback' => 'gp_child_sanitize_general']);

    // Options (preserves Fireball options key)
    register_setting('gp_child_options_group',    'blueflamingo_plugin_options_settings',          ['sanitize_callback' => 'gp_child_sanitize_options']);

    // Google Analytics / GTM (preserves Fireball GA key)
    register_setting('gp_child_ga_group',         'blueflamingo_plugin_google_analytics_settings', ['sanitize_callback' => 'gp_child_sanitize_ga']);

    // Custom 404 (preserves Fireball error page key)
    register_setting('gp_child_404_group',        'blueflamingo_plugin_error_page_settings',       ['sanitize_callback' => 'gp_child_sanitize_404']);

    // Cache buster (new – child theme only)
    register_setting('gp_child_cache_group',      'gp_child_css_version',                          ['type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 1]);
});

// ── 4. Sanitize callbacks ─────────────────────────────────────────────────────

function gp_child_sanitize_general(mixed $input): array
{
    if (! is_array($input)) {
        $input = [];
    }
    // Merge with existing so we never wipe Fireball-managed keys
    $existing = (array) get_option('blueflamingo_plugin_general_settings', []);
    $clean    = $existing;

    $clean['move_notes']  = sanitize_textarea_field($input['move_notes']  ?? '');
    $clean['live_url']    = sanitize_text_field($input['live_url']    ?? '');
    $clean['staging_url'] = sanitize_text_field($input['staging_url'] ?? '');

    return $clean;
}

function gp_child_sanitize_options(mixed $input): array
{
    if (! is_array($input)) {
        $input = [];
    }
    $existing = (array) get_option('blueflamingo_plugin_options_settings', []);
    $clean    = $existing;

    $clean['json_basic_authentication']    = ! empty($input['json_basic_authentication'])    ? '1' : '0';
    $clean['hide_google_recaptcha_logo']   = ! empty($input['hide_google_recaptcha_logo'])   ? '1' : '0';
    $clean['admin_user_registration_date'] = ! empty($input['admin_user_registration_date']) ? '1' : '0';
    $clean['default_featured_image']       = absint($input['default_featured_image'] ?? 0);
    $clean['id_whatConverts']              = sanitize_text_field($input['id_whatConverts'] ?? '');

    return $clean;
}

function gp_child_sanitize_ga(mixed $input): array
{
    if (! is_array($input)) {
        $input = [];
    }
    $existing = (array) get_option('blueflamingo_plugin_google_analytics_settings', []);
    $clean    = $existing;

    $clean['activate_google_analytics']  = ! empty($input['activate_google_analytics'])  ? '1' : '0';
    $clean['google_analytics_id']        = sanitize_text_field($input['google_analytics_id'] ?? '');
    $clean['google_analytics_position']  = in_array($input['google_analytics_position'] ?? '', ['Head', 'Footer'], true)
        ? $input['google_analytics_position']
        : 'Head';
    $clean['google_analytics_logged_in'] = ! empty($input['google_analytics_logged_in']) ? '1' : '0';

    return $clean;
}

function gp_child_sanitize_404(mixed $input): array
{
    if (! is_array($input)) {
        $input = [];
    }
    $existing = (array) get_option('blueflamingo_plugin_error_page_settings', []);
    $clean    = $existing;

    $clean['activate_404']    = ! empty($input['activate_404']) ? '1' : '0';
    $clean['custom_404_page'] = absint($input['custom_404_page'] ?? 0);

    return $clean;
}

// ── 5. Settings page renderer ─────────────────────────────────────────────────

function gp_child_render_settings_page(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $gen  = (array) get_option('blueflamingo_plugin_general_settings', []);
    $opts = (array) get_option('blueflamingo_plugin_options_settings', []);
    $ga   = (array) get_option('blueflamingo_plugin_google_analytics_settings', []);
    $ep   = (array) get_option('blueflamingo_plugin_error_page_settings', []);
    $cv   = intval(get_option('gp_child_css_version', 1));

    $dfi_id  = intval($opts['default_featured_image'] ?? 0);
    $dfi_url = $dfi_id ? wp_get_attachment_image_url($dfi_id, 'thumbnail') : '';

    $tabs = [
        'site-info'  => __('Site Info',    'generatepress-child'),
        'options'    => __('Options',      'generatepress-child'),
        'analytics'  => __('Analytics',    'generatepress-child'),
        '404'        => __('404 Page',     'generatepress-child'),
        'cache'      => __('Cache Buster', 'generatepress-child'),
    ];
    ?>
    <div class="wrap gp-child-settings">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <style>
            /* ── Wrapper ──────────────────────────────────────────────────── */
            .gp-child-settings { max-width: 900px; }

            /* ── Tab nav: use nav+button so WordPress list CSS can't interfere */
            .gp-tab-nav-wrap {
                display: -webkit-box;
                display: -ms-flexbox;
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
                gap: 0;
                margin: 18px 0 0;
                padding: 0;
                border-bottom: 1px solid #c3c4c7;
            }
            .gp-tab-btn {
                display: inline-block;
                padding: 9px 17px;
                cursor: pointer;
                font-size: 13px;
                font-weight: 500;
                color: #50575e;
                background: #f1f1f1;
                border: 1px solid #c3c4c7;
                border-bottom: none;
                border-radius: 3px 3px 0 0;
                margin-right: 3px;
                margin-bottom: -1px;
                line-height: 1.5;
                white-space: nowrap;
                text-decoration: none;
                transition: color .15s, background .15s;
                outline: none;
            }
            .gp-tab-btn:hover { color: #1d2327; background: #f6f7f7; }
            .gp-tab-btn.is-active {
                background: #fff;
                border-color: #c3c4c7;
                color: #1d2327;
                font-weight: 600;
            }

            /* ── Tab panels ─────────────────────────────────────────────── */
            .gp-tab-panel {
                display: none;          /* hidden by default — JS shows active one */
                background: #fff;
                border: 1px solid #c3c4c7;
                border-top: none;
                border-radius: 0 0 3px 3px;
                padding: 24px 28px;
            }
            .gp-tab-panel.is-active { display: block; }
            .gp-tab-panel .form-table th { width: 220px; }

            /* ── Default Featured Image ─────────────────────────────────── */
            .gp-dfi-preview {
                max-width: 120px;
                max-height: 90px;
                display: block;
                margin-top: 6px;
                border-radius: 3px;
                border: 1px solid #ddd;
            }

            /* ── No-JS fallback: show all panels ────────────────────────── */
            .gp-child-settings.no-js .gp-tab-panel { display: block; margin-bottom: 16px; border-top: 1px solid #c3c4c7; border-radius: 3px; }
        </style>

        <?php /* ── Tab navigation: <nav> + <button> avoids WP list styles ── */ ?>
        <nav class="gp-tab-nav-wrap" role="tablist" aria-label="<?php esc_attr_e('Settings sections', 'generatepress-child'); ?>">
            <?php foreach ($tabs as $id => $label) : ?>
                <button type="button"
                        class="gp-tab-btn"
                        role="tab"
                        data-tab="<?php echo esc_attr($id); ?>"
                        aria-controls="gp-tab-<?php echo esc_attr($id); ?>"
                        aria-selected="false">
                    <?php echo esc_html($label); ?>
                </button>
            <?php endforeach; ?>
        </nav>

        <?php /* ── Site Info ─────────────────────────────────────────── */ ?>
        <div id="gp-tab-site-info" class="gp-tab-panel" role="tabpanel">
            <form method="post" action="options.php">
                <?php settings_fields('gp_child_site_info_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Move Notes', 'generatepress-child'); ?></th>
                        <td>
                            <textarea name="blueflamingo_plugin_general_settings[move_notes]"
                                      rows="5" class="large-text"><?php echo esc_textarea($gen['move_notes'] ?? ''); ?></textarea>
                            <p class="description"><?php esc_html_e('Internal notes — migration status, site quirks, project history.', 'generatepress-child'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Live Site URL', 'generatepress-child'); ?></th>
                        <td>
                            <input type="text" name="blueflamingo_plugin_general_settings[live_url]"
                                   value="<?php echo esc_attr($gen['live_url'] ?? ''); ?>" class="regular-text"
                                   placeholder="example.com">
                            <p class="description"><?php esc_html_e('Excluding https://www.', 'generatepress-child'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Staging Site URL', 'generatepress-child'); ?></th>
                        <td>
                            <input type="text" name="blueflamingo_plugin_general_settings[staging_url]"
                                   value="<?php echo esc_attr($gen['staging_url'] ?? ''); ?>" class="regular-text"
                                   placeholder="staging.example.com">
                            <p class="description"><?php esc_html_e('Excluding https://www.', 'generatepress-child'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Site Info', 'generatepress-child')); ?>
            </form>
        </div>

        <?php /* ── Options ────────────────────────────────────────────── */ ?>
        <div id="gp-tab-options" class="gp-tab-panel" role="tabpanel">
            <form method="post" action="options.php">
                <?php settings_fields('gp_child_options_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('JSON Basic Authentication', 'generatepress-child'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="blueflamingo_plugin_options_settings[json_basic_authentication]"
                                       value="1" <?php checked('1', $opts['json_basic_authentication'] ?? ''); ?>>
                                <?php esc_html_e('Enable JSON Basic Authentication for the REST API.', 'generatepress-child'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Hide reCAPTCHA Badge', 'generatepress-child'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="blueflamingo_plugin_options_settings[hide_google_recaptcha_logo]"
                                       value="1" <?php checked('1', $opts['hide_google_recaptcha_logo'] ?? ''); ?>>
                                <?php esc_html_e('Hide the floating Google reCAPTCHA v3 badge via CSS.', 'generatepress-child'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('User Registration Date', 'generatepress-child'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="blueflamingo_plugin_options_settings[admin_user_registration_date]"
                                       value="1" <?php checked('1', $opts['admin_user_registration_date'] ?? ''); ?>>
                                <?php esc_html_e('Add a Registration Date column to the Users list table.', 'generatepress-child'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Default Featured Image', 'generatepress-child'); ?></th>
                        <td>
                            <input type="hidden" id="gp-dfi-id"
                                   name="blueflamingo_plugin_options_settings[default_featured_image]"
                                   value="<?php echo esc_attr($dfi_id); ?>">
                            <img id="gp-dfi-preview" class="gp-dfi-preview"
                                 src="<?php echo esc_url($dfi_url ?: ''); ?>"
                                 alt="" <?php echo $dfi_url ? '' : 'style="display:none;"'; ?>>
                            <p style="margin-top:8px;">
                                <button type="button" class="button" id="gp-dfi-select">
                                    <?php esc_html_e('Select Image', 'generatepress-child'); ?>
                                </button>
                                <button type="button" class="button" id="gp-dfi-remove"
                                        <?php echo $dfi_id ? '' : 'style="display:none;"'; ?>>
                                    <?php esc_html_e('Remove', 'generatepress-child'); ?>
                                </button>
                            </p>
                            <p class="description"><?php esc_html_e('Fallback image shown when a post has no featured image.', 'generatepress-child'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('WhatConverts ID', 'generatepress-child'); ?></th>
                        <td>
                            <input type="text"
                                   name="blueflamingo_plugin_options_settings[id_whatConverts]"
                                   value="<?php echo esc_attr($opts['id_whatConverts'] ?? ''); ?>"
                                   class="regular-text" placeholder="123456">
                            <p class="description"><?php esc_html_e('WhatConverts account ID. Leave blank to disable.', 'generatepress-child'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Options', 'generatepress-child')); ?>
            </form>
        </div>

        <?php /* ── Google Analytics / GTM ─────────────────────────────── */ ?>
        <div id="gp-tab-analytics" class="gp-tab-panel" role="tabpanel">
            <form method="post" action="options.php">
                <?php settings_fields('gp_child_ga_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Activate', 'generatepress-child'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="blueflamingo_plugin_google_analytics_settings[activate_google_analytics]"
                                       value="1" <?php checked('1', $ga['activate_google_analytics'] ?? ''); ?>>
                                <?php esc_html_e('Enable Analytics / GTM output on the front end.', 'generatepress-child'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Tracking ID', 'generatepress-child'); ?></th>
                        <td>
                            <input type="text"
                                   name="blueflamingo_plugin_google_analytics_settings[google_analytics_id]"
                                   value="<?php echo esc_attr($ga['google_analytics_id'] ?? ''); ?>"
                                   class="regular-text" placeholder="G-XXXXXXXXXX or GTM-XXXXXXX">
                            <p class="description"><?php esc_html_e('GA4 (G-…) or GTM container ID (GTM-…). Type is auto-detected.', 'generatepress-child'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Script Position', 'generatepress-child'); ?></th>
                        <td>
                            <label>
                                <input type="radio"
                                       name="blueflamingo_plugin_google_analytics_settings[google_analytics_position]"
                                       value="Head" <?php checked('Head', $ga['google_analytics_position'] ?? 'Head'); ?>>
                                <?php esc_html_e('Head (default)', 'generatepress-child'); ?>
                            </label>&nbsp;&nbsp;
                            <label>
                                <input type="radio"
                                       name="blueflamingo_plugin_google_analytics_settings[google_analytics_position]"
                                       value="Footer" <?php checked('Footer', $ga['google_analytics_position'] ?? ''); ?>>
                                <?php esc_html_e('Footer', 'generatepress-child'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Include Logged-In Users', 'generatepress-child'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="blueflamingo_plugin_google_analytics_settings[google_analytics_logged_in]"
                                       value="1" <?php checked('1', $ga['google_analytics_logged_in'] ?? ''); ?>>
                                <?php esc_html_e('Track admin/logged-in users as well.', 'generatepress-child'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Analytics', 'generatepress-child')); ?>
            </form>
        </div>

        <?php /* ── Custom 404 Page ──────────────────────────────────────── */ ?>
        <div id="gp-tab-404" class="gp-tab-panel" role="tabpanel">
            <form method="post" action="options.php">
                <?php settings_fields('gp_child_404_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Activate', 'generatepress-child'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="blueflamingo_plugin_error_page_settings[activate_404]"
                                       value="1" <?php checked('1', $ep['activate_404'] ?? ''); ?>>
                                <?php esc_html_e('Use a custom WordPress page as the 404 error template.', 'generatepress-child'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Select Page', 'generatepress-child'); ?></th>
                        <td>
                            <select name="blueflamingo_plugin_error_page_settings[custom_404_page]">
                                <option value="0"><?php esc_html_e('— Select a page —', 'generatepress-child'); ?></option>
                                <?php foreach (get_pages() as $page) : ?>
                                    <option value="<?php echo esc_attr($page->ID); ?>"
                                            <?php selected(intval($ep['custom_404_page'] ?? 0), $page->ID); ?>>
                                        <?php echo esc_html($page->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save 404 Settings', 'generatepress-child')); ?>
            </form>
        </div>

        <?php /* ── Cache Buster ─────────────────────────────────────────── */ ?>
        <div id="gp-tab-cache" class="gp-tab-panel" role="tabpanel">
            <p><?php
                printf(
                    esc_html__('Increment the counter to force browsers to re-download all theme assets. Current version: %s', 'generatepress-child'),
                    '<strong>' . esc_html(GP_CHILD_VERSION . '.' . $cv) . '</strong>'
                );
            ?></p>
            <form method="post" action="options.php">
                <?php settings_fields('gp_child_cache_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('CSS / JS Version Counter', 'generatepress-child'); ?></th>
                        <td>
                            <input type="number" name="gp_child_css_version"
                                   value="<?php echo esc_attr($cv); ?>" min="1" step="1" style="width:80px;">
                            &nbsp;
                            <button type="submit" name="gp_child_css_version"
                                    value="<?php echo esc_attr($cv + 1); ?>"
                                    class="button button-secondary">
                                <?php esc_html_e('Increment +1', 'generatepress-child'); ?>
                            </button>
                            <p class="description"><?php esc_html_e('Increase this number to bust caches on all front-end assets.', 'generatepress-child'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Version', 'generatepress-child')); ?>
            </form>
        </div>

    </div><!-- .gp-child-settings -->
    <?php
}

// ── 6. Front-end: apply all saved settings ────────────────────────────────────

// — JSON Basic Auth ——————————————————————————————————————————————————————————
add_action('init', function (): void {
    $opts = (array) get_option('blueflamingo_plugin_options_settings', []);
    if (! empty($opts['json_basic_authentication'])) {
        add_filter('determine_current_user', 'gp_child_json_basic_auth_handler', 20);
        add_filter('rest_authentication_errors', 'gp_child_json_basic_auth_error');
    }
});

function gp_child_json_basic_auth_handler(mixed $user): mixed
{
    global $gp_child_json_basic_auth_error;
    $gp_child_json_basic_auth_error = null;

    if (! empty($user)) {
        return $user;
    }
    if (! isset($_SERVER['PHP_AUTH_USER'])) {
        return $user;
    }

    remove_filter('determine_current_user', 'gp_child_json_basic_auth_handler', 20);
    $auth_user = wp_authenticate(
        sanitize_user((string) $_SERVER['PHP_AUTH_USER']),
        (string) ($_SERVER['PHP_AUTH_PW'] ?? '')
    );
    add_filter('determine_current_user', 'gp_child_json_basic_auth_handler', 20);

    if (is_wp_error($auth_user)) {
        $gp_child_json_basic_auth_error = $auth_user;
        return null;
    }

    $gp_child_json_basic_auth_error = true;
    return $auth_user->ID;
}

function gp_child_json_basic_auth_error(mixed $error): mixed
{
    if (! empty($error)) {
        return $error;
    }
    global $gp_child_json_basic_auth_error;
    return $gp_child_json_basic_auth_error;
}

// — Hide reCAPTCHA badge ——————————————————————————————————————————————————————
add_action('wp_head', function (): void {
    $opts = (array) get_option('blueflamingo_plugin_options_settings', []);
    if (! empty($opts['hide_google_recaptcha_logo'])) {
        echo '<style>.grecaptcha-badge{visibility:collapse!important;}</style>' . "\n";
    }
}, 99);

// — User registration date column ────────────────────────────────────────────
add_action('init', function (): void {
    $opts = (array) get_option('blueflamingo_plugin_options_settings', []);
    if (empty($opts['admin_user_registration_date'])) {
        return;
    }
    add_filter('manage_users_columns', function (array $cols): array {
        $cols['registration_date'] = __('Registered', 'generatepress-child');
        return $cols;
    });
    add_filter('manage_users_custom_column', function (string $out, string $col, int $uid): string {
        if ($col !== 'registration_date') {
            return $out;
        }
        $user = get_userdata($uid);
        return $user ? esc_html(date_i18n('j M Y', strtotime($user->user_registered))) : '—';
    }, 10, 3);
    add_filter('manage_users_sortable_columns', function (array $cols): array {
        return wp_parse_args(['registration_date' => 'registered'], $cols);
    });
});

// — Default featured image fallback ──────────────────────────────────────────
add_filter('post_thumbnail_html', function (string $html, int $post_id, int $thumbnail_id, mixed $size, mixed $attr): string {
    if (! empty($html)) {
        return $html;
    }
    $opts       = (array) get_option('blueflamingo_plugin_options_settings', []);
    $default_id = intval($opts['default_featured_image'] ?? 0);
    if ($default_id) {
        $html = wp_get_attachment_image($default_id, $size, false, (array) $attr);
    }
    return $html;
}, 10, 5);

// — WhatConverts ─────────────────────────────────────────────────────────────
add_action('wp_footer', function (): void {
    $opts  = (array) get_option('blueflamingo_plugin_options_settings', []);
    $wc_id = sanitize_text_field($opts['id_whatConverts'] ?? '');
    if (! empty($wc_id)) {
        printf(
            '<script src="//scripts.iconnode.com/%s.js"></script>' . "\n",
            esc_attr($wc_id)
        );
    }
}, 99);

// — Google Analytics / GTM ───────────────────────────────────────────────────
add_action('init', function (): void {
    $ga = (array) get_option('blueflamingo_plugin_google_analytics_settings', []);
    if (empty($ga['activate_google_analytics']) || empty($ga['google_analytics_id'])) {
        return;
    }
    $hook = ($ga['google_analytics_position'] ?? 'Head') === 'Footer' ? 'wp_footer' : 'wp_head';
    add_action($hook, 'gp_child_output_analytics', 1);
});

function gp_child_output_analytics(): void
{
    $ga = (array) get_option('blueflamingo_plugin_google_analytics_settings', []);
    $id = sanitize_text_field($ga['google_analytics_id'] ?? '');

    if (empty($id)) {
        return;
    }
    // Skip admins unless "include logged-in" is ticked
    if (is_user_logged_in() && current_user_can('update_core') && empty($ga['google_analytics_logged_in'])) {
        return;
    }

    if (strncmp($id, 'GTM-', 4) === 0) {
        // Google Tag Manager
        ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo esc_js($id); ?>');</script>
<!-- End Google Tag Manager -->
        <?php
    } else {
        // GA4 (G-…)
        ?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($id); ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?php echo esc_js($id); ?>');</script>
<!-- End Google tag -->
        <?php
    }
}

// — Custom 404 page ───────────────────────────────────────────────────────────
add_action('init', function (): void {
    $ep = (array) get_option('blueflamingo_plugin_error_page_settings', []);
    if (! empty($ep['activate_404'])) {
        add_filter('404_template', 'gp_child_custom_404_template');
    }
});

function gp_child_custom_404_template(string $template): string
{
    global $wp_query, $post;

    $ep          = (array) get_option('blueflamingo_plugin_error_page_settings', []);
    $custom_page = get_post(intval($ep['custom_404_page'] ?? 0));

    if (! ($custom_page instanceof WP_Post)) {
        return $template;
    }

    $post = $custom_page; // phpcs:ignore WordPress.WP.GlobalVariablesOverride

    $wp_query->posts             = [$post];
    $wp_query->queried_object_id = $post->ID;
    $wp_query->queried_object    = $post;
    $wp_query->post_count        = 1;
    $wp_query->found_posts       = 1;
    $wp_query->is_404            = false;
    $wp_query->is_page           = true;
    $wp_query->is_singular       = true;

    return get_page_template();
}
