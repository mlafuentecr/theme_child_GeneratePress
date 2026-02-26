<?php
/* ============================================================
 * Pattern assets – load only on pages that use each pattern
 *
 * 'class'  → string searched in post content / post type
 *            to decide whether to enqueue.
 *            Use false to always load (global patterns).
 * 'css'    → enqueue CSS file
 * 'js'     → enqueue JS file
 * ============================================================ */
$patterns = [

    'logo-strip' => [
        'css'   => true,
        'js'    => true,
        'class' => 'logo-strip',
    ],

    'comparison' => [
        'css'   => true,
        'class' => 'comparison',
    ],
    'image-grid' => [
        'css'   => true,
        'class' => 'image-grid',
    ],

    'product-image' => [
        'css'   => true,
        'js'    => true,
        'class' => 'product-image',
    ],
    'video-and-text' => [
        'css'   => true,
        'js'    => true,
        'class' => 'video-and-text',
    ],
    'title-and-text' => [
        'css'   => true,
        'class' => 'title-and-text',
    ],
    'product-grid' => [
        'css'   => true,
        'class' => 'product-grid',
    ],


];


/* ============================================================
 * Helper – does the current page/post use this pattern?
 * Checks:
 *  1. Post content contains the class string
 *  2. Any GeneratePress Element active on this page contains the class
 * ============================================================ */
function signifi_page_uses_pattern( string $class ): bool {

    if ( ! is_singular() && ! is_home() && ! is_front_page() && ! is_archive() ) {
        return false;
    }

    $post = get_queried_object();
    if ( ! $post instanceof WP_Post ) {
        return false;
    }

    $content = $post->post_content ?? '';

    // 1. Direct class match in page content
    if ( str_contains( $content, $class ) ) {
        return true;
    }

    // 2. Resolve wp:block refs used in this page and check their content
    if ( preg_match_all( '/wp:block\s+\{"ref":(\d+)\}/', $content, $matches ) ) {
        foreach ( $matches[1] as $ref_id ) {
            $block_content = get_post_field( 'post_content', (int) $ref_id );
            if ( $block_content && str_contains( $block_content, $class ) ) {
                return true;
            }
        }
    }

    // 3. Check GP Elements (gp_elements) active on this page
    $elements = get_posts( [
        'post_type'      => 'gp_elements',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    ] );

    foreach ( $elements as $el_id ) {
        $el_content = get_post_field( 'post_content', $el_id );
        if ( ! str_contains( $el_content, $class ) ) {
            continue;
        }
        if ( class_exists( 'GeneratePress_Conditions' ) ) {
            $display = get_post_meta( $el_id, '_generate_element_display_conditions', true ) ?: [];
            $exclude = get_post_meta( $el_id, '_generate_element_exclude_conditions', true ) ?: [];
            $roles   = get_post_meta( $el_id, '_generate_element_user_conditions',    true ) ?: [];
            if ( ! empty( $display ) && GeneratePress_Conditions::show_data( $display, $exclude, $roles ) ) {
                return true;
            }
        } else {
            return true;
        }
    }

    return false;
}


/* ============================================================
 * Frontend enqueue – conditional per pattern
 * ============================================================ */
add_action( 'wp_enqueue_scripts', function () use ( $patterns ) {

    foreach ( $patterns as $name => $assets ) {

        $class = $assets['class'] ?? false;

        // Always load if class === false, otherwise check content
        $should_load = ( $class === false ) || signifi_page_uses_pattern( $class );

        if ( ! $should_load ) continue;

        if ( ! empty( $assets['css'] ) ) {
            wp_enqueue_style(
                "signifi-$name",
                GP_CHILD_URI . "/assets/css/patterns/$name.css",
                [],
                GP_CHILD_VERSION
            );
        }

        if ( ! empty( $assets['js'] ) ) {
            wp_enqueue_script(
                "signifi-$name",
                GP_CHILD_URI . "/assets/js/patterns/$name.js",
                [],
                GP_CHILD_VERSION,
                true
            );
        }
    }

} );


/* ============================================================
 * Editor styles – always load all (needed in block editor)
 * ============================================================ */
add_action( 'after_setup_theme', function () use ( $patterns ) {
    add_theme_support( 'editor-styles' );
    foreach ( $patterns as $name => $assets ) {
        add_editor_style( "assets/css/patterns/$name.css" );
    }
} );
