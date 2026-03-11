<?php
/**
 * Basic theme setup.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    // Nav menus are registered in inc/menus.php.
});


// FOOTER LOGO
add_action('customize_register', function($wp_customize){

    $wp_customize->add_setting('footer_logo');

    $wp_customize->add_control(
        new WP_Customize_Image_Control(
            $wp_customize,
            'footer_logo',
            [
                'label' => __('Footer Logo', 'generatepress-child'),
                'section' => 'title_tagline',
                'settings' => 'footer_logo'
            ]
        )
    );

});
