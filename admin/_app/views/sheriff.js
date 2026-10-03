/* Sheriff views — loaded by admin/_app/shell.js for admin/sheriff/ */
(function () {
  'use strict';
  var A = window.A, D = A.D, V = A.V;
  var esc = A.esc, money = A.money, $ = A.$, $$ = A.$$, pill = A.pill, toast = A.toast, confirmBox = A.confirmBox,
      table = A.table, head = A.head, marketOptions = A.marketOptions, planByCode = A.planByCode,
      persist = A.persist, route = A.route, renderMenu = A.renderMenu, currentUser = A.currentUser, extraNotes = A.notes;

  // ===== SHERIFF =====
  function appsFor(role) {
    var r = D.ROLES.filter(function (x) { return x.name === role; })[0];
    var names = ET.APPS.filter(function (a) { return r && r.perms.indexOf(a.key) > -1; }).map(function (a) { return a.name; });
    return names.length === ET.APPS.length ? 'All' : names.join(', ') || '—';
  }
  A.badges.crons = function () { return D.CRONS.filter(function (c) { return c.status === 'Failed'; }).length || ''; };

  V['users'] = function () {
    return head('Users', 'Who can sign in to the admin tools.', '<button class="btn" id="u-add">Add User</button>') +
      '<div class="panel">' + table(['Name', 'Email', 'Role', 'Apps', 'Last Sign-in', 'Status', ''], D.USERS.map(function (u) {
        return '<tr><td><b>' + esc(u.name) + '</b></td><td>' + esc(u.email) + '</td><td><select data-urole="' + u.id + '">' + D.ROLES.map(function (r) { return '<option' + (r.name === u.role ? ' selected' : '') + '>' + r.name + '</option>'; }).join('') + '</select></td>' +
          '<td>' + esc(appsFor(u.role)) + '</td><td>' + u.last + '</td><td>' + (u.active ? pill('Active', 'ok') : pill('Disabled')) + '</td>' +
          '<td><button class="btn sm ghost" data-utoggle="' + u.id + '">' + (u.active ? 'Disable' : 'Enable') + '</button></td></tr>';
      })) + '</div>';
  };
  V['users'].init = function () {
    function u(id) { return D.USERS.filter(function (x) { return String(x.id) === id; })[0]; }
    $$('[data-urole]').forEach(function (s) { s.addEventListener('change', function () { u(s.dataset.urole).role = s.value; persist(['USERS']); toast('Role updated'); }); });
    $$('[data-utoggle]').forEach(function (b) { b.addEventListener('click', function () { var x = u(b.dataset.utoggle); x.active = !x.active; persist(['USERS']); toast(x.name + (x.active ? ' enabled' : ' disabled')); route(); }); });
    $('#u-add').addEventListener('click', function () {
      var bg = document.createElement('div'); bg.className = 'modal-bg';
      bg.innerHTML = '<form class="modal"><h2>Add User</h2><div style="display:flex;flex-direction:column;gap:12px;margin-top:12px"><div class="field"><label for="nu-name">Name</label><input id="nu-name" required></div><div class="field"><label for="nu-email">Email</label><input id="nu-email" type="email" required></div><div class="field"><label for="nu-role">Role</label><select id="nu-role">' + D.ROLES.map(function (r) { return '<option>' + r.name + '</option>'; }).join('') + '</select></div></div><div class="actions" style="justify-content:flex-end;margin-top:16px"><button type="button" class="btn ghost" data-x>Cancel</button><button class="btn cyan">Add User</button></div></form>';
      document.body.appendChild(bg);
      bg.addEventListener('click', function (e) { if (e.target === bg || e.target.hasAttribute('data-x')) bg.remove(); });
      $('#nu-name', bg).focus();
      $('form', bg).addEventListener('submit', function (e) {
        e.preventDefault();
        D.USERS.push({ id: Date.now(), name: $('#nu-name', bg).value, email: $('#nu-email', bg).value, role: $('#nu-role', bg).value, last: 'Never', active: true });
        persist(['USERS']); bg.remove(); toast('User added — they will get an invite email once the backend is connected'); route();
      });
    });
  };
  V['roles'] = function () {
    return head('Roles', 'What each role is allowed to do.', '<button class="btn cyan" id="roles-save">Save Roles</button>') +
      '<div class="panel">' + table(['Role'].concat(ET.PERMS.map(function (p) { return p[1]; })), D.ROLES.map(function (r, ri) {
        return '<tr><td><b>' + r.name + '</b></td>' + ET.PERMS.map(function (p) {
          return '<td style="text-align:center"><input type="checkbox" data-role="' + ri + '" data-perm="' + p[0] + '"' + (r.perms.indexOf(p[0]) > -1 ? ' checked' : '') + (r.name === 'Administrator' ? ' disabled' : '') + ' aria-label="' + r.name + ': ' + p[1] + '"></td>';
        }).join('') + '</tr>';
      })) + '</div>';
  };
  V['roles'].init = function () {
    $('#roles-save').addEventListener('click', function () {
      D.ROLES.forEach(function (r, ri) {
        if (r.name === 'Administrator') return;
        r.perms = $$('[data-role="' + ri + '"]:checked').map(function (c) { return c.dataset.perm; });
      });
      persist(['ROLES']); toast('Roles saved');
    });
  };
  V['apis'] = function () {
    return head('APIs', 'Third-party integrations.') +
      '<div class="banner-note">API keys and passwords are not stored in this site. Keep them in server environment variables or a secrets manager; this screen only shows whether each one is set.</div>' +
      '<div class="grid-3">' + D.APIS.map(function (a) {
        return '<div class="panel"><div class="panel-head"><h2>' + a.name + '</h2>' + pill('Not configured', 'warn') + '</div><div class="panel-body"><p class="muted" style="margin-bottom:10px">' + esc(a.purpose) + '</p>' +
          '<dl class="kv" style="grid-template-columns:1fr auto">' + a.fields.map(function (f) { return '<dt class="mono">' + f + '</dt><dd class="muted">—</dd>'; }).join('') + '</dl>' +
          '<div class="actions" style="margin-top:14px"><button class="btn sm ghost" data-test="' + a.name + '">Test Connection</button></div></div></div>';
      }).join('') + '</div>';
  };
  V['apis'].init = function () {
    $$('[data-test]').forEach(function (b) { b.addEventListener('click', function () { toast(b.dataset.test + ': no credentials set on the server'); }); });
  };
  V['crons'] = function () {
    return head('Crons', 'Scheduled background jobs.') +
      '<div class="panel">' + table(['Job', 'Schedule', 'Last Run', { label: 'Duration', num: 1 }, 'Status', ''], D.CRONS.map(function (c, i) {
        return '<tr><td><b>' + esc(c.name) + '</b></td><td class="mono">' + c.schedule + '</td><td>' + c.last + '</td><td class="num">' + (c.ms ? (c.ms / 1000).toFixed(1) + 's' : '—') + '</td>' +
          '<td>' + pill(c.status, c.status === 'OK' ? 'ok' : c.status === 'Warning' ? 'warn' : 'bad') + '</td><td><button class="btn sm ghost" data-run="' + i + '">Run Now</button></td></tr>';
      })) + '</div>';
  };
  V['crons'].init = function () {
    $$('[data-run]').forEach(function (b) {
      b.addEventListener('click', function () {
        var c = D.CRONS[+b.dataset.run];
        c.last = new Date().toISOString().slice(0, 16).replace('T', ' '); c.status = 'OK'; c.ms = 1000 + Math.floor(Math.random() * 9000);
        toast(c.name + ' finished'); renderMenu(); route();
      });
    });
  };
  V['data'] = function (q, name) {
    name = decodeURIComponent(name || '');
    var t = D.DATA_TABLES[name];
    if (!t) return '<div class="panel empty">Table not found.</div>';
    return head(name, 'Reference data used by enrollment, billing and the website.', '<button class="btn ghost" id="dt-add">Add Row</button><button class="btn cyan" id="dt-save">Save</button>') +
      '<div class="panel">' + table(t.cols.concat(['']), t.rows.map(function (r, ri) {
        return '<tr>' + r.map(function (v, ci) { return '<td><input data-r="' + ri + '" data-c="' + ci + '" value="' + esc(v) + '" style="width:100%;min-width:110px;text-align:left"></td>'; }).join('') + '<td><button class="btn sm ghost" data-del="' + ri + '">Remove</button></td></tr>';
      })) + '</div>';
  };
  V['data'].init = function (q, name) {
    name = decodeURIComponent(name || '');
    var t = D.DATA_TABLES[name]; if (!t) return;
    function collect() { $$('[data-r]').forEach(function (i) { t.rows[+i.dataset.r][+i.dataset.c] = i.value; }); }
    $('#dt-add').addEventListener('click', function () { collect(); t.rows.push(t.cols.map(function () { return ''; })); route(); });
    $('#dt-save').addEventListener('click', function () { collect(); persist(['DATA_TABLES']); toast(name + ' saved'); });
    $$('[data-del]').forEach(function (b) { b.addEventListener('click', function () { collect(); t.rows.splice(+b.dataset.del, 1); route(); }); });
  };

  A.viewsLoaded = true;
})();
