<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
 * AJAX – Use Cases Filter
 * ============================================================ */

add_action('wp_ajax_filter_use_cases', 'cl_filter_use_cases');
add_action('wp_ajax_nopriv_filter_use_cases', 'cl_filter_use_cases');


function cl_filter_use_cases() {

    $industry = sanitize_text_field($_POST['industry'] ?? '');
    $solution = sanitize_text_field($_POST['solution'] ?? '');
    $product  = sanitize_text_field($_POST['product'] ?? '');
    $search   = sanitize_text_field($_POST['search'] ?? '');
    $page     = max(1, intval($_POST['page'] ?? 1));

    /* ========================================================
     * Tax Query
     * ======================================================== */
    $tax_query = [];

    if ($industry) {
        $tax_query[] = [
            'taxonomy' => 'use_case_industry',
            'field'    => 'slug',
            'terms'    => $industry,
        ];
    }

    if ($solution) {
        $tax_query[] = [
            'taxonomy' => 'use_case_solution',
            'field'    => 'slug',
            'terms'    => $solution,
        ];
    }

    if ($product) {
        $tax_query[] = [
            'taxonomy' => 'use_case_product',
            'field'    => 'slug',
            'terms'    => $product,
        ];
    }

    if (!empty($tax_query)) {
        $tax_query['relation'] = 'AND';
    }

    /* ========================================================
     * Query Args
     * ======================================================== */
   
        $args = [
            'post_type'      => 'use_case',
            'posts_per_page' => 6,
            'paged'          => $page,
            'post_status'    => 'publish',
        ];
   
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }

    /* ========================================================
     * Custom Search (Title + Content + Excerpt)
     * ======================================================== */
    if (!empty($search)) {

        add_filter('posts_where', function ($where) use ($search) {
            global $wpdb;

            $search = esc_sql($search);

            $where .= " AND (
                {$wpdb->posts}.post_title LIKE '%{$search}%'
                OR {$wpdb->posts}.post_content LIKE '%{$search}%'
                OR {$wpdb->posts}.post_excerpt LIKE '%{$search}%'
            )";

            return $where;
        });
    }

    $query = new WP_Query($args);

    /* ========================================================
     * Output
     * ======================================================== */
    if ($query->have_posts()) {

        while ($query->have_posts()) {
            $query->the_post();

            $image_url = get_the_post_thumbnail_url(get_the_ID(), 'large')
                ?: get_stylesheet_directory_uri() . '/assets/images/placeholder-use-case.jpg';

            $terms = array_merge(
                wp_get_post_terms(get_the_ID(), 'use_case_industry'),
                wp_get_post_terms(get_the_ID(), 'use_case_solution'),
                wp_get_post_terms(get_the_ID(), 'use_case_product')
            );
            ?>

            <article class="card use-case-card" data-post-id="<?php the_ID(); ?>">

                <a href="<?php the_permalink(); ?>" class="use-case-image js-open-use-case post-<?php the_ID(); ?>">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php the_title_attribute(); ?>">
                </a>

                <div class="content">
                <?php if (!empty($terms) && !is_wp_error($terms)) : ?>
                    <div class="use-case-tags">
                        <p class="gb-text gb-text-00c2c6c1">
                        <?php foreach ($terms as $term) : ?>
                            <a class="tag">
                                <?php echo esc_html($term->name); ?>
                            </a>
                        <?php endforeach; ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="card-body">
                    <h3 class="use-case-title"><?php the_title(); ?></h3>
                    <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?></p>
                    <a href="<?php the_permalink(); ?>" class="cta js-open-use-case">
                        Learn More
                    </a>
                </div>
                </div>

            </article>

            <?php
        }

        if ($query->max_num_pages > $page) {
            echo '<span class="has-more" data-next-page="' . ($page + 1) . '"></span>';
        }

    } elseif ($per_page <= 0) {
        echo '<div class="use-cases-empty">No results found.</div>';
    }

    wp_reset_postdata();

    // Remove custom search filter
    remove_all_filters('posts_where');

    wp_die();
}


