<?php

add_shortcode('shipment_grid', 'shipment_grid_shortcode');

function shipment_grid_shortcode() {

    $query = new WP_Query([
        'post_type' => 'shipment',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
    ob_start();
    echo '<div class="shipment-grid">';

    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post();

            $tracking = get_post_meta(get_the_ID(), '_shipment_tracking_number', true);
            $status   = get_post_meta(get_the_ID(), '_shipment_status', true);

            echo '<div class="shipment-card">';
            echo '<h3>' . get_the_title() . '</h3>';
            echo '<p><strong>Tracking:</strong> ' . esc_html($tracking) . '</p>';
            echo '<p><strong>Status:</strong> ' . esc_html($status) . '</p>';
            echo '<a href="' . get_permalink() . '" class="shipment-btn">View Details</a>';
            echo '</div>';

        endwhile;
    else :
        echo '<p>No shipments found.</p>';
    endif;

    echo '</div>';

    wp_reset_postdata();
    return ob_get_clean();
}

add_shortcode('shipment_search', function() {
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

            <div class="container-map">
                <div class="left-content">
                    <div id="shipment-map" style="width:100%;height:700px;"></div>
                </div>
                <div class="right-content">
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
                .shipment-blue-dot {
                    width: 14px; height: 14px;
                    background: #007bff; border-radius: 50%; border: 2px solid #fff;
                }
                .shipment-green-check {
                    width: 20px; height: 20px;
                    background: #28a745; border-radius: 50%; border: 2px solid #fff;
                    display:flex; align-items:center; justify-content:center; color:white; font-size:12px;
                }
                .shipment-green-check::after { content: "✔"; }

                .shipment-red-flag {
                    width: 20px; height: 20px;
                    background: #dc3545; border-radius: 50%; border: 2px solid #fff;
                    display:flex; align-items:center; justify-content:center; color:white; font-size:12px;
                }
                .shipment-red-flag::after { content: "🏁"; }

                .leaflet-routing-container {
                    display:none
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
                const blueDotIcon = L.divIcon({
                  className: 'shipment-blue-dot',
                  iconSize: [24, 24],
                  iconAnchor: [10, 10]
                });

                const greenCheckIcon = L.divIcon({
                  className: 'shipment-green-check',
                  iconSize: [24, 24],
                  iconAnchor: [10, 10]
                });

                const redFlagIcon = L.divIcon({
                  className: 'shipment-red-flag',
                  iconSize: [24, 24],
                  iconAnchor: [10, 10]
                });

                latlngs.forEach((coords, index) => {
                  const point = routePoints[index];

                  let iconToUse = blueDotIcon; // по default

                  if (index === 0) {
                    iconToUse = greenCheckIcon; // първата точка
                  } else if (index === routePoints.length - 1) {
                    iconToUse = redFlagIcon; // последната точка
                  }

                  L.marker(coords, { icon: iconToUse })
                    .addTo(map)
                    .bindPopup(`
                        <strong>${point.city}</strong><br>
                        ${point.address}
                    `);
                });
                // Routing
                L.Routing.control({
                  waypoints: latlngs.map(l => L.latLng(l[0], l[1])),
                  routeWhileDragging:false,
                  draggableWaypoints:false,
                  addWaypoints:false,
                  show:false,
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
        echo do_shortcode('[shipment_search_form]');
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

add_shortcode('shipment_search_form', function() {
    // Find URL of the page with [shipment_search]
    $tracking_page = get_page_by_path('tracking-search'); // slug на страницата
    $tracking_url = $tracking_page ? get_permalink($tracking_page->ID) : '#';

    ob_start(); ?>
    <form action="<?php echo esc_url($tracking_url); ?>" method="get" class="map-search" >
        <input
                type="text"
                class="map-input"
                name="tracking"
                placeholder="Enter Tracking Number"
                required
        />
        <button type="submit" class="button button-primary">Search</button>
    </form>
    <?php
    return ob_get_clean();
});


add_shortcode('shipment_timeline', 'shipment_timeline_shortcode');

function shipment_timeline_shortcode($atts) {

    if (!is_singular('shipment')) return '';

    global $post;

    $route_points = get_post_meta($post->ID, '_shipment_route_points', true);
    if (empty($route_points)) return '<p>No route data.</p>';

    $tracking = get_post_meta($post->ID, '_shipment_tracking_number', true);
    $status = get_post_meta($post->ID, '_shipment_status', true);
    $total_time = get_post_meta($post->ID, '_total_time', true);
    $courier_name = get_post_meta($post->ID, '_courier_name', true);
    $courier_phone = get_post_meta($post->ID, '_courier_phone', true);
    $courier_message = get_post_meta($post->ID, '_courier_message', true);

    ob_start();
    ?>

    <div class="shipment-details-box">

        <div class="shipment-top-info">
            <h3>Shipment Details</h3>
            <p><strong>Tracking:</strong> <?php echo esc_html($tracking); ?></p>
            <p><strong>Status:</strong> <?php echo esc_html($status); ?></p>
            <p><strong>Total Time:</strong> <?php echo esc_html($total_time); ?></p>
            <p><strong>Courier:</strong> <?php echo esc_html($courier_name); ?></p>
            <p><strong>Phone:</strong> <?php echo esc_html($courier_phone); ?></p>
            <?php if ($courier_message): ?>
                <p><strong>Message:</strong> <?php echo esc_html($courier_message); ?></p>
            <?php endif; ?>
        </div>

        <div class="shipment-timeline">
            <?php foreach ($route_points as $index => $point): ?>

                <div class="timeline-item">
                    <div class="timeline-dot
                        <?php
                    if ($index === 0) echo 'start';
                    elseif ($index === count($route_points)-1) echo 'end';
                    else echo 'middle';
                    ?>">
                    </div>

                    <div class="timeline-content">
                        <strong><?php echo esc_html($point['city']); ?></strong><br>
                        <?php echo esc_html($point['address']); ?><br>
                        <?php echo esc_html($point['datetime']); ?><br>
                        <?php echo esc_html($point['status']); ?>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>

    </div>
    <style>
        .shipment-timeline {
            border-left: 3px solid #2563eb;
            padding-left: 20px;
        }
        .timeline-item {
            margin-bottom: 25px;
            position: relative;
        }
        .timeline-dot {
            width: 14px;
            height: 14px;
            background: #2563eb;
            border-radius: 50%;
            position: absolute;
            left: -28px;
            top: 5px;
        }
        .timeline-status {
            color: #6b7280;
            font-size: 13px;
        }
    </style>
    <?php
    return ob_get_clean();
}


add_shortcode('shipment_single', 'shipment_single_shortcode');

function shipment_single_shortcode() {

    global $post;

    $route_points = get_post_meta($post->ID, '_shipment_route_points', true);
    $api_key = get_option('shipment_google_maps_api');

    if (empty($route_points)) {
        return '<p>No route points available.</p>';
    }

    ob_start();
    ?>
    <div id="route-summary"></div>
    <div id="shipment-map" style="width:100%; height:500px;"></div>

    <?php if ($api_key) : ?>

        <!-- ========================= -->
        <!-- GOOGLE MAPS VERSION -->
        <!-- ========================= -->

        <script>
          document.addEventListener("DOMContentLoaded", function() {

            const routePoints = <?php echo json_encode($route_points); ?>;
            if (!routePoints.length) return;

            const map = new google.maps.Map(document.getElementById("shipment-map"), {
              zoom: 6,
              center: {
                lat: parseFloat(routePoints[0].lat),
                lng: parseFloat(routePoints[0].lng)
              }
            });

            const directionsService = new google.maps.DirectionsService();
            const directionsRenderer = new google.maps.DirectionsRenderer({
              suppressMarkers: true,
              polylineOptions: {
                strokeColor: "#0f172a",
                strokeWeight: 6,
                strokeOpacity: 0.9
              }
            });

            directionsRenderer.setMap(map);

            const waypoints = routePoints.slice(1, -1).map(point => ({
              location: {
                lat: parseFloat(point.lat),
                lng: parseFloat(point.lng)
              },
              stopover: true
            }));

            directionsService.route({
              origin: {
                lat: parseFloat(routePoints[0].lat),
                lng: parseFloat(routePoints[0].lng)
              },
              destination: {
                lat: parseFloat(routePoints[routePoints.length - 1].lat),
                lng: parseFloat(routePoints[routePoints.length - 1].lng)
              },
              waypoints: waypoints,
              travelMode: google.maps.TravelMode.DRIVING
            }, function(result, status) {
              if (status === "OK") {
                directionsRenderer.setDirections(result);
              }
            });

          });
        </script>

        <script async defer
                src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr($api_key); ?>">
        </script>

    <?php else : ?>

        <!-- ========================= -->
        <!-- OPENSTREETMAP VERSION -->
        <!-- ========================= -->

        <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

        <script>
          document.addEventListener('DOMContentLoaded', function(){
            const routePoints = <?php echo json_encode($route_points); ?>;
            if (!routePoints.length) return;
            const latlngs = routePoints
              .filter(p => p.lat && p.lng)
              .map(p => [parseFloat(p.lat), parseFloat(p.lng)]);

            if (!latlngs.length) return;

            // We create the map
            const map = L.map('shipment-map');
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
              attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            map.fitBounds(latlngs, {padding:[50,50]});

            const blueDotIcon = L.divIcon({
              className: 'shipment-blue-dot',
              iconSize: [24, 24],
              iconAnchor: [10, 10]
            });

            const greenCheckIcon = L.divIcon({
              className: 'shipment-green-check',
              iconSize: [24, 24],
              iconAnchor: [10, 10]
            });

            const redFlagIcon = L.divIcon({
              className: 'shipment-red-flag',
              iconSize: [24, 24],
              iconAnchor: [10, 10]
            });

            // Popup markers
            routePoints.forEach((point, index) => {

              if (!point.lat || !point.lng) return;

              const formattedDate = new Date(point.datetime).toLocaleString('en-US', {
                month: 'numeric',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
              });

              let iconToUse = blueDotIcon; // по default

              if (index === 0) {
                iconToUse = greenCheckIcon; // първата точка
              } else if (index === routePoints.length - 1) {
                iconToUse = redFlagIcon; // последната точка
              }

              L.marker(
                [parseFloat(point.lat), parseFloat(point.lng)],
                { icon: iconToUse }
              )
                .addTo(map)
                .bindPopup(`
          <strong>${point.city}</strong><br>
          ${point.address ? point.address + '<br>' : ''}
          ${formattedDate}<br>
          ${point.status ?? ''}
        `);

            });

            let truckMarker = null;
            let truckInterval = null;

            // Routing Control on the map
            const routingControl = L.Routing.control({
              waypoints: latlngs.map(l => L.latLng(l[0], l[1])),
              routeWhileDragging: false,
              draggableWaypoints: false,
              addWaypoints: false,
              lineOptions: {
                styles: [{color: 'blue', opacity: 0.7, weight: 5}]
              },
              createMarker: function(){ return null; },
              show: true // Important: shows the panel on the map
            }).addTo(map);

            routingControl.on('routesfound', function(e){

              const route = e.routes[0];
       /*       const coordinates = route.coordinates;
              const totalDistance = (route.summary.totalDistance / 1000).toFixed(1);
              const totalTime = route.summary.totalTime;
              const hours = Math.floor(totalTime / 3600);
              const minutes = Math.floor((totalTime % 3600) / 60);

              // You can show text distance/time somewhere without moving the routing-container
              document.getElementById('route-summary').innerHTML = `
                <div style="background:#f3f4f6; padding:10px; border-radius:8px; margin-bottom:20px;">
                  <strong>Маршрут:</strong><br>
                  Разстояние: ${totalDistance} км<br>
                  Очаквано време: ${hours}ч ${minutes}мин
                </div>
              `;*/
            });

            setTimeout(() => {
              const routingDiv = document.querySelector('.leaflet-routing-container');
              if(routingDiv) routingDiv.style.display = 'none';
            }, 100);

            // Toggle button for the right box
            const toggleRoutingBtn = L.control({position: 'topright'});
            toggleRoutingBtn.onAdd = function(map){
              const btn = L.DomUtil.create('button', 'toggle-routing-btn leaflet-bar');
              btn.innerHTML = 'Покажи/Скрий маршрут';
              btn.style.background = 'white';
              btn.style.cursor = 'pointer';
              btn.style.padding = '5px 10px';
              btn.style.marginBottom = '5px';

              L.DomEvent.on(btn, 'click', function(e){
                L.DomEvent.stopPropagation(e);
                L.DomEvent.preventDefault(e);

                const routingDiv = document.querySelector('.leaflet-routing-container');
                if(routingDiv){
                  routingDiv.style.display = (routingDiv.style.display === 'none') ? 'block' : 'none';
                }
              });

              return btn;
            };
            toggleRoutingBtn.addTo(map);

            // Toggle button for timeline
            const timelineBtn = document.getElementById('toggle-timeline');
            const timeline = document.getElementById('shipment-timeline');

            // Safety checks with helpful errors
            if (!timelineBtn) {
              return;
            }
            if (!timeline) {
              return;
            }

            // Robust toggle (handles CSS-hidden elements too)
            timelineBtn.addEventListener('click', () => {
              const isHidden = window.getComputedStyle(timeline).display === 'none';
              timeline.style.display = isHidden ? 'block' : 'none';
            });

          });

        </script>
        <style>
            .leaflet-control-container {
                display: none;
            }
            .city-marker {
                width: 22px;
                height: 22px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: bold;
                color: #fff;
            }

            .shipment-blue-dot {
                width: 14px;
                height: 14px;
                background: #007bff;
                border-radius: 50%;
                border: 2px solid #fff;
                box-shadow: 0 0 6px rgba(0,0,0,0.4);
            }

            .shipment-green-check {
                width: 20px;
                height: 20px;
                background: #28a745;
                border-radius: 50%;
                border: 2px solid #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 12px;
                font-weight: bold;
                box-shadow: 0 0 6px rgba(0,0,0,0.4);
            }

            .shipment-green-check::after {
                content: "✔";
            }

            .shipment-red-flag {
                width: 20px;
                height: 20px;
                background: #dc3545;
                border-radius: 50%;
                border: 2px solid #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 12px;
                font-weight: bold;
                box-shadow: 0 0 6px rgba(0,0,0,0.4);
            }

            .shipment-red-flag::after {
                content: "🏁";
            }
            @media (max-width: 900px) {
                .shipment-main  {
                    flex-direction: column;
                }
                .shipment-column-left {
                    flex: none;
                    width: 100%;
                    height:550px;
                }

                .shipment-column-right {
                    flex: none;
                    width: 100%;
                    padding: 20px;
                }
            }

        </style>

    <?php endif;

    return ob_get_clean();
}



