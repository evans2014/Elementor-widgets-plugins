<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* Register Elementor widget */
add_action( 'elementor/widgets/register', 'egmw_register_elementor_widget' );
add_action( 'elementor/frontend/after_register_scripts', 'egmw_register_frontend_scripts' );

function egmw_register_elementor_widget( $widgets_manager ) {
    if ( ! did_action( 'elementor/loaded' ) ) return;
    require_once( __DIR__ . '/elementor-widget-class.php' );
    $widgets_manager->register( new \EGMW_Elementor_Map_Widget() );
}

/* Enqueue Google Maps + frontend JS */
function egmw_register_frontend_scripts() {
    // MarkerClusterer library (from Google)
    wp_register_script( 'markerclusterer', 'https://unpkg.com/@googlemaps/markerclustererplus/dist/index.min.js', array(), null, true );

    // our init script
    wp_register_script( 'egmw-map-init', EGMW_URL . 'assets/js/map-init.js', array('jquery','markerclusterer'), '1.0', true );
    // CSS (if any)
    wp_register_style( 'egmw-style', EGMW_URL . 'assets/css/egmw.css', array(), '1.0' );
}

/* Helper to output map scripts with API key and localized data - used by the widget class */
function egmw_enqueue_map_assets( $map_data = array(), $settings = array() ) {
    $api_key = get_option( 'egmw_api_key', '' );
    $api_key = 'ADD GOOGLE MAP KEY';
    if ( empty( $api_key ) ) {
        // don't enqueue map script if no key; widget will show warning
        return false;
    }

    // enqueue Google Maps JS with key
    $maps_url = add_query_arg( array(
        'key' => $api_key,
        'libraries' => 'places'
    ), 'https://maps.googleapis.com/maps/api/js' );

    wp_enqueue_script( 'egmw-google-maps', $maps_url, array(), null, true );
    wp_enqueue_script( 'egmw-map-init' );
    wp_enqueue_style( 'egmw-style' );

    // localize data
    wp_localize_script( 'egmw-map-init', 'EGMW_DATA', array(
        'locations' => $map_data,
        'settings' => $settings,
        'svgBaseUrl' => EGMW_URL . 'assets/svg/',
    ) );

    return true;
}