/* ============================================================
 * AJAX – MODAL CONTENT
 * ============================================================ */

add_action('wp_ajax_load_use_case_modal', 'load_use_case_modal');
add_action('wp_ajax_nopriv_load_use_case_modal', 'load_use_case_modal');

function load_use_case_modal() {

    $post_id = intval($_POST['post_id'] ?? 0);

    if (!$post_id) {
        wp_die('No post ID');
    }

    $query = new WP_Query([
        'post_type'   => 'use_case',
        'p'           => $post_id,
        'post_status' => 'publish',
    ]);

    if (!$query->have_posts()) {
        wp_die('Post not found');
    }

    while ($query->have_posts()) {
        $query->the_post();

        $content = apply_filters('the_content', get_the_content());
        ?>

        <article class="use-case-modal-article">

            <h2><?php the_title(); ?></h2>

            <?php if (has_post_thumbnail()) : ?>
                <div class="modal-thumb">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>

            <div class="modal-content">
                <?php echo $content; ?>
            </div>

        </article>

        <?php
    }

    wp_reset_postdata();
    wp_die();
}


/* ============================================================
 * AJAX – PRODUCT MODAL CONTENT
 * ============================================================ */

add_action( 'wp_ajax_load_product_modal',        'load_product_modal' );
add_action( 'wp_ajax_nopriv_load_product_modal', 'load_product_modal' );

function load_product_modal() {

    $post_id = intval( $_POST['post_id'] ?? 0 );
    if ( ! $post_id ) wp_die( 'No post ID' );

    $query = new WP_Query( [
        'post_type'   => 'product',
        'p'           => $post_id,
        'post_status' => 'publish',
    ] );

    if ( ! $query->have_posts() ) wp_die( 'Post not found' );

    while ( $query->have_posts() ) :
        $query->the_post();

        // Gallery images from meta box
        $image_ids = [];
        for ( $i = 1; $i <= 3; $i++ ) {
            $id = absint( get_post_meta( get_the_ID(), '_product_gallery_image_' . $i, true ) );
            if ( $id ) $image_ids[] = $id;
        }

        // Explore Use Cases URL
        $uc_term = get_post_meta( get_the_ID(), '_product_use_cases_term', true );
        $uc_url  = $uc_term
            ? add_query_arg( 'uc_product', $uc_term, home_url( '/cases/' ) )
            : home_url( '/cases/' );

        ?>
        <article class="use-case-modal-article product-modal-article">

            <!-- Left: text + buttons -->
            <div class="product-modal-content">

                <h2><?php the_title(); ?></h2>

                <?php $excerpt = get_the_excerpt();
                if ( $excerpt ) : ?>
                    <p class="product-modal-excerpt"><?php echo wp_kses_post( $excerpt ); ?></p>
                <?php endif; ?>

                <div class="product-modal-body">
                    <?php echo apply_filters( 'the_content', get_the_content() ); ?>
                </div>

                <div class="product-modal-buttons">
                    <a href="<?php echo esc_url( $uc_url ); ?>"
                       class="wp-block-button__link is-style-signifi-primary">
                        Explore use cases
                    </a>
                    <a href="<?php echo esc_url( get_permalink() ); ?>"
                       class="wp-block-button__link is-style-signifi-secondary">
                        View product
                    </a>
                </div>

            </div>

            <!-- Right: 3 stacked images -->
            <?php if ( ! empty( $image_ids ) ) : ?>
                <div class="product-modal-gallery">
                    <?php foreach ( $image_ids as $img_id ) :
                        echo wp_get_attachment_image( $img_id, 'medium_large', false, [
                            'loading' => 'lazy',
                            'class'   => 'product-modal-img',
                        ] );
                    endforeach; ?>
                </div>
            <?php elseif ( has_post_thumbnail() ) : ?>
                <div class="product-modal-gallery">
                    <?php the_post_thumbnail( 'medium_large', [ 'class' => 'product-modal-img' ] ); ?>
                </div>
            <?php endif; ?>

        </article>
        <?php
    endwhile;

    wp_reset_postdata();
    wp_die();
}
