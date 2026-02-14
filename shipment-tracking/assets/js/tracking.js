document.addEventListener('DOMContentLoaded', () => {
    const map = L.map('map').setView([38.9, -77.0], 10);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let routeLine;

    document.getElementById('track-btn').addEventListener('click', () => {
        fetch(ajaxurl + '?action=get_shipment')
            .then(res => res.json())
            .then(data => {
                if (routeLine) map.removeLayer(routeLine);

                routeLine = L.polyline(data.route, {
                    color: '#3b5bfd',
                    weight: 5
                }).addTo(map);

                map.fitBounds(routeLine.getBounds());

                document.getElementById('shipment-info').innerHTML = `
                    <strong>Status:</strong> ${data.status}
                `;
            });
    });
});
