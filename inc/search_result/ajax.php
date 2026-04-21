<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_load_more_search_results', 'load_more_search_results');
add_action('wp_ajax_nopriv_load_more_search_results', 'load_more_search_results');

function load_more_search_results() {

    check_ajax_referer('search_nonce', 'nonce');

    $search   = sanitize_text_field($_POST['search'] ?? '');
    $page     = max(1, intval($_POST['page'] ?? 1));
    $per_page = max(1, intval($_POST['posts_per_page'] ?? 10));
    $type     = sanitize_key($_POST['post_type'] ?? 'all');

    // Use the shared post-types list defined in index.php (loaded before this file).
    $all_types  = array_keys(signifi_search_post_types());
    $post_types = ($type === 'all') ? $all_types : [$type];

    $query_args = [
        'post_type'        => $post_types,
        'posts_per_page'   => $per_page,
        'paged'            => $page,
        'post__not_in'     => array_filter([absint(gp_child_get_search_settings()['results_page_id'] ?? 0)]),
        'suppress_filters' => false,
    ];

    if ($search !== '') {
        $query_args['s'] = $search;
    } else {
        $query_args['post__in'] = [0];
    }

    $query = new WP_Query($query_args);

    ob_start();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            echo gp_child_render_search_result_card(get_post());
        }
    } else {
        echo '<p class="search-no-results">No results found.</p>';
    }

    wp_reset_postdata();

    wp_send_json_success([
        'html'     => ob_get_clean(),
        'has_more' => $page < $query->max_num_pages,
        'debug'    => [
            'type' => $type,
            'posts_found' => $query->found_posts
        ]
    ]);
}
