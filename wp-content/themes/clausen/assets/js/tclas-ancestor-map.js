/* TCLAS Ancestral Commune Map — Leaflet initialisation
 * Depends on: leaflet.js, tclasMapData (localised by ancestor-map.php)
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('tclas-ancestor-map');
    if (!el) return;
    if (typeof L === 'undefined') {
      console.warn('TCLAS Map: Leaflet not loaded.');
      return;
    }

    var data     = (typeof tclasMapData !== 'undefined') ? tclasMapData : {};
    var communes = data.communes || {};
    var storyUrl = data.storyUrl || '/member-hub/my-story/';
    var isPublic = !!data.isPublic;
    var joinUrl  = data.joinUrl || '/join/';
    var keys     = Object.keys(communes);

    // ── Empty state ───────────────────────────────────────────────────────
    if (keys.length === 0) {
      el.style.display = 'none';
      var emptyEl = document.createElement('p');
      emptyEl.className = 'tclas-map-empty';
      emptyEl.innerHTML =
        'No ancestral communes have been recorded yet. ' +
        '<a href="' + storyUrl + '">Add yours in your Luxembourg Story.</a>';
      el.parentNode.insertBefore(emptyEl, el.nextSibling);
      return;
    }

    // ── Map init ──────────────────────────────────────────────────────────
    var map = L.map('tclas-ancestor-map', {
      scrollWheelZoom: false,
      minZoom: 7,
      maxZoom: 16,
    });

    // CartoDB Positron — light base map, minimal visual noise
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors, ' +
        '&copy; <a href="https://carto.com/attributions" target="_blank" rel="noopener">CARTO</a>',
      subdomains: 'abcd',
      maxZoom: 19,
    }).addTo(map);

    // ── Markers ───────────────────────────────────────────────────────────
    var latlngs = [];

    keys.forEach(function (slug) {
      var c = communes[slug];
      if (!c.lat || !c.lng) return;

      var ll     = [c.lat, c.lng];
      var radius = Math.max(7, Math.min(26, 6 + c.count * 4));

      latlngs.push(ll);

      // MN Gold markers for the "wine bar" aesthetic
      var marker = L.circleMarker(ll, {
        radius:      radius,
        fillColor:   '#D4AF37',
        color:       '#0A2540',
        weight:      2,
        opacity:     0.9,
        fillOpacity: 0.75,
      }).addTo(map);

      var memberWord   = c.count === 1 ? 'member' : 'members';
      var verbWord     = c.count === 1 ? 'has'    : 'have';
      var communeSlug  = slug;
      var profileUrl   = (data.communeBaseUrl || '/commune/') + communeSlug + '/';

      var popupLink = isPublic
        ? '<a href="' + joinUrl + '" class="tclas-map-popup-cta">Join TCLAS to connect &rarr;</a>'
        : '<a href="' + profileUrl + '" class="tclas-map-popup-link">View commune profile &rarr;</a>';

      var popupHtml =
        '<div class="tclas-map-popup">' +
          '<strong class="tclas-map-popup-name">' + _esc(c.name)   + '</strong>' +
          '<span  class="tclas-map-popup-canton">' + _esc(c.canton) + '</span>' +
          '<span  class="tclas-map-popup-count">'  +
            c.count + ' TCLAS&nbsp;' + memberWord + ' ' + verbWord +
            ' ancestors from here' +
          '</span>' +
          popupLink +
        '</div>';

      marker.bindPopup(popupHtml, { maxWidth: 240, className: 'tclas-map-popup-wrap' });
    });

    // ── Fit bounds ────────────────────────────────────────────────────────
    if (latlngs.length === 1) {
      map.setView(latlngs[0], 11);
    } else if (latlngs.length > 1) {
      try {
        map.fitBounds(latlngs, { padding: [40, 40], maxZoom: 12 });
      } catch (e) {
        map.setView([49.75, 6.10], 9);
      }
    } else {
      map.setView([49.75, 6.10], 9);
    }
  });

  // Minimal HTML-escape helper (Leaflet.Util.escapeHtml may not exist in all builds)
  function _esc(str) {
    return String(str)
      .replace(/&/g,  '&amp;')
      .replace(/</g,  '&lt;')
      .replace(/>/g,  '&gt;')
      .replace(/"/g,  '&quot;')
      .replace(/'/g,  '&#39;');
  }
})();
