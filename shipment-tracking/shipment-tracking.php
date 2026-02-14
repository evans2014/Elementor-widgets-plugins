<?php
/**
 * Plugin Name: Shipment Manager
 * Description: Управление на shipment-и с интерактивна карта и точки.
 * Version: 1.0
 * Author: Your Name
 * Text Domain: shipment-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/activation.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/demo-data.php';



// =======================
// Frontend Scripts
// =======================
add_action('wp_enqueue_scripts', 'shipment_frontend_scripts');
function shipment_frontend_scripts() {
    // Leaflet CSS и JS
    wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
    wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);

    // Leaflet Routing Machine CSS и JS
    wp_enqueue_style('leaflet-routing-machine-css', 'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css');
    wp_enqueue_script('leaflet-routing-machine-js', 'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js', ['leaflet-js'], null, true);
}

class Shipment_Manager {
    public function __construct() {
        add_action('init', [$this, 'register_shipment_post_type']);
        add_action('add_meta_boxes', [$this, 'add_shipment_meta_box']);
        add_action('save_post', [$this, 'save_shipment_meta_box']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Registration custom post type 'shipment'
     */
    public function register_shipment_post_type() {
        $labels = [
            'name' => __('Shipments', 'shipment-manager'),
            'singular_name' => __('Shipment', 'shipment-manager'),
            'add_new' => __('Add Shipment', 'shipment-manager'),
            'add_new_item' => __('Add New Shipment', 'shipment-manager'),
            'edit_item' => __('Edit Shipment', 'shipment-manager'),
            'new_item' => __('New Shipment', 'shipment-manager'),
            'view_item' => __('View Shipment', 'shipment-manager'),
            'search_items' => __('Search Shipments', 'shipment-manager'),
            'not_found' => __('No shipments found', 'shipment-manager'),
            'not_found_in_trash' => __('No shipments found in Trash', 'shipment-manager'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'supports' => ['title','thumbnail'],
            'menu_position' => 5,
            'menu_icon' => 'dashicons-location-alt',
        ];

        register_post_type('shipment', $args);
    }

    /**
     * Add Meta Box about the routes
     */
    public function add_shipment_meta_box() {
        add_meta_box(
            'shipment_route_meta',
            __('Shipment Route', 'shipment-manager'),
            [$this, 'render_shipment_meta_box'],
            'shipment',
            'normal',
            'high'
        );
    }

    /**
     * Renders Meta Box
     */
    public function render_shipment_meta_box($post) {
        wp_nonce_field('save_shipment_route', 'shipment_route_nonce');

        $route_points = get_post_meta($post->ID, '_shipment_route_points', true);
        if (!$route_points) {
            $route_points = [];
        }
        ?>

            <div id="shipment-route-container">
            <button type="button" id="add-route-point" class="button"><?php _e('Add Point', 'shipment-manager'); ?></button>
            <ul id="route-points-list">
                <?php

                foreach ($route_points as $index => $point): ?>
                    <li class="route-point-item" data-index="<?php echo esc_attr($index); ?>">
                        <input type="text" name="shipment_route[<?php echo $index; ?>][city]" value="<?php echo esc_attr($point['city']); ?>" placeholder="City" />
                        <input type="text" name="shipment_route[<?php echo $index; ?>][address]" value="<?php echo esc_attr($point['address']); ?>" placeholder="Address" />
                        <input type="datetime-local" name="shipment_route[<?php echo $index; ?>][datetime]" value="<?php echo esc_attr($point['datetime']); ?>" />
                        <input type="text" name="shipment_route[<?php echo $index; ?>][lat]" value="<?php echo esc_attr($point['lat']); ?>" placeholder="Latitude" />
                        <input type="text" name="shipment_route[<?php echo $index; ?>][lng]" value="<?php echo esc_attr($point['lng']); ?>" placeholder="Longitude" />
                        <select name="shipment_route[<?php echo $index; ?>][status]">
                            <option value="Departure" <?php selected($point['status'], 'Departure'); ?>>Departure</option>
                            <option value="Pending" <?php selected($point['status'], 'Pending'); ?>>Pending</option>
                            <option value="Shipped" <?php selected($point['status'], 'Shipped'); ?>>Shipped</option>
                            <option value="In Transit" <?php selected($point['status'], 'In Transit'); ?>>In Transit</option>
                            <option value="Delivered" <?php selected($point['status'], 'Delivered'); ?>>Delivered</option>
                            <option value="Done" <?php selected($point['status'], 'Done'); ?>>Done</option>
                        </select>
                        <button type="button" class="remove-route-point button">Remove</button>
                    </li>
                <?php endforeach; ?>
            </ul>

        </div>
        <div class="two-columns">
            <div class="left-column">
                <div id="shipment-map" style="width: 100%; height: 460px; margin-top: 15px;"></div>
            </div>
            <div class="right-column">
                    <p>
                        <label><?php _e('Tracking Number', 'shipment-manager'); ?></label><br/>
                        <input type="text" name="shipment_tracking_number" value="<?php echo esc_attr(get_post_meta($post->ID, '_shipment_tracking_number', true)); ?>" style="width:100%;" />
                    </p>
                    <p>
                        <label><?php _e('Status', 'shipment-manager'); ?></label><br/>
                        <select name="shipment_status">
                            <?php
                            $current_status = get_post_meta($post->ID, '_shipment_status', true);
                            $statuses = ['Pending','Shipped','In Transit','Delivered'];
                            foreach($statuses as $s): ?>
                                <option value="<?php echo esc_attr($s); ?>" <?php selected($current_status, $s); ?>><?php echo esc_html($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label><?php _e('Total Time', 'shipment-manager'); ?></label><br/>
                        <input type="text" name="total_time" value="<?php echo esc_attr(get_post_meta($post->ID, '_total_time', true)); ?>" style="width:100%;" />
                    </p>
                    <p>
                        <label><?php _e('Courier Name', 'shipment-manager'); ?></label><br/>
                        <input type="text" name="courier_name" value="<?php echo esc_attr(get_post_meta($post->ID, '_courier_name', true)); ?>" style="width:100%;" />
                    </p>
                    <p>
                        <label><?php _e('Courier Phone', 'shipment-manager'); ?></label><br/>
                        <input type="text" name="courier_phone" value="<?php echo esc_attr(get_post_meta($post->ID, '_courier_phone', true)); ?>" style="width:100%;" />
                    </p>
                    <p>
                        <label><?php _e('Courier Message', 'shipment-manager'); ?></label><br/>
                        <textarea name="courier_message" rows="3" style="width:100%;"><?php echo esc_textarea(get_post_meta($post->ID, '_courier_message', true)); ?></textarea>
                    </p>
            </div>
        </div>
<style>
    .leaflet-routing-container {
        display:none;
    }
    .two-columns {
        display: flex;
            }

    .left-column {
        flex: 2;
        background: #f3f4f6;
        padding: 10px 20px 0 0;
    }

    .right-column {
        flex: 1;
        background: #e5e7eb;
        padding: 20px;
    }

    @media (max-width: 768px) {
        .two-columns {
            flex-direction: column;
        }
    }

    #shipment-route-container {
        background: #e5e7eb;
        padding: 20px;
    }

</style>
        <?php
    }
    /**
     * Save Meta Box
     */
    public function save_shipment_meta_box($post_id) {
        if (!isset($_POST['shipment_route_nonce']) || !wp_verify_nonce($_POST['shipment_route_nonce'], 'save_shipment_route')) {
            return;
        }
        if (
            !isset($_POST['shipment_route_nonce']) ||
            !wp_verify_nonce($_POST['shipment_route_nonce'], 'save_shipment_route')
        ) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $route_data = $_POST['shipment_route'] ?? [];
        $clean_data = [];

        foreach ($route_data as $point) {
            // Skip empty cities/addresses
            if (empty($point['city']) && empty($point['address'])) continue;

            $clean_data[] = [
                'city' => sanitize_text_field($point['city']),
                'address' => sanitize_text_field($point['address']),
                'datetime' => sanitize_text_field($point['datetime']),
                'lat' => floatval($point['lat']),
                'lng' => floatval($point['lng']),
                'status' => sanitize_text_field($point['status']),
            ];
        }
        $existing_tracking = get_post_meta($post_id, '_shipment_tracking_number', true);
        if (empty($existing_tracking)) {
            $tracking_number = 'SHPMT-' . $post_id . '-' . wp_rand(1000, 9999);
            update_post_meta($post_id, '_shipment_tracking_number', $tracking_number);

            update_post_meta($post_id, '_shipment_tracking_number', $tracking_number);
        }

        update_post_meta($post_id, '_shipment_route_points', $clean_data);
        update_post_meta($post_id, '_shipment_status', sanitize_text_field($_POST['shipment_status'] ?? 'Pending'));
        update_post_meta($post_id, '_total_time', sanitize_text_field($_POST['total_time'] ?? ''));
        update_post_meta($post_id, '_courier_name', sanitize_text_field($_POST['courier_name'] ?? ''));
        update_post_meta($post_id, '_courier_phone', sanitize_text_field($_POST['courier_phone'] ?? ''));
        update_post_meta($post_id, '_courier_message', sanitize_textarea_field($_POST['courier_message'] ?? ''));

    }
    /**
     * Loads scripts and styles for admin
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        if ($post_type !== 'shipment') return;

        // Leaflet CSS & JS
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);

        wp_enqueue_style('leaflet-routing-machine-css', 'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css');
        wp_enqueue_script('leaflet-routing-machine-js', 'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js', ['leaflet-js'], null, true);

        // Custom admin JS
        wp_enqueue_script('shipment-admin-js', plugin_dir_url(__FILE__) . 'js/shipment-admin.js', ['jquery', 'leaflet-js'], null, true);
    }
}

new Shipment_Manager();

add_shortcode('shipment_tracking', 'shipment_tracking_shortcode');
function shipment_tracking_shortcode($atts) {
    $atts = shortcode_atts(['id' => 0], $atts, 'shipment_tracking');
    $post_id = intval($atts['id']);
    if (!$post_id) return 'Shipment not found.';

    $route_points = get_post_meta($post_id, '_shipment_route_points', true) ?: [];
    $tracking_number = get_post_meta($post_id, '_shipment_tracking_number', true);
    $status = get_post_meta($post_id, '_shipment_status', true);
    $total_time = get_post_meta($post_id, '_total_time', true);
    $courier_name = get_post_meta($post_id, '_courier_name', true);
    $courier_phone = get_post_meta($post_id, '_courier_phone', true);
    $courier_message = get_post_meta($post_id, '_courier_message', true);

    // Preparing a JS array for the route
    $route_json = json_encode($route_points);

    ob_start(); ?>
    <div class="shipment-tracking-container">
        <h3>Shipment #<?php echo esc_html($tracking_number); ?> - Status: <?php echo esc_html($status); ?></h3>
        <p><strong>Courier:</strong> <?php echo esc_html($courier_name); ?> | <strong>Phone:</strong> <?php echo esc_html($courier_phone); ?></p>
        <p><strong>Message:</strong> <?php echo esc_html($courier_message); ?></p>

        <div id="shipment-map-frontend" style="width:100%; height:400px; margin-bottom:15px;"></div>

        <ul id="shipment-timeline">
            <?php foreach ($route_points as $point): ?>
                <li>
                    <strong><?php echo esc_html($point['city']); ?></strong> - <?php echo esc_html($point['address']); ?>
                    <br/>
                    <?php echo esc_html($point['datetime']); ?> | Status: <?php echo esc_html($point['status']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function(){
        const routePoints = <?php echo $route_json; ?>;

        const map = L.map('shipment-map-frontend').setView([42.7, 23.3], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const waypoints = [];

        routePoints.forEach(pt => {
          if (pt.lat && pt.lng) {
            waypoints.push(L.latLng(pt.lat, pt.lng));
            L.marker([pt.lat, pt.lng]).addTo(map)
              .bindPopup(`<strong>${pt.city}</strong><br>${pt.address}<br>${pt.datetime}<br>Status: ${pt.status}`);
          }
        });

        if (waypoints.length > 1) {
          L.Routing.control({
            waypoints: waypoints,
            routeWhileDragging: false,
            draggableWaypoints: false,
            addWaypoints: false,
            lineOptions: {
              styles: [{color: 'blue', opacity: 0.7, weight: 5}]
            },
            createMarker: function() { return null; } // вече имаме свои маркери
          }).addTo(map);
        } else if (waypoints.length === 1) {
          map.setView(waypoints[0], 10);
        }
      });

    </script>
    <?php
    return ob_get_clean();
}


add_action('wp_ajax_shipment_search', 'shipment_search_ajax');
add_action('wp_ajax_nopriv_shipment_search', 'shipment_search_ajax');

function shipment_search_ajax() {
    $tracking_number = sanitize_text_field($_GET['tracking_number'] ?? '');
    if(!$tracking_number){
        wp_send_json(['success'=>false]);
    }

    $args = [
        'post_type'=>'shipment',
        'meta_query'=>[
            [
                'key'=>'_shipment_tracking_number',
                'value'=>$tracking_number,
                'compare'=>'='
            ]
        ],
        'posts_per_page'=>1
    ];
    $query = new WP_Query($args);
    if(!$query->have_posts()){
        wp_send_json(['success'=>false]);
    }

    $post = $query->posts[0];
    $route_points = get_post_meta($post->ID, '_shipment_route_points', true) ?: [];


    wp_send_json([
        'success'=>true,
        'route_points'=>$route_points,
        'tracking_number' => get_post_meta($post->ID,'_shipment_tracking_number',true),
        'status' => get_post_meta($post->ID,'_shipment_status',true),
        'courier_name' => get_post_meta($post->ID,'_courier_name',true),
        'courier_phone' => get_post_meta($post->ID,'_courier_phone',true),
        'courier_message' => get_post_meta($post->ID,'_courier_message',true)
    ]);
}


add_filter('template_include', 'shipment_load_single_template');
function shipment_load_single_template($template) {

    if (is_singular('shipment')) {

        $plugin_template = plugin_dir_path(__FILE__) . 'templates/single-shipment.php';

        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }

    return $template;
}

add_action('wp_enqueue_scripts', 'shipment_enqueue_frontend_assets');
function shipment_enqueue_frontend_assets() {

    // We only charge if we are in single shipment OR if there is a shortcode
    if ( is_singular('shipment') || is_page() ) {

        wp_enqueue_style(
            'leaflet-css',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );

        wp_enqueue_script(
            'leaflet-js',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );

        wp_enqueue_style(
            'leaflet-routing-css',
            'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css',
            [],
            '3.2.12'
        );

        wp_enqueue_script(
            'leaflet-routing-js',
            'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js',
            ['leaflet-js'],
            '3.2.12',
            true
        );
    }
    wp_localize_script(
        'shipment-frontend',
        'shipment_data',
        [
            'plugin_url' => plugin_dir_url(__FILE__)
        ]
    );

}

add_action('wp_enqueue_scripts', function() {

    wp_enqueue_style(
        'shipment-css',
        plugin_dir_url(__FILE__) . 'css/shipment.css',
        [],
        '1.0'
    );

});