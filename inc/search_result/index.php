<?php
if (!defined('ABSPATH')) exit;

/**
 * Search post types config
 */
function signifi_search_post_types() {
    return [
        'post'      => 'Posts',
        'page'      => 'Pages',
        'solution'  => 'Solutions',
        'use_case'  => 'Use Cases',
        'industry'  => 'Industries',
    ];
}

/* -------------------------------------------------------------------------
 * SHORTCODE
 * ------------------------------------------------------------------------- */
add_shortcode('post_search_result', 'custom_post_type_search_shortcode');

/* -------------------------------------------------------------------------
 * AJAX
 * ------------------------------------------------------------------------- */
add_action('wp_ajax_load_more_search_results', 'load_more_search_results');
add_action('wp_ajax_nopriv_load_more_search_results', 'load_more_search_results');

function custom_post_type_search_shortcode($atts)
{
    $search_post_types  = signifi_search_post_types();
    $default_post_types = array_keys($search_post_types);

    $atts = shortcode_atts([
        'post_type'      => $default_post_types,
        'posts_per_page' => 10,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ], $atts);

    if (!is_array($atts['post_type'])) {
        $atts['post_type'] = [$atts['post_type']];
    }

    // Accept both ?q= and ?s=
    $search_query = sanitize_text_field(
        $_GET['q'] ?? $_GET['s'] ?? ''
    );

    $search_year = sanitize_text_field($_GET['search_year'] ?? '');
    $paged       = 1;

    $args = [
        'post_type'        => $atts['post_type'],
        'posts_per_page'   => $atts['posts_per_page'],
        'orderby'          => $atts['orderby'],
        'order'            => $atts['order'],
        'paged'            => $paged,
        'suppress_filters' => false,
    ];

    if ($search_query) {
        $args['s'] = $search_query;
    }

    if ($search_year) {
        $args['date_query'] = [[ 'year' => $search_year ]];
    }

    $query = new WP_Query($args);

    ob_start();

    if ($query->have_posts()) : ?>

        <div class="search-results"
            data-posts-per-page="<?php echo esc_attr($atts['posts_per_page']); ?>"
            data-search="<?php echo esc_attr($search_query); ?>"
            data-current-page="1"
            data-max-pages="<?php echo esc_attr($query->max_num_pages); ?>">

            <form class="search-results__form" action="<?php echo esc_url(home_url('/search/')); ?>" method="get" role="search">
                <div class="search-results__input-wrap">
                    <input type="search"
                           name="q"
                           class="search-results__input"
                           value="<?php echo esc_attr($search_query); ?>"
                           placeholder="<?php esc_attr_e('Search…', 'generatepress-child'); ?>"
                           autocomplete="off"
                           aria-label="<?php esc_attr_e('Search', 'generatepress-child'); ?>">
                    <button type="submit" class="search-results__submit" aria-label="<?php esc_attr_e('Search', 'generatepress-child'); ?>">
                        <svg aria-hidden="true" focusable="false" width="20" height="20"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </button>
                </div>
            </form>

            <?php include __DIR__ . '/tabs.php'; ?>

            <div class="search-results-container">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <a href="<?php the_permalink(); ?>">
                        <article class="search-result-item">
                            <h2><?php the_title(); ?></h2>
                            <?php if (has_excerpt()) : ?>
                                <p><?php echo wp_trim_words(get_the_excerpt(), 50); ?></p>
                            <?php endif; ?>
                        </article>
                    </a>
                <?php endwhile; ?>
            </div>

            <?php if ($query->max_num_pages > 1) : ?>
                <button class="load-more-button">View more</button>
            <?php endif; ?>

        </div>

    <?php else : ?>
        <p>No posts found.</p>
    <?php endif;

    wp_reset_postdata();
    return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * ENQUEUE
 * ------------------------------------------------------------------------- */
add_action('wp_enqueue_scripts', function () {

    if (!is_page('search')) return;

    $base = get_stylesheet_directory_uri() . '/inc/search_result/assets';

    wp_enqueue_style(
        'search-results',
        $base . '/search-results.css',
        [],
        '1.0'
    );

    wp_enqueue_script(
        'search-results',
        $base . '/search-results.js',
        [],
        '1.0',
        true
    );

    wp_localize_script('search-results', 'search_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('search_nonce'),
    ]);
});

require_once __DIR__ . '/ajax.php';
