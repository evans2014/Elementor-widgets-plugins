<?php
/**
 * Plugin Name: Elementor Google Map Widget
 * Description: Elementor widget: Google Maps с Repeater locations, SVG icons, clustering, popup.
 * Version: 1.0.0
 * Author: ICT-Strypes
 */

if (!defined('ABSPATH')) exit;

// Зареждане на Google Maps и MarkerClusterer
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script(
        'google-maps-api',
        'https://maps.googleapis.com/maps/api/js?key=GOOGLE_MAP_KEY',
        [],
        null,
        true
    );

    wp_enqueue_script(
        'marker-clusterer',
        'https://unpkg.com/@googlemaps/markerclustererplus/dist/index.min.js',
        ['google-maps-api'],
        null,
        true
    );
    wp_register_script('ce_google_map', plugins_url('assets/js/map-init.js', __FILE__), ['jquery','marker-clusterer'], '1.0', true);
});


add_action('elementor/widgets/register', function ($widgets_manager) {
    require_once __DIR__ . '/widget/widget.php';
    $widgets_manager->register(new \CE_Map_Widget());
});

