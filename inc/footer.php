<?php
/**
 * Custom footer output — hooked into generate_footer.
 * GP's default footer is already removed in helpers.php.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'generate_footer', function (): void { ?>
<footer class="site-footer-custom" role="contentinfo">
  <div class="footer-inner">

    <div class="footer-brand">
      <div class="footer-logo">
        <?php
            $footer_logo_url = get_theme_mod('footer_logo');
            if ( $footer_logo_url ) : ?>
                <img src="<?php echo esc_url( $footer_logo_url ); ?>"
                     alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
                     class="footer-logo-img">
            <?php endif; ?>
      </div>
    </div>

    <div class="footer-bottom">
      <p class="footer-copyright">
        &copy; <?php echo esc_html( date( 'Y' ) ); ?>
        <?php esc_html_e( 'All Rights Reserved. Website by Blue Flamingo Solutions', 'generatepress-child' ); ?>
      </p>

      <?php wp_nav_menu( [
                'theme_location' => 'footer_menu',
                'container'      => 'nav',
                'container_class'=> 'footer-nav',
                'menu_class'     => 'footer-menu',
                'depth'          => 1,
                'fallback_cb'    => false,
            ] ); ?>
    </div>

  </div>
</footer>
<?php } );
