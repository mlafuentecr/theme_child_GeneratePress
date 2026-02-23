<?php
/**
 * Navigation menus — registration and item class filters.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');

    register_nav_menus([
        'main_menu'    => __('Main Menu', 'generatepress-child'),
        'account_menu' => __('Account Menu', 'generatepress-child'),
        'footer_menu'  => __('Footer Menu', 'generatepress-child'),
    ]);
});

/* ============================================================
 * Add .active class to nav menu items.
 * - Always on the first item of main_menu.
 * - On any item WordPress marks as current.
 * ============================================================ */
add_filter('nav_menu_css_class', function (array $classes, object $item, object $args): array {
    if ($args->theme_location === 'main_menu' && (int) $item->menu_order === 1) {
        $classes[] = 'active';
    }

    if (
        in_array('current-menu-item', $classes, true) ||
        in_array('current_page_item', $classes, true)
    ) {
        $classes[] = 'active';
    }

    return $classes;
}, 10, 3);
