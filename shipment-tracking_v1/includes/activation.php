<?php

register_activation_hook(
    plugin_dir_path(__DIR__) . 'shipment-tracking.php',
    'shipment_plugin_activate'
);

function shipment_plugin_activate() {

    // Създаваме страниците
    if (!get_option('shipment_pages_created')) {

        if (!get_page_by_title('Tracking search')) {
            wp_insert_post([
                'post_title'   => 'Tracking search',
                'post_content' => '[shipment_search1]',
                'post_status'  => 'publish',
                'post_type'    => 'page'
            ]);
        }

        if (!get_page_by_title('Shipment Posts')) {
            wp_insert_post([
                'post_title'   => 'Shipment Posts',
                'post_content' => '[shipment_grid]',
                'post_status'  => 'publish',
                'post_type'    => 'page'
            ]);
        }

        add_option('shipment_pages_created', 1);
    }

    // Създаваме demo shipment-и
    shipment_install_demo();
}
