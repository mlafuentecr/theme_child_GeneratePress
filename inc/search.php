<?php
/**
 * Reusable AJAX search component.
 *
 * Shortcode: [gp_search placeholder="Search..." post_types="post,page" limit="5"]
 * Alias: [gp_search_bar placeholder="Search..." post_types="post,page" limit="5"]
 *
 * The component outputs an accessible search input that fires a live AJAX
 * search and displays results in a dropdown below the field.
 */

if (! defined('ABSPATH')) {
    exit;
}

// ── Enqueue assets ────────────────────────────────────────────────────────────

add_action('wp_enqueue_scripts', function (): void {
    $css = GP_CHILD_DIR . '/assets/css/gp-search.css';
    $js  = GP_CHILD_DIR . '/assets/js/gp-search.js';

    wp_enqueue_style(
        'gp-search',
        GP_CHILD_URI . '/assets/css/gp-search.css',
        [],
        file_exists($css) ? (string) filemtime($css) : GP_CHILD_VERSION
    );

    wp_enqueue_script(
        'gp-search',
        GP_CHILD_URI . '/assets/js/gp-search.js',
        [],
        file_exists($js) ? (string) filemtime($js) : GP_CHILD_VERSION,
        true
    );

    wp_localize_script('gp-search', 'gpSearch', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('gp_search_nonce'),
        'noResults' => __('No results found.', 'generatepress-child'),
        'searching' => __('Searching…', 'generatepress-child'),
    ]);
});

function gp_child_get_search_settings(): array
{
    $settings = (array) get_option('gp_child_search_settings', []);

    return [
        'mode'            => in_array($settings['mode'] ?? '', ['live_ajax', 'results_page'], true) ? $settings['mode'] : 'live_ajax',
        'results_page_id' => absint($settings['results_page_id'] ?? 0),
    ];
}

function gp_child_get_search_results_page_url(): string
{
    $settings = gp_child_get_search_settings();

    if (! empty($settings['results_page_id'])) {
        $url = get_permalink($settings['results_page_id']);
        if ($url) {
            return $url;
        }
    }

    return home_url('/');
}

// ── Shortcode ─────────────────────────────────────────────────────────────────

function gp_child_render_search_shortcode(array $atts): string
{
    $a = shortcode_atts([
        'placeholder' => __('Search…', 'generatepress-child'),
        'post_types'  => 'post,page',
        'limit'       => 5,
        'id'          => 'gp-search-' . wp_unique_id(),
        'mode'        => '',
        'variant'     => 'full',
    ], $atts, 'gp_search');

    $settings = gp_child_get_search_settings();
    $id       = sanitize_html_class($a['id']);
    $types    = implode(',', array_map('sanitize_key', explode(',', $a['post_types'])));
    $mode     = in_array($a['mode'], ['live_ajax', 'results_page'], true) ? $a['mode'] : $settings['mode'];
    $variant  = in_array($a['variant'], ['full', 'icon'], true) ? $a['variant'] : 'full';
    $label_id = $id . '-label';
    $action = $mode === 'results_page' ? gp_child_get_search_results_page_url() : home_url('/');

    ob_start();
    ?>
    <form class="gp-search-wrap<?php echo $variant === 'icon' ? ' gp-search-wrap--icon' : ''; ?>" id="<?php echo esc_attr($id); ?>"
         action="<?php echo esc_url($action); ?>"
         method="get"
         data-post-types="<?php echo esc_attr($types); ?>"
         data-limit="<?php echo esc_attr(intval($a['limit'])); ?>"
         data-mode="<?php echo esc_attr($mode); ?>"
         data-variant="<?php echo esc_attr($variant); ?>"
         role="search">
        <label class="screen-reader-text" id="<?php echo esc_attr($label_id); ?>" for="<?php echo esc_attr($id . '-input'); ?>">
            <?php esc_html_e('Search', 'generatepress-child'); ?>
        </label>
        <?php if ($variant === 'icon') : ?>
            <button type="button"
                    class="gp-search-toggle"
                    aria-expanded="false"
                    aria-controls="<?php echo esc_attr($id . '-input'); ?>"
                    aria-labelledby="<?php echo esc_attr($label_id); ?>">
                <span class="gp-search-toggle__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="20" height="20" focusable="false">
                        <path d="M10.5 4a6.5 6.5 0 1 0 4.03 11.6l4.43 4.43 1.41-1.41-4.43-4.43A6.5 6.5 0 0 0 10.5 4Zm0 2a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Z" fill="currentColor"/>
                    </svg>
                </span>
                <span class="screen-reader-text"><?php esc_html_e('Open search', 'generatepress-child'); ?></span>
            </button>
        <?php endif; ?>
        <input type="search"
               name="<?php echo esc_attr($mode === 'results_page' ? 'q' : 's'); ?>"
               id="<?php echo esc_attr($id . '-input'); ?>"
               class="gp-search-input"
               placeholder="<?php echo esc_attr($a['placeholder']); ?>"
               autocomplete="off"
               aria-haspopup="listbox"
               aria-expanded="false"
               aria-autocomplete="list"<?php echo $variant === 'icon' ? ' hidden' : ''; ?>>
        <?php if ($variant !== 'icon') : ?>
            <button type="submit" class="gp-search-submit"><?php esc_html_e('Search', 'generatepress-child'); ?></button>
        <?php endif; ?>
        <?php if ($mode === 'results_page') : ?>
            <input type="hidden" name="post_types" value="<?php echo esc_attr($types); ?>">
        <?php else : ?>
            <div class="gp-search-results" role="listbox" aria-label="<?php esc_attr_e('Search results', 'generatepress-child'); ?>" hidden></div>
        <?php endif; ?>
    </form>
    <?php
    return ob_get_clean();
}

add_shortcode('gp_search', 'gp_child_render_search_shortcode');
add_shortcode('gp_search_bar', 'gp_child_render_search_shortcode');

// ── AJAX handler ─────────────────────────────────────────────────────────────

add_action('wp_ajax_gp_search',        'gp_child_handle_search');
add_action('wp_ajax_nopriv_gp_search', 'gp_child_handle_search');

function gp_child_handle_search(): void
{
    check_ajax_referer('gp_search_nonce', 'nonce');

    $query      = sanitize_text_field(wp_unslash($_POST['query'] ?? ''));
    $post_types = array_map('sanitize_key', explode(',', $_POST['post_types'] ?? 'post,page'));
    $limit      = min(20, max(1, intval($_POST['limit'] ?? 5)));

    if (strlen($query) < 2) {
        wp_send_json_success([]);
    }

    $results = get_posts([
        's'              => $query,
        'post_type'      => $post_types,
        'posts_per_page' => $limit,
        'post_status'    => 'publish',
        'no_found_rows'  => true,
    ]);

    $output = [];
    foreach ($results as $post) {
        $output[] = [
            'id'    => $post->ID,
            'title' => get_the_title($post),
            'url'   => get_permalink($post),
            'type'  => get_post_type_object($post->post_type)->labels->singular_name ?? $post->post_type,
        ];
    }

    wp_send_json_success($output);
}
