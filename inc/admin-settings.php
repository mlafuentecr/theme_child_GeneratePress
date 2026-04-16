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
        GP_CHILD_BRAND . ' ' . __('Settings', 'generatepress-child'),
        GP_CHILD_BRAND . ' ' . __('Settings', 'generatepress-child'),
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
    wp_register_script('gp-child-admin', false, [], false, true);
    wp_enqueue_script('gp-child-admin');
    wp_localize_script('gp-child-admin', 'gpNotes', [
        'nonce'   => wp_create_nonce('gp_child_notes'),
        'ajaxurl' => admin_url('admin-ajax.php'),
    ]);
    wp_localize_script('gp-child-admin', 'gpDfi', [
        'title'  => __('Select Default Featured Image', 'generatepress-child'),
        'button' => __('Use this image', 'generatepress-child'),
    ]);
    wp_add_inline_script('gp-child-admin', gp_child_admin_js());
});

function gp_child_admin_js(): string
{
    $script = <<<'JS'
(function(){
    document.addEventListener('DOMContentLoaded', function(){
        var STORAGE_KEY = 'gp_child_active_tab';

        // ── Activate a tab by its data-tab value ───────────────────────────
        function activateTab(tabId) {
            document.querySelectorAll('.gp-tab-btn').forEach(function(btn){
                btn.classList.remove('is-active');
                btn.setAttribute('aria-selected', 'false');
            });
            var btn = document.querySelector('.gp-tab-btn[data-tab="' + tabId + '"]');
            if (!btn) {
                btn = document.querySelector('.gp-tab-btn');
                tabId = btn ? btn.dataset.tab : '';
            }
            if (btn) {
                btn.classList.add('is-active');
                btn.setAttribute('aria-selected', 'true');
            }
            document.querySelectorAll('.gp-tab-panel').forEach(function(panel){
                panel.classList.remove('is-active');
            });
            var panel = document.getElementById('gp-tab-' + tabId);
            if (panel) { panel.classList.add('is-active'); }
            try { sessionStorage.setItem(STORAGE_KEY, tabId); } catch(e) {}
        }

        // ── Determine initial tab ──────────────────────────────────────────
        var saved = '';
        try { saved = sessionStorage.getItem(STORAGE_KEY) || ''; } catch(e) {}
        var firstBtn = document.querySelector('.gp-tab-btn');
        activateTab(saved || (firstBtn ? firstBtn.dataset.tab : ''));

        // ── Click handler ──────────────────────────────────────────────────
        document.addEventListener('click', function(e){
            var btn = e.target.closest('.gp-tab-btn');
            if (btn) { activateTab(btn.dataset.tab); }
        });

        // ── Default Featured Image media picker ────────────────────────────
        var frame;
        document.addEventListener('click', function(e){
            var sel = e.target.closest('#gp-dfi-select');
            if (sel) {
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({
                    title:    (window.gpDfi && gpDfi.title)  || 'Select Default Featured Image',
                    multiple: false,
                    library:  { type: 'image' },
                    button:   { text: (window.gpDfi && gpDfi.button) || 'Use this image' }
                });
                frame.on('select', function(){
                    var att = frame.state().get('selection').first().toJSON();
                    document.getElementById('gp-dfi-id').value = att.id;
                    var src = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
                    var preview = document.getElementById('gp-dfi-preview');
                    preview.src = src;
                    preview.style.display = '';
                    document.getElementById('gp-dfi-remove').style.display = '';
                });
                frame.on('open', function(){
                    var id = document.getElementById('gp-dfi-id').value;
                    if (!id) { return; }
                    var attachment = wp.media.attachment(id);
                    attachment.fetch();
                    frame.state().get('selection').add(attachment ? [attachment] : []);
                });
                frame.open();
            }
        });
        document.addEventListener('click', function(e){
            var rem = e.target.closest('#gp-dfi-remove');
            if (rem) {
                e.preventDefault();
                document.getElementById('gp-dfi-id').value = '';
                var preview = document.getElementById('gp-dfi-preview');
                preview.src = '';
                preview.style.display = 'none';
                rem.style.display = 'none';
            }
        });

        // ── Notes ──────────────────────────────────────────────────────────
        var COLORS = ['yellow','blue','green','pink'];

        function gpEsc(str) {
            var div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }

        function buildCard(note) {
            var colorsHtml = '';
            COLORS.forEach(function(c){
                colorsHtml += '<span class="gp-note-color-dot' + (note.color === c ? ' is-selected' : '') +
                    '" data-color="' + c + '" title="' + c + '"></span>';
            });
            return '<div class="gp-note-card" data-id="' + note.id + '" data-color="' + (note.color||'yellow') + '">' +
                '<div class="gp-note-colors">' + colorsHtml + '</div>' +
                '<button type="button" class="gp-note-delete" title="Delete note">&#10005;</button>' +
                '<input type="text" class="gp-note-title" placeholder="Title…" value="' + gpEsc(note.title) + '">' +
                '<textarea class="gp-note-body" placeholder="Note…" rows="5">' + gpEsc(note.content) + '</textarea>' +
                '<div class="gp-note-footer">' +
                    '<span class="gp-note-status"></span>' +
                    '<button type="button" class="button button-primary gp-note-save">Save</button>' +
                '</div>' +
            '</div>';
        }

        function ajaxPost(data, callback) {
            fetch(gpNotes.ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data).toString()
            })
            .then(function(res){ return res.json(); })
            .then(callback)
            .catch(function(){ callback({ success: false }); });
        }

        // Add note
        document.addEventListener('click', function(e){
            var addBtn = e.target.closest('#gp-add-note');
            if (addBtn) {
                addBtn.disabled = true;
                ajaxPost({ action: 'gp_child_add_note', nonce: gpNotes.nonce }, function(r){
                    if (r.success) {
                        document.getElementById('gp-notes-grid').insertAdjacentHTML('afterbegin', buildCard(r.data));
                    }
                    addBtn.disabled = false;
                });
            }
        });

        // Color picker
        document.addEventListener('click', function(e){
            var dot = e.target.closest('.gp-note-color-dot');
            if (dot) {
                var card = dot.closest('.gp-note-card');
                card.setAttribute('data-color', dot.dataset.color);
                card.querySelectorAll('.gp-note-color-dot').forEach(function(d){
                    d.classList.remove('is-selected');
                });
                dot.classList.add('is-selected');
            }
        });

        // Save note
        document.addEventListener('click', function(e){
            var saveBtn = e.target.closest('.gp-note-save');
            if (saveBtn) {
                saveBtn.disabled = true;
                var card = saveBtn.closest('.gp-note-card');
                var status = card.querySelector('.gp-note-status');
                status.textContent = 'Saving…';
                ajaxPost({
                    action:  'gp_child_save_note',
                    nonce:   gpNotes.nonce,
                    id:      card.dataset.id,
                    title:   card.querySelector('.gp-note-title').value,
                    content: card.querySelector('.gp-note-body').value,
                    color:   card.getAttribute('data-color')
                }, function(r){
                    status.textContent = r.success ? 'Saved ✓' : 'Error';
                    setTimeout(function(){ status.textContent = ''; }, 2500);
                    saveBtn.disabled = false;
                });
            }
        });

        // Delete note
        document.addEventListener('click', function(e){
            var delBtn = e.target.closest('.gp-note-delete');
            if (delBtn) {
                if (!window.confirm('Delete this note?')) { return; }
                var card = delBtn.closest('.gp-note-card');
                ajaxPost({
                    action: 'gp_child_delete_note',
                    nonce:  gpNotes.nonce,
                    id:     card.dataset.id
                }, function(r){
                    if (r.success) { card.remove(); }
                });
            }
        });

        function toggleSearchResultsPageRow() {
            var selected = document.querySelector('input[name="gp_child_search_settings[mode]"]:checked');
            var row = document.querySelector('[data-search-results-page-row]');
            if (!row) { return; }
            row.style.display = selected && selected.value === 'results_page' ? '' : 'none';
        }

        document.addEventListener('change', function(e){
            if (e.target && e.target.name === 'gp_child_search_settings[mode]') {
                toggleSearchResultsPageRow();
            }
        });

        toggleSearchResultsPageRow();
    });
}());
JS;

    return $script . "\n" . apply_filters('gp_child_admin_settings_js', '');
}

// ── 3. Register every settings group ─────────────────────────────────────────

