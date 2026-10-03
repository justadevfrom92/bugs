/* Admin sign-in page. Uses the same ET.auth as the website's Admin prompt. */
(function () {
  'use strict';
  var ET = window.ET;
  ET.ready(function () {
    document.querySelector('[data-wordmark]').textContent = ET.brand.wordmark;
    document.getElementById('users').innerHTML = ET.data('USERS').map(function (u) {
      return '<option value="' + ET.esc(u.email) + '">' + ET.esc(u.name + ' (' + u.role + ')') + '</option>';
    }).join('');
    // Only follow ?next= to a path on this site
    var next = ET.query().get('next');
    if (!next || !/^\/[^/]/.test(next)) next = './';
    if (ET.auth.user()) { ET.go(next); return; }
    document.getElementById('email').focus();
    document.getElementById('login').addEventListener('submit', function (e) {
      e.preventDefault();
      var res = ET.auth.login(document.getElementById('email').value, document.getElementById('pw').value);
      if (res.error) { document.getElementById('err').textContent = res.error; return; }
      ET.go(next);
    });
  });
})();
