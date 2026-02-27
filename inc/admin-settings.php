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
    return <<<'JS'
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
    });
}());
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
});

// ── 4. Sanitize callbacks ─────────────────────────────────────────────────────

function gp_child_sanitize_general(mixed $input): array
{
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

    $notes = gp_child_get_notes();

    $tabs = [
        'site-info'  => __('Site Info',    'generatepress-child'),
        'options'    => __('Options',      'generatepress-child'),
        'analytics'  => __('Analytics',    'generatepress-child'),
        '404'        => __('404 Page',     'generatepress-child'),
        'cache'      => __('Cache Buster', 'generatepress-child'),
        'notes'      => __('Notes',        'generatepress-child'),
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
                flex-wrap: wrap;
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

            /* ── Cache tab sections ──────────────────────────────────────── */
            .gp-cache-section {
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px 24px 4px;
                margin-bottom: 24px;
                background: #fdfdfd;
            }
            .gp-cache-section + .gp-cache-section { margin-top: 0; }
            .gp-cache-section-title {
                font-size: 13px; font-weight: 600; color: #1d2327;
                margin: 0 0 4px; display: flex; align-items: center; gap: 7px;
            }
            .gp-cache-section-desc {
                font-size: 12px; color: #646970; margin: 0 0 14px; line-height: 1.6;
            }
            /* ── Notes grid ──────────────────────────────────────────────── */
            .gp-notes-header { display:flex; align-items:center; margin-bottom:20px; }
            .gp-notes-header h3 { margin:0; flex:1; font-size:14px; font-weight:600; color:#1d2327; }
            .gp-notes-grid {
                display: -ms-grid;
                display: grid;
                -ms-grid-columns: 1fr 16px 1fr 16px 1fr;
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 16px;
            }
            .gp-notes-empty { color:#888; font-size:13px; font-style:italic; }

            /* ── Note card ───────────────────────────────────────────────── */
            .gp-note-card {
                border-radius: 6px;
                padding: 14px 16px 12px;
                position: relative;
                box-shadow: 0 1px 3px rgba(0,0,0,.08);
                display: flex;
                flex-direction: column;
            }
            .gp-note-card[data-color="yellow"] { background:#fffde7; border:1px solid #f0d048; }
            .gp-note-card[data-color="blue"]   { background:#e3f2fd; border:1px solid #90caf9; }
            .gp-note-card[data-color="green"]  { background:#e8f5e9; border:1px solid #a5d6a7; }
            .gp-note-card[data-color="pink"]   { background:#fce4ec; border:1px solid #f48fb1; }

            .gp-note-colors { display:flex; gap:6px; margin-bottom:10px; align-items:center; }
            .gp-note-color-dot {
                width: 13px; height: 13px; border-radius: 50%; cursor: pointer;
                border: 2px solid transparent; flex-shrink: 0; transition: transform .1s;
            }
            .gp-note-color-dot[data-color="yellow"] { background:#f9c922; }
            .gp-note-color-dot[data-color="blue"]   { background:#64b5f6; }
            .gp-note-color-dot[data-color="green"]  { background:#81c784; }
            .gp-note-color-dot[data-color="pink"]   { background:#f06292; }
            .gp-note-color-dot.is-selected { border-color:#555; transform:scale(1.25); }
            .gp-note-color-dot:hover { transform:scale(1.2); }

            .gp-note-delete {
                position: absolute; top:10px; right:10px;
                background: none; border: none; cursor: pointer;
                color: #bbb; font-size: 15px; line-height:1; padding:2px 4px;
                border-radius: 3px; transition: color .15s, background .15s;
            }
            .gp-note-delete:hover { color:#d63638; background:rgba(214,54,56,.08); }

            .gp-note-title {
                width: 100%; border: none; background: transparent;
                font-weight: 600; font-size: 13px; margin-bottom: 8px;
                padding: 0; color: #1d2327; outline: none;
                box-shadow: none !important;
            }
            .gp-note-title:focus { border-bottom:1px dashed #aaa; }
            .gp-note-body {
                width: 100%; min-height: 90px; border: none; background: transparent;
                resize: vertical; font-size: 12px; line-height:1.6;
                color: #3c434a; padding: 0; outline: none; flex:1;
                box-shadow: none !important;
            }
            .gp-note-footer { display:flex; align-items:center; margin-top:10px; gap:8px; }
            .gp-note-status { font-size:11px; color:#5a7a5a; flex:1; }
            .gp-note-save { flex-shrink:0; }
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
                            <p class="description">
                                <?php esc_html_e('WhatConverts account ID. Leave blank to disable.', 'generatepress-child'); ?><br>
                                <?php esc_html_e('WhatConverts tracks calls, forms and chats and attributes them to a marketing source (Google Ads, SEO, etc.).', 'generatepress-child'); ?><br>
                                <?php esc_html_e('Find your ID in WhatConverts: Settings → Profiles → your profile → Profile ID.', 'generatepress-child'); ?>
                            </p>
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

            <?php
            $vi = get_option('gp_child_version_images', '0');
            ?>

            <?php /* ── Section 1: Browser cache ─────────────────────── */ ?>
            <div class="gp-cache-section">
                <h3 class="gp-cache-section-title">&#128274; <?php esc_html_e('Client Browser Cache', 'generatepress-child'); ?></h3>
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
                                <input type="number" name="gp_child_css_version"
                                       value="<?php echo esc_attr($cv); ?>" min="1" step="1" style="width:80px;">
                                &nbsp;
                                <button type="submit" name="gp_child_css_version"
                                        value="<?php echo esc_attr($cv + 1); ?>"
                                        class="button button-secondary">
                                    <?php esc_html_e('Increment +1', 'generatepress-child'); ?>
                                </button>
                                <p class="description"><?php esc_html_e('Appended as ?v=X to all theme CSS and JS files.', 'generatepress-child'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Version Image URLs', 'generatepress-child'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="gp_child_version_images" value="1" <?php checked('1', $vi); ?>>
                                    <?php esc_html_e('Also append ?v=X to media/attachment image URLs.', 'generatepress-child'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Useful after replacing an image file at the same URL. Uses the same counter above.', 'generatepress-child'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Save Browser Cache Settings', 'generatepress-child')); ?>
                </form>
            </div>

            <?php /* ── Section 2: Firewall / CDN Cache (info only) ─── */ ?>
            <div class="gp-cache-section">
                <h3 class="gp-cache-section-title">&#127760; <?php esc_html_e('Firewall / CDN Cache (Cloudflare, Sucuri, etc.)', 'generatepress-child'); ?></h3>
                <p class="gp-cache-section-desc"><?php esc_html_e(
                    'CDN and firewall services (Cloudflare, Sucuri WAF, WP Engine, Kinsta, etc.) cache full HTML pages at edge servers worldwide. ' .
                    'When you update a page in WordPress the CDN may keep serving a stale copy for hours or days — ' .
                    'visitors will not see your changes until that cache is cleared.',
                    'generatepress-child'
                ); ?></p>

                <p style="font-size:12px;font-weight:600;color:#1d2327;margin:0 0 6px;"><?php esc_html_e('When to purge your CDN cache:', 'generatepress-child'); ?></p>
                <ul style="margin:0 0 16px 18px;font-size:12px;color:#50575e;list-style:disc;">
                    <li><?php esc_html_e('After editing a published page, post, or landing page', 'generatepress-child'); ?></li>
                    <li><?php esc_html_e('After updating the theme, CSS, or global layout', 'generatepress-child'); ?></li>
                    <li><?php esc_html_e('After changing headers, footers, navigation menus, or sidebars', 'generatepress-child'); ?></li>
                    <li><?php esc_html_e('After a plugin update that affects front-end output', 'generatepress-child'); ?></li>
                </ul>

                <p style="font-size:12px;font-weight:600;color:#1d2327;margin:0 0 6px;"><?php esc_html_e('How to purge — go to your provider\'s dashboard:', 'generatepress-child'); ?></p>
                <ul style="margin:0 0 8px 18px;font-size:12px;color:#50575e;list-style:disc;">
                    <li><strong>Cloudflare:</strong> <?php esc_html_e('Caching → Configuration → Purge Everything', 'generatepress-child'); ?></li>
                    <li><strong>Sucuri WAF:</strong> <?php esc_html_e('Firewall → Clear Cache', 'generatepress-child'); ?></li>
                    <li><strong>WP Rocket:</strong> <?php esc_html_e('Dashboard → Clear cache button (top bar)', 'generatepress-child'); ?></li>
                    <li><strong>WP Engine / Kinsta:</strong> <?php esc_html_e('Hosting dashboard → Clear all caches', 'generatepress-child'); ?></li>
                </ul>
            </div>

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
                            <p class="description"><?php esc_html_e('Which user roles see these notes as notices on the dashboard.', 'generatepress-child'); ?></p>
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
                    <p class="gp-notes-empty"><?php esc_html_e('No notes yet. Click "Add Note" to create one.', 'generatepress-child'); ?></p>
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
                        <button type="button" class="gp-note-delete" title="<?php esc_attr_e('Delete note', 'generatepress-child'); ?>">&#10005;</button>
                        <input type="text" class="gp-note-title" placeholder="<?php esc_attr_e('Title…', 'generatepress-child'); ?>" value="<?php echo $title; ?>">
                        <textarea class="gp-note-body" placeholder="<?php esc_attr_e('Note…', 'generatepress-child'); ?>" rows="5"><?php echo $content; ?></textarea>
                        <div class="gp-note-footer">
                            <span class="gp-note-status"></span>
                            <button type="button" class="button button-primary gp-note-save"><?php esc_html_e('Save', 'generatepress-child'); ?></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

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

// ── 8. Dashboard admin notices ────────────────────────────────────────────────

add_action('admin_notices', function (): void {
    global $pagenow;
    if ($pagenow !== 'index.php') {
        return;
    }
    $required_cap = get_option('gp_child_notes_role', 'manage_options');
    if (! current_user_can($required_cap)) {
        return;
    }
    $notes = gp_child_get_notes();
    if (empty($notes)) {
        return;
    }

    // Map note color → WP notice type + custom border color
    $color_map = [
        'yellow' => ['type' => 'notice-warning',  'border' => '#f0d048'],
        'blue'   => ['type' => 'notice-info',      'border' => '#90caf9'],
        'green'  => ['type' => 'notice-success',   'border' => '#a5d6a7'],
        'pink'   => ['type' => 'notice-error',     'border' => '#f48fb1'],
    ];

    $manage_url = admin_url('themes.php?page=gp-child-settings');

    foreach ($notes as $note) {
        $color   = in_array($note['color'] ?? '', ['yellow','blue','green','pink'], true) ? $note['color'] : 'yellow';
        $map     = $color_map[$color];
        $title   = esc_html(trim($note['title']   ?? ''));
        $content = esc_html(trim($note['content'] ?? ''));

        if ($title === '' && $content === '') {
            continue;
        }

        printf(
            '<div class="notice %s gp-site-note" style="border-left-color:%s;">' .
                '<p>' .
                    '%s' .
                    '%s' .
                    '<a href="%s" style="margin-left:10px;font-size:11px;opacity:.7;">%s</a>' .
                '</p>' .
            '</div>',
            esc_attr($map['type']),
            esc_attr($map['border']),
            $title   !== '' ? '<strong>' . $title . '</strong> ' : '',
            $content !== '' ? '<span style="color:#50575e;">' . nl2br($content) . '</span>' : '',
            esc_url($manage_url),
            esc_html__('Manage notes →', 'generatepress-child')
        );
    }
});

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

// ── 7. Cache: image URL versioning (Option A) ────────────────────────────────

// — Image URL versioning ──────────────────────────────────────────────────────
add_filter('wp_get_attachment_url', function (string $url): string {
    if (get_option('gp_child_version_images', '0') !== '1') {
        return $url;
    }
    $v = intval(get_option('gp_child_css_version', 1));
    return add_query_arg('v', $v, $url);
});

// ── 8. Notes helpers + AJAX ───────────────────────────────────────────────────

function gp_child_get_notes(): array
{
    $raw = get_option('gp_child_notes', '[]');
    $arr = json_decode((string) $raw, true);
    return is_array($arr) ? $arr : [];
}

function gp_child_save_notes(array $notes): void
{
    update_option('gp_child_notes', wp_json_encode(array_values($notes)));
}

add_action('wp_ajax_gp_child_add_note', function (): void {
    check_ajax_referer('gp_child_notes', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }
    $notes  = gp_child_get_notes();
    $note   = [
        'id'      => wp_generate_uuid4(),
        'title'   => '',
        'content' => '',
        'color'   => 'yellow',
        'created' => gmdate('Y-m-d'),
    ];
    array_unshift($notes, $note);
    gp_child_save_notes($notes);
    wp_send_json_success($note);
});

add_action('wp_ajax_gp_child_save_note', function (): void {
    check_ajax_referer('gp_child_notes', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }
    $id      = sanitize_text_field(wp_unslash($_POST['id']      ?? ''));
    $title   = sanitize_text_field(wp_unslash($_POST['title']   ?? ''));
    $content = sanitize_textarea_field(wp_unslash($_POST['content'] ?? ''));
    $color   = in_array($_POST['color'] ?? '', ['yellow','blue','green','pink'], true)
        ? sanitize_text_field($_POST['color'])
        : 'yellow';

    $notes = gp_child_get_notes();
    $found = false;
    foreach ($notes as &$note) {
        if ($note['id'] === $id) {
            $note['title']   = $title;
            $note['content'] = $content;
            $note['color']   = $color;
            $found           = true;
            break;
        }
    }
    unset($note);
    if (! $found) {
        wp_send_json_error('Note not found', 404);
    }
    gp_child_save_notes($notes);
    wp_send_json_success();
});

add_action('wp_ajax_gp_child_delete_note', function (): void {
    check_ajax_referer('gp_child_notes', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }
    $id    = sanitize_text_field(wp_unslash($_POST['id'] ?? ''));
    $notes = array_filter(gp_child_get_notes(), fn($n) => $n['id'] !== $id);
    gp_child_save_notes(array_values($notes));
    wp_send_json_success();
});

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
