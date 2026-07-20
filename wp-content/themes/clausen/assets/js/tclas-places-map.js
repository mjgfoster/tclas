/* TCLAS Luxembourg in America — places map (Leaflet initialisation)
 * Depends on: leaflet.js, tclasPlacesData (localised by places-map.php)
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('tclas-places-map');
    if (!el) return;
    if (typeof L === 'undefined') {
      console.warn('TCLAS Places Map: Leaflet not loaded.');
      return;
    }

    var data   = (typeof tclasPlacesData !== 'undefined') ? tclasPlacesData : {};
    var places = data.places || [];
    var types  = data.types || {};

    // ── Empty state ───────────────────────────────────────────────────────
    if (places.length === 0) {
      el.style.display = 'none';
      var emptyEl = document.createElement('p');
      emptyEl.className = 'tclas-map-empty';
      emptyEl.textContent = 'No places have been added yet — check back soon.';
      el.parentNode.insertBefore(emptyEl, el.nextSibling);
      return;
    }

    // ── Map init ──────────────────────────────────────────────────────────
    var map = L.map('tclas-places-map', {
      scrollWheelZoom: false,
      minZoom: 3,
      maxZoom: 14,
    });

    if (data.mapboxTileUrl) {
      L.tileLayer(data.mapboxTileUrl, {
        attribution:
          '&copy; <a href="https://www.mapbox.com/about/maps/" target="_blank" rel="noopener">Mapbox</a> ' +
          '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
        tileSize: 256,
        maxZoom: 18,
      }).addTo(map);
    } else {
      L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution:
          '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors, ' +
          '&copy; <a href="https://carto.com/attributions" target="_blank" rel="noopener">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 19,
      }).addTo(map);
    }

    // ── Category layer state ──────────────────────────────────────────────
    var activeTypes = {};
    Object.keys(types).forEach(function (slug) { activeTypes[slug] = true; });

    // A place is visible when ANY of its types is active (untyped places
    // are always visible so a missing term never hides content).
    function isVisible(p) {
      if (!p.types || p.types.length === 0) return true;
      return p.types.some(function (t) { return activeTypes[t] !== false; });
    }

    function typeColor(p) {
      var slug = (p.types || [])[0];
      return (types[slug] && types[slug].color) || '#8B3A3A';
    }

    function typeLabels(p) {
      return (p.types || [])
        .map(function (t) { return types[t] ? types[t].label : null; })
        .filter(Boolean);
    }

    // ── Markers ───────────────────────────────────────────────────────────
    var entries = []; // { place, marker, latlng }

    places.forEach(function (p) {
      var ll     = L.latLng(p.lat, p.lng);
      var marker = L.circleMarker(ll, {
        radius:      9,
        fillColor:   typeColor(p),
        color:       '#FFFFFF',
        weight:      2,
        opacity:     0.9,
        fillOpacity: 0.85,
      }).addTo(map);

      var badgesHtml = typeLabels(p).map(function (label) {
        return '<span class="tclas-places-badge">' + _esc(label) + '</span>';
      }).join('');

      var locality = [p.county, p.state].filter(Boolean).join(', ');

      marker.bindPopup(
        '<div class="tclas-map-popup">' +
          '<span class="tclas-map-popup-name">' + _esc(p.name) + '</span>' +
          (locality ? '<span class="tclas-map-popup-canton">' + _esc(locality) + '</span>' : '') +
          (badgesHtml ? '<span class="tclas-places-popup-badges">' + badgesHtml + '</span>' : '') +
          (p.excerpt ? '<span class="tclas-map-popup-count">' + _esc(p.excerpt) + '</span>' : '') +
          '<a href="' + _esc(p.url) + '" class="tclas-map-popup-link">Read the history &rarr;</a>' +
        '</div>',
        { maxWidth: 260, className: 'tclas-map-popup-wrap' }
      );

      entries.push({ place: p, marker: marker, latlng: ll });
    });

    // ── Fit bounds ────────────────────────────────────────────────────────
    function fitMap() {
      var pts = entries
        .filter(function (e) { return isVisible(e.place); })
        .map(function (e) { return e.latlng; });
      if (pts.length === 0) pts = entries.map(function (e) { return e.latlng; });
      try {
        map.fitBounds(L.latLngBounds(pts), { padding: [40, 40], maxZoom: 8 });
      } catch (e) {
        map.setView([43.5, -90.5], 5); // Upper Midwest fallback
      }
    }

    fitMap();
    setTimeout(function () {
      map.invalidateSize();
      fitMap();
    }, 200);

    // ── List ──────────────────────────────────────────────────────────────
    var listBody = document.getElementById('tclas-places-list-body');
    var countEl  = document.getElementById('tclas-places-list-count');

    function buildRow(entry) {
      var p  = entry.place;
      var tr = document.createElement('tr');
      tr.className = 'tclas-map-list__row--clickable';

      var nameCell = document.createElement('td');
      var link = document.createElement('a');
      link.href = p.url;
      link.textContent = p.name;
      nameCell.appendChild(link);

      var stateCell = document.createElement('td');
      stateCell.textContent = p.state || '—';

      var typeCell = document.createElement('td');
      typeCell.className = 'tclas-places-list__types';
      typeLabels(p).forEach(function (label) {
        var badge = document.createElement('span');
        badge.className = 'tclas-places-badge';
        badge.textContent = label;
        typeCell.appendChild(badge);
      });

      tr.appendChild(nameCell);
      tr.appendChild(stateCell);
      tr.appendChild(typeCell);

      tr.addEventListener('click', function (e) {
        if (e.target.tagName === 'A') return; // let the title link navigate
        map.stop();
        map.flyTo(entry.latlng, 9, { duration: 0.6 });
        // Open after the flight lands — the popup's autoPan would otherwise
        // fight the flyTo animation.
        map.once('moveend', function () { entry.marker.openPopup(); });
      });

      return tr;
    }

    function updateList() {
      var visible = entries.filter(function (e) { return isVisible(e.place); });

      if (countEl) {
        countEl.textContent = visible.length === entries.length
          ? entries.length + ' place' + (entries.length !== 1 ? 's' : '')
          : visible.length + ' of ' + entries.length + ' places';
      }

      listBody.innerHTML = '';
      visible.forEach(function (e) {
        listBody.appendChild(buildRow(e));
      });
    }

    updateList();

    // ── Filter chips ──────────────────────────────────────────────────────
    document.querySelectorAll('.tclas-places-chip').forEach(function (chip) {
      chip.addEventListener('click', function () {
        var slug = chip.getAttribute('data-type');
        var on   = chip.getAttribute('aria-pressed') === 'true';
        chip.setAttribute('aria-pressed', on ? 'false' : 'true');
        activeTypes[slug] = !on;

        // Cancel any in-flight fly/fit animation first: adding vector layers
        // mid-animation projects them against a stale origin (markers pile up
        // at one wrong point until the next zoom).
        map.stop();

        entries.forEach(function (e) {
          var show = isVisible(e.place);
          if (show && !map.hasLayer(e.marker)) {
            e.marker.addTo(map);
          } else if (!show && map.hasLayer(e.marker)) {
            e.marker.closePopup();
            map.removeLayer(e.marker);
          }
        });

        updateList();
        fitMap();
      });
    });
  });

  // Minimal HTML-escape helper
  function _esc(str) {
    return String(str)
      .replace(/&/g,  '&amp;')
      .replace(/</g,  '&lt;')
      .replace(/>/g,  '&gt;')
      .replace(/"/g,  '&quot;')
      .replace(/'/g,  '&#39;');
  }
})();
