<?php
/**
 * Internal notes and dashboard notices.
 */

if (! defined('ABSPATH')) {
    exit;
}

function gp_child_get_notes(): array
{
    $raw = get_option('gp_child_notes', '[]');
    $arr = json_decode((string) $raw, true);
    return is_array($arr) ? $arr : [];
}

function gp_child_save_notes(array $notes): void
{
    update_option('gp_child_notes', wp_json_encode(array_values($notes)));
}

add_action('admin_notices', function (): void {
    global $pagenow;

    if ($pagenow !== 'index.php') {
        return;
    }

    $required_cap = get_option('gp_child_notes_role', 'manage_options');
    if (! current_user_can($required_cap)) {
        return;
    }

    $notes = gp_child_get_notes();
    if (empty($notes)) {
        return;
    }

    $color_map = [
        'yellow' => ['type' => 'notice-warning', 'border' => '#f0d048'],
        'blue'   => ['type' => 'notice-info', 'border' => '#90caf9'],
        'green'  => ['type' => 'notice-success', 'border' => '#a5d6a7'],
        'pink'   => ['type' => 'notice-error', 'border' => '#f48fb1'],
    ];

    $manage_url = admin_url('themes.php?page=gp-child-settings');

    foreach ($notes as $note) {
        $color   = in_array($note['color'] ?? '', ['yellow', 'blue', 'green', 'pink'], true) ? $note['color'] : 'yellow';
        $map     = $color_map[$color];
        $title   = esc_html(trim($note['title'] ?? ''));
        $content = esc_html(trim($note['content'] ?? ''));

        if ($title === '' && $content === '') {
            continue;
        }

        printf(
            '<div class="notice %s gp-site-note" style="border-left-color:%s;"><p>%s%s<a href="%s" style="margin-left:10px;font-size:11px;opacity:.7;">%s</a></p></div>',
            esc_attr($map['type']),
            esc_attr($map['border']),
            $title !== '' ? '<strong>' . $title . '</strong> ' : '',
            $content !== '' ? '<span style="color:#50575e;">' . nl2br($content) . '</span>' : '',
            esc_url($manage_url),
            esc_html__('Manage notes →', 'generatepress-child')
        );
    }
});

add_action('wp_ajax_gp_child_add_note', function (): void {
    check_ajax_referer('gp_child_notes', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $notes = gp_child_get_notes();
    $note  = [
        'id'      => wp_generate_uuid4(),
        'title'   => '',
        'content' => '',
        'color'   => 'yellow',
        'created' => gmdate('Y-m-d'),
    ];

    array_unshift($notes, $note);
    gp_child_save_notes($notes);
    wp_send_json_success($note);
});

add_action('wp_ajax_gp_child_save_note', function (): void {
    check_ajax_referer('gp_child_notes', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $id      = sanitize_text_field(wp_unslash($_POST['id'] ?? ''));
    $title   = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
    $content = sanitize_textarea_field(wp_unslash($_POST['content'] ?? ''));
    $color   = in_array($_POST['color'] ?? '', ['yellow', 'blue', 'green', 'pink'], true)
        ? sanitize_text_field(wp_unslash($_POST['color']))
        : 'yellow';

    $notes = gp_child_get_notes();
    $found = false;

    foreach ($notes as &$note) {
        if ($note['id'] === $id) {
            $note['title']   = $title;
            $note['content'] = $content;
            $note['color']   = $color;
            $found           = true;
            break;
        }
    }
    unset($note);

    if (! $found) {
        wp_send_json_error('Note not found', 404);
    }

    gp_child_save_notes($notes);
    wp_send_json_success();
});

add_action('wp_ajax_gp_child_delete_note', function (): void {
    check_ajax_referer('gp_child_notes', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $id    = sanitize_text_field(wp_unslash($_POST['id'] ?? ''));
    $notes = array_filter(gp_child_get_notes(), fn($n) => $n['id'] !== $id);
    gp_child_save_notes(array_values($notes));
    wp_send_json_success();
});
