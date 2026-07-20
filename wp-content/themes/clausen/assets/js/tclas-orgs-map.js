/* TCLAS Groups & Events — organizations map (Leaflet initialisation)
 * Depends on: leaflet.js, tclasOrgsData (localised by orgs-events.php)
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('tclas-orgs-map');
    if (!el) return;
    if (typeof L === 'undefined') {
      console.warn('TCLAS Orgs Map: Leaflet not loaded.');
      return;
    }

    var data = (typeof tclasOrgsData !== 'undefined') ? tclasOrgsData : {};
    var orgs = data.orgs || [];

    // ── Empty state ───────────────────────────────────────────────────────
    if (orgs.length === 0) {
      el.style.display = 'none';
      var emptyEl = document.createElement('p');
      emptyEl.className = 'tclas-map-empty';
      emptyEl.textContent = 'No organizations listed yet — check back soon.';
      el.parentNode.insertBefore(emptyEl, el.nextSibling);
      return;
    }

    // ── Map init ──────────────────────────────────────────────────────────
    var map = L.map('tclas-orgs-map', {
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

    // ── Markers (orgs without coordinates are list-only) ──────────────────
    var entries = [];

    orgs.forEach(function (o) {
      var entry = { org: o, marker: null, latlng: null };

      if (o.lat && o.lng) {
        entry.latlng = L.latLng(o.lat, o.lng);
        entry.marker = L.circleMarker(entry.latlng, {
          radius:      9,
          fillColor:   '#2B6282',
          color:       '#FFFFFF',
          weight:      2,
          opacity:     0.9,
          fillOpacity: 0.85,
        }).addTo(map);

        entry.marker.bindPopup(
          '<div class="tclas-map-popup">' +
            '<span class="tclas-map-popup-name">' + _esc(o.name) + '</span>' +
            (o.city ? '<span class="tclas-map-popup-canton">' + _esc(o.city) + '</span>' : '') +
            (o.blurb ? '<span class="tclas-map-popup-count">' + _esc(o.blurb) + '</span>' : '') +
            '<a href="' + _esc(o.website) + '" target="_blank" rel="noopener" class="tclas-map-popup-link">Visit website &rarr;</a>' +
          '</div>',
          { maxWidth: 260, className: 'tclas-map-popup-wrap' }
        );
      }

      entries.push(entry);
    });

    // ── Fit bounds ────────────────────────────────────────────────────────
    function fitMap() {
      var pts = entries.filter(function (e) { return e.latlng; })
                       .map(function (e) { return e.latlng; });
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
    var listBody = document.getElementById('tclas-orgs-list-body');
    var countEl  = document.getElementById('tclas-orgs-list-count');

    if (countEl) {
      countEl.textContent = entries.length + ' organization' + (entries.length !== 1 ? 's' : '');
    }

    entries.forEach(function (entry) {
      var o  = entry.org;
      var tr = document.createElement('tr');

      var nameCell = document.createElement('td');
      var link = document.createElement('a');
      link.href = o.website;
      link.target = '_blank';
      link.rel = 'noopener';
      link.textContent = o.name;
      nameCell.appendChild(link);

      var cityCell = document.createElement('td');
      cityCell.textContent = o.city || '—';

      tr.appendChild(nameCell);
      tr.appendChild(cityCell);

      // Rows with a marker fly to it; online-only orgs just link out.
      if (entry.marker) {
        tr.className = 'tclas-map-list__row--clickable';
        tr.addEventListener('click', function (e) {
          if (e.target.tagName === 'A') return;
          map.stop();
          map.flyTo(entry.latlng, 9, { duration: 0.6 });
          map.once('moveend', function () { entry.marker.openPopup(); });
        });
      }

      listBody.appendChild(tr);
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
