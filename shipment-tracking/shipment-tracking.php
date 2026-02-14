<?php
/**
 * Plugin Name: Shipment Tracking – Real Route + City Points (OSM)
 * Description: Shipment tracking with OpenStreetMap, OSRM routing and city markers
 * Version: 1.3
 */

if (!defined('ABSPATH')) exit;

/* -------------------------------------------------
   1. REGISTER CPT
------------------------------------------------- */
add_action('init', function () {
    register_post_type('shipment', [
        'labels' => [
            'name' => 'Shipments',
            'singular_name' => 'Shipment',
        ],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-location-alt',
        'supports' => ['title'],
    ]);
});

/* -------------------------------------------------
   2. META BOX
------------------------------------------------- */
add_action('add_meta_boxes', function () {
    add_meta_box('shipment_details', 'Shipment Details', 'shipment_meta_box', 'shipment');
});

function shipment_meta_box($post) {
    $tracking = get_post_meta($post->ID, 'tracking_number', true);
    $route = get_post_meta($post->ID, 'route', true);
    ?>
    <p>
        <label><strong>Shipping Number</strong></label><br>
        <input type="text" name="tracking_number"
               value="<?= esc_attr($tracking) ?>"
               placeholder="#BG-0001"
               style="width:100%">
    </p>

    <p>
        <label><strong>Route (START → cities → END)</strong></label>
        <textarea name="shipment_route" rows="5" style="width:100%"><?= esc_textarea($route) ?></textarea>
        <small>
            Example:<br>
            [
            [42.6977,23.3219],
            [42.2666,23.4000],
            [42.1354,24.7453],
            [42.5048,27.4626]
            ]
        </small>
    </p>
    <?php
}

/* -------------------------------------------------
   3. SAVE META
------------------------------------------------- */
add_action('save_post', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (isset($_POST['tracking_number'])) {
        update_post_meta($post_id, 'tracking_number', sanitize_text_field($_POST['tracking_number']));
    }

    if (isset($_POST['shipment_route'])) {
        update_post_meta($post_id, 'route', wp_kses_post($_POST['shipment_route']));
    }
});

