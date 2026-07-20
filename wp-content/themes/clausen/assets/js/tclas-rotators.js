/* TCLAS culture corner — pronunciation play button */
(function () {
  'use strict';
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.tclas-culture__play');
    if (!btn) return;
    var src = btn.getAttribute('data-audio-src');
    if (src) new Audio(src).play();
  });
})();
