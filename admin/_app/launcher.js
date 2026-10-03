/* Admin launcher: lists the apps from shared/config/apps.js the signed-in role can open. */
(function () {
  'use strict';
  var ET = window.ET, esc = ET.esc;
  ET.ready(function () {
    if (!ET.auth.require()) return;
    var u = ET.auth.user();
    document.title = ET.brand.name + ' Admin';
    document.querySelector('[data-wordmark]').textContent = ET.brand.wordmark;
    document.getElementById('user-name').textContent = u.name;
    document.getElementById('user-role').textContent = u.role;
    document.getElementById('user-initials').textContent = u.name.split(' ').map(function (w) { return w[0]; }).join('').slice(0, 2);
    document.getElementById('greeting').textContent = 'Howdy, ' + u.name.split(' ')[0];

    var denied = ET.query().get('denied');
    var app = denied && ET.APPS.filter(function (a) { return a.key === denied; })[0];
    if (app) {
      var note = document.getElementById('denied');
      note.textContent = 'Your role (' + u.role + ') does not include ' + app.name + '. Ask an administrator to add it in Sheriff → Roles.';
      note.hidden = false;
    }

    document.getElementById('tiles').innerHTML = ET.APPS.map(function (a) {
      var ok = ET.auth.can(a.key);
      return '<a class="tile' + (ok ? '' : ' locked') + '" href="' + a.key + '/"' + (ok ? '' : ' aria-disabled="true" tabindex="-1"') + '>' +
        '<span class="ico">' + a.icon + '</span><h2>' + esc(a.name) + '</h2><p>' + esc(a.desc) + '</p>' +
        '<span class="open">' + (ok ? 'Open ' + esc(a.name) + ' →' : 'No access') + '</span></a>';
    }).join('');

    document.getElementById('logout').addEventListener('click', function (e) {
      e.preventDefault(); ET.auth.logout(); ET.go('login.html');
    });
  });
})();
