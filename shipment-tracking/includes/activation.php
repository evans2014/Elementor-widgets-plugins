<?php

register_activation_hook(
    plugin_dir_path(__DIR__) . 'shipment-tracking.php',
    'shipment_plugin_activate'
);

    function shipment_plugin_activate() {

        if (!function_exists('get_page_by_title')) {
            require_once ABSPATH . 'wp-includes/post.php';
        }
        if (!get_option('shipment_pages_created')) {

            // Важно: укажете 'page' като трети параметър!
            if (!get_page_by_title('Tracking search', OBJECT, 'page')) {
                wp_insert_post([
                    'post_title'   => 'Tracking search',
                    'post_content' => '[shipment_search]',
                    'post_status'  => 'publish',
                    'post_type'    => 'page'
                ]);
            }

            if (!get_page_by_title('Shipment Posts', OBJECT, 'page')) {
                wp_insert_post([
                    'post_title'   => 'Shipment Posts',
                    'post_content' => '[shipment_grid]',
                    'post_status'  => 'publish',
                    'post_type'    => 'page'
                ]);
            }

            add_option('shipment_pages_created', 1);
        }

        shipment_install_demo();
        shipment_add_pages_to_menu();
    }

// Add to Header Menu

function shipment_add_pages_to_menu() {

    if (get_option('shipment_menu_created')) {
        return;
    }

    // We take pages
    $tracking_page = get_page_by_title('Tracking search');
    $posts_page    = get_page_by_title('Shipment Posts');

    if (!$tracking_page || !$posts_page) {
        return;
    }

    // We take all menus
    $menus = wp_get_nav_menus();

    if (!empty($menus)) {
        $menu = $menus[0];
        $menu_id = $menu->term_id;
    } else {
        // If there is no menu – create it
        $menu_id = wp_create_nav_menu('Main Menu');
    }

    // Add the pages to the menu
    wp_update_nav_menu_item($menu_id, 0, [
        'menu-item-title'  => 'Tracking search',
        'menu-item-object' => 'page',
        'menu-item-object-id' => $tracking_page->ID,
        'menu-item-type'   => 'post_type',
        'menu-item-status' => 'publish'
    ]);

    wp_update_nav_menu_item($menu_id, 0, [
        'menu-item-title'  => 'Shipment Posts',
        'menu-item-object' => 'page',
        'menu-item-object-id' => $posts_page->ID,
        'menu-item-type'   => 'post_type',
        'menu-item-status' => 'publish'
    ]);

    // We try to set it as primary location
    $locations = get_theme_mod('nav_menu_locations');

    if (isset($locations['header'])) {
        $locations['header'] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }

    add_option('shipment_menu_created', 1);
}
