<?php
if (!defined('ABSPATH')) exit;

get_header();

global $post;

$route_points = get_post_meta($post->ID, '_shipment_route_points', true) ?: [];
$tracking_number = get_post_meta($post->ID,'_shipment_tracking_number',true);
$status = get_post_meta($post->ID,'_shipment_status',true);
$courier_name = get_post_meta($post->ID,'_courier_name',true);
$courier_phone = get_post_meta($post->ID,'_courier_phone',true);
$courier_message = get_post_meta($post->ID,'_courier_message',true);
?>

<div class="shipment-main" >
    <div class="shipment-column-left" >
        <div id="shipment-map" style="height:900px; width:100%;"></div>
    </div>
    <div class="shipment-column-right" >
        <h4>Shipment Information</h4>
        <div><strong>Tracking Number:</strong> <?php echo esc_html($tracking_number); ?></div>
        <div><strong>Status:</strong> <?php echo esc_html($status); ?></div>
        <div><strong>Courier:</strong> <?php echo esc_html($courier_name); ?></div>
        <div><strong>Phone:</strong> <?php echo esc_html($courier_phone); ?></div>
        <div><strong>Message:</strong> <?php echo esc_html($courier_message); ?></div>
        <hr>
        <div id="route-summary"></div>
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



</style>

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
    //routePoints.forEach(point => {
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
<?php get_footer(); ?>
