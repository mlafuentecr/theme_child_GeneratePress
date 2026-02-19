<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
 * Product – Meta Boxes
 *   1. Gallery Images (3 slots)
 *   2. Explore Use Cases – linked use_case_product term
 * ============================================================ */

/* ----------------------------------------------------------
 * 1. Register meta boxes
 * ---------------------------------------------------------- */
add_action( 'add_meta_boxes', function () {

    add_meta_box(
        'product_gallery_images',
        'Product Gallery Images',
        'signifi_product_gallery_images_cb',
        'product',
        'normal',
        'high'
    );

    add_meta_box(
        'product_use_cases_link',
        'Explore Use Cases – Product Filter',
        'signifi_product_use_cases_link_cb',
        'product',
        'side',
        'default'
    );

} );


/* ----------------------------------------------------------
 * 2. Gallery Images – callback
 * ---------------------------------------------------------- */
function signifi_product_gallery_images_cb( $post ) {

    wp_nonce_field( 'signifi_product_meta_save', 'signifi_product_meta_nonce' );

    $images = [
        get_post_meta( $post->ID, '_product_gallery_image_1', true ),
        get_post_meta( $post->ID, '_product_gallery_image_2', true ),
        get_post_meta( $post->ID, '_product_gallery_image_3', true ),
    ];

    echo '<p style="color:#666;margin-bottom:12px;">Upload up to 3 images that will appear alongside the product description.</p>';
    echo '<div style="display:flex;gap:16px;flex-wrap:wrap;">';

    foreach ( $images as $index => $image_id ) :
        $num     = $index + 1;
        $meta    = '_product_gallery_image_' . $num;
        $preview = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
        ?>
        <div class="signifi-img-slot" style="flex:1;min-width:180px;max-width:220px;border:1px solid #ddd;border-radius:6px;padding:12px;text-align:center;background:#f9f9f9;">
            <p style="font-weight:600;margin-bottom:8px;">Image <?php echo $num; ?></p>

            <div class="signifi-img-preview" style="width:100%;height:140px;display:flex;align-items:center;justify-content:center;background:#eee;border-radius:4px;overflow:hidden;margin-bottom:8px;">
                <?php if ( $preview ) : ?>
                    <img src="<?php echo esc_url( $preview ); ?>" style="max-width:100%;max-height:140px;object-fit:cover;" />
                <?php else : ?>
                    <span style="color:#aaa;font-size:12px;">No image</span>
                <?php endif; ?>
            </div>

            <input type="hidden"
                   name="<?php echo esc_attr( $meta ); ?>"
                   value="<?php echo esc_attr( $image_id ); ?>"
                   class="signifi-img-id" />

            <button type="button"
                    class="button signifi-upload-btn"
                    style="width:100%;margin-bottom:4px;"
                    data-slot="<?php echo $num; ?>">
                <?php echo $preview ? 'Change Image' : 'Upload / Select'; ?>
            </button>

            <?php if ( $image_id ) : ?>
                <button type="button"
                        class="button signifi-remove-btn"
                        style="width:100%;color:#a00;">
                    Remove
                </button>
            <?php else : ?>
                <button type="button"
                        class="button signifi-remove-btn"
                        style="width:100%;color:#a00;display:none;">
                    Remove
                </button>
            <?php endif; ?>
        </div>
    <?php endforeach;

    echo '</div>';

    // Enqueue WP media + inline JS
    wp_enqueue_media();
    ?>
    <script>
    (function($){
        $('.signifi-upload-btn').on('click', function(){
            var $btn     = $(this);
            var $slot    = $btn.closest('.signifi-img-slot');
            var $input   = $slot.find('.signifi-img-id');
            var $preview = $slot.find('.signifi-img-preview');
            var $remove  = $slot.find('.signifi-remove-btn');

            var frame = wp.media({
                title:    'Select Product Gallery Image',
                button:   { text: 'Use this image' },
                multiple: false,
                library:  { type: 'image' }
            });

            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                var url = attachment.sizes && attachment.sizes.medium
                          ? attachment.sizes.medium.url
                          : attachment.url;

                $input.val(attachment.id);
                $preview.html('<img src="' + url + '" style="max-width:100%;max-height:140px;object-fit:cover;" />');
                $btn.text('Change Image');
                $remove.show();
            });

            frame.open();
        });

        $('.signifi-remove-btn').on('click', function(){
            var $slot    = $(this).closest('.signifi-img-slot');
            var $input   = $slot.find('.signifi-img-id');
            var $preview = $slot.find('.signifi-img-preview');
            var $btn     = $slot.find('.signifi-upload-btn');

            $input.val('');
            $preview.html('<span style="color:#aaa;font-size:12px;">No image</span>');
            $btn.text('Upload / Select');
            $(this).hide();
        });
    }(jQuery));
    </script>
    <?php
}


