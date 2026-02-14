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


add_shortcode('shipment_search', 'shipment_search_shortcode');
function shipment_search_shortcode() {
    ob_start();
    ?>
    <div id="shipment-search-container" style="display:flex; flex-direction:column; gap:10px;">
        <div>
            <label for="tracking_number_input">Въведи Tracking Number:</label>
            <input type="text" id="tracking_number_input" style="width:200px; padding:5px;">
            <button id="shipment_search_btn" style="padding:5px 10px;">Търси</button>
        </div>
        <div style="display:flex; gap:20px; flex-wrap:wrap; height:700px;">
            <div id="shipment-map-search" style="flex:2; height:100%; min-width:300px;"></div>
            <div id="shipment-details-search" style="flex:1; height:100%; min-width:200px; padding:10px; overflow:hidden;"></div>
        </div>
    </div>
    <script>
      document.addEventListener('DOMContentLoaded', function(){
        // Loading Leaflet
        if(typeof L === 'undefined'){
          const link = document.createElement('link');
          link.rel = 'stylesheet';
          link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
          document.head.appendChild(link);

          const script = document.createElement('script');
          script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
          document.head.appendChild(script);
        }
        // Leaflet Routing Machine
        if(typeof L.Routing === 'undefined'){
          const link2 = document.createElement('link');
          link2.rel = 'stylesheet';
          link2.href = 'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css';
          document.head.appendChild(link2);

          const script2 = document.createElement('script');
          script2.src = 'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js';
          document.head.appendChild(script2);
        }

        let map = null;
        let routingControl = null;
        function loadShipment(tracking_number){
          fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=shipment_search&tracking_number=' + encodeURIComponent(tracking_number))
            .then(res => res.json())
            .then(data => {
              if(!data.success){
                alert('Shipment not found');
                return;
              }

              const routePoints = data.route_points;
              const detailsDiv = document.getElementById('shipment-details-search');
              //console.log(data);
              detailsDiv.innerHTML = `
                    <p><strong>Tracking Number:</strong> ${data.tracking_number}</p>
                    <p><strong>Status:</strong> ${data.status}</p>
                    <p><strong>Courier:</strong> ${data.courier_name}</p>
                    <p><strong> Phone:</strong> ${data.courier_phone}</p>
                    <p><strong>Message:</strong> ${data.courier_message}</p>
                    <hr>
                    <div id="route-summary"></div>
                `;
              // Timeline of the points
              routePoints.forEach(pt=>{
                date = formatIsoDateTime(pt.datetime);
                const div = document.createElement('div');
                div.style.borderBottom = '1px solid #ddd';
                div.style.paddingBottom = '5px';
                div.style.marginBottom = '5px';
                div.innerHTML = `
                <strong>${pt.city}</strong><br>${date}<br>${pt.address}
                `;
                detailsDiv.appendChild(div);
              });
              // Card initialization
              if(!map){
                map = L.map('shipment-map-search').setView([42.7, 23.3], 7);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                  attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
              }
              // We are removing the old routing control
              if(routingControl){
                map.removeControl(routingControl);
              }
              const latlngs = [];
              routePoints.forEach(pt=>{
                if(pt.lat && pt.lng){
                  latlngs.push([pt.lat, pt.lng]);
                  L.marker([pt.lat, pt.lng]).addTo(map)
                    .bindPopup(`<strong>${pt.city}</strong><br>${pt.address}<br>${pt.datetime}<br>Status: ${pt.status}`);
                }
              });
              // Centering the map
              if(latlngs.length > 0){
                map.fitBounds(latlngs, {padding:[50,50]});
              }
              function formatIsoDateTime(isoString) {
                const date = new Date(isoString);

                // Check if the date is valid
                if (isNaN(date.getTime())) {
                  throw new Error('Invalid date string');
                }

                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0'); // getMonth() е 0-базиран
                const year = date.getFullYear();
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');

                return `Date: ${day}/${month}/${year} Time: ${hours}:${minutes}`;
              }

              // Routing Machine
              if(latlngs.length > 1){
                routingControl = L.Routing.control({
                  waypoints: latlngs.map(l => L.latLng(l[0], l[1])),
                  routeWhileDragging:false,
                  draggableWaypoints:false,
                  addWaypoints:false,
                  lineOptions:{styles:[{color:'blue',opacity:0.7,weight:5}]},
                  createMarker:function(){ return null; }
                }).addTo(map);

                routingControl.on('routesfound', function(e){
                  const route = e.routes[0];
                  const coordinates = route.coordinates;
                  const totalDistance = (route.summary.totalDistance / 1000).toFixed(1);
                  const totalTime = route.summary.totalTime;
                  const hours = Math.floor(totalTime / 3600);
                  const minutes = Math.floor((totalTime % 3600) / 60);
                  document.getElementById('route-summary').innerHTML = `
                        <div style="background:#f3f4f6; padding:10px; border-radius:8px; margin-bottom:20px;">
                          <strong>Маршрут:</strong><br>
                          Разстояние: ${totalDistance} км<br>
                          Очаквано време: ${hours}ч ${minutes}мин
                        </div>
                      `;
                });
                // Hide by default
                setTimeout(()=>{
                  const routingDiv = document.querySelector('.leaflet-routing-container');
                  if(routingDiv) routingDiv.style.display='none';
                },100);
              }

            });
        }

        document.getElementById('shipment_search_btn').addEventListener('click', function(){
          const tn = document.getElementById('tracking_number_input').value.trim();
          if(tn) loadShipment(tn);
        });
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
add_shortcode('shipment_search1', function() {
    ob_start();
    $tracking = isset($_GET['tracking']) ? sanitize_text_field($_GET['tracking']) : '';
    ?>
    <?php
    // IF THERE IS SEARCH
    if ($tracking) {

        echo "<h3>Резултат от търсене</h3>";

        $args = [
            'post_type'  => 'shipment',
            'meta_query' => [
                [
                    'key'   => '_shipment_tracking_number',
                    'value' => $tracking,
                    'compare' => '='
                ]
            ]
        ];

    } else {
        // IF NO SEARCH → LAST POST
        echo "<h3>Показваме последната пратка</h3>";

        $args = [
            'post_type'      => 'shipment',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC'
        ];
    }

    $shipment = new WP_Query($args);

    if ($shipment->have_posts()) :

        while ($shipment->have_posts()) :
            $shipment->the_post();

            $post_id = get_the_ID();

            $route_points = get_post_meta($post_id, '_shipment_route_points', true) ?: [];
            $tracking_number = get_post_meta($post_id, '_shipment_tracking_number', true);
            $status       = get_post_meta($post_id, '_shipment_status', true);
            $total_time = get_post_meta($post_id, '_total_time', true);
            $courier_name = get_post_meta($post_id, '_courier_name', true);
            $courier_phone= get_post_meta($post_id, '_courier_phone', true);
            $courier_msg  = get_post_meta($post_id, '_courier_message', true);
            ?>

            <div style="display:flex;  align-items:flex-start;">
                <div style="flex:2;">
                    <div id="shipment-map" style="height:700px;"></div>
                </div>
                <div style="flex:1; background:#f3f4f6; padding:5px; border-radius:10px;padding: 5px 20px">
                    <form method="get" style="margin-bottom:20px;">
                        <input type="text" name="tracking"
                               placeholder="Въведете Tracking Number"
                               value="<?php echo esc_attr($tracking); ?>"
                               style="padding:8px; width:280px;">
                        <button type="submit" style="padding:8px 12px;">Търси</button>
                    </form>

                    <div><strong>Tracking:</strong> <?php echo esc_html($tracking_number); ?></div>
                    <div><strong>Status:</strong> <?php echo esc_html($status); ?></div>
                    <hr>
                    <h5><strong>Detail</strong></h5>
                    <?php
                    $total_points = count($route_points);

                    if ($total_points > 0) {

                        $first_point = $route_points[0];
                        $last_point  = $route_points[$total_points - 1];
                        ?>
                        <div style="display:flex; gap:5px; margin-bottom:10px;">
                            <div style="flex:1;">
                                <label><b>Total Time</b></label>
                                <div><?php echo esc_html($total_time); ?></div>
                            </div>
                            <?php
                            $date1=strtotime($first_point['datetime']);
                            $date2=strtotime($last_point['datetime']);
                            $diff = abs($date2 - $date1);

                            $days = floor($diff / (60 * 60 * 24));
                            $hours = floor(($diff % (60 * 60 * 24)) / (60 * 60));
                            $minutes = floor(($diff % (60 * 60)) / 60);
                            //echo $days." дни, $hours часа и $minutes минути";
                            ?>
                            <div style="flex:1;">
                                <label><b>Departure Тime</b></label>
                                <div><?php echo date('j-m-y H:i', strtotime($first_point['datetime'])); ?></div>
                            </div>
                            <div style="flex:1;">
                                <label><b>Arrival Time</b></label>
                                <div><?php echo date('j-m-y H:i', strtotime($last_point['datetime'])); ?></div>
                            </div>

                        </div>
                        <?php
                    }
                    ?>

                    <div class="timeline">
                    <?php
                    $total_points = count($route_points);
                    foreach ($route_points as $index => $point):
                        $position = $index + 1;
                        if ($position === 1) {
                            $label = 'Departure';
                        } elseif ($position === $total_points) {
                            if ($total_points === 2) {
                                $label = 'In Transit – Final Destination';
                            } else {
                                $label = 'In Transit – Final Destination';
                            }
                        } else {
                            $label = 'In Transit';
                        }
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-city"><?php echo $label; ?> <span><?php echo esc_html($point['city']); ?></span></div>
                                <div class="timeline-date">
                                    <?php echo date('j-m-y H:i', strtotime($point['datetime'])); ?>
                                </div>
                                <div class="timeline-address"><?php echo esc_html($point['address']); ?></div>

                            </div>
                        </div>

                    <?php endforeach; ?>
                    </div>
                    <div><strong>Куриер:</strong> <?php echo esc_html($courier_name); ?></div>
                    <div><strong>Телефон:</strong> <?php echo esc_html($courier_phone); ?></div>
                    <div><strong>Съобщение:</strong> <?php echo esc_html($courier_msg); ?></div>
                </div>

            </div>
           <style>
               .leaflet-routing-container {
                   display: none !important;
               }

                .timeline {
                    position: relative;
                    max-width: 800px;
                    margin: 30px auto;
                    padding-left: 40px;
                }

               .timeline-item {
                   position: relative;
                   margin-bottom: 15px;
                   display: flex;
                   align-items: flex-start; /* важна част! */
               }
               .timeline-dot {
                   position: absolute;
                   left: -28px;
                   top: 9px;
                   width: 16px;
                   height: 16px;
                   background-color: #0073aa;
                   border: 3px solid #fff;
                   border-radius: 50%;
                   z-index: 1;
                   box-shadow: 0 0 0 2px #0073aa;
               }

               .timeline-content {
                   flex: 1;
                   background: #f9f9f9;
                   padding: 0 10px 14px 10px;
                   border-radius: 8px;
                   box-shadow: 0 2px 4px rgba(0,0,0,0.1);
               }

               .timeline-city {
                   font-weight: bold;
                   margin-bottom: 4px;
               }

               .timeline-address {
                   color: #333;
                   margin-bottom: 4px;
               }

               .timeline-date {
                   color: #666;
                   font-size: 0.9em;
               }

               .timeline-item:not(:last-child)::after {
                   content: '';
                   position: absolute;
                   left: -20px;
                   top: 22px;
                   bottom: -25px;
                   width: 2px;
                   background-color: #ddd;
                   z-index: 0;
               }
               .toggle-routing-btn {
                   color:#0A1A3D !important;
               }
               .leaflet-routing-alt  table td,.leaflet-routing-alt  table th {
                   border: 1px solid hsla(0,0%,50%,.502);
                   line-height: 1.5;
                   padding: 5px;
                   vertical-align: top;
               }

           </style>
            <script>

              document.addEventListener('DOMContentLoaded', function(){

                const routePoints = <?php echo json_encode($route_points); ?>;
                if (!routePoints.length) return;

                const latlngs = routePoints
                  .filter(p => p.lat && p.lng)
                  .map(p => [parseFloat(p.lat), parseFloat(p.lng)]);

                if (!latlngs.length) return;

                const map = L.map('shipment-map');
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                  attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                map.fitBounds(latlngs, {padding:[50,50]});

                // Popup markers (only shown on click)

                latlngs.forEach((coords, index) => {
                  const point = routePoints[index];

                  L.marker(coords)
                    .addTo(map)
                    .bindPopup(`
                <strong>${point.city}</strong><br>
                ${point.address}
            `);
                  // not use openPopup()
                });
                // Routing
                L.Routing.control({
                  waypoints: latlngs.map(l => L.latLng(l[0], l[1])),
                  routeWhileDragging:false,
                  draggableWaypoints:false,
                  addWaypoints:false,
                  lineOptions:{styles:[{color:'blue',opacity:0.7,weight:5}]},
                  createMarker:function(){ return null; }
                }).on('routesfound', function(e){

                  const route = e.routes[0];

                  const totalDistance = (route.summary.totalDistance / 1000).toFixed(1);
                  const totalTime = route.summary.totalTime;

                  const hours = Math.floor(totalTime / 3600);
                  const minutes = Math.floor((totalTime % 3600) / 60);

                  const summaryBox = document.getElementById('route-summary-box');

                  if (summaryBox) {
                    summaryBox.innerHTML = `
                <div style="background:#f3f4f6;border-radius:8px; padding-bottom: 10px;">
                    <strong>Маршрут:</strong><br>
                    Разстояние: ${totalDistance} км<br>
                    Очаквано време: ${hours}ч ${minutes}мин
                </div>
            `;
                  }

                }).addTo(map);

              });

            </script>

        <?php
        endwhile;

        wp_reset_postdata();

    else :
        echo "<p>Няма намерена пратка.</p>";
    endif;

    return ob_get_clean();
});
add_filter('admin_footer_text', function($text) {

    $screen = get_current_screen();

    if ($screen && $screen->post_type === 'shipment') {
        return '';
    }

    return $text;
});
