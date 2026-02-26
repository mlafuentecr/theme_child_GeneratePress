<?php
/**
 * Microbite WebP Converter (SAFE VERSION)
 *
 * - Convierte imágenes subidas a WebP usando Imagick
 * - SOLO reemplaza el archivo usado por WP si WebP es más pequeño
 * - NO elimina el original físicamente (evita romper thumbnails)
 * - Ajusta tamaño máximo y calidad
 * - Corrige orientación EXIF
 *
 * Compatible con:
 * - functions.php
 * - MU-plugins
 * - Snippet plugins
 *
 * Requiere:
 * - PHP Imagick con soporte WebP
 */

/* ============================================================
 * INIT
 * ============================================================ */
add_action('init', function () {
    add_action('admin_notices', 'mbwpc_admin_notices');
    add_action('admin_init', 'mbwpc_register_settings');
    add_filter('wp_handle_upload', 'mbwpc_handle_upload_convert_to_webp');
});

/* ============================================================
 * ADMIN NOTICES
 * ============================================================ */
function mbwpc_admin_notices() {

    if ( ! function_exists('get_current_screen') ) {
        return;
    }

    $screen = get_current_screen();
    if ( ! $screen ) {
        return;
    }

    if ( ! in_array($screen->id, ['upload', 'media'], true) ) {
        return;
    }

    if ( ! extension_loaded('imagick') ) {
        echo '<div class="notice notice-warning is-dismissible">
            <p><strong>WebP Converter:</strong> Imagick no está instalado. La conversión WebP está deshabilitada.</p>
        </div>';
        return;
    }

    $enabled = get_option('mbwpc_convert_to_webp', false);
    $settings_url = admin_url('options-media.php');

    echo '<div class="notice notice-info is-dismissible">
        <p><strong>WebP Converter:</strong> ' .
        ( $enabled ? 'Activo' : 'Inactivo' ) .
        '. Configuración en <a href="' . esc_url($settings_url) . '">Settings → Media</a>.</p>
    </div>';
}

/* ============================================================
 * SETTINGS
 * ============================================================ */
function mbwpc_register_settings() {

    register_setting('media', 'mbwpc_convert_to_webp', [
        'type'              => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default'           => false,
    ]);

    register_setting('media', 'mbwpc_max_width', [
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 1920,
    ]);

    register_setting('media', 'mbwpc_max_height', [
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 1080,
    ]);

    register_setting('media', 'mbwpc_quality', [
        'type'              => 'integer',
        'sanitize_callback' => function ($v) {
            return max(1, min(100, absint($v)));
        },
        'default' => 80,
    ]);

    add_settings_field(
        'mbwpc_convert_to_webp',
        __('Convert uploaded images to WebP', 'mbwpc'),
        function () {
            $val = get_option('mbwpc_convert_to_webp', false);
            echo '<input type="checkbox" name="mbwpc_convert_to_webp" value="1" ' . checked(1, $val, false) . '> ';
            echo esc_html__('Enable automatic WebP conversion', 'mbwpc');
        },
        'media'
    );

    add_settings_field(
        'mbwpc_image_settings',
        __('WebP Image Settings', 'mbwpc'),
        'mbwpc_image_settings_callback',
        'media'
    );
}

function mbwpc_image_settings_callback() {
    ?>
    <p>
        <label>Max Width</label><br>
        <input type="number" name="mbwpc_max_width" value="<?php echo esc_attr(get_option('mbwpc_max_width', 1920)); ?>">
    </p>
    <p>
        <label>Max Height</label><br>
        <input type="number" name="mbwpc_max_height" value="<?php echo esc_attr(get_option('mbwpc_max_height', 1080)); ?>">
    </p>
    <p>
        <label>Quality (1–100)</label><br>
        <input type="number" name="mbwpc_quality" value="<?php echo esc_attr(get_option('mbwpc_quality', 80)); ?>">
    </p>
    <?php
}

/* ============================================================
 * UPLOAD HANDLER
 * ============================================================ */
function mbwpc_handle_upload_convert_to_webp($upload) {

    if ( ! get_option('mbwpc_convert_to_webp') ) {
        return $upload;
    }

    if ( ! extension_loaded('imagick') ) {
        return $upload;
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/heic'];

    if ( ! in_array($upload['type'], $allowed_types, true) ) {
        return $upload;
    }

    try {
        $imagick = new Imagick($upload['file']);

        /* ===== EXIF ORIENTATION ===== */
        switch ($imagick->getImageOrientation()) {
            case Imagick::ORIENTATION_BOTTOMRIGHT:
                $imagick->rotateImage('#000', 180);
                break;
            case Imagick::ORIENTATION_RIGHTTOP:
                $imagick->rotateImage('#000', 90);
                break;
            case Imagick::ORIENTATION_LEFTBOTTOM:
                $imagick->rotateImage('#000', -90);
                break;
        }
        $imagick->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);

        /* ===== RESIZE ===== */
        $max_w = get_option('mbwpc_max_width', 1920);
        $max_h = get_option('mbwpc_max_height', 1080);

        $w = $imagick->getImageWidth();
        $h = $imagick->getImageHeight();

        if ($w > $max_w || $h > $max_h) {
            $imagick->resizeImage($max_w, $max_h, Imagick::FILTER_LANCZOS, 1, true);
        }

        /* ===== WEBP ===== */
        $imagick->setImageFormat('webp');
        $imagick->setImageCompressionQuality(get_option('mbwpc_quality', 80));

        $path = pathinfo($upload['file']);
        $webp = $path['dirname'] . '/' . $path['filename'] . '.webp';

        $imagick->writeImage($webp);

        if (filesize($webp) < filesize($upload['file'])) {
            $upload['file'] = $webp;
            $upload['url']  = str_replace(basename($upload['url']), basename($webp), $upload['url']);
            $upload['type'] = 'image/webp';
        } else {
            wp_delete_file($webp);
        }

        $imagick->clear();
        $imagick->destroy();

    } catch (Throwable $e) {
        error_log('[WebP Converter] ' . $e->getMessage());
    }

    return $upload;
}
