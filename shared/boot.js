/*
  One script tag loads everything a page needs:
    <script src="shared/boot.js" data-then="js/main.js"></script>
  The shared config and helpers load first (in order), then the page's own
  files listed in data-then (paths relative to the page).
*/
(function () {
  'use strict';
  var me = document.currentScript;
  window.ET = window.ET || {};
  ET.root = me.src.replace(/shared\/boot\.js(\?.*)?$/, '');

  var shared = [
    'shared/config/brand.js',
    'shared/config/catalog.js',
    'shared/config/access.js',
    'shared/config/apps.js',
    'shared/js/core.js',
    'shared/js/auth.js'
  ];
  var then = (me.getAttribute('data-then') || '').split(/\s+/).filter(Boolean);

  shared.map(function (f) { return ET.root + f; }).concat(then).forEach(function (src) {
    var s = document.createElement('script');
    s.src = src;
    s.async = false; // keep execution order
    document.head.appendChild(s);
  });
})();
