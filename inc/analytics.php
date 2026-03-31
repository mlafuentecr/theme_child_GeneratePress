<?php
/**
 * Analytics and tracking integrations.
 */

if (! defined('ABSPATH')) {
    exit;
}

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

    if (is_user_logged_in() && current_user_can('update_core') && empty($ga['google_analytics_logged_in'])) {
        return;
    }

    if (strncmp($id, 'GTM-', 4) === 0) {
        ?>
<!-- Google Tag Manager -->
<script>
(function(w, d, s, l, i) {
  w[l] = w[l] || [];
  w[l].push({
    'gtm.start': new Date().getTime(),
    event: 'gtm.js'
  });
  var f = d.getElementsByTagName(s)[0],
    j = d.createElement(s),
    dl = l != 'dataLayer' ? '&l=' + l : '';
  j.async = true;
  j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
  f.parentNode.insertBefore(j, f);
})(window, document, 'script', 'dataLayer', '<?php echo esc_js($id); ?>');
</script>
<!-- End Google Tag Manager -->
<?php
        return;
    }
    ?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($id); ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];

function gtag() {
  dataLayer.push(arguments);
}
gtag('js', new Date());
gtag('config', '<?php echo esc_js($id); ?>');
</script>
<!-- End Google tag -->
<?php
}

add_action('wp_footer', function (): void {
    $opts  = (array) get_option('blueflamingo_plugin_options_settings', []);
    $wc_id = sanitize_text_field($opts['id_whatConverts'] ?? '');

    if (! empty($wc_id)) {
        printf('<script src="//scripts.iconnode.com/%s.js"></script>' . "\n", esc_attr($wc_id));
    }
}, 99);
