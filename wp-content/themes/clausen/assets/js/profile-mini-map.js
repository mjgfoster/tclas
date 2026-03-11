/**
 * Profile mini-map — static Leaflet map showing a member's ancestral communes.
 *
 * Reads `window.tclasProfileMap`:
 *   - communes: [{ name, canton, lat, lng, surnames[] }]
 *   - tileUrl:  Mapbox raster tile URL (falls back to CartoDB Positron)
 *
 * The map is non-interactive (no scroll-zoom, no dragging) — purely decorative.
 *
 * @package TCLAS
 */
(function () {
	'use strict';

	var cfg = window.tclasProfileMap;
	if (!cfg || !cfg.communes || !cfg.communes.length) return;

	var el = document.getElementById('tclas-profile-map');
	if (!el) return;

	// Tile layer: Mapbox or CartoDB Positron fallback.
	var tileUrl = cfg.tileUrl ||
		'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
	var tileAttr = cfg.tileUrl
		? '&copy; <a href="https://www.mapbox.com/">Mapbox</a> &copy; <a href="https://www.openstreetmap.org/">OSM</a>'
		: '&copy; <a href="https://carto.com/">CARTO</a> &copy; <a href="https://www.openstreetmap.org/">OSM</a>';

	var map = L.map(el, {
		zoomControl:     false,
		scrollWheelZoom: false,
		dragging:        false,
		touchZoom:       false,
		doubleClickZoom: false,
		boxZoom:         false,
		keyboard:        false,
		attributionControl: false
	});

	L.tileLayer(tileUrl, { attribution: tileAttr, maxZoom: 18 }).addTo(map);

	// Crimson markers.
	var markerStyle = {
		radius:      6,
		fillColor:   '#8B3A3A',
		color:       '#FFFFFF',
		weight:      2,
		fillOpacity: 0.9
	};

	var bounds = L.latLngBounds();
	cfg.communes.forEach(function (c) {
		var ll = L.latLng(c.lat, c.lng);
		bounds.extend(ll);

		var tooltip = c.name;
		if (c.surnames && c.surnames.length) {
			tooltip += ': ' + c.surnames.join(', ');
		}

		L.circleMarker(ll, markerStyle)
			.bindTooltip(tooltip, { direction: 'top', offset: [0, -6] })
			.addTo(map);
	});

	// Fit bounds with padding, clamped to reasonable zoom.
	if (bounds.isValid()) {
		map.fitBounds(bounds, { padding: [30, 30], maxZoom: 12 });
	}
})();
