<?php
/**
 * Reusable AJAX search component.
 *
 * Shortcode: [gp_search placeholder="Search..." post_types="post,page" limit="5"]
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

// ── Shortcode ─────────────────────────────────────────────────────────────────

add_shortcode('gp_search', function (array $atts): string {
    $a = shortcode_atts([
        'placeholder' => __('Search…', 'generatepress-child'),
        'post_types'  => 'post,page',
        'limit'       => 5,
        'id'          => 'gp-search-' . wp_unique_id(),
    ], $atts, 'gp_search');

    $id    = sanitize_html_class($a['id']);
    $types = implode(',', array_map('sanitize_key', explode(',', $a['post_types'])));

    ob_start();
    ?>
    <div class="gp-search-wrap" id="<?php echo esc_attr($id); ?>"
         data-post-types="<?php echo esc_attr($types); ?>"
         data-limit="<?php echo esc_attr(intval($a['limit'])); ?>"
         role="search">
        <label class="screen-reader-text" for="<?php echo esc_attr($id . '-input'); ?>">
            <?php esc_html_e('Search', 'generatepress-child'); ?>
        </label>
        <input type="search"
               id="<?php echo esc_attr($id . '-input'); ?>"
               class="gp-search-input"
               placeholder="<?php echo esc_attr($a['placeholder']); ?>"
               autocomplete="off"
               aria-haspopup="listbox"
               aria-expanded="false"
               aria-autocomplete="list">
        <div class="gp-search-results" role="listbox" aria-label="<?php esc_attr_e('Search results', 'generatepress-child'); ?>" hidden></div>
    </div>
    <?php
    return ob_get_clean();
});

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
