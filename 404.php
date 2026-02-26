<?php
/**
 * Basic 404 template.
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main">
    <section class="gp-child-card">
        <h1><?php esc_html_e('Página no encontrada', 'generatepress-child'); ?></h1>
        <p><?php esc_html_e('Lo sentimos, el contenido no está disponible.', 'generatepress-child'); ?></p>
        <p><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Volver al inicio', 'generatepress-child'); ?></a></p>
    </section>
</main>

<?php
get_footer();