/* -------------------------------------------------
   4. FRONTEND ASSETS
------------------------------------------------- */
add_action('wp_enqueue_scripts', function () {

    wp_enqueue_style('leaflet-css',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
    );

    wp_enqueue_script('leaflet-js',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        [],
        null,
        true
    );

    wp_add_inline_style('leaflet-css', '
        #map{height:450px;border-radius:14px;margin-top:15px}
        .shipment-wrapper{max-width:900px}
        input,button{padding:8px}

        .start-marker,.end-marker,.city-marker{
            width:22px;height:22px;border-radius:50%;
            display:flex;align-items:center;justify-content:center;
            font-size:12px;font-weight:bold;color:#fff
        }
        .start-marker{background:#28a745}
        .end-marker{background:#dc3545}
        .city-marker{background:#0d6efd}
    ');

    wp_add_inline_script('leaflet-js', '
    document.addEventListener("DOMContentLoaded", function () {

        const map = L.map("map").setView([42.7, 23.3], 7);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap"
        }).addTo(map);

        const startIcon = L.divIcon({
            html: "<div class=\"start-marker\">✔</div>",
            className: "",
            iconSize: [22,22]
        });

        const endIcon = L.divIcon({
            html: "<div class=\"end-marker\">📦</div>",
            className: "",
            iconSize: [22,22]
        });

        const cityIcon = L.divIcon({
            html: "<div class=\"city-marker\">●</div>",
            className: "",
            iconSize: [18,18]
        });

        let layers = [];
        let cityMarkers = [];
        let currentCityIndex = 0;
        let truck = null;
        let animIndex = 0;
        let animTimer = null;

        document.getElementById("track-btn").onclick = function () {

            const code = document.getElementById("shipping-id").value.trim();

            fetch("' . admin_url('admin-ajax.php') . '?action=get_shipment&shipping_id=" + encodeURIComponent(code))
                .then(r => r.json())
                .then(res => {

                    layers.forEach(l => map.removeLayer(l));
                    layers = [];

                    if (!res.success) {
                        alert("Shipment not found");
                        return;
                    }

                    const route = res.data.route;

                    // OSRM – real route via ALL points
                    const coords = route.map(p => p[1] + "," + p[0]).join(";");

                    const osrmUrl =
                        "https://router.project-osrm.org/route/v1/driving/" +
                        coords +
                        "?overview=full&geometries=geojson";

                    fetch(osrmUrl)
                        .then(r => r.json())
                        .then(osrm => {

                            if (!osrm.routes || !osrm.routes.length) {
                                alert("Route not found");
                                return;
                            }

                            const realRoute = osrm.routes[0].geometry.coordinates
                                .map(c => [c[1], c[0]]);
                                
                             // ETA + KM
                            const km = (osrm.routes[0].distance / 1000).toFixed(1);
                            const min = Math.round(osrm.routes[0].duration / 60);
                            
                            document.getElementById("km").innerText = km;
                            document.getElementById("eta").innerText = min;
                            
                            // REMOVE OLD TRUCK
                            if (truck) {
                                map.removeLayer(truck);
                                clearTimeout(animTimer);
                            }
                            
                            // CREATE TRUCK
                            truck = L.marker(realRoute[0], {
                                icon: L.divIcon({
                                    html: "🚚",
                                    className: "",
                                    iconSize: [30,30]
                                })
                            }).addTo(map);
                            
                            animIndex = 0;
                            
                            // ANIMATION FUNCTION
                            /*function animateTruck() {
                                if (animIndex >= realRoute.length) return;
                                truck.setLatLng(realRoute[animIndex]);
                                animIndex++;
                                animTimer = setTimeout(animateTruck, 120);
                            }*/
                            function animateTruck() {
                            if (animIndex >= realRoute.length) return;
                        
                            truck.setLatLng(realRoute[animIndex]);
                        
                            // progress % по маршрута
                            const progress = animIndex / realRoute.length;
                            const cityStep = Math.floor(progress * (cityMarkers.length - 1));
                        
                            if (cityStep !== currentCityIndex) {
                                currentCityIndex = cityStep;
                        
                                cityMarkers.forEach((m, i) => {
                                    if (i < currentCityIndex) {
                                        m.setPopupContent("✅ Arrived");
                                    } else if (i === currentCityIndex) {
                                        m.setPopupContent("🚚 In transit");
                                        m.openPopup();
                                    } else {
                                        m.setPopupContent("📍 Next stop");
                                    }
                                });
                            }
                        
                            animIndex++;
                            animTimer = setTimeout(animateTruck, 120);
                        }

                            
                            animateTruck();


                            const line = L.polyline(realRoute, {
                                color: "#3b5bfd",
                                weight: 5
                            }).addTo(map);

                            layers.push(line);

                           /* // START
                            layers.push(
                                L.marker(route[0], {icon: startIcon}).addTo(map)
                            );

                            // CITY WAYPOINTS
                            for (let i = 1; i < route.length - 1; i++) {
                                layers.push(
                                    L.marker(route[i], {icon: cityIcon}).addTo(map)
                                );
                            }

                            // END
                            layers.push(
                                L.marker(route[route.length - 1], {icon: endIcon}).addTo(map)
                            );*/
                            
                            cityMarkers = [];
                            currentCityIndex = 0;
                            
                            // START
                            const startMarker = L.marker(route[0], {icon: startIcon})
                                .bindPopup("🚚 Departed from start")
                                .addTo(map);
                            
                            cityMarkers.push(startMarker);
                            layers.push(startMarker);
                            
                            // WAYPOINTS
                            for (let i = 1; i < route.length - 1; i++) {
                                const m = L.marker(route[i], {icon: cityIcon})
                                    .bindPopup("📍 Next stop")
                                    .addTo(map);
                            
                                cityMarkers.push(m);
                                layers.push(m);
                            }
                            
                            // END
                            const endMarker = L.marker(route[route.length - 1], {icon: endIcon})
                                .bindPopup("📦 Destination")
                                .addTo(map);
                            
                            cityMarkers.push(endMarker);
                            layers.push(endMarker);


                            map.fitBounds(line.getBounds());
                        });
                });
        };
    });
    ');
});

/* -------------------------------------------------
   5. SHORTCODE
------------------------------------------------- */
add_shortcode('shipment_tracking', function () {
    ob_start();

    $args = array(
        'post_type'      => 'shipment',
        'posts_per_page' => -1,
        'fields'         => 'ids', // 👈 only return IDs
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        foreach ($query->posts as $post_id) {

            $tracking = get_post_meta($post_id, 'tracking_number', true);
            echo $tracking . '<br>';
        }
    }
    //
    ?>

    <div class="shipment-wrapper">
        <input type="text" id="shipping-id" placeholder="Enter Shipping Number">
        <button id="track-btn">Track</button>

        <div id="eta-panel" style="margin-top:10px;font-weight:bold">
            Distance: <span id="km">–</span> km |
            ETA: <span id="eta">–</span> min
        </div>

        <div id="map"></div>
    </div>

    <?php
    return ob_get_clean();
});

/* -------------------------------------------------
   6. AJAX – SEARCH BY SHIPPING NUMBER
------------------------------------------------- */
add_action('wp_ajax_get_shipment', 'get_shipment');
add_action('wp_ajax_nopriv_get_shipment', 'get_shipment');

function get_shipment() {

    $tracking = sanitize_text_field($_GET['shipping_id'] ?? '');

    $q = new WP_Query([
        'post_type' => 'shipment',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => 'tracking_number',
                'value' => $tracking,
                'compare' => '='
            ]
        ]
    ]);

    if (!$q->have_posts()) {
        wp_send_json_error();
    }

    $post = $q->posts[0];

    wp_send_json_success([
        'route' => json_decode(get_post_meta($post->ID, 'route', true))
    ]);
}
