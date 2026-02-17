<?php
/**
 * Plugin Name: Map Widget Elementor
 * Description: Elementor widget: Google Maps с Repeater locations, SVG icons, clustering, popup.
 * Version: 1.5.0
 * Author: IVB
 * Text Domain: map-widget-elementor
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined('MWEL_GOOGLE_MAPS_API_KEY') ) {
    define('MWEL_GOOGLE_MAPS_API_KEY', 'ADD GOOGLE MAP API KEY'); // <-- 
}

function mwe_enqueue_scripts() {
    wp_register_script('mwe-markerclusterer', 'https://unpkg.com/@googlemaps/markerclustererplus/dist/index.min.js', [], null, true);
    wp_register_script('mwe-map-init', plugins_url('assets/js/map-init.js', __FILE__), ['jquery','mwe-markerclusterer'], '1.0', true);
    wp_register_style('mwe-style', plugins_url('assets/css/map-widget.css', __FILE__));
    wp_enqueue_script('mwe-map-init');
    wp_enqueue_style('mwe-style');
}
add_action('wp_enqueue_scripts', 'mwe_enqueue_scripts');

add_action('elementor/widgets/register', function($widgets_manager){
    if ( ! did_action('elementor/loaded') ) return;
    require_once __DIR__ . '/includes/class-map-widget.php';
    $widgets_manager->register(new \Map_Widget_Elementor());
});

function mwe_enqueue_google_maps() {
    $api_key = MWEL_GOOGLE_MAPS_API_KEY;

    if ( $api_key ) {
        wp_enqueue_script( 'mwe-gmaps', 'https://maps.googleapis.com/maps/api/js?key=' . $api_key, [], null, true );
    }
}
add_action( 'wp_enqueue_scripts', 'mwe_enqueue_google_maps' );

