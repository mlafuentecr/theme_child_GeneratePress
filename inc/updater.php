<?php
/**
 * Theme updater using a manifest file stored in a public repository.
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('GP_CHILD_UPDATE_MANIFEST_URL')) {
    define('GP_CHILD_UPDATE_MANIFEST_URL', 'https://raw.githubusercontent.com/blueflamingo-solutions/generatepress-child/main/updates/theme.json');
}

if (! defined('GP_CHILD_UPDATE_CACHE_KEY')) {
    define('GP_CHILD_UPDATE_CACHE_KEY', 'gp_child_theme_update_manifest');
}

function gp_child_normalize_update_manifest(array $manifest): array
{
    $release_notes = [];
    if (! empty($manifest['release_notes']) && is_array($manifest['release_notes'])) {
        foreach ($manifest['release_notes'] as $note) {
            $note = sanitize_text_field((string) $note);
            if ($note !== '') {
                $release_notes[] = $note;
            }
        }
    }

    return [
        'theme'         => sanitize_key($manifest['theme'] ?? ''),
        'version'       => sanitize_text_field($manifest['version'] ?? ''),
        'details_url'   => esc_url_raw($manifest['details_url'] ?? ''),
        'download_url'  => esc_url_raw($manifest['download_url'] ?? ''),
        'tested'        => sanitize_text_field($manifest['tested'] ?? ''),
        'requires_php'  => sanitize_text_field($manifest['requires_php'] ?? ''),
        'last_updated'  => sanitize_text_field($manifest['last_updated'] ?? ''),
        'release_notes' => $release_notes,
    ];
}

function gp_child_get_update_uri(): string
{
    $theme = wp_get_theme(get_stylesheet());
    $update_uri = (string) $theme->get('UpdateURI');

    return $update_uri !== '' ? $update_uri : GP_CHILD_UPDATE_MANIFEST_URL;
}

function gp_child_get_update_manifest(): array
{
    $cached = get_site_transient(GP_CHILD_UPDATE_CACHE_KEY);
    if (is_array($cached)) {
        return $cached;
    }

    $response = wp_safe_remote_get(GP_CHILD_UPDATE_MANIFEST_URL, [
        'timeout' => 10,
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        set_site_transient(GP_CHILD_UPDATE_CACHE_KEY, [], HOUR_IN_SECONDS);
        return [];
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    if ($code !== 200 || $body === '') {
        set_site_transient(GP_CHILD_UPDATE_CACHE_KEY, [], HOUR_IN_SECONDS);
        return [];
    }

    $manifest = json_decode($body, true);
    if (! is_array($manifest)) {
        set_site_transient(GP_CHILD_UPDATE_CACHE_KEY, [], HOUR_IN_SECONDS);
        return [];
    }

    $manifest = gp_child_normalize_update_manifest($manifest);

    set_site_transient(GP_CHILD_UPDATE_CACHE_KEY, $manifest, 6 * HOUR_IN_SECONDS);

    return $manifest;
}

function gp_child_get_local_update_manifest(): array
{
    $path = GP_CHILD_DIR . '/updates/theme.json';
    if (! file_exists($path)) {
        return [];
    }

    $manifest = json_decode((string) file_get_contents($path), true);
    if (! is_array($manifest)) {
        return [];
    }

    return gp_child_normalize_update_manifest($manifest);
}

function gp_child_get_current_release_notes(): array
{
    return gp_child_get_local_update_manifest()['release_notes'] ?? [];
}

add_filter('update_themes_raw.githubusercontent.com', function ($update, array $theme_data, string $theme_stylesheet, array $locales) {
    unset($locales);

    if ($theme_stylesheet !== get_stylesheet()) {
        return $update;
    }

    $manifest = gp_child_get_update_manifest();
    if (empty($manifest['version'])) {
        return $update;
    }

    if (! empty($manifest['theme']) && $manifest['theme'] !== sanitize_key($theme_stylesheet)) {
        return $update;
    }

    $current_version = (string) ($theme_data['Version'] ?? '');
    if ($current_version === '' || version_compare($manifest['version'], $current_version, '<=')) {
        return $update;
    }

    return [
        'id'           => gp_child_get_update_uri(),
        'theme'        => $theme_stylesheet,
        'version'      => $manifest['version'],
        'url'          => $manifest['details_url'] ?: 'https://github.com/blueflamingo-solutions/generatepress-child',
        'package'      => $manifest['download_url'] ?? '',
        'tested'       => $manifest['tested'] ?? '',
        'requires_php' => $manifest['requires_php'] ?? '',
        'autoupdate'   => false,
    ];
}, 10, 4);

add_action('upgrader_process_complete', function ($upgrader, array $hook_extra): void {
    unset($upgrader);

    if (($hook_extra['type'] ?? '') !== 'theme') {
        return;
    }

    delete_site_transient(GP_CHILD_UPDATE_CACHE_KEY);
}, 10, 2);
