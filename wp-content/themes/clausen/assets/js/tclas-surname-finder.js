/* TCLAS Surname Explorer — instant client-side finder
 * Depends on: tclasSurnameData (localised by surname-explorer.php)
 *
 * Mirrors the connections-engine matching ladder (connections.php):
 * normalize → exact variant match → umlaut expansion → Levenshtein with
 * length-scaled thresholds. Keep the two in sync.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var input   = document.getElementById('tclas-surname-input');
    var results = document.getElementById('tclas-surname-results');
    var nfTmpl  = document.getElementById('tclas-surname-notfound');
    if (!input || !results) return;

    var data = (typeof tclasSurnameData !== 'undefined') ? tclasSurnameData : {};
    var surnames = data.surnames || [];

    // ── Normalization (mirrors tclas_normalize_string) ────────────────────
    function normalize(raw) {
      return String(raw)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '') // strip diacritics (combining marks after NFD)
        .replace(/[^a-z]/g, '');          // letters only
    }

    // ── Threshold (mirrors tclas_levenshtein_threshold) ───────────────────
    function threshold(s) {
      var len = s.length;
      if (len <= 4) return 0;
      if (len <= 7) return 1;
      if (len <= 11) return 2;
      return 3;
    }

    // ── Levenshtein distance ──────────────────────────────────────────────
    function levenshtein(a, b) {
      if (a === b) return 0;
      var m = a.length, n = b.length;
      if (m === 0) return n;
      if (n === 0) return m;
      var prev = new Array(n + 1);
      var curr = new Array(n + 1);
      for (var j = 0; j <= n; j++) prev[j] = j;
      for (var i = 1; i <= m; i++) {
        curr[0] = i;
        for (var k = 1; k <= n; k++) {
          var cost = a.charAt(i - 1) === b.charAt(k - 1) ? 0 : 1;
          curr[k] = Math.min(prev[k] + 1, curr[k - 1] + 1, prev[k - 1] + cost);
        }
        var tmp = prev; prev = curr; curr = tmp;
      }
      return prev[n];
    }

    // ── Matching ladder ───────────────────────────────────────────────────
    // Returns [{entry, dist}] — dist 0 = exact, sorted best-first, max 3.
    function findMatches(query) {
      var norm = normalize(query);
      if (norm.length < 2) return null; // too short to judge

      var exact = [];
      surnames.forEach(function (s) {
        if (s.norms.indexOf(norm) !== -1) exact.push({ entry: s, dist: 0 });
      });
      if (exact.length) return exact;

      // Umlaut expansion (mirrors PHP: u→ue, o→oe, a→ae) then exact retry
      var expanded = norm.replace(/u/g, 'ue').replace(/o/g, 'oe').replace(/a/g, 'ae');
      if (expanded !== norm) {
        surnames.forEach(function (s) {
          if (s.norms.indexOf(expanded) !== -1) exact.push({ entry: s, dist: 0 });
        });
        if (exact.length) return exact;
      }

      // Fuzzy
      var limit = threshold(norm);
      if (limit === 0) return [];
      var fuzzy = [];
      surnames.forEach(function (s) {
        var best = Infinity;
        s.norms.forEach(function (v) {
          if (Math.abs(v.length - norm.length) > limit) return; // cheap prune
          var d = levenshtein(norm, v);
          if (d < best) best = d;
        });
        if (best <= limit) fuzzy.push({ entry: s, dist: best });
      });
      fuzzy.sort(function (a, b) { return a.dist - b.dist; });
      return fuzzy.slice(0, 3);
    }

    // ── Rendering ─────────────────────────────────────────────────────────
    function renderCard(match, isFuzzy) {
      var s = match.entry;
      var card = document.createElement('a');
      card.className = 'tclas-surname-card' + (isFuzzy ? ' tclas-surname-card--fuzzy' : '');
      card.href = s.url;

      var head = document.createElement('span');
      head.className = 'tclas-surname-card__label';
      head.textContent = s.label;
      card.appendChild(head);

      if (s.variants.length > 1) {
        var vars = document.createElement('span');
        vars.className = 'tclas-surname-card__variants';
        vars.textContent = s.variants.join(' · ');
        card.appendChild(vars);
      }

      var cta = document.createElement('span');
      cta.className = 'tclas-surname-card__cta';
      cta.textContent = 'Read about this name →';
      card.appendChild(cta);

      return card;
    }

    function render(matches) {
      results.innerHTML = '';
      if (matches === null) return; // query too short: show nothing

      if (matches.length === 0) {
        if (nfTmpl) results.appendChild(nfTmpl.content.cloneNode(true));
        return;
      }

      var isFuzzy = matches[0].dist > 0;
      if (isFuzzy) {
        var hint = document.createElement('p');
        hint.className = 'tclas-surname-finder__hint';
        hint.textContent = 'No exact match — closest Luxembourgish names:';
        results.appendChild(hint);
      }
      matches.forEach(function (m) {
        results.appendChild(renderCard(m, isFuzzy));
      });
    }

    var timer = null;
    input.addEventListener('input', function () {
      clearTimeout(timer);
      var q = input.value;
      timer = setTimeout(function () { render(findMatches(q)); }, 120);
    });
  });
})();
