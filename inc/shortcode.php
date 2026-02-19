<?php
/* ============================================================
 * Shortcodes
 * ============================================================ */

/**
 * Current year © [current_year]
 */
add_shortcode('current_year', fn () => date('Y'));

/**
 * Main menu [main_menu]
 */
add_shortcode('main_menu', function () {
    return wp_nav_menu([
        'theme_location' => 'main_menu',
        'container'      => false,
        'echo'           => false,
    ]);
});

/**
 * Footer menu [footer_menu]
 */
add_shortcode('footer_menu', function () {
    return wp_nav_menu([
        'theme_location' => 'footer_menu',
        'container'      => false,
        'echo'           => false,
    ]);
});


/* ============================================================
 * Product Hero – text left / 3 stacked images right
 * Shortcode: [product_hero]
 * ============================================================ */
add_shortcode( 'product_hero', function () {

    $post_id = get_the_ID();

    // Title
    $title = get_the_title( $post_id );

    // Excerpt
    $excerpt = get_the_excerpt( $post_id );

    // Body content (editor)
    $content = apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) );

    // Gallery images (IDs from meta box)
    $image_ids = [];
    for ( $i = 1; $i <= 3; $i++ ) {
        $id = absint( get_post_meta( $post_id, '_product_gallery_image_' . $i, true ) );
        if ( $id ) $image_ids[] = $id;
    }

    // Explore Use Cases URL
    $uc_term     = get_post_meta( $post_id, '_product_use_cases_term', true );
    $uc_base_url = home_url( '/cases/' );
    $uc_url      = $uc_term ? add_query_arg( 'uc_product', $uc_term, $uc_base_url ) : $uc_base_url;

    ob_start(); ?>

    <div class="product-hero">

        <!-- Left: text + buttons -->
        <div class="product-hero__content">

            <h1 class="product-hero__title"><?php echo esc_html( $title ); ?></h1>

            <?php if ( $excerpt ) : ?>
                <p class="product-hero__excerpt"><?php echo wp_kses_post( $excerpt ); ?></p>
            <?php endif; ?>

            <?php if ( $content ) : ?>
                <div class="product-hero__body"><?php echo $content; ?></div>
            <?php endif; ?>

            <div class="product-hero__buttons">
                <a href="<?php echo esc_url( $uc_url ); ?>"
                   class="wp-block-button__link signifi-primary">
                    Explore use cases
                </a>
                <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"
                   class="wp-block-button__link signifi-outline">
                    Talk to our team
                </a>
            </div>

        </div>

        <!-- Right: 3 stacked images -->
        <?php if ( ! empty( $image_ids ) ) : ?>
        <div class="product-hero__images">
            <?php foreach ( $image_ids as $img_id ) :
                $src = wp_get_attachment_image_url( $img_id, 'medium_large' );
                $alt = get_post_meta( $img_id, '_wp_attachment_image_alt', true );
                if ( $src ) :
            ?>
                <img src="<?php echo esc_url( $src ); ?>"
                     alt="<?php echo esc_attr( $alt ); ?>"
                     loading="lazy" />
            <?php endif; endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <?php
    return ob_get_clean();
} );


/* ============================================================
 * Use Cases – Filters + Grid
 * Shortcode: [use_cases]
 * ============================================================ */

add_shortcode('use_cases', function () {

    // Read URL parameters for shareable filter links (uc_ prefix avoids WP query var conflicts)
    $current_industry = sanitize_text_field( $_GET['uc_industry'] ?? '' );
    $current_solution = sanitize_text_field( $_GET['uc_solution'] ?? '' );
    $current_product  = sanitize_text_field( $_GET['uc_product']  ?? '' );
    $current_search   = sanitize_text_field( $_GET['uc_search']   ?? '' );

    ob_start(); ?>

    <section class="use-cases-section">

        <form class="use-cases-filters" autocomplete="off">

            <button type="button"
                    id="clear-use-cases-filters"
                    class="use-cases-clear<?php echo ( $current_industry || $current_solution || $current_product || $current_search ) ? '' : ' d-none'; ?>">
                Clear All ✕
            </button>

            <!-- INDUSTRY -->
            <select id="filter-industry">
                <option value="">Industry</option>
                <?php
                $industries = get_terms([
                    'taxonomy'   => 'use_case_industry',
                    'hide_empty' => false,
                ]);

                if (!is_wp_error($industries)) {
                    foreach ($industries as $term) {
                        echo '<option value="' . esc_attr($term->slug) . '"' .
                            selected( $current_industry, $term->slug, false ) . '>' .
                            esc_html($term->name) .
                        '</option>';
                    }
                }
                ?>
            </select>

            <!-- SOLUTION -->
            <select id="filter-solution">
                <option value="">Solution</option>
                <?php
                $solutions = get_terms([
                    'taxonomy'   => 'use_case_solution',
                    'hide_empty' => false,
                ]);

                if (!is_wp_error($solutions)) {
                    foreach ($solutions as $term) {
                        echo '<option value="' . esc_attr($term->slug) . '"' .
                            selected( $current_solution, $term->slug, false ) . '>' .
                            esc_html($term->name) .
                        '</option>';
                    }
                }
                ?>
            </select>

            <!-- PRODUCT -->
            <select id="filter-product">
                <option value="">Product</option>
                <?php
                $products = get_terms([
                    'taxonomy'   => 'use_case_product',
                    'hide_empty' => false,
                ]);

                if (!is_wp_error($products)) {
                    foreach ($products as $term) {
                        echo '<option value="' . esc_attr($term->slug) . '"' .
                            selected( $current_product, $term->slug, false ) . '>' .
                            esc_html($term->name) .
                        '</option>';
                    }
                }
                ?>
            </select>

            <!-- SEARCH -->
            <input type="text"
                   id="filter-search"
                   placeholder="Search"
                   value="<?php echo esc_attr( $current_search ); ?>" />

        </form>

        <div id="use-cases-results" class="use-cases-wrap use-cases" ></div>

        <div class="use-cases-cta">
            <a href="#"
               id="load-more-use-cases"
               class="wp-block-button__link d-none">
                Load More
            </a>
        </div>

    </section>

    <?php
    return ob_get_clean();
});

/* ============================================================
 * GLOBAL USE CASE MODAL (Injected in Footer)
 * ============================================================ */
add_action('wp_footer', 'signifi_render_use_case_modal');

function signifi_render_use_case_modal() {
    ?>

    <div id="use-case-modal" class="use-case-modal hidden">
        <div class="use-case-modal-overlay"></div>

        <div class="use-case-modal-wrapper">
            <button type="button" class="use-case-modal-close" aria-label="Close modal">
                &times;
            </button>

            <div class="use-case-modal-content">
                <div id="use-case-modal-body"></div>
            </div>
        </div>
    </div>

    <?php
}


