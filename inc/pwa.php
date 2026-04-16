<?php
/**
 * Progressive Web App support for the child theme.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('admin_init', function (): void {
    register_setting('gp_child_pwa_group', 'gp_child_pwa_settings', ['sanitize_callback' => 'gp_child_sanitize_pwa']);
}, 20);

add_action('admin_enqueue_scripts', function (string $hook): void {
    if ($hook !== 'appearance_page_gp-child-settings') {
        return;
    }

    wp_localize_script('gp-child-admin', 'gpPwa', [
        'title'  => __('Select PWA Icon', 'generatepress-child'),
        'button' => __('Use this icon', 'generatepress-child'),
    ]);
});

add_filter('gp_child_settings_tabs', function (array $tabs): array {
    $tabs['pwa'] = __('PWA', 'generatepress-child');
    return $tabs;
});

add_action('gp_child_render_settings_panels', 'gp_child_render_pwa_settings_panel');
add_filter('gp_child_admin_settings_js', function (string $script): string {
    return $script . "\n" . gp_child_get_pwa_admin_settings_js();
});
add_filter('gp_child_admin_settings_css', function (string $css): string {
    return $css . "\n" . gp_child_get_pwa_admin_settings_css();
});

function gp_child_default_pwa_settings(): array
{
    return [
        'enabled'          => '0',
        'app_name'         => '',
        'theme_color'      => '#0f172a',
        'background_color' => '#ffffff',
        'icon_id'          => 0,
    ];
}

function gp_child_get_pwa_settings(): array
{
    $settings = wp_parse_args((array) get_option('gp_child_pwa_settings', []), gp_child_default_pwa_settings());

    $settings['enabled']          = ! empty($settings['enabled']) ? '1' : '0';
    $settings['app_name']         = trim((string) ($settings['app_name'] ?? '')) ?: get_bloginfo('name');
    $settings['theme_color']      = sanitize_hex_color((string) ($settings['theme_color'] ?? '')) ?: '#0f172a';
    $settings['background_color'] = sanitize_hex_color((string) ($settings['background_color'] ?? '')) ?: '#ffffff';
    $settings['icon_id']          = absint($settings['icon_id'] ?? 0);

    return $settings;
}

function gp_child_is_pwa_enabled(): bool
{
    $settings = gp_child_get_pwa_settings();
    return $settings['enabled'] === '1';
}

function gp_child_sanitize_pwa(mixed $input): array
{
    $submitted_page = sanitize_text_field(wp_unslash($_POST['option_page'] ?? ''));
    if ($submitted_page !== 'gp_child_pwa_group') {
        return is_array($input) ? $input : [];
    }

    if (! is_array($input)) {
        $input = [];
    }

    return [
        'enabled'          => ! empty($input['enabled']) ? '1' : '0',
        'app_name'         => sanitize_text_field($input['app_name'] ?? ''),
        'theme_color'      => sanitize_hex_color($input['theme_color'] ?? '') ?: '#0f172a',
        'background_color' => sanitize_hex_color($input['background_color'] ?? '') ?: '#ffffff',
        'icon_id'          => absint($input['icon_id'] ?? 0),
    ];
}

function gp_child_get_pwa_icon_url(?int $icon_id = null, string $size = 'full'): string
{
    $icon_id = $icon_id ?? absint(gp_child_get_pwa_settings()['icon_id'] ?? 0);
    if ($icon_id > 0) {
        $url = wp_get_attachment_image_url($icon_id, $size);
        if ($url) {
            return $url;
        }
    }

    $fallback = GP_CHILD_DIR . '/screenshot.png';
    return file_exists($fallback) ? GP_CHILD_URI . '/screenshot.png' : '';
}

function gp_child_get_pwa_manifest_icons(): array
{
    $icon_id = absint(gp_child_get_pwa_settings()['icon_id'] ?? 0);

    if ($icon_id > 0) {
        $url = wp_get_attachment_image_url($icon_id, 'full');
        $meta = wp_get_attachment_metadata($icon_id);
        $mime = get_post_mime_type($icon_id) ?: 'image/png';
        $sizes = '';

        if (! empty($meta['width']) && ! empty($meta['height'])) {
            $sizes = absint($meta['width']) . 'x' . absint($meta['height']);
        }

        if ($url) {
            return [[
                'src'   => $url,
                'sizes' => $sizes ?: '512x512',
                'type'  => $mime,
            ]];
        }
    }

    $fallback = GP_CHILD_DIR . '/screenshot.png';
    if (file_exists($fallback)) {
        $image_size = wp_getimagesize($fallback);
        return [[
            'src'   => GP_CHILD_URI . '/screenshot.png',
            'sizes' => ! empty($image_size[0]) && ! empty($image_size[1]) ? absint($image_size[0]) . 'x' . absint($image_size[1]) : '1200x900',
            'type'  => 'image/png',
        ]];
    }

    return [];
}

function gp_child_get_pwa_manifest_data(): array
{
    $settings = gp_child_get_pwa_settings();
    $short_name = function_exists('mb_substr')
        ? mb_substr($settings['app_name'], 0, 12)
        : substr($settings['app_name'], 0, 12);

    return [
        'name'             => $settings['app_name'],
        'short_name'       => $short_name,
        'start_url'        => home_url('/'),
        'scope'            => home_url('/'),
        'display'          => 'standalone',
        'background_color' => $settings['background_color'],
        'theme_color'      => $settings['theme_color'],
        'icons'            => gp_child_get_pwa_manifest_icons(),
    ];
}

function gp_child_get_pwa_scope_path(): string
{
    $path = wp_parse_url(home_url('/'), PHP_URL_PATH);
    return $path ? trailingslashit($path) : '/';
}

function gp_child_get_pwa_admin_settings_js(): string
{
    return <<<'JS'
var pwaFrame;
document.addEventListener('click', function(e){
    var sel = e.target.closest('#gp-pwa-icon-select');
    if (sel) {
        e.preventDefault();
        if (pwaFrame) { pwaFrame.open(); return; }
        pwaFrame = wp.media({
            title:    (window.gpPwa && gpPwa.title)  || 'Select PWA Icon',
            multiple: false,
            library:  { type: 'image' },
            button:   { text: (window.gpPwa && gpPwa.button) || 'Use this icon' }
        });
        pwaFrame.on('select', function(){
            var att = pwaFrame.state().get('selection').first().toJSON();
            document.getElementById('gp-pwa-icon-id').value = att.id;
            var src = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
            var preview = document.getElementById('gp-pwa-icon-preview');
            preview.src = src;
            preview.style.display = '';
            document.getElementById('gp-pwa-icon-remove').style.display = '';
        });
        pwaFrame.on('open', function(){
            var id = document.getElementById('gp-pwa-icon-id').value;
            if (!id) { return; }
            var attachment = wp.media.attachment(id);
            attachment.fetch();
            pwaFrame.state().get('selection').add(attachment ? [attachment] : []);
        });
        pwaFrame.open();
    }
});
document.addEventListener('click', function(e){
    var rem = e.target.closest('#gp-pwa-icon-remove');
    if (rem) {
        e.preventDefault();
        document.getElementById('gp-pwa-icon-id').value = '';
        var preview = document.getElementById('gp-pwa-icon-preview');
        preview.src = '';
        preview.style.display = 'none';
        rem.style.display = 'none';
    }
});
document.addEventListener('input', function(e){
    if (e.target && e.target.dataset.syncColor) {
        var target = document.getElementById(e.target.dataset.syncColor);
        if (target) { target.value = e.target.value; }
    }

    if (e.target && e.target.dataset.syncPicker) {
        var picker = document.getElementById(e.target.dataset.syncPicker);
        if (picker && /^#[0-9a-fA-F]{6}$/.test(e.target.value)) {
            picker.value = e.target.value;
        }
    }
});
JS;
}

function gp_child_get_pwa_admin_settings_css(): string
{
    return <<<'CSS'
.gp-pwa-icon-preview {
  width: 96px;
  height: 96px;
  object-fit: cover;
  display: block;
  margin-top: 10px;
  border-radius: 18px;
  border: 1px solid #dcdcde;
  background: #f6f7f7;
}

.gp-coming-soon-card {
  border: 1px solid #dcdcde;
  border-radius: 4px;
  padding: 20px 24px;
  margin-top: 24px;
  background: #f6f7f7;
  color: #646970;
}

.gp-coming-soon-card .gp-cache-section-title,
.gp-coming-soon-card strong {
  color: #50575e;
}

.gp-disabled-fieldset {
  opacity: .68;
  filter: grayscale(1);
}

.gp-disabled-fieldset input,
.gp-disabled-fieldset select,
.gp-disabled-fieldset textarea,
.gp-disabled-fieldset button {
  cursor: not-allowed !important;
}
CSS;
}

function gp_child_render_pwa_settings_panel(): void
{
    $pwa_settings = gp_child_get_pwa_settings();
    $pwa_icon_id  = absint($pwa_settings['icon_id'] ?? 0);
    $pwa_icon_url = $pwa_icon_id ? wp_get_attachment_image_url($pwa_icon_id, 'thumbnail') : '';
    ?>
  <div id="gp-tab-pwa" class="gp-tab-panel" role="tabpanel">
    <form method="post" action="options.php">
      <?php settings_fields('gp_child_pwa_group'); ?>
      <div class="gp-cache-section">
        <h3 class="gp-cache-section-title"><?php esc_html_e('Progressive Web App', 'generatepress-child'); ?></h3>
        <p class="gp-cache-section-desc">
          <?php esc_html_e('Enable a lightweight app shell for install prompts, manifest metadata, and a basic service worker.', 'generatepress-child'); ?>
        </p>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><?php esc_html_e('Enable PWA', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="gp_child_pwa_settings[enabled]" value="1"
                  <?php checked('1', $pwa_settings['enabled'] ?? '0'); ?>>
                <?php esc_html_e('Turn on the manifest, browser theme color, install metadata, and service worker registration.', 'generatepress-child'); ?>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('App Name', 'generatepress-child'); ?></th>
            <td>
              <input type="text" name="gp_child_pwa_settings[app_name]" class="regular-text"
                value="<?php echo esc_attr($pwa_settings['app_name'] ?? ''); ?>"
                placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
              <p class="description">
                <?php esc_html_e('Used in the web app manifest and install prompt. Leave blank to use the site title.', 'generatepress-child'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Theme Color', 'generatepress-child'); ?></th>
            <td>
              <input type="color" id="gp-pwa-theme-color-picker" data-sync-color="gp-pwa-theme-color-text"
                value="<?php echo esc_attr($pwa_settings['theme_color'] ?? '#0f172a'); ?>">
              <input type="text" id="gp-pwa-theme-color-text" data-sync-picker="gp-pwa-theme-color-picker"
                class="regular-text" style="max-width:110px;margin-left:8px;"
                name="gp_child_pwa_settings[theme_color]"
                value="<?php echo esc_attr($pwa_settings['theme_color'] ?? '#0f172a'); ?>">
              <p class="description">
                <?php esc_html_e('Controls the browser UI tint on supported devices.', 'generatepress-child'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Background Color', 'generatepress-child'); ?></th>
            <td>
              <input type="color" id="gp-pwa-background-color-picker" data-sync-color="gp-pwa-background-color-text"
                value="<?php echo esc_attr($pwa_settings['background_color'] ?? '#ffffff'); ?>">
              <input type="text" id="gp-pwa-background-color-text" data-sync-picker="gp-pwa-background-color-picker"
                class="regular-text" style="max-width:110px;margin-left:8px;"
                name="gp_child_pwa_settings[background_color]"
                value="<?php echo esc_attr($pwa_settings['background_color'] ?? '#ffffff'); ?>">
              <p class="description">
                <?php esc_html_e('Used in the splash screen and manifest background.', 'generatepress-child'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Icon Upload', 'generatepress-child'); ?></th>
            <td>
              <input type="hidden" name="gp_child_pwa_settings[icon_id]" id="gp-pwa-icon-id"
                value="<?php echo esc_attr($pwa_icon_id); ?>">
              <button type="button" class="button" id="gp-pwa-icon-select">
                <?php echo $pwa_icon_id ? esc_html__('Replace icon', 'generatepress-child') : esc_html__('Select icon', 'generatepress-child'); ?>
              </button>
              <button type="button" class="button" id="gp-pwa-icon-remove"
                style="<?php echo $pwa_icon_id ? '' : 'display:none;'; ?>">
                <?php esc_html_e('Remove', 'generatepress-child'); ?>
              </button>
              <img id="gp-pwa-icon-preview" class="gp-pwa-icon-preview" alt=""
                src="<?php echo esc_url($pwa_icon_url); ?>" style="<?php echo $pwa_icon_url ? '' : 'display:none;'; ?>">
              <p class="description">
                <?php esc_html_e('Upload a square PNG if possible. The manifest falls back to screenshot.png when no custom icon is selected.', 'generatepress-child'); ?>
              </p>
            </td>
          </tr>
        </table>
      </div>
      <?php submit_button(__('Save PWA Settings', 'generatepress-child')); ?>
    </form>

    <div class="gp-coming-soon-card">
      <h3 class="gp-cache-section-title"><?php esc_html_e('Push Notifications', 'generatepress-child'); ?></h3>
      <p class="gp-cache-section-desc" style="margin-bottom:18px;">
        <?php esc_html_e('This section is planned, but not active yet. It is shown here so the future feature feels connected to the PWA setup without exposing a broken workflow.', 'generatepress-child'); ?>
      </p>
      <fieldset class="gp-disabled-fieldset" disabled>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><?php esc_html_e('Enable Push Notifications', 'generatepress-child'); ?></th>
            <td>
              <label>
                <input type="checkbox" disabled>
                <?php esc_html_e('Coming soon. This will require provider configuration before it can be used.', 'generatepress-child'); ?>
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Provider', 'generatepress-child'); ?></th>
            <td>
              <select disabled>
                <option><?php esc_html_e('Not available yet', 'generatepress-child'); ?></option>
              </select>
            </td>
          </tr>
        </table>
      </fieldset>
    </div>

    <?php gp_child_render_tab_help(__('Use this tab to give the site installable app metadata with minimal setup. Push notifications are intentionally visible here as a muted placeholder, but they are not wired up yet.', 'generatepress-child')); ?>
  </div>
    <?php
}

function gp_child_get_pwa_sw_script(): string
{
    $settings   = gp_child_get_pwa_settings();
    $cache_name = 'gp-child-pwa-' . md5(wp_json_encode([
        $settings['app_name'],
        $settings['theme_color'],
        $settings['background_color'],
        $settings['icon_id'],
        GP_CHILD_VERSION,
    ]));
    $precache = array_values(array_filter([
        home_url('/'),
        gp_child_get_pwa_icon_url(null, 'full'),
    ]));

    return "(function(){\n" .
        'const CACHE_NAME = ' . wp_json_encode($cache_name) . ";\n" .
        'const HOME_URL = ' . wp_json_encode(home_url('/')) . ";\n" .
        'const PRECACHE_URLS = ' . wp_json_encode($precache) . ";\n" .
        "function shouldSkip(request, url) {\n" .
        "  if (request.method !== 'GET' || url.origin !== self.location.origin) return true;\n" .
        "  if (url.pathname.indexOf('/wp-admin') === 0 || url.pathname.indexOf('/wp-login.php') === 0 || url.pathname.indexOf('/wp-json') === 0) return true;\n" .
        "  if (url.searchParams.has('preview') || url.searchParams.has('customize_changeset_uuid')) return true;\n" .
        "  return false;\n" .
        "}\n" .
        "self.addEventListener('install', function(event) {\n" .
        "  event.waitUntil(caches.open(CACHE_NAME).then(function(cache) {\n" .
        "    return cache.addAll(PRECACHE_URLS);\n" .
        "  }).catch(function() { return null; }));\n" .
        "  self.skipWaiting();\n" .
        "});\n" .
        "self.addEventListener('activate', function(event) {\n" .
        "  event.waitUntil(caches.keys().then(function(keys) {\n" .
        "    return Promise.all(keys.map(function(key) {\n" .
        "      return key !== CACHE_NAME ? caches.delete(key) : Promise.resolve();\n" .
        "    }));\n" .
        "  }).then(function() { return self.clients.claim(); }));\n" .
        "});\n" .
        "self.addEventListener('fetch', function(event) {\n" .
        "  const request = event.request;\n" .
        "  const url = new URL(request.url);\n" .
        "  if (shouldSkip(request, url)) return;\n" .
        "  if (request.mode === 'navigate') {\n" .
        "    event.respondWith(fetch(request).then(function(response) {\n" .
        "      if (response && response.ok) {\n" .
        "        const copy = response.clone();\n" .
        "        caches.open(CACHE_NAME).then(function(cache) { cache.put(request, copy); });\n" .
        "      }\n" .
        "      return response;\n" .
        "    }).catch(function() {\n" .
        "      return caches.match(request).then(function(match) {\n" .
        "        return match || caches.match(HOME_URL);\n" .
        "      });\n" .
        "    }));\n" .
        "    return;\n" .
        "  }\n" .
        "  if (['style', 'script', 'image', 'font'].indexOf(request.destination) === -1) return;\n" .
        "  event.respondWith(caches.match(request).then(function(match) {\n" .
        "    if (match) { return match; }\n" .
        "    return fetch(request).then(function(response) {\n" .
        "      if (response && response.ok) {\n" .
        "        const copy = response.clone();\n" .
        "        caches.open(CACHE_NAME).then(function(cache) { cache.put(request, copy); });\n" .
        "      }\n" .
        "      return response;\n" .
        "    }).catch(function() { return Response.error(); });\n" .
        "  }));\n" .
        "});\n" .
        "}());\n";
}

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'gp_child_manifest';
    $vars[] = 'gp_child_sw';
    return $vars;
});

add_action('template_redirect', function (): void {
    if (! gp_child_is_pwa_enabled()) {
        return;
    }

    if (get_query_var('gp_child_manifest')) {
        nocache_headers();
        header('Content-Type: application/manifest+json; charset=utf-8');
        echo wp_json_encode(gp_child_get_pwa_manifest_data(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (get_query_var('gp_child_sw')) {
        nocache_headers();
        header('Content-Type: application/javascript; charset=utf-8');
        echo gp_child_get_pwa_sw_script();
        exit;
    }
}, 0);

add_action('wp_head', function (): void {
    if (! gp_child_is_pwa_enabled()) {
        return;
    }

    $settings = gp_child_get_pwa_settings();
    $icon_url = gp_child_get_pwa_icon_url();
    ?>
<link rel="manifest" href="<?php echo esc_url(home_url('/?gp_child_manifest=1')); ?>">
<meta name="theme-color" content="<?php echo esc_attr($settings['theme_color']); ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr($settings['app_name']); ?>">
<?php if ($icon_url) : ?>
<link rel="apple-touch-icon" href="<?php echo esc_url($icon_url); ?>">
<?php endif; ?>
    <?php
}, 1);

add_action('wp_enqueue_scripts', function (): void {
    if (is_admin() || ! gp_child_is_pwa_enabled()) {
        return;
    }

    $scope = gp_child_get_pwa_scope_path();

    wp_register_script('gp-child-pwa-register', false, [], GP_CHILD_VERSION, true);
    wp_enqueue_script('gp-child-pwa-register');
    wp_add_inline_script(
        'gp-child-pwa-register',
        '(function(){if(!("serviceWorker" in navigator)){return;}window.addEventListener("load",function(){navigator.serviceWorker.register(' .
        wp_json_encode(home_url('/?gp_child_sw=1')) .
        ',{scope:' . wp_json_encode($scope) . '}).catch(function(){});});}());'
    );
}, 20);