/* ----------------------------------------------------------
 * 3. Use Cases Link – callback
 * ---------------------------------------------------------- */
function signifi_product_use_cases_link_cb( $post ) {

    $saved_term = get_post_meta( $post->ID, '_product_use_cases_term', true );

    $terms = get_terms( [
        'taxonomy'   => 'use_case_product',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ] );

    echo '<p style="color:#666;font-size:12px;margin-bottom:10px;">';
    echo 'Select the <strong>Products</strong> tag (use_case_product) that matches this product. ';
    echo 'The "Explore Use Cases" button will link to the Use Cases page pre-filtered by this tag.';
    echo '</p>';

    echo '<label for="product_use_cases_term" style="display:block;font-weight:600;margin-bottom:4px;">Linked Product Tag</label>';
    echo '<select name="_product_use_cases_term" id="product_use_cases_term" style="width:100%;">';
    echo '<option value="">— None —</option>';

    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
        foreach ( $terms as $term ) {
            $selected = selected( $saved_term, $term->slug, false );
            echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>' . esc_html( $term->name ) . '</option>';
        }
    }

    echo '</select>';

    if ( $saved_term ) {
        $use_cases_page = get_option( 'signifi_use_cases_page_url', '' );
        if ( ! $use_cases_page ) {
            // Fallback: try to find a page with [use_cases] shortcode
            $page = get_posts( [
                'post_type'      => 'page',
                'posts_per_page' => 1,
                's'              => 'use_cases',
                'fields'         => 'ids',
            ] );
            $use_cases_page = ! empty( $page ) ? get_permalink( $page[0] ) : home_url( '/cases/' );
        }

        $url = add_query_arg( 'uc_product', $saved_term, $use_cases_page );
        echo '<p style="margin-top:10px;font-size:12px;word-break:break-all;">';
        echo '<strong>Preview URL:</strong><br>';
        echo '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $url ) . '</a>';
        echo '</p>';
    }
}


/* ----------------------------------------------------------
 * 4. Save meta
 * ---------------------------------------------------------- */
add_action( 'save_post_product', function ( $post_id ) {

    // Security checks
    if ( ! isset( $_POST['signifi_product_meta_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['signifi_product_meta_nonce'], 'signifi_product_meta_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Gallery images
    for ( $i = 1; $i <= 3; $i++ ) {
        $key = '_product_gallery_image_' . $i;
        if ( isset( $_POST[ $key ] ) ) {
            $value = absint( $_POST[ $key ] );
            if ( $value ) {
                update_post_meta( $post_id, $key, $value );
            } else {
                delete_post_meta( $post_id, $key );
            }
        }
    }

    // Use cases term
    if ( isset( $_POST['_product_use_cases_term'] ) ) {
        $term = sanitize_text_field( $_POST['_product_use_cases_term'] );
        if ( $term ) {
            update_post_meta( $post_id, '_product_use_cases_term', $term );
        } else {
            delete_post_meta( $post_id, '_product_use_cases_term' );
        }
    }

} );


/* ----------------------------------------------------------
 * 5. Helper functions – usable from blocks / templates
 * ---------------------------------------------------------- */

/**
 * Get gallery image IDs for a product.
 *
 * @param  int   $post_id  Product post ID (defaults to current post).
 * @return int[] Array of attachment IDs (empty slots are excluded).
 */
function signifi_get_product_gallery_ids( $post_id = null ) {
    $post_id = $post_id ?: get_the_ID();
    $ids = [];
    for ( $i = 1; $i <= 3; $i++ ) {
        $id = absint( get_post_meta( $post_id, '_product_gallery_image_' . $i, true ) );
        if ( $id ) $ids[] = $id;
    }
    return $ids;
}

/**
 * Get the "Explore Use Cases" URL for a product.
 *
 * @param  int    $post_id  Product post ID (defaults to current post).
 * @param  string $base_url Base URL of the Use Cases page.
 * @return string           URL with uc_product query arg, or empty string.
 */
function signifi_get_product_use_cases_url( $post_id = null, $base_url = '' ) {
    $post_id = $post_id ?: get_the_ID();
    $term    = get_post_meta( $post_id, '_product_use_cases_term', true );
    if ( ! $term ) return '';

    if ( ! $base_url ) {
        $base_url = home_url( '/cases/' );
    }

    return add_query_arg( 'uc_product', $term, $base_url );
}
