<?php
if (!defined('ABSPATH')) exit;

get_header();

global $post;

$route_points = get_post_meta($post->ID, '_shipment_route_points', true) ?: [];
$tracking_number = get_post_meta($post->ID,'_shipment_tracking_number',true);
$status = get_post_meta($post->ID,'_shipment_status',true);
$total_time = get_post_meta($post->ID, '_total_time', true);
$courier_name = get_post_meta($post->ID,'_courier_name',true);
$courier_phone = get_post_meta($post->ID,'_courier_phone',true);
$courier_message = get_post_meta($post->ID,'_courier_message',true);
$google_api_key = get_option('shipment_google_maps_api', '');
?>

<div class="shipment-main">
    <div class="shipment-column-left" >
        <div id="shipment-map" <!--style="height:800px; width:100%;"-->></div>
    </div>
    <div class="shipment-column-right">
        <h4>Shipment Information</h4>
        <div><strong>Tracking Number:</strong> <?php echo esc_html($tracking_number); ?></div>
        <div><strong>Status:</strong> <?php echo esc_html($status); ?></div>
        <div><strong>Courier:</strong> <?php echo esc_html($courier_name); ?></div>
        <div><strong>Phone:</strong> <?php echo esc_html($courier_phone); ?></div>
        <div><strong>Message:</strong> <?php echo esc_html($courier_message); ?></div>
        <hr>
        <div id="route-summary"></div>
        <h5>Detail</h5>
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
        <h5>Timeline</h5>

        <div class="timeline">
            <?php foreach($route_points as $point): ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-city"><strong><?php echo esc_html($point['city']); ?></strong></div>
                        <div class="timeline-date">
                            <?php echo date('j-m-y H:i', strtotime($point['datetime'])); ?>
                        </div>
                        <div class="timeline-address"><?php echo esc_html($point['address']); ?></div>
                        <div class="timeline-address"><?php echo esc_html($point['status']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>
<style>
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
<?php
if($google_api_key) {

    ?>
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
            strokeColor: "#0e59f0",
            strokeWeight: 4,
            strokeOpacity: 1
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
        // Custom circle markers

        let activeInfoWindow = null; // ще държи текущо отворения popup

        routePoints.forEach((point, index) => {

          let color = "#2563eb"; // blue default
          if (index === 0) {
            color = "#16a34a"; // green
          } else if (index === routePoints.length - 1) {
            color = "#dc2626"; // red
          }

          const marker = new google.maps.Marker({
            position: {
              lat: parseFloat(point.lat),
              lng: parseFloat(point.lng)
            },
            map: map,
            icon: {
              path: google.maps.SymbolPath.CIRCLE,
              scale: 12,
              fillColor: color,
              fillOpacity: 1,
              strokeColor: "#ffffff",
              strokeWeight: 3
            }
          });

          const formattedDate = new Date(point.datetime).toLocaleString('en-US', {
            month: 'numeric',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
          });

          const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="min-width:200px">
                    <strong>${point.city}</strong><br>
                    ${point.address ?? ''}<br>
                    ${formattedDate ?? ''}<br>
                    ${point.status ?? ''}
                </div>
            `
          });

          marker.addListener("click", function() {

            // Close popup
            if (activeInfoWindow) {
              activeInfoWindow.close();
            }

            infoWindow.open(map, marker);
            activeInfoWindow = infoWindow;
          });
        });
      });

    </script>
    <?php } else { ?>

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
          const coordinates = route.coordinates;
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
      `;

          // Truck icon
          const truckIcon = L.icon({
            iconUrl: "<?php echo plugin_dir_url(__FILE__); ?>../assets/img/truck.png",
            iconSize: [40,40],
            iconAnchor: [20,20]
          });

          if (truckMarker) map.removeLayer(truckMarker);
          truckMarker = L.marker(coordinates[0], {icon: truckIcon}).addTo(map);

          let index = 0;
          if (truckInterval) clearInterval(truckInterval);

          truckInterval = setInterval(function(){
            if (index >= coordinates.length) {
              clearInterval(truckInterval);
              return;
            }
            truckMarker.setLatLng(coordinates[index]);
            index++;
          }, 80);
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

    <?php } ?>

<?php get_footer(); ?>
