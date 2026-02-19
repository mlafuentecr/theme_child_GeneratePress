<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_load_more_search_results', 'load_more_search_results');
add_action('wp_ajax_nopriv_load_more_search_results', 'load_more_search_results');

function load_more_search_results() {


    $search   = sanitize_text_field($_POST['search'] ?? '');
    $page     = max(1, intval($_POST['page'] ?? 1));
    $per_page = max(1, intval($_POST['posts_per_page'] ?? 10));
    $type     = sanitize_key($_POST['post_type'] ?? 'all');

    $post_types = ($type === 'all')
        ? ['post', 'page', 'solution', 'use_case', 'industry']
        : [$type];

    $query = new WP_Query([
        'post_type'        => $post_types,
        'posts_per_page'   => $per_page,
        'paged'            => $page,
        's'                => $search,
        'suppress_filters' => false,
    ]);

    ob_start();

if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post(); ?>
        <a href="<?php the_permalink(); ?>">
            <article class="search-result-item">
                <h2><?php the_title(); ?></h2>
                <?php if (has_excerpt()) : ?>
                    <p><?php echo wp_trim_words(get_the_excerpt(), 40); ?></p>
                <?php endif; ?>
            </article>
        </a>
    <?php }
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