// Priority 20 so our registration runs AFTER the bf-fireball plugin's register_stripe_fields()
// (which fires at admin_init priority 10). This ensures our group names and sanitize callbacks
// win in $wp_registered_settings for the shared option keys.
add_action('admin_init', function (): void {

    // Site Info (preserves Fireball general settings key)
    register_setting('gp_child_site_info_group',  'blueflamingo_plugin_general_settings',         ['sanitize_callback' => 'gp_child_sanitize_general']);

    // Options (preserves Fireball options key)
    register_setting('gp_child_options_group',    'blueflamingo_plugin_options_settings',          ['sanitize_callback' => 'gp_child_sanitize_options']);

    // Google Analytics / GTM (preserves Fireball GA key)
    register_setting('gp_child_ga_group',         'blueflamingo_plugin_google_analytics_settings', ['sanitize_callback' => 'gp_child_sanitize_ga']);

    // Custom 404 (preserves Fireball error page key)
    register_setting('gp_child_404_group',        'blueflamingo_plugin_error_page_settings',       ['sanitize_callback' => 'gp_child_sanitize_404']);

    // Email redirect (preserves Fireball email redirect key)
    register_setting('gp_child_email_redirect_group', 'blueflamingo_plugin_email_redirect_settings', ['sanitize_callback' => 'gp_child_sanitize_email_redirect']);

    // Search settings
    register_setting('gp_child_search_group',     'gp_child_search_settings',                      ['sanitize_callback' => 'gp_child_sanitize_search']);

    // Cache buster counter
    register_setting('gp_child_cache_group', 'gp_child_css_version',    ['type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 1]);
    // Image URL versioning (Option A)
    register_setting('gp_child_cache_group', 'gp_child_version_images', ['type' => 'string',  'sanitize_callback' => 'sanitize_text_field', 'default' => '0']);

    // Notes visibility
    register_setting('gp_child_notes_group', 'gp_child_notes_role', [
        'sanitize_callback' => function ($v) {
            $allowed = ['manage_options', 'edit_others_posts', 'publish_posts', 'edit_posts', 'read'];
            return in_array($v, $allowed, true) ? $v : 'manage_options';
        },
        'default' => 'manage_options',
    ]);

    // WebP Converter settings
    register_setting('gp_child_webp_group', 'mbwpc_convert_to_webp', [
        'type'              => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default'           => false,
    ]);
    register_setting('gp_child_webp_group', 'mbwpc_max_width', [
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 1920,
    ]);
    register_setting('gp_child_webp_group', 'mbwpc_max_height', [
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 1080,
    ]);
    register_setting('gp_child_webp_group', 'mbwpc_quality', [
        'type'              => 'integer',
        'sanitize_callback' => fn($v) => max(1, min(100, absint($v))),
        'default'           => 80,
    ]);
}, 20); // priority 20 — must stay in sync with the add_action call above

// ── 4. Sanitize callbacks ─────────────────────────────────────────────────────

function gp_child_sanitize_general(mixed $input): array
{
    // Guard: only apply theme sanitization when the save comes from our own settings form.
    // When bf-fireball plugin saves this option (option_page = blueflamingo_plugin_general_settings_group),
    // we return $input unchanged so the plugin's data is not overwritten.
    $submitted_page = sanitize_text_field(wp_unslash($_POST['option_page'] ?? ''));
    if ($submitted_page !== 'gp_child_site_info_group') {
        return is_array($input) ? $input : [];
    }

    if (! is_array($input)) {
        $input = [];
    }
    $existing = (array) get_option('blueflamingo_plugin_general_settings', []);
    $clean    = $existing;

    $clean['live_url']    = sanitize_text_field($input['live_url']    ?? '');
    $clean['staging_url'] = sanitize_text_field($input['staging_url'] ?? '');

    return $clean;
}

function gp_child_sanitize_options(mixed $input): array
{
    // Guard: only apply theme sanitization when the save comes from our own settings form.
    // When bf-fireball plugin saves this option (option_page = blueflamingo_plugin_options_settings_group),
    // we return $input unchanged so the plugin's checkboxes/fields are not overwritten.
    $submitted_page = sanitize_text_field(wp_unslash($_POST['option_page'] ?? ''));
    if ($submitted_page !== 'gp_child_options_group') {
        return is_array($input) ? $input : [];
    }

    if (! is_array($input)) {
        $input = [];
    }
    $existing = (array) get_option('blueflamingo_plugin_options_settings', []);
    $clean    = $existing;

    $clean['json_basic_authentication']    = ! empty($input['json_basic_authentication'])    ? '1' : '0';
    $clean['activate_stripe_test_mode']    = ! empty($input['activate_stripe_test_mode']) ? '1' : '0';
    $clean['activate_wpsimplepay_testmode'] = ! empty($input['activate_wpsimplepay_testmode']) ? '1' : '0';
    $clean['Show_all_meta_fields']         = ! empty($input['Show_all_meta_fields']) ? '1' : '0';
    $clean['disable_admin_notifications_of_password_changes'] = ! empty($input['disable_admin_notifications_of_password_changes']) ? '1' : '0';
    $clean['hide_google_recaptcha_logo']   = ! empty($input['hide_google_recaptcha_logo'])   ? '1' : '0';
    $clean['admin_user_registration_date'] = ! empty($input['admin_user_registration_date']) ? '1' : '0';
    $clean['enable_duplicate_content']     = ! empty($input['enable_duplicate_content']) ? '1' : '0';
    $clean['disable_emojis']               = ! empty($input['disable_emojis']) ? '1' : '0';
    $clean['disable_dashicons']            = ! empty($input['disable_dashicons']) ? '1' : '0';
    $clean['disable_embeds']               = ! empty($input['disable_embeds']) ? '1' : '0';
    $clean['remove_jquery_migrate']        = ! empty($input['remove_jquery_migrate']) ? '1' : '0';
    $clean['remove_global_styles']         = ! empty($input['remove_global_styles']) ? '1' : '0';
    $clean['load_separate_block_styles']   = ! empty($input['load_separate_block_styles']) ? '1' : '0';
    $clean['disable_xml_rpc']              = ! empty($input['disable_xml_rpc']) ? '1' : '0';
    $clean['hide_wp_version']              = ! empty($input['hide_wp_version']) ? '1' : '0';
    $clean['remove_rsd_link']              = ! empty($input['remove_rsd_link']) ? '1' : '0';
    $clean['remove_shortlink']             = ! empty($input['remove_shortlink']) ? '1' : '0';
    $clean['disable_rss_feeds']            = ! empty($input['disable_rss_feeds']) ? '1' : '0';
    $clean['remove_rss_feed_links']        = ! empty($input['remove_rss_feed_links']) ? '1' : '0';
    $clean['disable_self_pingbacks']       = ! empty($input['disable_self_pingbacks']) ? '1' : '0';
    $clean['disable_rest_api']             = in_array($input['disable_rest_api'] ?? '', ['', 'non_admins', 'logged_out'], true)
        ? $input['disable_rest_api']
        : '';
    $clean['remove_rest_api_links']        = ! empty($input['remove_rest_api_links']) ? '1' : '0';
    $clean['remove_generatepress_header']  = ! empty($input['remove_generatepress_header']) ? '1' : '0';
    $clean['remove_generatepress_footer']  = ! empty($input['remove_generatepress_footer']) ? '1' : '0';
    $clean['hide_generatepress_layout_box'] = ! empty($input['hide_generatepress_layout_box']) ? '1' : '0';
    $clean['default_sidebar_layout']       = in_array($input['default_sidebar_layout'] ?? '', ['', 'right-sidebar', 'left-sidebar', 'no-sidebar', 'both-sidebars', 'both-left', 'both-right'], true)
        ? $input['default_sidebar_layout']
        : '';
    $clean['default_footer_widgets']       = in_array((string) ($input['default_footer_widgets'] ?? ''), ['', '0', '1', '2', '3', '4', '5'], true)
        ? (string) ($input['default_footer_widgets'] ?? '')
        : '';
    $clean['default_content_container']    = in_array($input['default_content_container'] ?? '', ['', 'true', 'contained'], true)
        ? $input['default_content_container']
        : '';
    $clean['default_disable_content_title'] = ! empty($input['default_disable_content_title']) ? '1' : '0';
    $clean['default_featured_image']       = absint($input['default_featured_image'] ?? 0);
    $clean['id_whatConverts']              = sanitize_text_field($input['id_whatConverts'] ?? '');
    $clean['restrict_admin_creation']      = ! empty($input['restrict_admin_creation']) ? '1' : '0';
    $clean['restrict_plugin_management']   = ! empty($input['restrict_plugin_management']) ? '1' : '0';
    $clean['auto_delete_standard_theme']   = ! empty($input['auto_delete_standard_theme']) ? '1' : '0';
    $clean['limit_ability_to_add_new_plugin'] = ! empty($input['limit_ability_to_add_new_plugin']) ? '1' : '0';

    return $clean;
}

function gp_child_sanitize_ga(mixed $input): array
{
    // Guard: only apply theme sanitization when the save comes from our own settings form.
    $submitted_page = sanitize_text_field(wp_unslash($_POST['option_page'] ?? ''));
    if ($submitted_page !== 'gp_child_ga_group') {
        return is_array($input) ? $input : [];
    }

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
    // Guard: only apply theme sanitization when the save comes from our own settings form.
    $submitted_page = sanitize_text_field(wp_unslash($_POST['option_page'] ?? ''));
    if ($submitted_page !== 'gp_child_404_group') {
        return is_array($input) ? $input : [];
    }

    if (! is_array($input)) {
        $input = [];
    }
    $existing = (array) get_option('blueflamingo_plugin_error_page_settings', []);
    $clean    = $existing;

    $clean['activate_404']    = ! empty($input['activate_404']) ? '1' : '0';
    $clean['custom_404_page'] = absint($input['custom_404_page'] ?? 0);

    return $clean;
}

function gp_child_sanitize_search(mixed $input): array
{
    $submitted_page = sanitize_text_field(wp_unslash($_POST['option_page'] ?? ''));
    if ($submitted_page !== 'gp_child_search_group') {
        return is_array($input) ? $input : [];
    }

    if (! is_array($input)) {
        $input = [];
    }

    return [
        'mode'            => in_array($input['mode'] ?? '', ['live_ajax', 'results_page'], true) ? $input['mode'] : 'live_ajax',
        'results_page_id' => absint($input['results_page_id'] ?? 0),
    ];
}

function gp_child_sanitize_email_redirect(mixed $input): array
{
    $submitted_page = sanitize_text_field(wp_unslash($_POST['option_page'] ?? ''));
    if ($submitted_page !== 'gp_child_email_redirect_group') {
        return is_array($input) ? $input : [];
    }

    if (! is_array($input)) {
        $input = [];
    }

    $existing = (array) get_option('blueflamingo_plugin_email_redirect_settings', []);
    $clean    = $existing;

    $clean['activate_email_redirect_staging_or_development'] = ! empty($input['activate_email_redirect_staging_or_development']) ? '1' : '0';
    $clean['activate_email_redirect_production']             = ! empty($input['activate_email_redirect_production']) ? '1' : '0';
    $clean['redirect_email_id']                              = sanitize_email($input['redirect_email_id'] ?? '');

    return $clean;
}

function gp_child_render_tab_help(string $text): void
{
    echo '<p class="description" style="margin-top:18px;padding-top:14px;border-top:1px solid #e0e0e0;">' . esc_html($text) . '</p>';
}

function gp_child_default_options(): array
{
    return [
        'disable_emojis'             => '1',
        'disable_dashicons'          => '1',
        'disable_embeds'             => '1',
        'remove_global_styles'       => '1',
        'load_separate_block_styles' => '1',
        'disable_xml_rpc'            => '1',
        'hide_wp_version'            => '1',
        'remove_rsd_link'            => '1',
        'remove_shortlink'           => '1',
        'remove_rss_feed_links'      => '1',
        'disable_self_pingbacks'     => '1',
        'remove_rest_api_links'      => '1',
    ];
}

// ── 5. Settings page renderer ─────────────────────────────────────────────────

function gp_child_render_settings_page(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $gen  = (array) get_option('blueflamingo_plugin_general_settings', []);
    $opts = wp_parse_args((array) get_option('blueflamingo_plugin_options_settings', []), gp_child_default_options());
    $ga   = (array) get_option('blueflamingo_plugin_google_analytics_settings', []);
    $ep   = (array) get_option('blueflamingo_plugin_error_page_settings', []);
    $er   = (array) get_option('blueflamingo_plugin_email_redirect_settings', []);
    $search_settings = (array) get_option('gp_child_search_settings', []);
    $cv   = intval(get_option('gp_child_css_version', 1));

    $dfi_id  = intval($opts['default_featured_image'] ?? 0);
    $dfi_url = $dfi_id ? wp_get_attachment_image_url($dfi_id, 'thumbnail') : '';

    $notes = gp_child_get_notes();
    $theme = wp_get_theme(get_stylesheet());
    $theme_version = (string) $theme->get('Version');
    $release_notes = function_exists('gp_child_get_current_release_notes') ? gp_child_get_current_release_notes() : [];
    $environment = function_exists('gp_child_get_environment') ? gp_child_get_environment() : 'unknown';
    $environment_label = match ($environment) {
        'live' => __('Live', 'generatepress-child'),
        'staging' => __('Staging', 'generatepress-child'),
        default => __('Unknown', 'generatepress-child'),
    };
    $environment_color = match ($environment) {
        'live' => '#0a7a35',
        'staging' => '#b35c00',
        default => '#646970',
    };

    $tabs = [
        'core-features' => __('Core Features', 'generatepress-child'),
        'options'    => __('Options',      'generatepress-child'),
        'email-redirect' => __('Email Redirect', 'generatepress-child'),
        'analytics'  => __('Analytics',    'generatepress-child'),
        'pages-search' => __('Pages & Search', 'generatepress-child'),
        'cache'      => __('Cache', 'generatepress-child'),
        'notes'      => __('Notes',        'generatepress-child'),
        'webp'       => __('WebP',         'generatepress-child'),
    ];
    $tabs = apply_filters('gp_child_settings_tabs', $tabs);
    ?>
<div class="wrap gp-child-settings">
  <h1 class="gp-settings-title">
    <span><?php echo esc_html(get_admin_page_title()); ?></span>
    <?php if ($theme_version !== '') : ?>
    <span class="gp-settings-version"><?php echo esc_html('v' . $theme_version); ?></span>
    <?php endif; ?>
  </h1>

  <style>
  /* ── Wrapper ──────────────────────────────────────────────────── */
  .gp-child-settings {
    max-width: 900px;
  }

  .gp-settings-title {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  .gp-settings-version {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    background: #eef4ff;
    border: 1px solid #c9d8ff;
    color: #1f4b99;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.3;
  }

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
    flex: 0 0 auto;
    padding: 8px 14px;
    cursor: pointer;
    font-size: 12px;
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

  .gp-tab-btn:hover {
    color: #1d2327;
    background: #f6f7f7;
  }

  .gp-tab-btn.is-active {
    background: #fff;
    border-color: #c3c4c7;
    color: #1d2327;
    font-weight: 600;
  }

  /* ── Tab panels ─────────────────────────────────────────────── */
  .gp-tab-panel {
    display: none;
    /* hidden by default — JS shows active one */
    background: #fff;
    border: 1px solid #c3c4c7;
    border-top: none;
    border-radius: 0 0 3px 3px;
    padding: 24px 28px;
  }

  .gp-tab-panel.is-active {
    display: block;
  }

  .gp-tab-panel .form-table th {
    width: 220px;
  }

  .gp-tab-panel .form-table th.gp-recommended-setting {
    color: #1d2327;
    font-weight: 600;
  }

  .gp-tab-panel .form-table th.gp-recommended-setting::after {
    content: "Recommended";
    display: block;
    width: max-content;
    margin-top: 6px;
    margin-left: 0;
    padding: 1px 8px;
    border-radius: 999px;
    background: #e8f5e9;
    border: 1px solid #b7e1c1;
    color: #116329;
    font-size: 10px;
    font-weight: 600;
    line-height: 1.7;
    vertical-align: top;
    letter-spacing: .02em;
    text-transform: uppercase;
  }

  .gp-tab-panel .form-table .gp-when-recommended {
    display: block;
    margin-top: 4px;
    font-size: 11px;
    color: #1d2327;
    line-height: 1.45;
  }

  /* ── Default Featured Image ─────────────────────────────────── */
  .gp-dfi-preview {
    max-width: 120px;
    max-height: 90px;
    display: block;
    margin-top: 6px;
    border-radius: 3px;
    border: 1px solid #ddd;
  }
  <?php echo apply_filters('gp_child_admin_settings_css', ''); ?>

  /* ── No-JS fallback: show all panels ────────────────────────── */
  .gp-child-settings.no-js .gp-tab-panel {
    display: block;
    margin-bottom: 16px;
    border-top: 1px solid #c3c4c7;
    border-radius: 3px;
  }

  /* ── Cache tab sections ──────────────────────────────────────── */
  .gp-cache-section {
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px 24px 4px;
    margin-bottom: 24px;
    background: #fdfdfd;
  }

  .gp-cache-section+.gp-cache-section {
    margin-top: 0;
  }

  .gp-cache-section-title {
    font-size: 13px;
    font-weight: 600;
    color: #1d2327;
    margin: 0 0 4px;
    display: flex;
    align-items: center;
    gap: 7px;
  }

  .gp-cache-section-desc {
    font-size: 12px;
    color: #646970;
    margin: 0 0 14px;
    line-height: 1.6;
  }

  .gp-release-box {
    margin-bottom: 24px;
    padding: 18px 20px;
    border: 1px solid #dcdcde;
    border-left: 4px solid #2271b1;
    border-radius: 4px;
    background: #f6fbff;
  }

  .gp-release-box h3 {
    margin: 0 0 8px;
    font-size: 14px;
  }

  .gp-release-box ul {
    margin: 0;
    padding-left: 18px;
  }

  .gp-release-box li + li {
    margin-top: 6px;
  }

  /* ── Notes grid ──────────────────────────────────────────────── */
  .gp-notes-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
  }

  .gp-notes-header h3 {
    margin: 0;
    flex: 1;
    font-size: 14px;
    font-weight: 600;
    color: #1d2327;
  }

  .gp-notes-grid {
    display: -ms-grid;
    display: grid;
    -ms-grid-columns: 1fr 16px 1fr 16px 1fr;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 16px;
  }

  .gp-notes-empty {
    color: #888;
    font-size: 13px;
    font-style: italic;
  }

  /* ── Note card ───────────────────────────────────────────────── */
  .gp-note-card {
    border-radius: 6px;
    padding: 14px 16px 12px;
    position: relative;
    box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
    display: flex;
    flex-direction: column;
  }

  .gp-note-card[data-color="yellow"] {
    background: #fffde7;
    border: 1px solid #f0d048;
  }

  .gp-note-card[data-color="blue"] {
    background: #e3f2fd;
    border: 1px solid #90caf9;
  }

  .gp-note-card[data-color="green"] {
    background: #e8f5e9;
    border: 1px solid #a5d6a7;
  }

  .gp-note-card[data-color="pink"] {
    background: #fce4ec;
    border: 1px solid #f48fb1;
  }

  .gp-note-colors {
    display: flex;
    gap: 6px;
    margin-bottom: 10px;
    align-items: center;
  }

  .gp-note-color-dot {
    width: 13px;
    height: 13px;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid transparent;
    flex-shrink: 0;
    transition: transform .1s;
  }

  .gp-note-color-dot[data-color="yellow"] {
    background: #f9c922;
  }

  .gp-note-color-dot[data-color="blue"] {
    background: #64b5f6;
  }

  .gp-note-color-dot[data-color="green"] {
    background: #81c784;
  }

  .gp-note-color-dot[data-color="pink"] {
    background: #f06292;
  }

  .gp-note-color-dot.is-selected {
    border-color: #555;
    transform: scale(1.25);
  }

  .gp-note-color-dot:hover {
    transform: scale(1.2);
  }

  .gp-note-delete {
    position: absolute;
    top: 10px;
    right: 10px;
    background: none;
    border: none;
    cursor: pointer;
    color: #bbb;
    font-size: 15px;
    line-height: 1;
    padding: 2px 4px;
    border-radius: 3px;
    transition: color .15s, background .15s;
  }

  .gp-note-delete:hover {
    color: #d63638;
    background: rgba(214, 54, 56, .08);
  }

  .gp-note-title {
    width: 100%;
    border: none;
    background: transparent;
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 8px;
    padding: 0;
    color: #1d2327;
    outline: none;
    box-shadow: none !important;
  }

  .gp-note-title:focus {
    border-bottom: 1px dashed #aaa;
  }

  .gp-note-body {
    width: 100%;
    min-height: 90px;
    border: none;
    background: transparent;
    resize: vertical;
    font-size: 12px;
    line-height: 1.6;
    color: #3c434a;
    padding: 0;
    outline: none;
    flex: 1;
    box-shadow: none !important;
  }

  .gp-note-footer {
    display: flex;
    align-items: center;
    margin-top: 10px;
    gap: 8px;
  }

  .gp-note-status {
    font-size: 11px;
    color: #5a7a5a;
    flex: 1;
  }

  .gp-note-save {
    flex-shrink: 0;
  }

  </style>

  <?php /* ── Tab navigation: <nav> + <button> avoids WP list styles ── */ ?>
  <nav class="gp-tab-nav-wrap" role="tablist"
    aria-label="<?php esc_attr_e('Settings sections', 'generatepress-child'); ?>">
    <?php foreach ($tabs as $id => $label) : ?>
    <button type="button" class="gp-tab-btn" role="tab" data-tab="<?php echo esc_attr($id); ?>"
      aria-controls="gp-tab-<?php echo esc_attr($id); ?>" aria-selected="false">
      <?php echo esc_html($label); ?>
    </button>
    <?php endforeach; ?>
  </nav>

  <?php /* ── Core Features ─────────────────────────────────────── */ ?>
  <div id="gp-tab-core-features" class="gp-tab-panel" role="tabpanel">
    <form method="post" action="options.php">
      <?php settings_fields('gp_child_options_group'); ?>

      <?php if (! empty($release_notes)) : ?>
      <div class="gp-release-box">
        <h3>
          <?php
          printf(
              esc_html__('What’s New in v%s', 'generatepress-child'),
              esc_html($theme_version !== '' ? $theme_version : 'current')
          );
          ?>
        </h3>
        <ul>
          <?php foreach ($release_notes as $note) : ?>
          <li><?php echo esc_html($note); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <div class="gp-cache-section">
        <h3 class="gp-cache-section-title"><?php esc_html_e('Scripts & Styles', 'generatepress-child'); ?></h3>
        <p class="gp-cache-section-desc">
          <?php esc_html_e('Turn off front-end assets and WordPress extras you do not need.', 'generatepress-child'); ?>
        </p>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row" class="gp-recommended-setting"><?php esc_html_e('Disable Emojis', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[disable_emojis]" value="1"
                  <?php checked('1', $opts['disable_emojis'] ?? ''); ?>>
                <?php esc_html_e('Remove emoji scripts, styles and related filters.', 'generatepress-child'); ?>
                <span class="gp-when-recommended"><?php esc_html_e('Use when you do not need legacy emoji compatibility.', 'generatepress-child'); ?></span>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row" class="gp-recommended-setting"><?php esc_html_e('Disable Dashicons', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[disable_dashicons]" value="1"
                  <?php checked('1', $opts['disable_dashicons'] ?? ''); ?>>
                <?php esc_html_e('Stop loading Dashicons for visitors on the front end.', 'generatepress-child'); ?>
                <span class="gp-when-recommended"><?php esc_html_e('Use when visitors do not need admin icon fonts.', 'generatepress-child'); ?></span>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row" class="gp-recommended-setting"><?php esc_html_e('Disable Embeds', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[disable_embeds]" value="1"
                  <?php checked('1', $opts['disable_embeds'] ?? ''); ?>>
                <?php esc_html_e('Disable oEmbed discovery links, host JS and embed rewrite filters.', 'generatepress-child'); ?>
                <span class="gp-when-recommended"><?php esc_html_e('Use when your site does not rely on WordPress oEmbed.', 'generatepress-child'); ?></span>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Remove jQuery Migrate', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[remove_jquery_migrate]" value="1"
                  <?php checked('1', $opts['remove_jquery_migrate'] ?? '1'); ?>>
                <?php esc_html_e('Use when your site no longer depends on legacy jQuery scripts. Improves front-end performance, but can break older plugins or theme JS. Test in staging first.', 'generatepress-child'); ?>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Remove Global Styles', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[remove_global_styles]" value="1"
                  <?php checked('1', $opts['remove_global_styles'] ?? ''); ?>>
                <?php esc_html_e('Disable block global styles and duotone output when not needed.', 'generatepress-child'); ?>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Load Separate Block Styles', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[load_separate_block_styles]" value="1"
                  <?php checked('1', $opts['load_separate_block_styles'] ?? ''); ?>>
                <?php esc_html_e('Load block styles only when a block needs them.', 'generatepress-child'); ?>
              </label>
            </td>
          </tr>
        </table>
      </div>

      <div class="gp-cache-section">
        <h3 class="gp-cache-section-title"><?php esc_html_e('WordPress Features', 'generatepress-child'); ?></h3>
        <p class="gp-cache-section-desc">
          <?php esc_html_e('Remove discovery links, feeds and APIs you do not want publicly exposed.', 'generatepress-child'); ?>
        </p>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row" class="gp-recommended-setting"><?php esc_html_e('Disable XML-RPC', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[disable_xml_rpc]" value="1"
                  <?php checked('1', $opts['disable_xml_rpc'] ?? ''); ?>>
                <?php esc_html_e('Disable the XML-RPC endpoint.', 'generatepress-child'); ?>
                <span class="gp-when-recommended"><?php esc_html_e('Use when you do not use XML-RPC clients or integrations.', 'generatepress-child'); ?></span>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row" class="gp-recommended-setting"><?php esc_html_e('Hide WP Version', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[hide_wp_version]" value="1"
                  <?php checked('1', $opts['hide_wp_version'] ?? ''); ?>>
                <?php esc_html_e('Remove WordPress version output from common places.', 'generatepress-child'); ?>
                <span class="gp-when-recommended"><?php esc_html_e('Use on production sites to reduce version fingerprinting.', 'generatepress-child'); ?></span>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row" class="gp-recommended-setting"><?php esc_html_e('Remove RSD Link', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[remove_rsd_link]" value="1"
                  <?php checked('1', $opts['remove_rsd_link'] ?? ''); ?>>
                <?php esc_html_e('Remove the Really Simple Discovery link from the document head.', 'generatepress-child'); ?>
                <span class="gp-when-recommended"><?php esc_html_e('Use when you do not use remote publishing tools.', 'generatepress-child'); ?></span>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row" class="gp-recommended-setting"><?php esc_html_e('Remove Shortlink', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[remove_shortlink]" value="1"
                  <?php checked('1', $opts['remove_shortlink'] ?? ''); ?>>
                <?php esc_html_e('Remove shortlink tags from the document head and headers.', 'generatepress-child'); ?>
                <span class="gp-when-recommended"><?php esc_html_e('Use when your project does not use WordPress shortlinks.', 'generatepress-child'); ?></span>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Disable RSS Feeds', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[disable_rss_feeds]" value="1"
                  <?php checked('1', $opts['disable_rss_feeds'] ?? ''); ?>>
                <?php esc_html_e('Block front-end feed endpoints.', 'generatepress-child'); ?>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row" class="gp-recommended-setting"><?php esc_html_e('Remove RSS Feed Links', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[remove_rss_feed_links]" value="1"
                  <?php checked('1', $opts['remove_rss_feed_links'] ?? ''); ?>>
                <?php esc_html_e('Remove feed discovery links from the document head.', 'generatepress-child'); ?>
                <span class="gp-when-recommended"><?php esc_html_e('Use when feeds are disabled or not part of your strategy.', 'generatepress-child'); ?></span>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row" class="gp-recommended-setting"><?php esc_html_e('Disable Self Pingbacks', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[disable_self_pingbacks]" value="1"
                  <?php checked('1', $opts['disable_self_pingbacks'] ?? ''); ?>>
                <?php esc_html_e('Prevent your own domain links from creating pingbacks.', 'generatepress-child'); ?>
                <span class="gp-when-recommended"><?php esc_html_e('Use when internal links should never create pingbacks.', 'generatepress-child'); ?></span>
              </label>
            </td>
          </tr>
        </table>
      </div>

      <div class="gp-cache-section">
        <h3 class="gp-cache-section-title"><?php esc_html_e('REST API', 'generatepress-child'); ?></h3>
        <p class="gp-cache-section-desc">
          <?php esc_html_e('Control REST API access and authentication from one place.', 'generatepress-child'); ?>
        </p>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><?php esc_html_e('JSON Basic Authentication', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[json_basic_authentication]" value="1"
                  <?php checked('1', $opts['json_basic_authentication'] ?? ''); ?>>
                <?php esc_html_e('Enable JSON Basic Authentication for the REST API.', 'generatepress-child'); ?>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Disable REST API', 'generatepress-child'); ?></th>
            <td>
              <?php $rest_mode = $opts['disable_rest_api'] ?? ''; ?>
              <label style="display:block;margin-bottom:6px;">
                <input type="radio" name="blueflamingo_plugin_options_settings[disable_rest_api]" value=""
                  <?php checked('', $rest_mode); ?>>
                <?php esc_html_e('Default (Enabled)', 'generatepress-child'); ?>
              </label>
              <label style="display:block;margin-bottom:6px;">
                <input type="radio" name="blueflamingo_plugin_options_settings[disable_rest_api]" value="non_admins"
                  <?php checked('non_admins', $rest_mode); ?>>
                <?php esc_html_e('Disable for Non-Admins', 'generatepress-child'); ?>
              </label>
              <label style="display:block;">
                <input type="radio" name="blueflamingo_plugin_options_settings[disable_rest_api]" value="logged_out"
                  <?php checked('logged_out', $rest_mode); ?>>
                <?php esc_html_e('Disable When Logged Out', 'generatepress-child'); ?>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row" class="gp-recommended-setting"><?php esc_html_e('Remove REST API Links', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[remove_rest_api_links]" value="1"
                  <?php checked('1', $opts['remove_rest_api_links'] ?? ''); ?>>
                <?php esc_html_e('Remove REST API discovery links and headers.', 'generatepress-child'); ?>
                <span class="gp-when-recommended"><?php esc_html_e('Use when public REST discovery links are unnecessary.', 'generatepress-child'); ?></span>
              </label>
            </td>
          </tr>
        </table>
      </div>

      <div class="gp-cache-section">
        <h3 class="gp-cache-section-title"><?php esc_html_e('GeneratePress Layout', 'generatepress-child'); ?></h3>
        <p class="gp-cache-section-desc">
          <?php esc_html_e('Apply global GeneratePress layout cleanup directly from the child theme.', 'generatepress-child'); ?>
        </p>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><?php esc_html_e('Remove Header Globally', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[remove_generatepress_header]" value="1"
                  <?php checked('1', $opts['remove_generatepress_header'] ?? ''); ?>>
                <?php esc_html_e('Remove the GeneratePress header output on the front end.', 'generatepress-child'); ?>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Remove Footer Globally', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[remove_generatepress_footer]" value="1"
                  <?php checked('1', $opts['remove_generatepress_footer'] ?? ''); ?>>
                <?php esc_html_e('Remove GeneratePress footer widgets, footer bar and back-to-top output.', 'generatepress-child'); ?>
              </label>
            </td>
          </tr>
        </table>
      </div>

      <div class="gp-cache-section">
        <h3 class="gp-cache-section-title"><?php esc_html_e('Default Page Layout', 'generatepress-child'); ?></h3>
        <p class="gp-cache-section-desc">
          <?php esc_html_e('Set GeneratePress layout defaults for new and existing posts/pages that do not have custom layout meta yet.', 'generatepress-child'); ?>
        </p>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><?php esc_html_e('Hide Layout Box in Editor', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[hide_generatepress_layout_box]" value="1"
                  <?php checked('1', $opts['hide_generatepress_layout_box'] ?? ''); ?>>
                <?php esc_html_e('Hide the GeneratePress Layout box in the post/page editor using admin CSS.', 'generatepress-child'); ?>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('All Site Sidebar Layout', 'generatepress-child'); ?></th>
            <td>
              <select name="blueflamingo_plugin_options_settings[default_sidebar_layout]">
                <option value="" <?php selected($opts['default_sidebar_layout'] ?? '', ''); ?>><?php esc_html_e('Default', 'generatepress-child'); ?></option>
                <option value="right-sidebar" <?php selected($opts['default_sidebar_layout'] ?? '', 'right-sidebar'); ?>><?php esc_html_e('Right Sidebar', 'generatepress-child'); ?></option>
                <option value="left-sidebar" <?php selected($opts['default_sidebar_layout'] ?? '', 'left-sidebar'); ?>><?php esc_html_e('Left Sidebar', 'generatepress-child'); ?></option>
                <option value="no-sidebar" <?php selected($opts['default_sidebar_layout'] ?? '', 'no-sidebar'); ?>><?php esc_html_e('No Sidebars', 'generatepress-child'); ?></option>
                <option value="both-sidebars" <?php selected($opts['default_sidebar_layout'] ?? '', 'both-sidebars'); ?>><?php esc_html_e('Both Sidebars', 'generatepress-child'); ?></option>
                <option value="both-left" <?php selected($opts['default_sidebar_layout'] ?? '', 'both-left'); ?>><?php esc_html_e('Both Sidebars on Left', 'generatepress-child'); ?></option>
                <option value="both-right" <?php selected($opts['default_sidebar_layout'] ?? '', 'both-right'); ?>><?php esc_html_e('Both Sidebars on Right', 'generatepress-child'); ?></option>
              </select>
              <p class="description">
                <?php esc_html_e('Example: choose No Sidebars to keep future pages/posts without sidebars by default.', 'generatepress-child'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Default Footer Widgets', 'generatepress-child'); ?></th>
            <td>
              <select name="blueflamingo_plugin_options_settings[default_footer_widgets]">
                <option value="" <?php selected((string) ($opts['default_footer_widgets'] ?? ''), ''); ?>><?php esc_html_e('Default', 'generatepress-child'); ?></option>
                <option value="0" <?php selected((string) ($opts['default_footer_widgets'] ?? ''), '0'); ?>><?php esc_html_e('0 Widgets', 'generatepress-child'); ?></option>
                <option value="1" <?php selected((string) ($opts['default_footer_widgets'] ?? ''), '1'); ?>><?php esc_html_e('1 Widget', 'generatepress-child'); ?></option>
                <option value="2" <?php selected((string) ($opts['default_footer_widgets'] ?? ''), '2'); ?>><?php esc_html_e('2 Widgets', 'generatepress-child'); ?></option>
                <option value="3" <?php selected((string) ($opts['default_footer_widgets'] ?? ''), '3'); ?>><?php esc_html_e('3 Widgets', 'generatepress-child'); ?></option>
                <option value="4" <?php selected((string) ($opts['default_footer_widgets'] ?? ''), '4'); ?>><?php esc_html_e('4 Widgets', 'generatepress-child'); ?></option>
                <option value="5" <?php selected((string) ($opts['default_footer_widgets'] ?? ''), '5'); ?>><?php esc_html_e('5 Widgets', 'generatepress-child'); ?></option>
              </select>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Default Content Container', 'generatepress-child'); ?></th>
            <td>
              <select name="blueflamingo_plugin_options_settings[default_content_container]">
                <option value="" <?php selected($opts['default_content_container'] ?? '', ''); ?>><?php esc_html_e('Default', 'generatepress-child'); ?></option>
                <option value="true" <?php selected($opts['default_content_container'] ?? '', 'true'); ?>><?php esc_html_e('Full Width', 'generatepress-child'); ?></option>
                <option value="contained" <?php selected($opts['default_content_container'] ?? '', 'contained'); ?>><?php esc_html_e('Contained', 'generatepress-child'); ?></option>
              </select>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Disable Content Title by Default', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blueflamingo_plugin_options_settings[default_disable_content_title]" value="1"
                  <?php checked('1', $opts['default_disable_content_title'] ?? ''); ?>>
                <?php esc_html_e('Default new pages/posts to hide the content title.', 'generatepress-child'); ?>
              </label>
            </td>
          </tr>
        </table>
      </div>

      <?php submit_button(__('Save Core Features', 'generatepress-child')); ?>
    </form>
    <?php gp_child_render_tab_help(__('Use this tab for global WordPress and GeneratePress cleanup, REST API controls, and default layout behavior across the site. | ./scripts/build-theme-package.sh (version command)', 'generatepress-child')); ?>
  </div>

  <?php /* ── Options ────────────────────────────────────────────── */ ?>
  <div id="gp-tab-options" class="gp-tab-panel" role="tabpanel">
    <form method="post" action="options.php" style="margin-bottom:24px;">
      <?php settings_fields('gp_child_site_info_group'); ?>
      <div class="gp-cache-section">
        <h3 class="gp-cache-section-title"><?php esc_html_e('Environment', 'generatepress-child'); ?></h3>
        <p class="gp-cache-section-desc">
          <?php esc_html_e('Store the live and staging URLs used by the theme to detect the current environment.', 'generatepress-child'); ?>
        </p>
        <div style="margin-bottom:18px;padding:12px 14px;border:1px solid #dcdcde;border-left:4px solid <?php echo esc_attr($environment_color); ?>;background:#fff;">
          <strong><?php esc_html_e('Current Environment:', 'generatepress-child'); ?></strong>
          <span style="color:<?php echo esc_attr($environment_color); ?>;font-weight:600;">
            <?php echo esc_html($environment_label); ?>
          </span>
          <p style="margin:6px 0 0;color:#646970;">
            <?php esc_html_e('Detected by comparing the current site URL with the Live Site URL and Staging Site URL below.', 'generatepress-child'); ?>
          </p>
        </div>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><?php esc_html_e('Live Site URL', 'generatepress-child'); ?></th>
            <td>
              <input type="text" name="blueflamingo_plugin_general_settings[live_url]"
                value="<?php echo esc_attr($gen['live_url'] ?? ''); ?>" class="regular-text" placeholder="example.com">
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
      </div>
      <?php submit_button(__('Save Environment', 'generatepress-child'), 'secondary'); ?>
    </form>

    <form method="post" action="options.php">
      <?php settings_fields('gp_child_options_group'); ?>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><?php esc_html_e('Stripe Test Mode', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="blueflamingo_plugin_options_settings[activate_stripe_test_mode]" value="1"
                <?php checked('1', $opts['activate_stripe_test_mode'] ?? ''); ?>>
              <?php esc_html_e('On staging sites, force WooCommerce Stripe into test mode.', 'generatepress-child'); ?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('WP Simple Pay Test Mode', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="blueflamingo_plugin_options_settings[activate_wpsimplepay_testmode]" value="1"
                <?php checked('1', $opts['activate_wpsimplepay_testmode'] ?? ''); ?>>
              <?php esc_html_e('On staging sites, force WP Simple Pay into test mode.', 'generatepress-child'); ?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Hide reCAPTCHA Badge', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="blueflamingo_plugin_options_settings[hide_google_recaptcha_logo]" value="1"
                <?php checked('1', $opts['hide_google_recaptcha_logo'] ?? ''); ?>>
              <?php esc_html_e('Hide the floating Google reCAPTCHA v3 badge via CSS.', 'generatepress-child'); ?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Show All Meta Fields', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="blueflamingo_plugin_options_settings[Show_all_meta_fields]" value="1"
                <?php checked('1', $opts['Show_all_meta_fields'] ?? ''); ?>>
              <?php esc_html_e('Display all post meta in the editor screen for debugging and content audits.', 'generatepress-child'); ?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Disable Password Change Emails', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="blueflamingo_plugin_options_settings[disable_admin_notifications_of_password_changes]" value="1"
                <?php checked('1', $opts['disable_admin_notifications_of_password_changes'] ?? ''); ?>>
              <?php esc_html_e('Stop WordPress from emailing the admin address when a user changes their password.', 'generatepress-child'); ?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('User Registration Date', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="blueflamingo_plugin_options_settings[admin_user_registration_date]" value="1"
                <?php checked('1', $opts['admin_user_registration_date'] ?? ''); ?>>
              <?php esc_html_e('Add a Registration Date column to the Users list table.', 'generatepress-child'); ?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Duplicate Pages & Posts', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="blueflamingo_plugin_options_settings[enable_duplicate_content]" value="1"
                <?php checked('1', $opts['enable_duplicate_content'] ?? ''); ?>>
              <?php esc_html_e('Enable a Duplicate action for pages and posts in the WordPress admin.', 'generatepress-child'); ?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Default Featured Image', 'generatepress-child'); ?></th>
          <td>
            <input type="hidden" id="gp-dfi-id" name="blueflamingo_plugin_options_settings[default_featured_image]"
              value="<?php echo esc_attr($dfi_id); ?>">
            <img id="gp-dfi-preview" class="gp-dfi-preview" src="<?php echo esc_url($dfi_url ?: ''); ?>" alt=""
              <?php echo $dfi_url ? '' : 'style="display:none;"'; ?>>
            <p style="margin-top:8px;">
              <button type="button" class="button" id="gp-dfi-select">
                <?php esc_html_e('Select Image', 'generatepress-child'); ?>
              </button>
              <button type="button" class="button" id="gp-dfi-remove"
                <?php echo $dfi_id ? '' : 'style="display:none;"'; ?>>
                <?php esc_html_e('Remove', 'generatepress-child'); ?>
              </button>
            </p>
            <p class="description">
              <?php esc_html_e('Fallback image shown when a post has no featured image.', 'generatepress-child'); ?></p>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('WhatConverts ID', 'generatepress-child'); ?></th>
          <td>
            <input type="text" name="blueflamingo_plugin_options_settings[id_whatConverts]"
              value="<?php echo esc_attr($opts['id_whatConverts'] ?? ''); ?>" class="regular-text" placeholder="123456">
            <p class="description">
              <?php esc_html_e('WhatConverts account ID. Leave blank to disable.', 'generatepress-child'); ?><br>
              <?php esc_html_e('WhatConverts tracks calls, forms and chats and attributes them to a marketing source (Google Ads, SEO, etc.).', 'generatepress-child'); ?><br>
              <?php esc_html_e('Find your ID in WhatConverts: Settings → Profiles → your profile → Profile ID.', 'generatepress-child'); ?>
            </p>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Restrict Administrator Creation', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="blueflamingo_plugin_options_settings[restrict_admin_creation]" value="1"
                <?php checked('1', $opts['restrict_admin_creation'] ?? ''); ?>>
              <?php esc_html_e('Hide the Administrator role for non-support users when creating or promoting users.', 'generatepress-child'); ?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Restrict Plugin Management', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="blueflamingo_plugin_options_settings[restrict_plugin_management]" value="1"
                <?php checked('1', $opts['restrict_plugin_management'] ?? ''); ?>>
              <?php esc_html_e('Restrict plugin activation, deactivation, updates and deletion for non-support users.', 'generatepress-child'); ?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Limit Ability to Add New Plugins', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="blueflamingo_plugin_options_settings[limit_ability_to_add_new_plugin]" value="1"
                <?php checked('1', $opts['limit_ability_to_add_new_plugin'] ?? ''); ?>>
              <?php esc_html_e('Hide and block the Add New Plugin screens for non-support users.', 'generatepress-child'); ?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Auto Delete Standard Themes', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="blueflamingo_plugin_options_settings[auto_delete_standard_theme]" value="1"
                <?php checked('1', $opts['auto_delete_standard_theme'] ?? ''); ?>>
              <?php esc_html_e('Delete unused default WordPress themes authored by the WordPress team.', 'generatepress-child'); ?>
            </label>
            <p class="description"><?php esc_html_e('Use carefully: this is destructive and intended for cleanup on maintained sites.', 'generatepress-child'); ?></p>
          </td>
        </tr>
      </table>
      <?php submit_button(__('Save Options', 'generatepress-child')); ?>
    </form>
    <?php gp_child_render_tab_help(__('This tab groups utility tools and operational toggles such as test modes, admin restrictions, default images, and content helpers.', 'generatepress-child')); ?>
  </div>

  <?php /* ── Email Redirect ───────────────────────────────────── */ ?>
  <div id="gp-tab-email-redirect" class="gp-tab-panel" role="tabpanel">
    <form method="post" action="options.php">
      <?php settings_fields('gp_child_email_redirect_group'); ?>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><?php esc_html_e('Redirect on Staging / Development', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="blueflamingo_plugin_email_redirect_settings[activate_email_redirect_staging_or_development]" value="1"
                <?php checked('1', $er['activate_email_redirect_staging_or_development'] ?? ''); ?>>
              <?php esc_html_e('Replace outgoing email recipients when the current site matches the Staging Site URL.', 'generatepress-child'); ?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Redirect on Production', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="blueflamingo_plugin_email_redirect_settings[activate_email_redirect_production]" value="1"
                <?php checked('1', $er['activate_email_redirect_production'] ?? ''); ?>>
              <?php esc_html_e('Replace outgoing email recipients when the current site matches the Live Site URL.', 'generatepress-child'); ?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Redirect Email Address', 'generatepress-child'); ?></th>
          <td>
            <input type="email" name="blueflamingo_plugin_email_redirect_settings[redirect_email_id]"
              value="<?php echo esc_attr($er['redirect_email_id'] ?? ''); ?>" class="regular-text"
              placeholder="test@example.com">
            <p class="description">
              <?php esc_html_e('All redirected emails will be sent to this address instead of the original recipient.', 'generatepress-child'); ?><br>
              <?php esc_html_e('The email subject gets prefixed with [TEST], and the original recipient is added to the message for reference.', 'generatepress-child'); ?>
            </p>
          </td>
        </tr>
      </table>
      <?php submit_button(__('Save Email Redirect Settings', 'generatepress-child')); ?>
    </form>
    <?php gp_child_render_tab_help(__('Redirect outgoing email here when testing so messages go to a safe inbox instead of real recipients.', 'generatepress-child')); ?>
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
              <input type="checkbox" name="blueflamingo_plugin_google_analytics_settings[activate_google_analytics]"
                value="1" <?php checked('1', $ga['activate_google_analytics'] ?? ''); ?>>
              <?php esc_html_e('Enable Analytics / GTM output on the front end.', 'generatepress-child'); ?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Tracking ID', 'generatepress-child'); ?></th>
          <td>
            <input type="text" name="blueflamingo_plugin_google_analytics_settings[google_analytics_id]"
              value="<?php echo esc_attr($ga['google_analytics_id'] ?? ''); ?>" class="regular-text"
              placeholder="G-XXXXXXXXXX or GTM-XXXXXXX">
            <p class="description">
              <?php esc_html_e('GA4 (G-…) or GTM container ID (GTM-…). Type is auto-detected.', 'generatepress-child'); ?>
            </p>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Script Position', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="radio" name="blueflamingo_plugin_google_analytics_settings[google_analytics_position]"
                value="Head" <?php checked('Head', $ga['google_analytics_position'] ?? 'Head'); ?>>
              <?php esc_html_e('Head (default)', 'generatepress-child'); ?>
            </label>&nbsp;&nbsp;
            <label>
              <input type="radio" name="blueflamingo_plugin_google_analytics_settings[google_analytics_position]"
                value="Footer" <?php checked('Footer', $ga['google_analytics_position'] ?? ''); ?>>
              <?php esc_html_e('Footer', 'generatepress-child'); ?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Include Logged-In Users', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="blueflamingo_plugin_google_analytics_settings[google_analytics_logged_in]"
                value="1" <?php checked('1', $ga['google_analytics_logged_in'] ?? ''); ?>>
              <?php esc_html_e('Track admin/logged-in users as well.', 'generatepress-child'); ?>
            </label>
          </td>
        </tr>
      </table>
      <?php submit_button(__('Save Analytics', 'generatepress-child')); ?>
    </form>
    <?php gp_child_render_tab_help(__('Configure front-end tracking scripts here, including GA4, GTM, and the WhatConverts integration.', 'generatepress-child')); ?>
  </div>

  <?php /* ── Pages & Search ─────────────────────────────────────── */ ?>
  <div id="gp-tab-pages-search" class="gp-tab-panel" role="tabpanel">
    <form method="post" action="options.php">
      <?php settings_fields('gp_child_404_group'); ?>
      <div class="gp-cache-section">
        <h3 class="gp-cache-section-title"><?php esc_html_e('404 Page', 'generatepress-child'); ?></h3>
        <p class="gp-cache-section-desc">
          <?php esc_html_e('Choose a normal WordPress page to be used as the custom 404 template.', 'generatepress-child'); ?>
        </p>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><?php esc_html_e('Activate', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="blueflamingo_plugin_error_page_settings[activate_404]" value="1"
                <?php checked('1', $ep['activate_404'] ?? ''); ?>>
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
      </div>
      <?php submit_button(__('Save 404 Settings', 'generatepress-child'), 'secondary'); ?>
    </form>

    <form method="post" action="options.php" style="margin-top:24px;">
      <?php settings_fields('gp_child_search_group'); ?>
      <div class="gp-cache-section">
        <h3 class="gp-cache-section-title"><?php esc_html_e('Search', 'generatepress-child'); ?></h3>
        <p class="gp-cache-section-desc">
          <?php esc_html_e('Control how the search bar behaves and which page should render search results.', 'generatepress-child'); ?>
        </p>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><?php esc_html_e('Search Mode', 'generatepress-child'); ?></th>
          <td>
            <label style="display:block;margin-bottom:8px;">
              <input type="radio" name="gp_child_search_settings[mode]" value="live_ajax"
                <?php checked($search_settings['mode'] ?? 'live_ajax', 'live_ajax'); ?>>
              <?php esc_html_e('Live search dropdown', 'generatepress-child'); ?>
            </label>
            <label style="display:block;">
              <input type="radio" name="gp_child_search_settings[mode]" value="results_page"
                <?php checked($search_settings['mode'] ?? 'live_ajax', 'results_page'); ?>>
              <?php esc_html_e('Redirect to results page', 'generatepress-child'); ?>
            </label>
            <p class="description">
              <?php esc_html_e('Live search keeps results under the input. Redirect mode sends the visitor to a selected WordPress page that contains the results shortcode.', 'generatepress-child'); ?>
            </p>
          </td>
        </tr>
        <tr data-search-results-page-row <?php echo (($search_settings['mode'] ?? 'live_ajax') === 'results_page') ? '' : 'style="display:none;"'; ?>>
          <th scope="row"><?php esc_html_e('Results Page', 'generatepress-child'); ?></th>
          <td>
            <select name="gp_child_search_settings[results_page_id]">
              <option value="0"><?php esc_html_e('— Select a page —', 'generatepress-child'); ?></option>
              <?php foreach (get_pages() as $page) : ?>
              <option value="<?php echo esc_attr($page->ID); ?>"
                <?php selected(intval($search_settings['results_page_id'] ?? 0), $page->ID); ?>>
                <?php echo esc_html($page->post_title); ?>
              </option>
              <?php endforeach; ?>
            </select>
            <p class="description">
              <?php esc_html_e('Use this when Search Mode is set to Redirect to results page.', 'generatepress-child'); ?>
            </p>
          </td>
        </tr>
      </table>
      </div>
      <?php submit_button(__('Save Search Settings', 'generatepress-child')); ?>
    </form>

    <hr style="margin:28px 0;">

    <h2 style="margin-top:0;"><?php esc_html_e('How to use it', 'generatepress-child'); ?></h2>
    <p><code>[gp_search_bar]</code> <?php esc_html_e('adds the search field anywhere in your content, template or block shortcode area.', 'generatepress-child'); ?></p>
    <p><code>[gp_search_bar variant="icon"]</code> <?php esc_html_e('shows only the search icon at first, then expands into the search field when clicked.', 'generatepress-child'); ?></p>
    <p><code>[post_search_result]</code> <?php esc_html_e('renders the results page layout. If you use Redirect mode, place this shortcode on the page selected above.', 'generatepress-child'); ?></p>
    <p class="description">
      <?php esc_html_e('Recommended setup: create a page like "Search", place [post_search_result] on it, then select that page here and enable Redirect to results page.', 'generatepress-child'); ?>
    </p>
    <?php gp_child_render_tab_help(__('Keep related page-routing tools together here: 404 handling for missing URLs and search routing for visitors looking for content.', 'generatepress-child')); ?>
  </div>

  <?php do_action('gp_child_render_settings_panels'); ?>

  <?php /* ── Cache Buster ─────────────────────────────────────────── */ ?>
  <div id="gp-tab-cache" class="gp-tab-panel" role="tabpanel">

    <?php
            $vi = get_option('gp_child_version_images', '0');
            ?>

    <?php /* ── Section 1: Browser cache ─────────────────────── */ ?>
    <div class="gp-cache-section">
      <h3 class="gp-cache-section-title">&#128274; <?php esc_html_e('Client Browser Cache', 'generatepress-child'); ?>
      </h3>
      <p class="gp-cache-section-desc"><?php esc_html_e(
                    'Browsers store CSS, JS and images locally so pages load faster on repeat visits. ' .
                    'When you update a file the browser may keep serving its old cached copy for days. ' .
                    'Incrementing the version counter appends ?v=X to every asset URL, forcing a fresh download. ' .
                    'Current asset version: ', 'generatepress-child');
                ?><strong><?php echo esc_html(GP_CHILD_VERSION . '.' . $cv); ?></strong></p>

      <form method="post" action="options.php">
        <?php settings_fields('gp_child_cache_group'); ?>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><?php esc_html_e('CSS / JS Version Counter', 'generatepress-child'); ?></th>
            <td>
              <input type="number" name="gp_child_css_version" value="<?php echo esc_attr($cv); ?>" min="1" step="1"
                style="width:80px;">
              &nbsp;
              <button type="submit" name="gp_child_css_version" value="<?php echo esc_attr($cv + 1); ?>"
                class="button button-secondary">
                <?php esc_html_e('Increment +1', 'generatepress-child'); ?>
              </button>
              <p class="description">
                <?php esc_html_e('Appended as ?v=X to all theme CSS and JS files.', 'generatepress-child'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Version Image URLs', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="gp_child_version_images" value="1" <?php checked('1', $vi); ?>>
                <?php esc_html_e('Also append ?v=X to media/attachment image URLs.', 'generatepress-child'); ?>
              </label>
              <p class="description">
                <?php esc_html_e('Useful after replacing an image file at the same URL. Uses the same counter above.', 'generatepress-child'); ?>
              </p>
            </td>
          </tr>
        </table>
        <?php submit_button(__('Save Browser Cache Settings', 'generatepress-child')); ?>
      </form>
    </div>

    <?php /* ── Section 2: Firewall / CDN Cache (info only) ─── */ ?>
    <div class="gp-cache-section">
      <h3 class="gp-cache-section-title">&#127760;
        <?php esc_html_e('Firewall / CDN Cache (Cloudflare, Sucuri, etc.)', 'generatepress-child'); ?></h3>
      <p class="gp-cache-section-desc"><?php esc_html_e(
                    'CDN and firewall services (Cloudflare, Sucuri WAF, WP Engine, Kinsta, etc.) cache full HTML pages at edge servers worldwide. ' .
                    'When you update a page in WordPress the CDN may keep serving a stale copy for hours or days — ' .
                    'visitors will not see your changes until that cache is cleared.',
                    'generatepress-child'
                ); ?></p>

      <p style="font-size:12px;font-weight:600;color:#1d2327;margin:0 0 6px;">
        <?php esc_html_e('When to purge your CDN cache:', 'generatepress-child'); ?></p>
      <ul style="margin:0 0 16px 18px;font-size:12px;color:#50575e;list-style:disc;">
        <li><?php esc_html_e('After editing a published page, post, or landing page', 'generatepress-child'); ?></li>
        <li><?php esc_html_e('After updating the theme, CSS, or global layout', 'generatepress-child'); ?></li>
        <li>
          <?php esc_html_e('After changing headers, footers, navigation menus, or sidebars', 'generatepress-child'); ?>
        </li>
        <li><?php esc_html_e('After a plugin update that affects front-end output', 'generatepress-child'); ?></li>
      </ul>

      <p style="font-size:12px;font-weight:600;color:#1d2327;margin:0 0 6px;">
        <?php esc_html_e('How to purge — go to your provider\'s dashboard:', 'generatepress-child'); ?></p>
      <ul style="margin:0 0 8px 18px;font-size:12px;color:#50575e;list-style:disc;">
        <li><strong>Cloudflare:</strong>
          <?php esc_html_e('Caching → Configuration → Purge Everything', 'generatepress-child'); ?></li>
        <li><strong>Sucuri WAF:</strong> <?php esc_html_e('Firewall → Clear Cache', 'generatepress-child'); ?></li>
        <li><strong>WP Rocket:</strong>
          <?php esc_html_e('Dashboard → Clear cache button (top bar)', 'generatepress-child'); ?></li>
        <li><strong>WP Engine / Kinsta:</strong>
          <?php esc_html_e('Hosting dashboard → Clear all caches', 'generatepress-child'); ?></li>
      </ul>
    </div>

    <?php gp_child_render_tab_help(__('Use Cache Buster whenever browsers or caching layers keep serving old CSS, JS, or image URLs after updates.', 'generatepress-child')); ?>

  </div>

  <?php /* ── Notes ──────────────────────────────────────────────── */ ?>
  <div id="gp-tab-notes" class="gp-tab-panel" role="tabpanel">

    <?php
            $notes_role = get_option('gp_child_notes_role', 'manage_options');
            $role_options = [
                'manage_options'    => __('Administrator only',      'generatepress-child'),
                'edit_others_posts' => __('Editor and above',        'generatepress-child'),
                'publish_posts'     => __('Author and above',        'generatepress-child'),
                'edit_posts'        => __('Contributor and above',   'generatepress-child'),
                'read'              => __('All logged-in users',     'generatepress-child'),
            ];
            ?>
    <form method="post" action="options.php" style="margin-bottom:24px;">
      <?php settings_fields('gp_child_notes_group'); ?>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><?php esc_html_e('Show dashboard notices to', 'generatepress-child'); ?></th>
          <td>
            <select name="gp_child_notes_role">
              <?php foreach ($role_options as $cap => $label) : ?>
              <option value="<?php echo esc_attr($cap); ?>" <?php selected($notes_role, $cap); ?>>
                <?php echo esc_html($label); ?>
              </option>
              <?php endforeach; ?>
            </select>
            <p class="description">
              <?php esc_html_e('Which user roles see these notes as notices on the dashboard.', 'generatepress-child'); ?>
            </p>
          </td>
        </tr>
      </table>
      <?php submit_button(__('Save Visibility', 'generatepress-child'), 'secondary'); ?>
    </form>

    <div class="gp-notes-header">
      <h3><?php esc_html_e('Internal Notes', 'generatepress-child'); ?></h3>
      <button type="button" id="gp-add-note" class="button button-primary">
        + <?php esc_html_e('Add Note', 'generatepress-child'); ?>
      </button>
    </div>

    <div id="gp-notes-grid" class="gp-notes-grid">
      <?php if (empty($notes)) : ?>
      <p class="gp-notes-empty">
        <?php esc_html_e('No notes yet. Click "Add Note" to create one.', 'generatepress-child'); ?></p>
      <?php else : ?>
      <?php foreach ($notes as $note) :
                        $id      = esc_attr($note['id']      ?? '');
                        $title   = esc_attr($note['title']   ?? '');
                        $content = esc_textarea($note['content'] ?? '');
                        $color   = in_array($note['color'] ?? '', ['yellow','blue','green','pink'], true) ? $note['color'] : 'yellow';
                    ?>
      <div class="gp-note-card" data-id="<?php echo $id; ?>" data-color="<?php echo esc_attr($color); ?>">
        <div class="gp-note-colors">
          <?php foreach (['yellow','blue','green','pink'] as $c) : ?>
          <span class="gp-note-color-dot<?php echo $color === $c ? ' is-selected' : ''; ?>"
            data-color="<?php echo esc_attr($c); ?>" title="<?php echo esc_attr($c); ?>"></span>
          <?php endforeach; ?>
        </div>
        <button type="button" class="gp-note-delete"
          title="<?php esc_attr_e('Delete note', 'generatepress-child'); ?>">&#10005;</button>
        <input type="text" class="gp-note-title" placeholder="<?php esc_attr_e('Title…', 'generatepress-child'); ?>"
          value="<?php echo $title; ?>">
        <textarea class="gp-note-body" placeholder="<?php esc_attr_e('Note…', 'generatepress-child'); ?>"
          rows="5"><?php echo $content; ?></textarea>
        <div class="gp-note-footer">
          <span class="gp-note-status"></span>
          <button type="button"
            class="button button-primary gp-note-save"><?php esc_html_e('Save', 'generatepress-child'); ?></button>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php gp_child_render_tab_help(__('Internal Notes are lightweight reminders for the team and can also appear as dashboard notices depending on visibility settings.', 'generatepress-child')); ?>

  </div>

  <?php /* ── WebP Converter ─────────────────────────────────────────── */ ?>
  <div id="gp-tab-webp" class="gp-tab-panel" role="tabpanel">
    <form method="post" action="options.php">
      <?php settings_fields('gp_child_webp_group'); ?>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><?php esc_html_e('Convert uploaded images to WebP', 'generatepress-child'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="mbwpc_convert_to_webp" value="1"
                <?php checked(1, get_option('mbwpc_convert_to_webp', false)); ?>>
              <?php esc_html_e('Enable automatic WebP conversion', 'generatepress-child'); ?>
            </label>
            <?php if (!extension_loaded('imagick')) : ?>
            <p class="description" style="color:#d63638;">
              <?php esc_html_e('Imagick no está instalado. La conversión WebP está deshabilitada.', 'generatepress-child'); ?>
            </p>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Max Width (px)', 'generatepress-child'); ?></th>
          <td>
            <input type="number" name="mbwpc_max_width" min="1" style="width:100px;"
              value="<?php echo esc_attr(get_option('mbwpc_max_width', 1920)); ?>">
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Max Height (px)', 'generatepress-child'); ?></th>
          <td>
            <input type="number" name="mbwpc_max_height" min="1" style="width:100px;"
              value="<?php echo esc_attr(get_option('mbwpc_max_height', 1080)); ?>">
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e('Quality (1–100)', 'generatepress-child'); ?></th>
          <td>
            <input type="number" name="mbwpc_quality" min="1" max="100" style="width:80px;"
              value="<?php echo esc_attr(get_option('mbwpc_quality', 80)); ?>">
          </td>
        </tr>
      </table>
      <?php submit_button(__('Save WebP Settings', 'generatepress-child')); ?>
    </form>
    <?php gp_child_render_tab_help(__('Enable automatic WebP conversion here to keep uploaded images lighter and more consistent across the site.', 'generatepress-child')); ?>
  </div>

</div><!-- .gp-child-settings -->
<?php
}

// ── 6. Front-end: apply all saved settings ────────────────────────────────────
