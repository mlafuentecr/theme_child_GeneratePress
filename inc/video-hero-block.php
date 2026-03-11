<?php
/**
 * Video Hero — Custom Gutenberg Block (dynamic, PHP-rendered).
 *
 * Layers:
 *   1. Fallback <img>      — always visible, shown on mobile & while video loads.
 *   2. Vimeo <iframe>      — cover-fill background video, hidden on mobile.
 *   3. Inner-blocks        — fully editable WP blocks on top of the video.
 *
 * Attributes are stored in the block comment by the editor JS.
 * PHP renders the front-end HTML so we can update markup without invalidation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── Editor JS ───────────────────────────────────────────────────────────── */

add_action( 'enqueue_block_editor_assets', function (): void {

    $js = GP_CHILD_DIR . '/assets/js/blocks/video-hero-block.js';

    wp_enqueue_script(
        'gp-video-hero-block-editor',
        GP_CHILD_URI . '/assets/js/blocks/video-hero-block.js',
        [ 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ],
        file_exists( $js ) ? (string) filemtime( $js ) : GP_CHILD_VERSION,
        true
    );

    wp_localize_script( 'gp-video-hero-block-editor', 'gpVideoHeroData', [
        'brandSlug' => GP_CHILD_BRAND_SLUG,
        'brandName' => GP_CHILD_BRAND,
    ] );
} );

/* ── Block type registration ─────────────────────────────────────────────── */

add_action( 'init', function (): void {

    register_block_type(
        GP_CHILD_BRAND_SLUG . '/video-hero',
        [
            'attributes' => [
                'vimeoId'     => [ 'type' => 'string',  'default' => ''   ],
                'vimeoParams' => [ 'type' => 'string',  'default' => 'autoplay=1&muted=1&loop=1&background=1' ],
                'videoFocalY' => [ 'type' => 'integer', 'default' => 50   ],
                'fallbackUrl' => [ 'type' => 'string',  'default' => ''   ],
                'fallbackId'  => [ 'type' => 'integer', 'default' => 0    ],
                'fallbackAlt' => [ 'type' => 'string',  'default' => ''   ],
                'focalPoint'       => [ 'type' => 'object',  'default' => [ 'x' => 0.5, 'y' => 0.5 ] ],
                'minHeight'        => [ 'type' => 'integer', 'default' => 560  ],
                'minHeightTablet'  => [ 'type' => 'integer', 'default' => 500  ],
                'minHeightMobile'  => [ 'type' => 'integer', 'default' => 420  ],
            ],
            'render_callback' => 'gp_render_video_hero_block',
        ]
    );
} );

/* ── Front-end render callback ───────────────────────────────────────────── */

/**
 * @param array  $attrs   Block attributes.
 * @param string $content Rendered inner-blocks HTML.
 * @return string
 */
function gp_render_video_hero_block( array $attrs, string $content ): string {

    $vimeo_id     = sanitize_text_field( $attrs['vimeoId']     ?? '' );
    $vimeo_params = sanitize_text_field( $attrs['vimeoParams'] ?? 'autoplay=1&muted=1&loop=1&background=1' );
    $fallback_url = esc_url( $attrs['fallbackUrl'] ?? '' );
    $fallback_alt = esc_attr( $attrs['fallbackAlt'] ?? '' );
    $min_height        = max( 100, intval( $attrs['minHeight']       ?? 560 ) );
    $min_height_tablet = max( 100, intval( $attrs['minHeightTablet'] ?? 500 ) );
    $min_height_mobile = max( 100, intval( $attrs['minHeightMobile'] ?? 420 ) );

    $fp           = $attrs['focalPoint'] ?? [ 'x' => 0.5, 'y' => 0.5 ];
    $fp_x         = round( floatval( $fp['x'] ?? 0.5 ) * 100, 2 );
    $fp_y         = round( floatval( $fp['y'] ?? 0.5 ) * 100, 2 );
    $object_pos   = esc_attr( $fp_x . '% ' . $fp_y . '%' );

    $video_focal_y = max( 0, min( 100, intval( $attrs['videoFocalY'] ?? 50 ) ) );

    $vimeo_src = $vimeo_id
        ? esc_url( 'https://player.vimeo.com/video/' . $vimeo_id . ( $vimeo_params ? '?' . $vimeo_params : '' ) )
        : '';

    ob_start();
    ?>
    <div class="video-hero" style="--hero-min-h:<?php echo $min_height; ?>px;--hero-min-h-md:<?php echo $min_height_tablet; ?>px;--hero-min-h-sm:<?php echo $min_height_mobile; ?>px">

        <div class="video-hero__bg">

            <?php if ( $fallback_url ) : ?>
            <img
                class="video-hero__fallback"
                src="<?php echo $fallback_url; ?>"
                alt="<?php echo $fallback_alt; ?>"
                loading="eager"
                decoding="async"
                style="object-position:<?php echo $object_pos; ?>"
            />
            <?php endif; ?>

            <?php if ( $vimeo_src ) : ?>
            <div class="video-hero__video-wrap" style="--vfy:<?php echo $video_focal_y; ?>%">
                <iframe
                    src="<?php echo $vimeo_src; ?>"
                    frameborder="0"
                    allow="autoplay; fullscreen"
                    allowfullscreen=""
                    title=""
                    aria-hidden="true"
                    tabindex="-1"
                ></iframe>
            </div>
            <?php endif; ?>

        </div>

        <div class="video-hero__content">
            <?php echo $content; ?>
        </div>

    </div>
    <?php
    return ob_get_clean();
}
