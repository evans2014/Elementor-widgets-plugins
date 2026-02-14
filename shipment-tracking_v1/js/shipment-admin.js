jQuery(document).ready(function($){
  const $routeList = $('#route-points-list');
  const $addBtn = $('#add-route-point');

  // Инициализация на картата
  const map = L.map('shipment-map').setView([42.7, 23.3], 7);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  const markers = [];
  let routingControl = null;

  function updateRoute() {
    const waypoints = markers.map(m => L.latLng(m.getLatLng()));
    if (routingControl) { map.removeControl(routingControl); routingControl = null; }
    if (waypoints.length < 2) return;

    routingControl = L.Routing.control({
      waypoints: waypoints,
      routeWhileDragging: false,
      draggableWaypoints: true,
      addWaypoints: false,
      lineOptions: { styles: [{ color:'blue', weight:4, opacity:0.7 }] },
      createMarker: () => null,
      show: false
    }).addTo(map);

  }

  function geocodeAddress($li) {
    const city = $li.find('input[name*="[city]"]').val();
    const address = $li.find('input[name*="[address]"]').val();
    if(!city && !address) return;

    const query = encodeURIComponent(`${address}, ${city}`);
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&accept-language=en`)
      .then(r=>r.json()).then(data=>{
      if(data && data.length){
        const lat = parseFloat(data[0].lat);
        const lng = parseFloat(data[0].lon);
        $li.find('input[name*="[lat]"]').val(lat.toFixed(6));
        $li.find('input[name*="[lng]"]').val(lng.toFixed(6));

        if($li.data('marker')){
          map.removeLayer($li.data('marker'));
          const idx = markers.indexOf($li.data('marker'));
          if(idx>-1) markers.splice(idx,1);
        }

        const marker = L.marker([lat,lng], {draggable:true}).addTo(map);
        marker.on('dragend', e=>{
          const pos = e.target.getLatLng();
          $li.find('input[name*="[lat]"]').val(pos.lat.toFixed(6));
          $li.find('input[name*="[lng]"]').val(pos.lng.toFixed(6));
          updateRoute();
        });

        $li.data('marker', marker);
        markers.push(marker);
        updateRoute();
        map.fitBounds(L.featureGroup(markers).getBounds().pad(0.2));
      }
    }).catch(console.error);
  }

  function createRouteItem(index, point={}) {
    const city = point.city||'', address=point.address||'';
    const datetime=point.datetime||'', lat=point.lat||'', lng=point.lng||'', status=point.status||'pending';
    const $li = $(`
            <li class="route-point-item" data-index="${index}">
                <input type="text" name="shipment_route[${index}][city]" value="${city}" placeholder="City" />
                <input type="text" name="shipment_route[${index}][address]" value="${address}" placeholder="Address" />
                <input type="datetime-local" name="shipment_route[${index}][datetime]" value="${datetime}" />
                <input type="text" name="shipment_route[${index}][lat]" value="${lat}" readonly placeholder="Latitude" />
                <input type="text" name="shipment_route[${index}][lng]" value="${lng}" readonly placeholder="Longitude" />
                <select name="shipment_route[${index}][status]">
                    <option value="pending" ${status==='pending'?'selected':''}>Pending</option>
                    <option value="done" ${status==='done'?'selected':''}>Done</option>
                </select>
                <button type="button" class="remove-route-point button">Remove</button>
            </li>
        `);
    $li.find('input[name*="[city]"], input[name*="[address]"]').on('change', ()=>geocodeAddress($li));
    return $li;
  }

  $addBtn.click(()=>{
    const index = $routeList.find('li').length;
    const $item = createRouteItem(index);
    $routeList.append($item);
    updateIndices();
    geocodeAddress($item);
  });

  $routeList.on('click', '.remove-route-point', function(){
    const $li = $(this).closest('li');
    if($li.data('marker')){
      map.removeLayer($li.data('marker'));
      const idx = markers.indexOf($li.data('marker'));
      if(idx>-1) markers.splice(idx,1);
    }
    $li.remove();
    updateIndices();
    updateRoute();
  });

  function updateIndices(){
    $routeList.find('li').each(function(index){
      const $li = $(this);
      $li.attr('data-index', index);
      $li.find('input,select').each(function(){
        const name = $(this).attr('name');
        $(this).attr('name', name.replace(/shipment_route\[\d+\]/, `shipment_route[${index}]`));
      });
    });
  }

  // Инициализация на съществуващите точки
  $routeList.find('li').each(function(){
    const $li = $(this);
    const lat = parseFloat($li.find('input[name*="[lat]"]').val());
    const lng = parseFloat($li.find('input[name*="[lng]"]').val());
    if(!isNaN(lat)&&!isNaN(lng)){
      const marker = L.marker([lat,lng], {draggable:true}).addTo(map);
      marker.on('dragend', e=>{
        const pos = e.target.getLatLng();
        $li.find('input[name*="[lat]"]').val(pos.lat.toFixed(6));
        $li.find('input[name*="[lng]"]').val(pos.lng.toFixed(6));
        updateRoute();
      });
      $li.data('marker', marker);
      markers.push(marker);
    } else {
      geocodeAddress($li);
    }
  });

  // Задържане на размера на картата
  setTimeout(()=>{
    map.invalidateSize();
    updateRoute();
  }, 500);

  // Ако meta box се колапсне/разгърне
  $('#poststuff').on('click', '.handlediv', ()=>{
    setTimeout(()=>{
      map.invalidateSize();
      updateRoute();
    }, 500);
  });
});
