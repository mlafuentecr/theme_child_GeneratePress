<?php
/**
 * Single Product template – hero layout with 3 sidebar images
 */
get_header();

while ( have_posts() ) :
    the_post();

    $post_id = get_the_ID();

    // Gallery images
    $image_ids = [];
    for ( $i = 1; $i <= 3; $i++ ) {
        $id = absint( get_post_meta( $post_id, '_product_gallery_image_' . $i, true ) );
        if ( $id ) $image_ids[] = $id;
    }

    // Explore Use Cases URL
    $uc_term = get_post_meta( $post_id, '_product_use_cases_term', true );
    $uc_url  = $uc_term
        ? add_query_arg( 'uc_product', $uc_term, home_url( '/cases/' ) )
        : home_url( '/cases/' );

    ?>

    <main id="primary" class="site-main">
        <div class="product-hero">

            <!-- Left: text + buttons -->
            <div class="product-hero__content">

                <h1 class="product-hero__title"><?php the_title(); ?></h1>

                <?php
                $excerpt = get_the_excerpt();
                if ( $excerpt ) : ?>
                    <p class="product-hero__excerpt"><?php echo wp_kses_post( $excerpt ); ?></p>
                <?php endif; ?>

                <div class="product-hero__body">
                    <?php the_content(); ?>
                </div>

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
                    if ( $src ) : ?>
                        <img src="<?php echo esc_url( $src ); ?>"
                             alt="<?php echo esc_attr( $alt ); ?>"
                             loading="lazy" />
                    <?php endif;
                endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </main>

<?php endwhile;

get_footer();
