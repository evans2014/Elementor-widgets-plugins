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
