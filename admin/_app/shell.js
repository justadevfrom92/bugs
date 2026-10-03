/*
  Admin shell shared by every app (Corral, Lando, Astro, Sheriff).
  Each app folder only holds config.js (its menu) and a link to admin/_app/app.html;
  this file builds the sidebar, top bar and router from that config, then loads
  the app's screens from admin/_app/views/<key>.js.
*/
(function () {
  'use strict';
  var ET = window.ET, CFG = window.APP_CONFIG;
  var APP = ET.APPS.filter(function (a) { return a.key === CFG.key; })[0];

  // Data the screens work with. Shared catalog + admin sample data, with saved edits applied.
  var KEYS = ['MARKETS', 'TDSP_FEES', 'PLANS', 'RATES', 'PLAN_GROUPS', 'REGIONS', 'TERM_MODS', 'BYOP_PRODUCTS',
    'USERS', 'ROLES', 'PAGES', 'TEMPLATES', 'BLOCKS', 'CUSTOMERS', 'QUEUES', 'APIS', 'CRONS', 'DATA_TABLES', 'NOTES'];
  var D = {};
  KEYS.forEach(function (k) { D[k] = ET.data(k); });
  ['REPORT_STATUSES', 'CUSTOMER_STATUSES', 'EXCEPTIONS', 'payments', 'bills', 'notes'].forEach(function (k) { D[k] = ET.sample[k]; });

  function persist(keys) {
    keys.concat('NOTES').forEach(function (k) { ET.save(k, D[k]); });
  }

  /* ---------- Helpers ---------- */
  var esc = ET.esc;
  function money(n) { return '$' + Number(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }
  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
  function pill(text, tone) { return '<span class="pill ' + (tone || '') + '">' + esc(text) + '</span>'; }
  function toast(msg) {
    var t = $('#toast'); t.textContent = msg; t.classList.add('show');
    clearTimeout(toast.t); toast.t = setTimeout(function () { t.classList.remove('show'); }, 2600);
  }
  function confirmBox(title, body, okLabel, onOk) {
    var bg = document.createElement('div');
    bg.className = 'modal-bg';
    bg.innerHTML = '<div class="modal" role="dialog" aria-modal="true"><h2>' + esc(title) + '</h2><p>' + esc(body) + '</p>' +
      '<div class="actions" style="justify-content:flex-end"><button class="btn ghost" data-x>Cancel</button><button class="btn red" data-ok>' + esc(okLabel) + '</button></div></div>';
    document.body.appendChild(bg);
    bg.addEventListener('click', function (e) {
      if (e.target === bg || e.target.hasAttribute('data-x')) bg.remove();
      if (e.target.hasAttribute('data-ok')) { bg.remove(); onOk(); }
    });
    $('[data-ok]', bg).focus();
  }
  function table(cols, rows, opts) {
    opts = opts || {};
    return '<div class="table-wrap"><table' + (opts.id ? ' id="' + opts.id + '"' : '') + '><thead><tr>' +
      cols.map(function (c) { return '<th' + (c.num ? ' class="num"' : '') + '>' + esc(c.label || c) + '</th>'; }).join('') +
      '</tr></thead><tbody>' + (rows.length ? rows.join('') : '<tr><td colspan="' + cols.length + '" class="empty">' + (opts.empty || 'Nothing to show.') + '</td></tr>') +
      '</tbody></table></div>';
  }
  function head(title, sub, actions) {
    return '<div class="page-head"><div><h1>' + esc(title) + '</h1>' + (sub ? '<p class="muted">' + sub + '</p>' : '') + '</div>' +
      (actions ? '<div class="actions">' + actions + '</div>' : '') + '</div>';
  }
  function marketOptions(sel, all) {
    return (all ? '<option value="">-- All Markets --</option>' : '') + D.MARKETS.map(function (m) {
      return '<option' + (m.name === sel ? ' selected' : '') + '>' + m.name + '</option>';
    }).join('');
  }
  function planByCode(code) { return D.PLANS.filter(function (p) { return p.internal === code; })[0]; }

  /* ---------- Routing (#/view/arg?query) ---------- */
  function getHash() { return location.hash; }
  function go(h) { location.hash = h; }
  function parseHash() {
    var h = getHash().replace(/^#\/?/, '') || CFG.home;
    var qi = h.indexOf('?'), qs = {};
    if (qi > -1) {
      h.slice(qi + 1).split('&').forEach(function (kv) { var p = kv.split('='); qs[decodeURIComponent(p[0])] = decodeURIComponent(p[1] || ''); });
      h = h.slice(0, qi);
    }
    var parts = h.split('/');
    return { view: parts[0], arg: parts.slice(1).join('/'), q: qs, path: h };
  }

  function currentUser() { var u = ET.auth.user(); return u ? u.name : 'Admin'; }

  function renderMenu() {
    var r = parseHash();
    var cur = (CFG.aliases || {})[r.view] || r.view;
    $('#app-switch').innerHTML = ET.auth.apps().map(function (a) {
      return '<a href="../' + a.key + '/"' + (a.key === CFG.key ? ' class="on" aria-current="page"' : '') + ' title="' + esc(a.desc) + '">' + a.icon + a.name + '</a>';
    }).join('');
    // A detail screen (e.g. plan/62) highlights its list item; plan/new highlights its own item if the menu has one
    var hasNewItem = CFG.menu.some(function (s) { return s[1].some(function (x) { return x[0] === r.view + '/new'; }); });
    $('#menu').innerHTML = CFG.menu.map(function (sec) {
      return '<h4>' + esc(sec[0]) + '</h4>' + sec[1].map(function (it) {
        var on = r.path === it[0] || (it[0] === cur && !(r.arg === 'new' && hasNewItem));
        var badge = it[2] && it[2].badge && A.badges[it[2].badge] ? A.badges[it[2].badge]() : '';
        return '<a href="#/' + it[0] + '"' + (on ? ' class="on"' : '') + '>' + esc(it[1]) +
          (badge ? '<span class="count' + (it[2].hot ? ' hot' : '') + '">' + badge + '</span>' : '') + '</a>';
      }).join('');
    }).join('');
  }

  function route() {
    var r = parseHash();
    var view = V[r.view];
    if (!view) { go('#/' + CFG.home); return; }
    renderMenu();
    var on = $('#menu a.on');
    $('#crumbs').innerHTML = esc(APP.name) + ' / <b>' + esc(on ? on.firstChild.textContent : r.view) + '</b>';
    var c = $('#content');
    c.innerHTML = view(r.q, r.arg);
    if (view.init) view.init(r.q, r.arg);
    $$('[data-href]', c).forEach(function (tr) {
      tr.addEventListener('click', function (e) { if (!e.target.closest('a,button,input,select')) go(tr.dataset.href); });
    });
    $$('[data-noop]', c).forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); toast('Documents are served by the backend'); }); });
    $('#sidebar').classList.remove('open');
    window.scrollTo(0, 0);
  }

  var V = {};
  // Everything the per-app view files need
  var A = window.A = {
    D: D, V: V, badges: {}, notes: D.NOTES,
    esc: esc, money: money, $: $, $$: $$, pill: pill, toast: toast, confirmBox: confirmBox,
    table: table, head: head, marketOptions: marketOptions, planByCode: planByCode,
    persist: persist, route: route, renderMenu: renderMenu, currentUser: currentUser,
    go: go, hash: getHash
  };

  function start() {
    var u = ET.auth.user();
    document.title = APP.name + ' · ' + ET.brand.name + ' Admin';
    $('#brand-name').textContent = ET.brand.wordmark;
    $('#user-name').textContent = u.name;
    $('#user-role').textContent = u.role;
    $('#user-initials').textContent = u.name.split(' ').map(function (w) { return w[0]; }).join('').slice(0, 2);
    $('#menu-btn').addEventListener('click', function () { $('#sidebar').classList.toggle('open'); });
    $('#logout').addEventListener('click', function (e) { e.preventDefault(); ET.auth.logout(); ET.go('../login.html'); });
    $('#site-link').href = ET.root + 'index.html';
    $('#reset').addEventListener('click', function (e) {
      e.preventDefault();
      confirmBox('Reset sample data?', 'This clears every change saved in this browser (admin and website) and restores the original data.', 'Reset Data', function () {
        ET.resetData(); location.reload();
      });
    });
    $('#find').addEventListener('input', function () {
      var v = this.value.toLowerCase();
      $$('#menu a').forEach(function (a) { a.hidden = !!v && a.textContent.toLowerCase().indexOf(v) < 0; });
    });
    window.addEventListener('hashchange', route);
    route();
  }

  ET.ready(function () {
    if (!ET.auth.require(CFG.key)) return;
    if (A.viewsLoaded) { start(); return; }
    var s = document.createElement('script');
    s.src = '../_app/views/' + CFG.key + '.js';
    s.onload = start;
    document.head.appendChild(s);
  });
})();
