<?php
/**
 * Reusable modal popup system.
 *
 * Shortcodes:
 *
 *   [gp_modal id="my-modal" class=""]
 *       Modal content goes here.
 *   [/gp_modal]
 *
 *   [gp_modal_trigger id="my-modal" tag="button" class=""]
 *       Click to open
 *   [/gp_modal_trigger]
 *
 * The modal is appended to <body> on render, supports keyboard navigation,
 * focus trapping, and Escape-to-close. Easing uses ease-out-expo.
 *
 * PHP helper — open a modal from a link:
 *   gp_modal_trigger('my-modal', 'Open modal', ['class' => 'btn'])
 */

if (! defined('ABSPATH')) {
    exit;
}

// ── Enqueue assets ────────────────────────────────────────────────────────────

add_action('wp_enqueue_scripts', function (): void {
    $css = GP_CHILD_DIR . '/assets/css/gp-modal.css';
    $js  = GP_CHILD_DIR . '/assets/js/gp-modal.js';

    wp_enqueue_style(
        'gp-modal',
        GP_CHILD_URI . '/assets/css/gp-modal.css',
        [],
        file_exists($css) ? (string) filemtime($css) : GP_CHILD_VERSION
    );

    wp_enqueue_script(
        'gp-modal',
        GP_CHILD_URI . '/assets/js/gp-modal.js',
        [],
        file_exists($js) ? (string) filemtime($js) : GP_CHILD_VERSION,
        true
    );
});

// ── [gp_modal] shortcode ──────────────────────────────────────────────────────

add_shortcode('gp_modal', function (array $atts, string $content = ''): string {
    $a = shortcode_atts([
        'id'            => 'gp-modal-' . wp_unique_id(),
        'class'         => '',
        'size'          => 'md',   // sm | md | lg | xl | full
        'close_label'   => __('Close', 'generatepress-child'),
        'close_outside' => 'true', // click overlay to close
    ], $atts, 'gp_modal');

    $id    = sanitize_html_class($a['id']);
    $class = 'gp-modal gp-modal--' . sanitize_html_class($a['size']);
    if ($a['class']) {
        $class .= ' ' . esc_attr($a['class']);
    }

    ob_start();
    ?>
    <div id="<?php echo esc_attr($id); ?>"
         class="<?php echo esc_attr($class); ?>"
         role="dialog"
         aria-modal="true"
         aria-hidden="true"
         data-close-outside="<?php echo esc_attr($a['close_outside']); ?>"
         tabindex="-1">
        <div class="gp-modal__overlay" data-gp-modal-close></div>
        <div class="gp-modal__container">
            <button class="gp-modal__close" data-gp-modal-close
                    aria-label="<?php echo esc_attr($a['close_label']); ?>">
                <svg aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="gp-modal__content">
                <?php echo wp_kses_post(do_shortcode($content)); ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

// ── [gp_modal_trigger] shortcode ─────────────────────────────────────────────

add_shortcode('gp_modal_trigger', function (array $atts, string $content = ''): string {
    $a = shortcode_atts([
        'id'    => '',
        'tag'   => 'button',
        'class' => '',
    ], $atts, 'gp_modal_trigger');

    if (empty($a['id'])) {
        return '';
    }

    $tag   = in_array($a['tag'], ['button', 'a', 'span', 'div'], true) ? $a['tag'] : 'button';
    $class = 'gp-modal-trigger ' . esc_attr($a['class']);
    $extra = ($tag === 'button') ? ' type="button"' : '';
    $href  = ($tag === 'a') ? ' href="#' . esc_attr($a['id']) . '"' : '';

    return sprintf(
        '<%1$s%2$s class="%3$s" data-gp-modal-open="%4$s" aria-haspopup="dialog">%5$s</%1$s>',
        $tag,
        $extra . $href,
        $class,
        esc_attr($a['id']),
        wp_kses_post(do_shortcode($content))
    );
});

// ── PHP helper function ───────────────────────────────────────────────────────

/**
 * Output a modal trigger link/button.
 *
 * @param string $modal_id  ID of the [gp_modal] to open.
 * @param string $label     Visible text.
 * @param array  $args      tag (button|a|span), class, echo (bool).
 */
function gp_modal_trigger(string $modal_id, string $label, array $args = []): string
{
    $args  = wp_parse_args($args, ['tag' => 'button', 'class' => '', 'echo' => true]);
    $tag   = in_array($args['tag'], ['button', 'a', 'span'], true) ? $args['tag'] : 'button';
    $class = 'gp-modal-trigger ' . sanitize_html_class($args['class']);
    $extra = ($tag === 'button') ? ' type="button"' : '';

    $html = sprintf(
        '<%1$s%2$s class="%3$s" data-gp-modal-open="%4$s" aria-haspopup="dialog">%5$s</%1$s>',
        $tag,
        $extra,
        esc_attr($class),
        esc_attr($modal_id),
        esc_html($label)
    );

    if ($args['echo']) {
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput
    }

    return $html;
}
