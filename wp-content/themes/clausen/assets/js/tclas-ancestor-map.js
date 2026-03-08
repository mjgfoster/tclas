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

    // Tile layer: custom Mapbox style if configured, otherwise CartoDB Positron fallback
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

    // ── Markers ───────────────────────────────────────────────────────────
    var latlngs = [];

    keys.forEach(function (slug) {
      var c = communes[slug];
      if (!c.lat || !c.lng) return;

      var ll     = [c.lat, c.lng];
      var radius = Math.max(7, Math.min(26, 6 + c.count * 4));

      latlngs.push(ll);

      // Crimson markers on pastel canton backgrounds
      var marker = L.circleMarker(ll, {
        radius:      radius,
        fillColor:   '#8B3A3A',
        color:       '#FFFFFF',
        weight:      2,
        opacity:     0.9,
        fillOpacity: 0.8,
      }).addTo(map);

      var memberWord   = c.count === 1 ? 'member' : 'members';
      var verbWord     = c.count === 1 ? 'has'    : 'have';
      var communeSlug  = slug;
      var profileUrl   = (data.communeBaseUrl || '/commune/') + communeSlug + '/';

      var popupLink = isPublic
        ? '<a href="' + joinUrl + '" class="tclas-map-popup-cta">Join to connect →</a>'
        : '<a href="' + profileUrl + '" class="tclas-map-popup-link">View commune profile →</a>';

      var surnamesHtml = '';
      if (!isPublic && c.surnames && c.surnames.length > 0) {
        surnamesHtml = '<span class="tclas-map-popup-surnames">Surnames: ' +
          c.surnames.map(_esc).join(', ') + '</span>';
      }

      var popupHtml =
        '<div class="tclas-map-popup">' +
          '<span class="tclas-map-popup-name">' + _esc(c.name) + '</span>' +
          '<span class="tclas-map-popup-canton">' + _esc(c.canton) + ' Canton</span>' +
          '<span class="tclas-map-popup-count">' +
            '<strong>' + c.count + '</strong> TCLAS ' + memberWord + ' ' + verbWord +
            ' roots here' +
          '</span>' +
          surnamesHtml +
          popupLink +
        '</div>';

      marker.bindPopup(popupHtml, { maxWidth: 240, className: 'tclas-map-popup-wrap' });
    });

    // ── Fit bounds ────────────────────────────────────────────────────────
    // Luxembourg's full extent — ensure all of the country is always visible
    var luxBounds = [[49.45, 5.73], [50.18, 6.53]];

    function fitMap() {
      if (latlngs.length === 0) {
        map.fitBounds(luxBounds, { padding: [20, 20] });
      } else {
        // Merge marker positions with Luxembourg extent so the whole
        // country shows even when markers cluster in one area.
        var allPoints = latlngs.concat(luxBounds);
        try {
          map.fitBounds(allPoints, { padding: [20, 20], maxZoom: 12 });
        } catch (e) {
          map.setView([49.815, 6.13], 9);
        }
      }
    }

    fitMap();

    // Re-fit after layout settles (CSS grid may resize the container)
    setTimeout(function () {
      map.invalidateSize();
      fitMap();
    }, 200);

    // ── List view toggle ──────────────────────────────────────────────────
    var toggleBtn = document.getElementById('tclas-map-view-toggle');
    var listEl    = document.getElementById('tclas-map-list');
    var listBody  = document.getElementById('tclas-map-list-body');

    if (toggleBtn && listEl && listBody) {
      // Build table rows, sorted by count descending
      var sorted = keys.slice().sort(function (a, b) {
        return communes[b].count - communes[a].count;
      });

      var profileBase = isPublic ? joinUrl : (data.communeBaseUrl || '/commune/');

      sorted.forEach(function (slug) {
        var c = communes[slug];
        var tr = document.createElement('tr');
        var nameCell = document.createElement('td');
        if (isPublic) {
          nameCell.textContent = c.name;
        } else {
          var link = document.createElement('a');
          link.href = profileBase + slug + '/';
          link.textContent = c.name;
          nameCell.appendChild(link);
        }
        var cantonCell = document.createElement('td');
        cantonCell.textContent = c.canton;
        var surnameCell = document.createElement('td');
        surnameCell.textContent = (c.surnames && c.surnames.length > 0)
          ? c.surnames.join(', ')
          : '—';
        surnameCell.style.fontSize = '.82rem';
        surnameCell.style.color = '#777';
        var countCell = document.createElement('td');
        countCell.textContent = c.count;
        tr.appendChild(nameCell);
        tr.appendChild(cantonCell);
        tr.appendChild(surnameCell);
        tr.appendChild(countCell);
        listBody.appendChild(tr);
      });

      toggleBtn.addEventListener('click', function () {
        var showing = toggleBtn.getAttribute('aria-pressed') === 'true';
        if (showing) {
          // Switch back to map
          el.hidden = false;
          listEl.hidden = true;
          toggleBtn.setAttribute('aria-pressed', 'false');
          toggleBtn.querySelector('span').textContent = 'View as list';
          map.invalidateSize();
        } else {
          // Switch to list
          el.hidden = true;
          listEl.hidden = false;
          toggleBtn.setAttribute('aria-pressed', 'true');
          toggleBtn.querySelector('span').textContent = 'View as map';
        }
      });
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
