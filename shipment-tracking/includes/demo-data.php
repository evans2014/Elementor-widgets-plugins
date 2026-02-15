<?php

// Създаване на DEMO DATA

function shipment_install_demo() {

    if (get_option('shipment_demo_installed')) {
        return;
    }

    // Shipment 1

    $shipment1 = wp_insert_post([
        'post_title'  => 'Shipment Sofia → Plovdiv',
        'post_type'   => 'shipment',
        'post_status' => 'publish'
    ]);

    if ($shipment1) {

        $tracking1 = 'SHPMT-' . $shipment1 . '-' . wp_rand(1000,9999);

        update_post_meta($shipment1, '_shipment_tracking_number', $tracking1);

        $route1 = [
            [
                'city' => 'София',
                'address' => 'бул. Акад. Иван Гешов № 2Е',
                'datetime' => date('Y-m-d\TH:i'),
                'lat' => 42.6977,
                'lng' => 23.3219,
                'status' => 'departure'
            ],
            [
                'city' => 'Пловдив',
                'address' => 'Южна промишлена зона, 4113',
                'datetime' => date('Y-m-d\TH:i', strtotime('+4 hours')),
                'lat' => 42.1354,
                'lng' => 24.7453,
                'status' => 'final'
            ]
        ];

        update_post_meta($shipment1, '_shipment_route_points', $route1);
        update_post_meta($shipment1, '_shipment_status', 'In Transit');
        update_post_meta($shipment1, '_courier_name', 'Ivan Petrov');
        update_post_meta($shipment1, '_courier_phone', '+359888123456');
        update_post_meta($shipment1, '_courier_message', 'Expected delivery today.');
    }

    // Shipment 2

    $shipment2 = wp_insert_post([
        'post_title'  => 'Shipment Prague → Paris',
        'post_type'   => 'shipment',
        'post_status' => 'publish'
    ]);

    if ($shipment2) {

        $tracking2 = 'SHPMT-' . $shipment2 . '-' . wp_rand(1000,9999);

        update_post_meta($shipment2, '_shipment_tracking_number', $tracking2);

        $route2 = [
            [
                'city' => 'Прага',
                'address' => 'Čestmírova 25, 140 00 ',
                'datetime' => date('Y-m-d\TH:i'),
                'lat' => 50.0755,
                'lng' => 14.4378,
                'status' => 'departure'
            ],
            [
                'city' => 'Мюнхен',
                'address' => 'Schäftlarnstraße 10, 81371',
                'datetime' => date('Y-m-d\TH:i', strtotime('+6 hours')),
                'lat' => 48.1351,
                'lng' => 11.5820,
                'status' => 'intransit'
            ],
            [
                'city' => 'Париж',
                'address' => 'Final Destination',
                'datetime' => date('Y-m-d\TH:i', strtotime('+12 hours')),
                'lat' => 48.8566,
                'lng' => 2.3522,
                'status' => 'final'
            ]
        ];

        update_post_meta($shipment2, '_shipment_route_points', $route2);
        update_post_meta($shipment2, '_shipment_status', 'Pending');
        update_post_meta($shipment2, '_courier_name', 'John Smith');
        update_post_meta($shipment2, '_courier_phone', '+33123456789');
        update_post_meta($shipment2, '_courier_message', 'International shipment.');
    }

    update_option('shipment_demo_installed', 1);
}

add_action('admin_init', function() {

    if (!isset($_POST['shipment_tools_nonce'])) return;
    if (!wp_verify_nonce($_POST['shipment_tools_nonce'], 'shipment_tools_action')) return;

    if (isset($_POST['install_demo'])) {
        shipment_install_demo();
    }

    if (isset($_POST['reset_demo'])) {

        $query = new WP_Query([
            'post_type' => 'shipment',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);

        while ($query->have_posts()) {
            $query->the_post();
            wp_delete_post(get_the_ID(), true);
        }

        delete_option('shipment_demo_installed');
        wp_reset_postdata();
    }
});
