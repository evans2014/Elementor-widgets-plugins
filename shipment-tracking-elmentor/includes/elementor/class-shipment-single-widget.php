<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Shipment_Single_Widget extends Widget_Base {

    public function get_name() {
        return 'shipment_single';
    }

    public function get_title() {
        return 'Single Shipment';
    }

    public function get_icon() {
        return 'eicon-map-pin';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function register_controls() {

        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Settings',
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'shipment_id',
            [
                'label' => 'Shipment Post ID',
                'type' => Controls_Manager::NUMBER,
                'description' => 'Leave empty to auto detect current shipment',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {

        $settings = $this->get_settings_for_display();

        $post_id = $settings['shipment_id'];

        if (!$post_id && is_singular('shipment')) {
            $post_id = get_the_ID();
        }

        if (!$post_id) {
            echo '<p>No shipment selected.</p>';
            return;
        }

        $tracking = get_post_meta($post_id, '_shipment_tracking_number', true);
        $route_points = get_post_meta($post_id, '_shipment_route_points', true);

        echo '<div class="shipment-single">';
        echo '<div id="shipment-map" style="width:100%; height:600px;"></div>';

        echo '</div>';

        ?>

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

        <?php
    }
}
