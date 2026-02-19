<?php

add_action('after_setup_theme', function () {
    register_nav_menus([
        // 'main_menu'     => __('Main Menu', 'ml-child'),
        'account_menu'  => __('Account Menu', 'ml-child'),
        'footer_menu'   => __('Footer Menu', 'ml-child'),
    ]);
});


// add active class
// add active class to current item AND to first item of main_menu
add_filter('main_menu', function ($classes, $item, $args) {

    // 1) Always mark the first item of main_menu
    if ($args->theme_location === 'main_menu' && (int) $item->menu_order === 1) {
        $classes[] = 'active';
    }

    // 2) If WP marks current-menu-item, also add active
    if (in_array('current-menu-item', $classes, true) || in_array('current_page_item', $classes, true)) {
        $classes[] = 'active';
    }

    return $classes;

}, 10, 3);

