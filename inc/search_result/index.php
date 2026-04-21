<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('gp_child_get_search_results_page_url')) {
    function gp_child_get_search_results_page_url() {
        return home_url('/search/');
    }
}

/**
 * Search post types config
 */
function signifi_search_post_types() {
    return [
        'post'      => 'Posts',
        'page'      => 'Pages',
    ];
}

function gp_child_search_type_label(string $post_type): string
{
    $labels = signifi_search_post_types();

    if (isset($labels[$post_type])) {
        return $labels[$post_type];
    }

    $object = get_post_type_object($post_type);
    return $object->labels->singular_name ?? ucfirst(str_replace('_', ' ', $post_type));
}

function gp_child_render_search_result_card(WP_Post $post): string
{
    $title = get_the_title($post);
    $url   = get_permalink($post);
    $type  = gp_child_search_type_label($post->post_type);
    $excerpt = has_excerpt($post) ? wp_trim_words(get_the_excerpt($post), 40) : '';

    ob_start();
    ?>
    <a href="<?php echo esc_url($url); ?>" class="search-result-link">
        <article class="search-result-item" data-type="<?php echo esc_attr($type); ?>">
            <h2><?php echo esc_html($title); ?></h2>
            <?php if ($excerpt !== '') : ?>
                <p><?php echo esc_html($excerpt); ?></p>
            <?php endif; ?>
        </article>
    </a>
    <?php

    return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * SHORTCODE
 * ------------------------------------------------------------------------- */
add_shortcode('post_search_result', 'custom_post_type_search_shortcode');

/* -------------------------------------------------------------------------
 * AJAX – registered in ajax.php (required below). Do NOT duplicate here.
 * ------------------------------------------------------------------------- */

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
    $search_query = sanitize_text_field($_GET['q'] ?? $_GET['s'] ?? '');
    $requested_types = sanitize_text_field($_GET['post_types'] ?? '');
    if ($requested_types !== '') {
        $requested_types = array_filter(array_map('sanitize_key', explode(',', $requested_types)));
        if (! empty($requested_types)) {
            $atts['post_type'] = $requested_types;
        }
    }

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

    if ($search_query !== '') {
        $args['post__not_in'] = [get_the_ID()];
    } else {
        $args['post__in'] = [0];
    }

    $query = new WP_Query($args);

    ob_start();

    ?>

    <div class="search-results"
        data-posts-per-page="<?php echo esc_attr($atts['posts_per_page']); ?>"
        data-search="<?php echo esc_attr($search_query); ?>"
        data-current-page="1"
        data-max-pages="<?php echo esc_attr($query->max_num_pages); ?>">

        <?php if ($search_query !== '') : ?>
            <p class="search-results__summary">
                <?php
                printf(
                    /* translators: %s is the search term. */
                    esc_html__('Results found for "%s"', 'generatepress-child'),
                    esc_html($search_query)
                );
                ?>
            </p>
        <?php endif; ?>

        <form class="search-results__form" action="<?php echo esc_url(gp_child_get_search_results_page_url()); ?>" method="get" role="search">
            <div class="search-results__input-wrap">
                <span class="search-results__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <input type="search"
                       name="q"
                       class="search-results__input"
                       value="<?php echo esc_attr($search_query); ?>"
                       placeholder="<?php esc_attr_e('Search…', 'generatepress-child'); ?>"
                       autocomplete="off"
                       aria-label="<?php esc_attr_e('Search', 'generatepress-child'); ?>">
                <button type="submit" class="search-results__submit" aria-label="<?php esc_attr_e('Search', 'generatepress-child'); ?>">
                    <svg aria-hidden="true" focusable="false" width="18" height="18"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
                <input type="hidden" name="post_types" value="<?php echo esc_attr(implode(',', $atts['post_type'])); ?>">
            </div>
        </form>

        <?php include __DIR__ . '/tabs.php'; ?>

        <div class="search-results-container">
            <?php if ($search_query !== '' && $query->have_posts()) : ?>
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <?php echo gp_child_render_search_result_card(get_post()); ?>
                <?php endwhile; ?>
            <?php elseif ($search_query !== '') : ?>
                <p class="search-no-results"><?php esc_html_e('No results found.', 'generatepress-child'); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($search_query !== '' && $query->max_num_pages > 1) : ?>
            <button class="load-more-button" type="button"><?php esc_html_e('View more', 'generatepress-child'); ?></button>
        <?php endif; ?>

    </div>
    <?php

    wp_reset_postdata();
    return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * ENQUEUE
 * ------------------------------------------------------------------------- */
add_action('wp_enqueue_scripts', function () {
    $target_id = absint((gp_child_get_search_settings()['results_page_id'] ?? 0));
    if ($target_id > 0) {
        if (!is_page($target_id)) {
            return;
        }
    } elseif (!is_page('search')) {
        return;
    }

    $base     = get_stylesheet_directory_uri() . '/inc/search_result/assets';
    $base_dir = get_stylesheet_directory() . '/inc/search_result/assets';

    wp_enqueue_style(
        'search-results',
        $base . '/search-results.css',
        [],
        gp_child_asset_version($base_dir . '/search-results.css')
    );

    wp_enqueue_script(
        'search-results',
        $base . '/search-results.js',
        [],
        gp_child_asset_version($base_dir . '/search-results.js'),
        true
    );

    wp_localize_script('search-results', 'search_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('search_nonce'),
    ]);
});

require_once __DIR__ . '/ajax.php';
