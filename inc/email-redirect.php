<?php
/**
 * Email redirect runtime.
 */

if (! defined('ABSPATH')) {
    exit;
}

function gp_child_get_email_redirect_settings(): array
{
    $settings = (array) get_option('blueflamingo_plugin_email_redirect_settings', []);

    return [
        'activate_email_redirect_staging_or_development' => ! empty($settings['activate_email_redirect_staging_or_development']) ? '1' : '0',
        'activate_email_redirect_production'             => ! empty($settings['activate_email_redirect_production']) ? '1' : '0',
        'redirect_email_id'                              => sanitize_email($settings['redirect_email_id'] ?? ''),
    ];
}

add_action('init', function (): void {
    $settings = gp_child_get_email_redirect_settings();

    if (
        empty($settings['redirect_email_id']) ||
        (
            empty($settings['activate_email_redirect_staging_or_development']) &&
            empty($settings['activate_email_redirect_production'])
        )
    ) {
        return;
    }

    add_filter('wp_mail', 'gp_child_override_mail_recipient');
});

function gp_child_override_mail_recipient(array $args): array
{
    $settings = gp_child_get_email_redirect_settings();

    if (empty($settings['redirect_email_id'])) {
        return $args;
    }

    $redirect_active = (
        ! empty($settings['activate_email_redirect_staging_or_development']) &&
        gp_child_is_staging_environment()
    ) || (
        ! empty($settings['activate_email_redirect_production']) &&
        gp_child_is_live_environment()
    );

    if (! $redirect_active) {
        return $args;
    }

    $original_to = $args['to'] ?? '';
    $subject     = (string) ($args['subject'] ?? '');
    $message     = (string) ($args['message'] ?? '');
    $html        = (string) ($args['html'] ?? '');
    $prefix      = '[TEST] ';
    $notice      = 'DEVELOPMENT ENVIRONMENT. THIS MESSAGE WOULD NORMALLY HAVE BEEN SENT TO: ';

    $args['to']      = $settings['redirect_email_id'];
    $args['subject'] = str_starts_with($subject, $prefix) ? $subject : $prefix . $subject;
    $args['message'] = $notice . (is_array($original_to) ? implode(', ', $original_to) : $original_to) . PHP_EOL . $message;

    if ($html !== '') {
        $args['html'] = $html . '<strong><em>' . esc_html($notice . (is_array($original_to) ? implode(', ', $original_to) : $original_to)) . '</em></strong>';
    }

    return $args;
}
