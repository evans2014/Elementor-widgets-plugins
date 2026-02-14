document.addEventListener("DOMContentLoaded", function() {
  console.log('admin');
  // Card initialization
  const map = L.map("map").setView([42.7, 23.3], 7);
  // OpenStreetMap tiles
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap"
  }).addTo(map);

  let layers = [];
  let currentMarker = null;
  let routingControl = null;
  let routeBounds = null;

  //Card clearing function
  function clearMap() {
    layers.forEach(l => map.removeLayer(l));
    layers = [];
    if(currentMarker) map.removeLayer(currentMarker);
    currentMarker = null;
    if(routingControl) map.removeControl(routingControl);
    routingControl = null;
  }

  // When you click on the Track button
  document.getElementById("track-btn").addEventListener("click", function() {
    const code = document.getElementById("shipping-id").value.trim();
    if(!code) return;

    fetch(ShipmentAjax.ajaxurl + "?action=get_shipment&shipping_id=" + encodeURIComponent(code))
      .then(r => r.json())
      .then(res => {
        if(!res.success){ alert("Shipment not found"); return; }
        clearMap();

        const points = res.data.route;       // [[lat,lng], ...]
        const details = res.data.details;    // [{city,address,datetime,lat,lng,status}, ...]

        if(!points || points.length === 0) return;

        const now = new Date();

        // Routing Leaflet Routing Machine
        if(points.length > 1){
          routingControl = L.Routing.control({
            waypoints: points.map(p => L.latLng(p[0], p[1])),
            router: L.Routing.osrmv1({ serviceUrl:'https://router.project-osrm.org/route/v1' }),
            show:false,
            addWaypoints:false,
            draggableWaypoints:false,
            routeWhileDragging:false,
            lineOptions:{styles:[{color:'#3b5bfd',weight:5}]}
          }).addTo(map);

          // When the route is calculated
          routingControl.on('routesfound', function(e) {
            const route = e.routes[0];
            routeBounds = L.latLngBounds(route.coordinates);
            map.fitBounds(routeBounds);
          });

        } else {
          map.setView(points[0], 12);
        }

        // Markers for each point with popup
        details.forEach((d, idx) => {
          if(!points[idx]) return;
          const m = L.marker([d.lat, d.lng]).bindPopup(
            `<strong>${d.city}</strong><br>${d.address}<br>${d.datetime}<br>${d.status}`
          ).addTo(map);
          layers.push(m);
        });

        // Current marker – the last or current point
        let currentIdx = details.findIndex(d => new Date(d.datetime) > now);
        if(currentIdx === -1) currentIdx = details.length - 1;

        currentMarker = L.marker([details[currentIdx].lat, details[currentIdx].lng], {
          icon: L.icon({
            iconUrl: ShipmentAjax.plugin_url + 'truck.png', // сложи път към иконата на камиона
            iconSize: [32,32],
            iconAnchor: [16,16]
          })
        }).addTo(map);

        // Timeline under the map
        const timeline = document.getElementById("shipment-timeline");
        timeline.innerHTML = "";
        details.forEach((d, idx) => {
          const circle = document.createElement("div");
          circle.style.width = "30px";
          circle.style.height = "30px";
          circle.style.borderRadius = "50%";
          circle.style.display = "flex";
          circle.style.alignItems = "center";
          circle.style.justifyContent = "center";
          circle.style.color = "#fff";
          circle.style.cursor = "pointer";
          circle.textContent = idx+1;
          const pointTime = new Date(d.datetime);
          circle.style.background = now >= pointTime ? "#28a745" : "#3b5bfd";

          circle.title = `${d.city}\n${d.address}\n${d.datetime}\n${d.status}`;
          circle.addEventListener("click", () => {
            map.setView([d.lat,d.lng],12,{animate:true});
            L.popup().setLatLng([d.lat,d.lng]).setContent(circle.title).openOn(map);
          });
          timeline.appendChild(circle);
        });

        // Details under the map
        const ul = document.getElementById("shipment-details");
        ul.innerHTML = "";
        details.forEach(d => {
          const li = document.createElement("li");
          li.textContent = `${d.city}: ${d.datetime} - ${d.address} - ${d.status}`;
          ul.appendChild(li);
        });

        // Courier info
        document.getElementById("courier-name").textContent = res.data.courier.name;
        document.getElementById("courier-phone").textContent = res.data.courier.phone;
        document.getElementById("courier-message").textContent = res.data.courier.message;

        // Shipment info
        document.getElementById("shipment-id").textContent = res.data.tracking_number;
        document.getElementById("shipment-status").textContent = res.data.status;
        document.getElementById("shipment-departure").textContent = res.data.departure_time;
        document.getElementById("shipment-arrival").textContent = res.data.arrival_time;

        // Remaining time
        const arrivalTime = new Date(details[details.length-1].datetime);
        let remainingMs = arrivalTime - now;
        if(remainingMs<0) remainingMs=0;
        const hours = Math.floor(remainingMs / 1000 / 60 / 60);
        const mins = Math.floor((remainingMs / 1000 / 60) % 60);
        document.getElementById("shipment-remaining").textContent = `${hours}h ${mins}m`;

      }).catch(err=>{
      console.error("AJAX error", err);
    });
  });

});
