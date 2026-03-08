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
    var isSplit  = data.layout === 'split';
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
    var markerMap = {}; // slug → { marker, latlng }

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

      markerMap[slug] = { marker: marker, latlng: L.latLng(ll[0], ll[1]) };

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

    // ── List helpers ──────────────────────────────────────────────────────
    var listBody   = document.getElementById('tclas-map-list-body');
    var countEl    = document.getElementById('tclas-map-list-count');
    var profileBase = isPublic ? joinUrl : (data.communeBaseUrl || '/commune/');

    var MAX_SURNAMES = 3; // show this many, then "+N more" link

    function buildRow(slug) {
      var c = communes[slug];
      var tr = document.createElement('tr');
      var communeUrl = profileBase + slug + '/';

      var nameCell = document.createElement('td');
      if (isPublic) {
        nameCell.textContent = c.name;
      } else {
        var link = document.createElement('a');
        link.href = communeUrl;
        link.textContent = c.name;
        nameCell.appendChild(link);
      }

      var cantonCell = document.createElement('td');
      cantonCell.textContent = c.canton;

      var surnameCell = document.createElement('td');
      surnameCell.className = 'tclas-map-list__surnames';
      if (c.surnames && c.surnames.length > 0) {
        var shown = c.surnames.slice(0, MAX_SURNAMES).join(', ');
        var remaining = c.surnames.length - MAX_SURNAMES;
        if (remaining > 0) {
          surnameCell.innerHTML = _esc(shown) + ', <a href="' + _esc(communeUrl) +
            '" class="tclas-map-list__more">+' + remaining + ' more</a>';
        } else {
          surnameCell.textContent = shown;
        }
      } else {
        surnameCell.textContent = '—';
      }

      var countCell = document.createElement('td');
      countCell.textContent = c.count;

      tr.appendChild(nameCell);
      tr.appendChild(cantonCell);
      tr.appendChild(surnameCell);
      tr.appendChild(countCell);

      // Click row to fly map to the commune marker
      if (!isPublic && markerMap[slug]) {
        tr.className = 'tclas-map-list__row--clickable';
        tr.addEventListener('click', function (e) {
          if (e.target.tagName === 'A') return; // let links navigate normally
          var m = markerMap[slug];
          map.flyTo(m.latlng, 12, { duration: 0.6 });
          m.marker.openPopup();
        });
      }

      return tr;
    }

    // ── Split layout: live-filtered list ──────────────────────────────────
    if (isSplit && listBody) {
      var searchInput = document.getElementById('tclas-map-list-search');
      var searchTerm  = '';

      if (searchInput) {
        searchInput.addEventListener('input', function () {
          searchTerm = this.value.toLowerCase().trim();
          updateList();
        });
      }

      function updateList() {
        var bounds = map.getBounds();
        var visible = [];

        keys.forEach(function (slug) {
          var m = markerMap[slug];
          if (!m || !bounds.contains(m.latlng)) return;

          // Apply search filter: match commune name, canton, or any surname
          if (searchTerm) {
            var c = communes[slug];
            var haystack = (c.name + ' ' + c.canton + ' ' +
              (c.surnames || []).join(' ')).toLowerCase();
            if (haystack.indexOf(searchTerm) === -1) return;
          }

          visible.push(slug);
        });

        // Sort by count descending
        visible.sort(function (a, b) {
          return communes[b].count - communes[a].count;
        });

        // Update count badge
        if (countEl) {
          var total = keys.length;
          if (searchTerm) {
            countEl.textContent = visible.length + ' result' +
              (visible.length !== 1 ? 's' : '');
          } else {
            countEl.textContent = visible.length === total
              ? total + ' communes'
              : visible.length + ' of ' + total + ' in view';
          }
        }

        // Rebuild table body
        listBody.innerHTML = '';
        visible.forEach(function (slug) {
          listBody.appendChild(buildRow(slug));
        });
      }

      map.on('moveend', updateList);
      // Initial render after map is ready
      setTimeout(updateList, 250);

    // ── Default layout: toggle between map and list ─────────────────────
    } else if (listBody) {
      var toggleBtn = document.getElementById('tclas-map-view-toggle');
      var listEl    = document.getElementById('tclas-map-list');

      if (toggleBtn && listEl) {
        // Build full table once, sorted by count descending
        var sorted = keys.slice().sort(function (a, b) {
          return communes[b].count - communes[a].count;
        });

        sorted.forEach(function (slug) {
          listBody.appendChild(buildRow(slug));
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
