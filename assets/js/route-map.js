/**
 * Leaflet route map — numbered stops, depot, polyline.
 */
(function () {
    'use strict';

    function initRouteMap(container) {
        if (!container || typeof L === 'undefined') return;

        let data;
        try {
            data = JSON.parse(container.dataset.route || '{}');
        } catch (e) {
            return;
        }

        const depot = data.depot || { lat: 5.6037, lng: -0.1870, label: 'Depot' };
        const stops = data.stops || [];
        if (!depot.lat || !depot.lng) return;

        const map = L.map(container, { scrollWheelZoom: true }).setView([depot.lat, depot.lng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        if (isDark) {
            container.style.filter = 'brightness(0.85) contrast(1.1)';
        }

        const depotIcon = L.divIcon({
            className: '',
            html: '<div class="route-marker-depot">DEPOT</div>',
            iconSize: [48, 24],
            iconAnchor: [24, 12],
        });
        L.marker([depot.lat, depot.lng], { icon: depotIcon })
            .addTo(map)
            .bindPopup('<strong>' + (depot.label || 'Starting Point') + '</strong>');

        const latLngs = [[depot.lat, depot.lng]];

        stops.forEach(function (stop) {
            if (!stop.lat || !stop.lng) return;

            const num = stop.order || '';
            const icon = L.divIcon({
                className: '',
                html: '<div class="route-marker-label">' + num + '</div>',
                iconSize: [28, 28],
                iconAnchor: [14, 14],
            });

            const gpsBadge = stop.location_source === 'gps'
                ? '<span style="color:#16a34a">GPS</span>'
                : '<span style="color:#ca8a04">Estimated location</span>';

            L.marker([stop.lat, stop.lng], { icon: icon })
                .addTo(map)
                .bindPopup(
                    '<strong>' + num + '. ' + (stop.customer_name || 'Customer') + '</strong><br>' +
                    (stop.address || '') + '<br>' +
                    (stop.bin_size ? stop.bin_size.toUpperCase() + ' bin · ' : '') +
                    gpsBadge
                );

            latLngs.push([stop.lat, stop.lng]);
        });

        latLngs.push([depot.lat, depot.lng]);

        if (latLngs.length > 1) {
            L.polyline(latLngs, {
                color: '#16a34a',
                weight: 4,
                opacity: 0.75,
                dashArray: stops.length > 20 ? '8 6' : null,
            }).addTo(map);

            map.fitBounds(L.latLngBounds(latLngs), { padding: [40, 40] });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('#routeMap, .route-map-container[data-route]').forEach(initRouteMap);
    });
})();
