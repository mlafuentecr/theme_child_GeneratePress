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

    'video-hero' => [
        'css'   => true,
        'class' => 'video-hero',
    ],

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
 * Helper – does the current request use this pattern?
 * Checks:
 *  1. Queried singular content / posts page content
 *  2. Reusable blocks referenced from that content
 *  3. Active GeneratePress Elements on this request
 * ============================================================ */
function gp_child_content_uses_pattern_class( string $content, string $class, array &$visited_refs = [] ): bool {

    if ( $content === '' ) {
        return false;
    }

    $quoted_class = preg_quote( $class, '/' );

    if ( preg_match( '/\b' . $quoted_class . '\b/i', $content ) ) {
        return true;
    }

    if ( preg_match_all( '/wp:block\s+\{"ref":(\d+)\}/', $content, $matches ) ) {
        foreach ( $matches[1] as $ref_id ) {
            $ref_id = (int) $ref_id;

            if ( isset( $visited_refs[ $ref_id ] ) ) {
                continue;
            }

            $visited_refs[ $ref_id ] = true;
            $block_content = (string) get_post_field( 'post_content', $ref_id );

            if ( gp_child_content_uses_pattern_class( $block_content, $class, $visited_refs ) ) {
                return true;
            }
        }
    }

    return false;
}

function gp_child_get_pattern_scan_contents(): array {
    static $contents = null;

    if ( is_array( $contents ) ) {
        return $contents;
    }

    $contents = [];

    if ( is_singular() ) {
        $post = get_queried_object();

        if ( $post instanceof WP_Post ) {
            $contents[] = (string) $post->post_content;
        }
    } elseif ( is_home() ) {
        $posts_page_id = (int) get_option( 'page_for_posts' );

        if ( $posts_page_id > 0 ) {
            $contents[] = (string) get_post_field( 'post_content', $posts_page_id );
        }
    }

    $elements = get_posts( [
        'post_type'      => 'gp_elements',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    ] );

    foreach ( $elements as $el_id ) {
        if ( class_exists( 'GeneratePress_Conditions' ) ) {
            $display = get_post_meta( $el_id, '_generate_element_display_conditions', true ) ?: [];
            $exclude = get_post_meta( $el_id, '_generate_element_exclude_conditions', true ) ?: [];
            $roles   = get_post_meta( $el_id, '_generate_element_user_conditions', true ) ?: [];

            if ( ! empty( $display ) && ! GeneratePress_Conditions::show_data( $display, $exclude, $roles ) ) {
                continue;
            }
        }

        $contents[] = (string) get_post_field( 'post_content', $el_id );
    }

    return array_values( array_filter( $contents, static fn( $content ) => $content !== '' ) );
}

function signifi_page_uses_pattern( string $class ): bool {

    if ( ! is_singular() && ! is_home() && ! is_front_page() && ! is_archive() ) {
        return false;
    }

    static $class_cache = [];

    if ( array_key_exists( $class, $class_cache ) ) {
        return $class_cache[ $class ];
    }

    foreach ( gp_child_get_pattern_scan_contents() as $content ) {
        $visited_refs = [];

        if ( gp_child_content_uses_pattern_class( $content, $class, $visited_refs ) ) {
            $class_cache[ $class ] = true;
            return true;
        }
    }

    $class_cache[ $class ] = false;
    return false;
}


/* ============================================================
 * Frontend enqueue – conditional per pattern
 * ============================================================ */
add_action( 'wp_enqueue_scripts', function () use ( $patterns ) {

    foreach ( $patterns as $name => $assets ) {

        $class = $assets['class'] ?? false;

        $should_load = $class ? signifi_page_uses_pattern( $class ) : false;

        if ( ! $should_load ) continue;

        if ( ! empty( $assets['css'] ) ) {
            $css_path = GP_CHILD_DIR . "/assets/css/patterns/$name.css";
            wp_enqueue_style(
                "signifi-$name",
                GP_CHILD_URI . "/assets/css/patterns/$name.css",
                [],
                gp_child_asset_version( $css_path )
            );
        }

        if ( ! empty( $assets['js'] ) ) {
            $js_path = GP_CHILD_DIR . "/assets/js/patterns/$name.js";
            wp_enqueue_script(
                "signifi-$name",
                GP_CHILD_URI . "/assets/js/patterns/$name.js",
                [],
                gp_child_asset_version( $js_path ),
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
