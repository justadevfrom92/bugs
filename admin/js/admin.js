/*
  Energy Texas admin — single-page app for Corral, Lando, Astro and Sheriff.
  Front-end only: edits are saved to this browser's localStorage so the screens
  can be demoed. Wire each save() call to the real API when the backend exists.
*/
(function () {
  'use strict';
  var D = window.ADMIN_DATA;
  var STORE_KEY = 'et_admin_v1';
  var SESSION_KEY = 'et_admin_session';

  /* ---------- Persistence ---------- */
  var saved = {};
  try { saved = JSON.parse(localStorage.getItem(STORE_KEY) || '{}'); } catch (e) { saved = {}; }
  ['PLANS', 'RATES', 'TDSP_FEES', 'BLOCKS', 'TERM_MODS', 'BYOP_PRODUCTS', 'USERS', 'ROLES', 'PLAN_GROUPS', 'CUSTOMERS', 'PAGES'].forEach(function (k) {
    if (saved[k]) D[k] = saved[k];
  });
  var extraNotes = saved.NOTES || {};
  function persist(keys) {
    keys.forEach(function (k) { saved[k] = D[k]; });
    saved.NOTES = extraNotes;
    try { localStorage.setItem(STORE_KEY, JSON.stringify(saved)); } catch (e) {}
  }

  /* ---------- Helpers ---------- */
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
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

  /* ---------- App + menu definitions ---------- */
  var ICONS = {
    corral: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0113 0M16 4.5a3.5 3.5 0 010 7M18 14a5.5 5.5 0 013.5 6"/></svg>',
    lando: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 13h8M8 16h5"/></svg>',
    astro: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>',
    sheriff: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M12 2l2.4 5 5.6.6-4.2 3.8 1.2 5.5L12 14l-5 2.9 1.2-5.5L4 7.6 9.6 7z"/><path d="M12 14v8"/></svg>'
  };
  function queueTotal() { return D.QUEUES.reduce(function (a, q) { return a + q[1]; }, 0); }
  var APPS = {
    corral: { name: 'Corral', desc: 'Customer service', menu: [
      ['Customers', [['home', 'Customer Search'], ['esiid', 'ESIID Lookup'], ['order', 'Create Order'], ['order-biz', 'Create Order - Biz']]],
      ['Reports', [['reports', 'Orders Report'], ['queues', 'Exception Queues', function () { return queueTotal(); }]]]
    ] },
    lando: { name: 'Lando', desc: 'Website CMS', menu: [
      ['Pages', [['pages', 'Pages List'], ['templates', 'Page Templates'], ['blocks', 'Content Blocks']]],
      ['Plans', [['plans', 'View Plans'], ['plan/new', 'Add a Plan'], ['groups', 'Plan Groups']]],
      ['Rates', [['rates', 'View Rates'], ['update-rates', 'Update Rates'], ['tdsp', 'TDSP Fees']]],
      ['Markets', [['markets', 'View Markets']]]
    ] },
    astro: { name: 'Astro', desc: 'Pricing modifiers', menu: [
      ['Modifiers', [['term', 'Term & Discounts'], ['etf', 'ETF by Term'], ['byop', 'BYOP Products']]]
    ] },
    sheriff: { name: 'Sheriff', desc: 'Settings & integrations', menu: [
      ['Access', [['users', 'Users'], ['roles', 'Roles']]],
      ['System', [['apis', 'APIs'], ['crons', 'Crons', function () { return D.CRONS.filter(function (c) { return c.status === 'Failed'; }).length || ''; }]]],
      ['Data', Object.keys(D.DATA_TABLES).map(function (k) { return ['data/' + encodeURIComponent(k), k]; })]
    ] }
  };

  /* ---------- Views ---------- */
  var V = {};

  // ===== CORRAL =====
  V['corral/home'] = function (q) {
    var C = D.CUSTOMERS;
    var good = C.filter(function (c) { return c.statusTone === 'ok'; }).length;
    var pend = C.filter(function (c) { return c.statusTone === 'warn' || c.statusTone === 'info'; }).length;
    var exc = C.filter(function (c) { return c.exception; }).length;
    return head('Customer Search', 'Find an account by name, phone, email, account number, ESIID or address.') +
      '<div class="grid-4">' +
      '<div class="stat"><span>Accounts</span><strong>' + C.length + '</strong><small>sample data</small></div>' +
      '<div class="stat"><span>On Flow</span><strong>' + good + '</strong><small>utility accepted</small></div>' +
      '<div class="stat"><span>Pending</span><strong>' + pend + '</strong><small>credit, deposit or utility</small></div>' +
      '<div class="stat alert"><span>Exceptions</span><strong>' + exc + '</strong><small>need attention</small></div>' +
      '</div>' +
      '<div class="panel"><div class="panel-body"><form id="search" class="form-row">' +
      '<div class="field grow"><label for="s-q">Search</label><input id="s-q" name="q" placeholder="Name, phone, email, account #, ESIID…" value="' + esc(q.q || '') + '"></div>' +
      '<div class="field"><label for="s-type">Customer Type</label><select id="s-type" name="type"><option value="">-- All Customer Types --</option><option>Residential</option><option>Small Business</option></select></div>' +
      '<div class="field"><label for="s-status">Status</label><select id="s-status" name="status"><option value="">-- All Statuses --</option>' + D.CUSTOMER_STATUSES.map(function (s) { return '<option>' + s + '</option>'; }).join('') + '</select></div>' +
      '<div class="field"><label for="s-exc">Exception</label><select id="s-exc" name="exc"><option value="">-- Exceptions --</option><option value="*">Any Exception</option>' + D.EXCEPTIONS.map(function (s) { return '<option>' + s + '</option>'; }).join('') + '</select></div>' +
      '<div class="field"><label for="s-limit">Limit</label><select id="s-limit" name="limit"><option value="0">No Limit</option><option>10</option><option selected>25</option><option>50</option><option>100</option></select></div>' +
      '<button class="btn">Search</button></form></div></div>' +
      '<div class="panel"><div class="panel-head"><h2>Bookmarks</h2></div><div id="bookmarks"></div></div>' +
      '<div class="panel"><div class="panel-head"><h2 id="res-title">Recent Accounts</h2><span class="muted" id="res-count"></span></div><div id="results"></div></div>';
  };
  function customerRows(list) {
    return list.map(function (c) {
      return '<tr class="click" data-href="#/corral/customer/' + c.account + '">' +
        '<td class="mono">' + c.account + '</td><td>' + esc(c.created) + '</td><td>' + pill(c.status, c.statusTone) + (c.exception ? ' ' + pill(c.exception, 'bad') : '') + '</td>' +
        '<td><b>' + esc(c.name) + '</b><br><span class="muted">' + esc(c.type) + '</span></td>' +
        '<td>' + esc(c.phone) + '<br><span class="muted">' + esc(c.email) + '</span></td>' +
        '<td>' + esc(c.address) + '<br><span class="muted">' + esc(c.city) + ', TX ' + c.zip + '</span></td>' +
        '<td>' + esc(c.plan) + '<br><span class="muted">' + esc(c.market) + '</span></td><td>' + esc(c.source) + '</td></tr>';
    });
  }
  var CUST_COLS = ['Account', 'Created', 'Status', 'Customer', 'Contact', 'Address', 'Plan', 'Source'];
  V['corral/home'].init = function () {
    var form = $('#search');
    function run() {
      var f = new FormData(form), q = (f.get('q') || '').toLowerCase().replace(/[()\s-]/g, '');
      var list = D.CUSTOMERS.filter(function (c) {
        if (f.get('type') && c.type !== f.get('type')) return false;
        if (f.get('status') && c.status !== f.get('status')) return false;
        if (f.get('exc') === '*' && !c.exception) return false;
        if (f.get('exc') && f.get('exc') !== '*' && c.exception !== f.get('exc')) return false;
        if (!q) return true;
        return [c.name, c.phone, c.email, c.account, c.esiid, c.address, c.city, c.zip, c.ticket].join('|').toLowerCase().replace(/[()\s-]/g, '').indexOf(q) > -1;
      }).sort(function (a, b) { return a.created < b.created ? 1 : -1; });
      var lim = +f.get('limit');
      var total = list.length;
      if (lim) list = list.slice(0, lim);
      $('#res-title').textContent = q || f.get('type') || f.get('status') || f.get('exc') ? 'Search Results' : 'Recent Accounts';
      $('#res-count').textContent = 'Showing ' + list.length + ' of ' + total;
      $('#results').innerHTML = table(CUST_COLS, customerRows(list), { empty: 'No accounts match. Try fewer filters.' });
    }
    form.addEventListener('submit', function (e) { e.preventDefault(); run(); });
    $$('select', form).forEach(function (s) { s.addEventListener('change', run); });
    var bm = D.CUSTOMERS.filter(function (c) { return c.bookmarked; });
    $('#bookmarks').innerHTML = table(CUST_COLS, customerRows(bm), { empty: 'No bookmarks yet. Open an account and choose Bookmark.' });
    run();
  };

  V['corral/customer'] = function (q, id) {
    var c = D.CUSTOMERS.filter(function (x) { return x.account === id; })[0];
    if (!c) return '<div class="panel empty">Account ' + esc(id) + ' was not found. <a href="#/corral/home">Back to search</a></div>';
    var pays = D.payments(c), bills = D.bills(c);
    var notes = D.notes(c).concat(extraNotes[c.account] || []);
    return head(c.name, 'Account <span class="mono">' + c.account + '</span> · Ticket <span class="mono">' + c.ticket + '</span>',
      '<button class="btn ghost" id="bm">' + (c.bookmarked ? '★ Bookmarked' : '☆ Bookmark') + '</button>' +
      '<button class="btn ghost" id="sms">Send SMS</button><a class="btn" href="#/corral/order?renew=' + c.account + '">Renew / Change Plan</a>') +
      '<div class="grid-4">' +
      '<div class="stat"><span>Status</span><strong style="font-size:1rem;margin-top:10px">' + pill(c.status, c.statusTone) + '</strong>' + (c.exception ? '<small>' + pill(c.exception, 'bad') + '</small>' : '') + '</div>' +
      '<div class="stat' + (c.balance > 150 ? ' alert' : '') + '"><span>Balance</span><strong>' + money(c.balance) + '</strong></div>' +
      '<div class="stat"><span>Plan</span><strong style="font-size:1rem;margin-top:8px">' + esc(c.plan) + '</strong><small class="mono">' + c.planCode + '</small></div>' +
      '<div class="stat"><span>Rangler Stars</span><strong>' + c.stars.toLocaleString() + '</strong></div>' +
      '</div>' +
      '<div class="panel"><div class="tabs" role="tablist">' +
      ['Account', 'Service', 'Billing', 'Payments', 'Notes', 'Products'].map(function (t, i) { return '<button role="tab" data-tab="' + i + '"' + (i === 0 ? ' class="on"' : '') + '>' + t + '</button>'; }).join('') +
      '</div><div class="panel-body">' +
      '<div data-pane="0"><dl class="kv">' +
      '<dt>Customer</dt><dd>' + esc(c.name) + '</dd><dt>Type</dt><dd>' + esc(c.type) + '</dd>' +
      '<dt>Phone</dt><dd>' + esc(c.phone) + '</dd><dt>Email</dt><dd>' + esc(c.email) + '</dd>' +
      '<dt>Created</dt><dd>' + esc(c.created) + '</dd><dt>Source</dt><dd>' + esc(c.source) + '</dd>' +
      '<dt>AutoPay</dt><dd>' + (c.autopay ? pill('Enrolled', 'ok') : pill('Not enrolled')) + '</dd>' +
      '<dt>Paperless</dt><dd>' + (c.paperless ? pill('Enrolled', 'ok') : pill('Not enrolled')) + '</dd></dl></div>' +
      '<div data-pane="1" hidden><dl class="kv">' +
      '<dt>Service Address</dt><dd>' + esc(c.address) + ', ' + esc(c.city) + ', TX ' + c.zip + '</dd>' +
      '<dt>ESIID</dt><dd class="mono">' + c.esiid + '</dd><dt>TDSP</dt><dd>' + esc(c.market) + '</dd>' +
      '<dt>Plan</dt><dd>' + esc(c.plan) + ' (' + c.planCode + ')</dd>' +
      '<dt>Status</dt><dd><select id="status-edit">' + D.CUSTOMER_STATUSES.map(function (s) { return '<option' + (s === c.status ? ' selected' : '') + '>' + s + '</option>'; }).join('') + '</select> <button class="btn sm" id="status-save">Update</button></dd></dl></div>' +
      '<div data-pane="2" hidden>' + table(['Bill', 'Date', { label: 'kWh', num: 1 }, { label: 'Amount', num: 1 }, ''], bills.map(function (b) {
        return '<tr><td class="mono">' + b.id + '</td><td>' + b.date + '</td><td class="num">' + b.kwh.toLocaleString() + '</td><td class="num">' + money(b.amount) + '</td><td><a href="#" data-noop>View PDF</a></td></tr>';
      })) + '</div>' +
      '<div data-pane="3" hidden>' + table(['Payment', 'Date', 'Method', 'Source', 'Status', { label: 'Amount', num: 1 }, ''], pays.map(function (p) {
        return '<tr><td class="mono">' + p.id + '</td><td>' + p.date + '</td><td>' + p.method + '</td><td>' + p.source + '</td><td>' + pill(p.status, p.status === 'Success' ? 'ok' : 'bad') + '</td><td class="num">' + money(p.amount) + '</td>' +
          '<td><button class="btn sm ghost" data-reverse="' + p.id + '">Reverse</button></td></tr>';
      })) + '</div>' +
      '<div data-pane="4" hidden><form id="note-form" class="form-row" style="margin-bottom:16px"><div class="field grow"><label for="note-text">Add Note</label><input id="note-text" required placeholder="What happened on this call?"></div>' +
      '<div class="field"><label for="note-disp">Disposition</label><select id="note-disp">' + D.DATA_TABLES['CIS Note Dispositions'].rows.map(function (r) { return '<option>' + r[1] + '</option>'; }).join('') + '</select></div><button class="btn">Add Note</button></form>' +
      '<div id="notes">' + notes.slice().reverse().map(noteHtml).join('') + '</div></div>' +
      '<div data-pane="5" hidden>' + table(['Product', 'Status'], [
        ['Rangler Rewards', 'Enrolled', 'ok'], ['AutoPay', c.autopay ? 'Enrolled' : 'Not enrolled', c.autopay ? 'ok' : ''], ['Paperless', c.paperless ? 'Enrolled' : 'Not enrolled', c.paperless ? 'ok' : ''],
        ['Peak Perks', 'Not enrolled', ''], ['A/C Warranty (AIG)', 'Not enrolled', '']
      ].map(function (r) { return '<tr><td>' + r[0] + '</td><td>' + pill(r[1], r[2]) + '</td></tr>'; })) + '</div>' +
      '</div></div>';
  };
  function noteHtml(n) { return '<div class="note"><small>' + esc(n.at) + ' · ' + esc(n.by) + (n.disp ? ' · ' + esc(n.disp) : '') + '</small>' + esc(n.text) + '</div>'; }
  V['corral/customer'].init = function (q, id) {
    var c = D.CUSTOMERS.filter(function (x) { return x.account === id; })[0];
    if (!c) return;
    $$('.tabs button').forEach(function (b) {
      b.addEventListener('click', function () {
        $$('.tabs button').forEach(function (x) { x.classList.toggle('on', x === b); });
        $$('[data-pane]').forEach(function (p) { p.hidden = p.getAttribute('data-pane') !== b.getAttribute('data-tab'); });
      });
    });
    $('#bm').addEventListener('click', function () {
      c.bookmarked = !c.bookmarked; persist(['CUSTOMERS']);
      this.textContent = c.bookmarked ? '★ Bookmarked' : '☆ Bookmark';
      toast(c.bookmarked ? 'Bookmarked' : 'Bookmark removed');
    });
    $('#sms').addEventListener('click', function () { toast('SMS sending is not connected yet'); });
    $('#status-save').addEventListener('click', function () {
      c.status = $('#status-edit').value;
      c.statusTone = /Good/.test(c.status) ? 'ok' : /Rejected|Dropped/.test(c.status) ? 'bad' : /Submitted/.test(c.status) ? 'info' : 'warn';
      persist(['CUSTOMERS']); toast('Status updated'); route();
    });
    $('#note-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var n = { by: currentUser(), at: new Date().toISOString().slice(0, 16).replace('T', ' '), text: $('#note-text').value, disp: $('#note-disp').value };
      (extraNotes[c.account] = extraNotes[c.account] || []).push(n);
      persist([]);
      $('#notes').insertAdjacentHTML('afterbegin', noteHtml(n));
      $('#note-text').value = '';
      toast('Note added');
    });
    $$('[data-reverse]').forEach(function (b) {
      b.addEventListener('click', function () {
        confirmBox('Reverse payment?', 'Payment ' + b.getAttribute('data-reverse') + ' will be refunded to the original method. This cannot be undone.', 'Reverse Payment', function () {
          b.closest('tr').querySelector('.pill').outerHTML = pill('Reversed', 'warn');
          b.remove(); toast('Payment reversed');
        });
      });
    });
  };

  V['corral/esiid'] = function () {
    return head('ESIID Lookup', 'Search the ERCOT premise database by address or ESIID.') +
      '<div class="panel"><div class="panel-body"><form id="esiid-form" class="form-row">' +
      '<div class="field grow"><label for="e-addr">Street Address or ESIID</label><input id="e-addr" required placeholder="e.g. 1047 Example Ln or 1008901…"></div>' +
      '<div class="field"><label for="e-zip">Zip</label><input id="e-zip" maxlength="5" inputmode="numeric" placeholder="77082" style="width:100px"></div>' +
      '<button class="btn">Look Up</button></form></div></div>' +
      '<div class="panel"><div class="panel-head"><h2>Results</h2></div><div id="esiid-res"><div class="empty">Enter an address or ESIID to search.</div></div></div>';
  };
  V['corral/esiid'].init = function () {
    $('#esiid-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var a = $('#e-addr').value.toLowerCase(), z = $('#e-zip').value;
      var list = D.CUSTOMERS.filter(function (c) { return (c.address.toLowerCase().indexOf(a) > -1 || c.esiid.indexOf(a) > -1) && (!z || c.zip === z); });
      $('#esiid-res').innerHTML = table(['ESIID', 'Address', 'TDSP', 'Meter', 'Premise', 'Status', ''], list.map(function (c, i) {
        return '<tr><td class="mono">' + c.esiid + '</td><td>' + esc(c.address) + ', ' + esc(c.city) + ' ' + c.zip + '</td><td>' + c.market + '</td><td>' + (i % 4 ? 'AMS' : 'AMSR') + '</td><td>' + (c.type === 'Residential' ? 'Residential' : 'Small Non-Residential') + '</td><td>' + pill('Active', 'ok') + '</td>' +
          '<td><a class="btn sm" href="#/corral/order?esiid=' + c.esiid + '">Start Order</a></td></tr>';
      }), { empty: 'No premises found. Check the spelling or try just the house number and street name.' });
    });
  };

  function orderView(biz) {
    return function (q) {
      var renew = q.renew && D.CUSTOMERS.filter(function (c) { return c.account === q.renew; })[0];
      var pre = renew || (q.esiid && D.CUSTOMERS.filter(function (c) { return c.esiid === q.esiid; })[0]) || {};
      var plans = D.PLANS.filter(function (p) { return p.active && p.type === (biz ? 'Biz' : 'Resi'); });
      return head(renew ? 'Renew / Change Plan' : biz ? 'Create Order - Biz' : 'Create Order', renew ? 'For account <span class="mono">' + renew.account + '</span>' : 'Enroll a new ' + (biz ? 'business' : 'residential') + ' customer over the phone.') +
        '<form class="panel" id="order-form"><div class="panel-body"><div class="form-grid">' +
        (biz ? '<label for="o-biz">Business Name</label><input id="o-biz" required>' : '') +
        '<label for="o-name">' + (biz ? 'Contact Name' : 'Customer Name') + '</label><input id="o-name" required value="' + esc(pre.name || '') + '">' +
        '<label for="o-phone">Phone</label><input id="o-phone" required value="' + esc(pre.phone || '') + '">' +
        '<label for="o-email">Email</label><input id="o-email" type="email" required value="' + esc(pre.email || '') + '">' +
        '<label for="o-addr">Service Address</label><input id="o-addr" required value="' + esc(pre.address ? pre.address + ', ' + pre.city + ' ' + pre.zip : '') + '">' +
        '<label for="o-esiid">ESIID</label><input id="o-esiid" class="mono" value="' + esc(pre.esiid || '') + '" placeholder="Leave blank to look up">' +
        '<label for="o-market">Market</label><select id="o-market">' + marketOptions(pre.market) + '</select>' +
        '<label for="o-plan">Plan</label><select id="o-plan">' + plans.map(function (p) { return '<option value="' + p.internal + '">' + esc(p.name) + ' — ' + p.term + ' mo (' + p.internal + ')</option>'; }).join('') + '</select>' +
        '<label for="o-type">Enrollment Type</label><select id="o-type"><option>Switch</option><option>Move-In</option><option>Self-Selected Switch</option>' + (renew ? '<option selected>Renewal</option>' : '') + '</select>' +
        '<label for="o-start">Requested Start</label><input id="o-start" type="date">' +
        '<label>Options</label><div class="actions"><label class="check"><input type="checkbox" checked> AutoPay</label><label class="check"><input type="checkbox" checked> Paperless</label><label class="check"><input type="checkbox"> Peak Perks</label></div>' +
        '<label for="o-source">Source</label><select id="o-source"><option>Phone</option><option>Website</option><option>Referral</option><option>Power to Choose</option></select>' +
        '</div><div class="actions" style="margin-top:20px"><button class="btn cyan">Submit Order</button><a class="btn ghost" href="#/corral/home">Cancel</a></div></div></form>';
    };
  }
  function orderInit() {
    $('#order-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var plan = planByCode($('#o-plan').value);
      var acct = String(1219100000 + Math.floor(Math.random() * 89999));
      var mk = $('#o-market').value, addr = $('#o-addr').value.split(',');
      D.CUSTOMERS.unshift({
        account: acct, ticket: String(Date.now()) + '000000', name: ($('#o-biz') && $('#o-biz').value) || $('#o-name').value,
        type: $('#o-biz') ? 'Small Business' : 'Residential', phone: $('#o-phone').value, email: $('#o-email').value,
        address: addr[0], city: (addr[1] || '').trim().replace(/\s*\d{5}$/, '') || '—', zip: ($('#o-addr').value.match(/\d{5}$/) || ['—'])[0], market: mk,
        esiid: $('#o-esiid').value || '(pending lookup)', plan: plan.name, planCode: plan.internal, status: 'Submitted', statusTone: 'info',
        exception: $('#o-esiid').value ? '' : 'No ESIID', source: $('#o-source').value, created: new Date().toISOString().slice(0, 10),
        balance: 0, autopay: true, paperless: true, stars: 0, bookmarked: false
      });
      persist(['CUSTOMERS']);
      toast('Order submitted for account ' + acct);
      location.hash = '#/corral/customer/' + acct;
    });
  }
  V['corral/order'] = orderView(false); V['corral/order'].init = orderInit;
  V['corral/order-biz'] = orderView(true); V['corral/order-biz'].init = orderInit;

  V['corral/reports'] = function () {
    var today = new Date().toISOString().slice(0, 10);
    return head('Order Report', 'Pull orders by date range and status.') +
      '<form class="panel" id="rep-form"><div class="panel-body" style="display:flex;flex-direction:column;gap:16px">' +
      '<div class="form-row"><div class="field"><label for="r-user">Username</label><input id="r-user" placeholder="Any"></div>' +
      '<div class="field"><label for="r-start">Start</label><input id="r-start" type="date" value="2026-01-01"></div>' +
      '<div class="field"><label for="r-end">End</label><input id="r-end" type="date" value="' + today + '"></div>' +
      '<label class="check"><input type="checkbox" id="r-inactive"> Include Inactive</label></div>' +
      '<div class="actions">' + D.REPORT_STATUSES.map(function (s, i) { return '<label class="check" style="margin-right:10px"><input type="checkbox" name="st" value="' + i + '" checked> ' + s + '</label>'; }).join('') + '</div>' +
      '<div class="form-row"><div class="field"><label for="r-out">Output</label><select id="r-out"><option>On-Screen</option><option>On-Screen - Summary</option><option>CSV</option><option>CSV - Summary</option><option>XLS</option></select></div><button class="btn">Run Report</button></div>' +
      '</div></form><div class="panel" id="rep-res" hidden></div>';
  };
  V['corral/reports'].init = function () {
    $('#rep-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var s = $('#r-start').value, en = $('#r-end').value, out = $('#r-out').value;
      var list = D.CUSTOMERS.filter(function (c) { return c.created >= s && c.created <= en; });
      var res = $('#rep-res'); res.hidden = false;
      if (/CSV|XLS/.test(out)) {
        res.innerHTML = '<div class="panel-body"><div class="banner-note">File exports need the backend. ' + list.length + ' orders matched; showing them on screen instead.</div></div>';
      } else res.innerHTML = '';
      if (/Summary/.test(out)) {
        var by = {};
        list.forEach(function (c) { by[c.status] = (by[c.status] || 0) + 1; });
        res.innerHTML += '<div class="panel-head"><h2>Summary</h2><span class="muted">' + list.length + ' orders</span></div>' +
          table(['Status', { label: 'Orders', num: 1 }], Object.keys(by).map(function (k) { return '<tr><td>' + k + '</td><td class="num">' + by[k] + '</td></tr>'; }));
      } else {
        res.innerHTML += '<div class="panel-head"><h2>Orders</h2><span class="muted">' + list.length + ' orders</span></div>' + table(CUST_COLS, customerRows(list));
      }
    });
  };

  V['corral/queues'] = function () {
    return head('Exception Queues', 'Work items that need a person. Counts are sample data.') +
      '<div class="panel">' + table(['Queue', 'What it holds', { label: 'Open', num: 1 }, ''], D.QUEUES.map(function (qq, i) {
        return '<tr><td><b>' + qq[0] + '</b></td><td class="wrap muted">' + qq[2] + '</td><td class="num">' + (qq[1] ? pill(qq[1], qq[1] > 5 ? 'bad' : 'warn') : pill('0', 'ok')) + '</td>' +
          '<td><a class="btn sm ghost" href="#/corral/queue/' + i + '">Open</a></td></tr>';
      })) + '</div>';
  };
  V['corral/queue'] = function (q, i) {
    var qq = D.QUEUES[+i];
    if (!qq) return '<div class="panel empty">Queue not found.</div>';
    var items = D.CUSTOMERS.slice(+i, +i + qq[1]);
    return head(qq[0], qq[2], '<a class="btn ghost" href="#/corral/queues">All Queues</a>') +
      '<div class="panel">' + table(['Account', 'Customer', 'Plan', 'Status', 'Age', ''], items.map(function (c, k) {
        return '<tr data-row><td class="mono"><a href="#/corral/customer/' + c.account + '">' + c.account + '</a></td><td>' + esc(c.name) + '</td><td>' + esc(c.plan) + '</td><td>' + pill(c.status, c.statusTone) + '</td><td>' + (k + 1) + 'd</td>' +
          '<td><button class="btn sm cyan" data-resolve>Mark Fixed</button></td></tr>';
      }), { empty: 'Queue is clear.' }) + '</div>';
  };
  V['corral/queue'].init = function (q, i) {
    $$('[data-resolve]').forEach(function (b) {
      b.addEventListener('click', function () {
        b.closest('tr').remove();
        D.QUEUES[+i][1] = Math.max(0, D.QUEUES[+i][1] - 1);
        renderMenu(); toast('Marked as fixed');
      });
    });
  };

  // ===== LANDO =====
  V['lando/pages'] = function () {
    var tree = {};
    D.PAGES.forEach(function (p) {
      var parts = p.path === '/' ? ['/'] : p.path.split('/');
      var node = tree;
      parts.forEach(function (part, i) {
        node[part] = node[part] || { _kids: {} };
        if (i === parts.length - 1) node[part]._page = p;
        node = node[part]._kids;
      });
    });
    function render(n) {
      return '<ul class="tree">' + Object.keys(n).map(function (k) {
        var p = n[k]._page, kids = Object.keys(n[k]._kids).length;
        var label = (p ? '<a href="#/lando/page/' + p.id + '">' + esc(k) + '</a>' : esc(k)) + (p && p.redirect ? ' <span class="redir">(redirect to ' + esc(p.redirect) + ')</span>' : '') + (p ? ' <span class="muted">· ' + p.template + '</span>' : '');
        return '<li>' + (kids ? '<details open><summary>' + label + '</summary>' + render(n[k]._kids) + '</details>' : label) + '</li>';
      }).join('') + '</ul>';
    }
    return head('Pages for Energy Texas', 'Working on site: energytexas.com', '<button class="btn ghost" id="sitemap">Rebuild Sitemap</button><a class="btn" href="#/lando/page/new">Add a Page</a>') +
      '<div class="panel"><div class="panel-head"><h2>Site Structure</h2><span class="muted">' + D.PAGES.length + ' pages</span></div><div class="panel-body">' + render(tree) + '</div></div>';
  };
  V['lando/pages'].init = function () { $('#sitemap').addEventListener('click', function () { toast('Sitemap rebuilt (' + D.PAGES.length + ' URLs)'); }); };
  V['lando/page'] = function (q, id) {
    var p = id === 'new' ? { id: 'new', path: '', title: '', redirect: '', template: 'default', status: 'Draft' } : D.PAGES.filter(function (x) { return String(x.id) === id; })[0];
    if (!p) return '<div class="panel empty">Page not found.</div>';
    return head(id === 'new' ? 'Add a Page' : 'Editing ' + p.title, p.path ? '/' + p.path.replace(/^\//, '') : '') +
      '<form class="panel" id="page-form"><div class="panel-body"><div class="form-grid">' +
      '<label for="pg-title">Title</label><input id="pg-title" required value="' + esc(p.title) + '">' +
      '<label for="pg-path">URL Path</label><input id="pg-path" required class="mono" value="' + esc(p.path) + '" placeholder="get-to-learnin/new-article">' +
      '<label for="pg-tpl">Template</label><select id="pg-tpl">' + D.TEMPLATES.map(function (t) { return '<option' + (t.name === p.template ? ' selected' : '') + '>' + t.name + '</option>'; }).join('') + '</select>' +
      '<label for="pg-redir">Redirect To</label><input id="pg-redir" value="' + esc(p.redirect) + '" placeholder="Leave blank for none">' +
      '<label for="pg-status">Status</label><select id="pg-status"><option' + (p.status === 'Published' ? ' selected' : '') + '>Published</option><option' + (p.status === 'Draft' ? ' selected' : '') + '>Draft</option></select>' +
      '<label for="pg-meta">Meta Description</label><input id="pg-meta" placeholder="Shown in search results">' +
      '</div><div class="actions" style="margin-top:20px"><button class="btn cyan">Save Page</button><a class="btn ghost" href="#/lando/pages">Cancel</a>' +
      (id !== 'new' ? '<button type="button" class="btn red" id="pg-del" style="margin-left:auto">Delete</button>' : '') + '</div></div></form>';
  };
  V['lando/page'].init = function (q, id) {
    $('#page-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var p = id === 'new' ? { id: Date.now() } : D.PAGES.filter(function (x) { return String(x.id) === id; })[0];
      p.title = $('#pg-title').value; p.path = $('#pg-path').value.replace(/^\/+/, '') || '/'; p.template = $('#pg-tpl').value;
      p.redirect = $('#pg-redir').value; p.status = $('#pg-status').value;
      if (id === 'new') D.PAGES.push(p);
      persist(['PAGES']); toast('Page saved'); location.hash = '#/lando/pages';
    });
    if ($('#pg-del')) $('#pg-del').addEventListener('click', function () {
      confirmBox('Delete page?', 'This removes the page and its URL from the site.', 'Delete Page', function () {
        D.PAGES = D.PAGES.filter(function (x) { return String(x.id) !== id; });
        persist(['PAGES']); toast('Page deleted'); location.hash = '#/lando/pages';
      });
    });
  };

  V['lando/templates'] = function () {
    return head('Page Templates', 'Layouts that pages are built on.') +
      '<div class="panel">' + table(['Template', 'Description', { label: 'Pages', num: 1 }], D.TEMPLATES.map(function (t) {
        return '<tr><td class="mono"><b>' + t.name + '</b></td><td class="wrap">' + esc(t.desc) + '</td><td class="num">' + t.pages + '</td></tr>';
      })) + '</div>';
  };

  V['lando/blocks'] = function () {
    return head('Content Blocks', 'Reusable HTML snippets placed into pages with shortcodes.', '<a class="btn" href="#/lando/block/new">Add a Block</a>') +
      '<div class="panel">' + table(['ID', 'Name', 'Site', 'Updated', 'Shortcode', ''], D.BLOCKS.map(function (b) {
        return '<tr><td class="num">' + b.id + '</td><td><b>' + esc(b.name) + '</b></td><td>' + b.site + '</td><td>' + b.updated + '</td><td class="mono">[[block|id=' + b.id + ']]</td><td><a class="btn sm ghost" href="#/lando/block/' + b.id + '">Edit</a></td></tr>';
      })) + '</div>';
  };
  V['lando/block'] = function (q, id) {
    var b = id === 'new' ? { id: 'new', name: '', site: 'energytexas.com', html: '' } : D.BLOCKS.filter(function (x) { return String(x.id) === id; })[0];
    if (!b) return '<div class="panel empty">Block not found.</div>';
    return head(id === 'new' ? 'Add a Content Block' : 'Editing Content Block ' + b.id, esc(b.name)) +
      '<div class="grid-2"><form class="panel" id="block-form"><div class="panel-body"><div class="form-grid" style="grid-template-columns:1fr">' +
      '<label for="b-name">Name</label><input id="b-name" required value="' + esc(b.name) + '">' +
      '<label for="b-html">HTML</label><textarea id="b-html" spellcheck="false">' + esc(b.html) + '</textarea>' +
      '</div><div class="actions" style="margin-top:16px"><button class="btn cyan">Save Block</button><a class="btn ghost" href="#/lando/blocks">Cancel</a></div><p class="muted" style="margin-top:10px;font-size:.8rem">Tip: Ctrl+S saves.</p></div></form>' +
      '<div class="panel"><div class="panel-head"><h2>Preview</h2><span class="muted">shortcodes shown as-is</span></div><div class="panel-body"><iframe id="b-prev" title="Block preview" sandbox="" style="width:100%;min-height:340px;border:1px solid var(--line);border-radius:6px;background:#fff"></iframe></div></div></div>';
  };
  V['lando/block'].init = function (q, id) {
    var ta = $('#b-html'), fr = $('#b-prev');
    function prev() { fr.srcdoc = '<style>body{font-family:sans-serif;padding:12px;color:#102247}</style>' + ta.value; }
    ta.addEventListener('input', prev); prev();
    function save(e) {
      if (e) e.preventDefault();
      var b = id === 'new' ? { id: Math.max.apply(null, D.BLOCKS.map(function (x) { return x.id; })) + 1, site: 'energytexas.com' } : D.BLOCKS.filter(function (x) { return String(x.id) === id; })[0];
      b.name = $('#b-name').value || 'Untitled block'; b.html = ta.value; b.updated = new Date().toISOString().slice(0, 10);
      if (id === 'new') { D.BLOCKS.push(b); location.hash = '#/lando/block/' + b.id; }
      persist(['BLOCKS']); toast('Block saved');
    }
    $('#block-form').addEventListener('submit', save);
    ta.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 's') save(e);
      if (e.key === 'Tab') { e.preventDefault(); var s = ta.selectionStart; ta.setRangeText('  ', s, ta.selectionEnd, 'end'); }
    });
  };

  V['lando/plans'] = function () {
    function rows(active) {
      return D.PLANS.filter(function (p) { return p.active === active; }).sort(function (a, b) { return a.type.localeCompare(b.type) || a.term - b.term; }).map(function (p) {
        return '<tr><td>' + p.type + '</td><td class="num">' + p.term + '</td><td><b>' + esc(p.name) + '</b></td><td class="mono">' + p.internal + '</td><td class="mono">' + p.rolloff + '</td><td>' + p.etf + '</td><td class="num">' + (p.mrc ? money(p.mrc) : '-') + '</td><td class="num">' + p.green + '%</td>' +
          '<td><a class="btn sm ghost" href="#/lando/plan/' + p.id + '">Edit</a> <a class="btn sm ghost" href="#/lando/rates?plan=' + p.internal + '">Rates</a></td></tr>';
      });
    }
    var cols = ['Type', { label: 'Term', num: 1 }, 'Display Name', 'Internal Name', 'Rolloff', 'ETF', { label: 'MRC', num: 1 }, { label: 'Green', num: 1 }, ''];
    return head('Plans', 'Every plan the site can sell.', '<a class="btn" href="#/lando/plan/new">Add a Plan</a>') +
      '<div class="panel"><div class="panel-head"><h2>Active Plans</h2><span class="muted">' + D.PLANS.filter(function (p) { return p.active; }).length + '</span></div>' + table(cols, rows(true)) + '</div>' +
      '<div class="panel"><div class="panel-head"><h2>Inactive Plans</h2><span class="muted">' + D.PLANS.filter(function (p) { return !p.active; }).length + '</span></div>' + table(cols, rows(false)) + '</div>';
  };
  V['lando/plan'] = function (q, id) {
    var p = id === 'new' ? { id: 'new', type: 'Resi', term: 12, name: '', internal: '', slug: '', rolloff: 'JEY3', etf: '$250', mrc: 4.95, green: 100, active: true } : D.PLANS.filter(function (x) { return String(x.id) === id; })[0];
    if (!p) return '<div class="panel empty">Plan not found.</div>';
    return head(id === 'new' ? 'Add a Plan' : 'Editing ' + p.name, id === 'new' ? '' : 'Plan ID ' + p.id) +
      '<form class="panel" id="plan-form"><div class="panel-body"><div class="form-grid">' +
      '<label for="p-name">Plan Name</label><input id="p-name" required value="' + esc(p.name) + '">' +
      '<label for="p-int">Internal Name</label><input id="p-int" required class="mono" value="' + esc(p.internal) + '">' +
      '<label for="p-slug">URL Slug</label><input id="p-slug" class="mono" value="' + esc(p.slug) + '" placeholder="plan-name-12">' +
      '<label for="p-status">Status</label><select id="p-status"><option value="1"' + (p.active ? ' selected' : '') + '>Active</option><option value="0"' + (!p.active ? ' selected' : '') + '>Inactive</option></select>' +
      '<label for="p-type">Rate Class</label><select id="p-type"><option' + (p.type === 'Resi' ? ' selected' : '') + '>Resi</option><option' + (p.type === 'Biz' ? ' selected' : '') + '>Biz</option></select>' +
      '<label for="p-term">Term (months)</label><input id="p-term" type="number" min="1" max="60" value="' + p.term + '">' +
      '<label for="p-roll">Rolloff Plan</label><select id="p-roll"><option>None</option>' + D.PLANS.filter(function (x) { return x.term === 1; }).map(function (x) { return '<option' + (x.internal === p.rolloff ? ' selected' : '') + '>' + x.internal + '</option>'; }).join('') + '</select>' +
      '<label for="p-etf">Early Termination Fee</label><input id="p-etf" value="' + esc(p.etf) + '">' +
      '<label for="p-mrc">Monthly Base Charge</label><input id="p-mrc" type="number" step="0.01" value="' + p.mrc + '">' +
      '<label for="p-green">Renewable %</label><input id="p-green" type="number" min="0" max="100" value="' + p.green + '">' +
      '<label for="p-desc">Marketing Bullets</label><textarea id="p-desc" style="min-height:100px;font-family:inherit" placeholder="One per line"></textarea>' +
      '</div><div class="actions" style="margin-top:20px"><button class="btn cyan">Save Plan</button><a class="btn ghost" href="#/lando/plans">Cancel</a></div></div></form>';
  };
  V['lando/plan'].init = function (q, id) {
    $('#p-name').addEventListener('input', function () {
      if (id === 'new') $('#p-slug').value = this.value.toLowerCase().replace(/&/g, 'and').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    });
    $('#plan-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var p = id === 'new' ? { id: Math.max.apply(null, D.PLANS.map(function (x) { return x.id; })) + 1 } : D.PLANS.filter(function (x) { return String(x.id) === id; })[0];
      var code = $('#p-int').value.trim();
      if (D.PLANS.some(function (x) { return x.internal === code && x !== p; })) { toast('Internal name ' + code + ' is already used by another plan'); return; }
      p.name = $('#p-name').value; p.internal = code; p.slug = $('#p-slug').value; p.active = $('#p-status').value === '1';
      p.type = $('#p-type').value; p.term = +$('#p-term').value; p.rolloff = $('#p-roll').value; p.etf = $('#p-etf').value;
      p.mrc = +$('#p-mrc').value; p.green = +$('#p-green').value;
      if (id === 'new') D.PLANS.push(p);
      persist(['PLANS']); toast('Plan saved'); location.hash = '#/lando/plans';
    });
  };

  V['lando/groups'] = function () {
    return head('Plan Groups', 'Groups control which plans show on each page (e.g. [[plans|group=featured]]).') +
      '<div class="grid-2">' + D.PLAN_GROUPS.map(function (g) {
        return '<div class="panel"><div class="panel-head"><h2>' + esc(g.name) + '</h2><span class="mono muted">' + g.slug + '</span></div><div class="panel-body">' +
          '<div class="actions" data-group="' + g.id + '">' + g.plans.map(function (c) { return '<span class="pill info">' + c + ' <a href="#" data-rm="' + c + '" aria-label="Remove ' + c + '">×</a></span>'; }).join('') + '</div>' +
          '<div class="form-row" style="margin-top:12px"><select data-add="' + g.id + '"><option value="">Add a plan…</option>' + D.PLANS.filter(function (p) { return p.active && g.plans.indexOf(p.internal) < 0; }).map(function (p) { return '<option value="' + p.internal + '">' + esc(p.name) + ' (' + p.internal + ')</option>'; }).join('') + '</select></div>' +
          '</div></div>';
      }).join('') + '</div>';
  };
  V['lando/groups'].init = function () {
    $$('[data-add]').forEach(function (s) {
      s.addEventListener('change', function () {
        if (!s.value) return;
        var g = D.PLAN_GROUPS.filter(function (x) { return String(x.id) === s.getAttribute('data-add'); })[0];
        g.plans.push(s.value); persist(['PLAN_GROUPS']); toast(s.value + ' added to ' + g.name); route();
      });
    });
    $$('[data-rm]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        var g = D.PLAN_GROUPS.filter(function (x) { return String(x.id) === a.closest('[data-group]').getAttribute('data-group'); })[0];
        g.plans = g.plans.filter(function (c) { return c !== a.getAttribute('data-rm'); });
        persist(['PLAN_GROUPS']); toast('Removed'); route();
      });
    });
  };

  function feeFor(m) { return D.TDSP_FEES.filter(function (f) { return f.market === m; })[0]; }
  V['lando/rates'] = function (q) {
    return head('Search Rates', 'Energy charge plus TDSP delivery gives the average price customers see on the EFL.') +
      '<div class="panel"><div class="panel-body"><form id="rate-form" class="form-row">' +
      '<div class="field grow"><label for="r-plan">Plan</label><input id="r-plan" placeholder="Search Plan Name or code" value="' + esc(q.plan || '') + '"></div>' +
      '<div class="field"><label for="r-market">Market</label><select id="r-market">' + marketOptions(q.market, true) + '</select></div>' +
      '<div class="field"><label for="r-kwh">Usage</label><select id="r-kwh"><option>500</option><option selected>1000</option><option>2000</option></select></div>' +
      '<button class="btn">Search</button></form></div></div>' +
      '<div class="panel"><div class="panel-head"><h2>Current Rates</h2><span class="muted" id="rate-count"></span></div><div id="rate-res"></div></div>';
  };
  V['lando/rates'].init = function () {
    function run(e) {
      if (e) e.preventDefault();
      var pq = $('#r-plan').value.toLowerCase(), m = $('#r-market').value, kwh = +$('#r-kwh').value;
      var list = D.RATES.filter(function (r) {
        var p = planByCode(r.plan); if (!p) return false;
        return (!m || r.market === m) && (!pq || p.name.toLowerCase().indexOf(pq) > -1 || p.internal.toLowerCase().indexOf(pq) > -1);
      });
      $('#rate-count').textContent = list.length + ' rates';
      $('#rate-res').innerHTML = table(['Rate Class', { label: 'Term', num: 1 }, 'Display Name', 'Internal', 'Market', { label: 'Energy ¢', num: 1 }, { label: 'TDSP ¢', num: 1 }, { label: 'Avg ¢ @ ' + kwh, num: 1 }, 'Effective', 'EFL'], list.map(function (r) {
        var p = planByCode(r.plan), f = feeFor(r.market);
        var avg = r.energy + f.perKwh + ((f.perBill + p.mrc) / kwh) * 100;
        return '<tr><td>' + p.type + '</td><td class="num">' + p.term + '</td><td>' + esc(p.name) + '</td><td class="mono">' + p.internal + '</td><td>' + r.market + '</td>' +
          '<td class="num">' + r.energy.toFixed(3) + '</td><td class="num">' + f.perKwh.toFixed(4) + '</td><td class="num"><b>' + avg.toFixed(1) + '</b></td><td>' + r.effective + '</td><td><a href="#" data-noop>PDF</a></td></tr>';
      }), { empty: 'No rates match.' });
    }
    $('#rate-form').addEventListener('submit', run);
    $('#r-market').addEventListener('change', run); $('#r-kwh').addEventListener('change', run);
    run();
  };

  V['lando/update-rates'] = function () {
    var plans = D.PLANS.filter(function (p) { return p.active; });
    return head('Update Rates', 'Edit energy charges (¢/kWh) by plan and market. Changed cells are highlighted.', '<button class="btn ghost" id="ur-bump">Adjust All…</button><button class="btn cyan" id="ur-save">Save Rates</button>') +
      '<div class="panel"><div class="panel-body form-row"><div class="field"><label for="ur-eff">Effective Date</label><input id="ur-eff" type="date" value="' + new Date().toISOString().slice(0, 10) + '"></div><p class="muted" style="margin:0 0 6px">Saving sets this effective date on every changed rate.</p></div>' +
      table(['Plan', 'Internal'].concat(D.MARKETS.map(function (m) { return { label: m.short, num: 1 }; })), plans.map(function (p) {
        return '<tr><td>' + esc(p.name) + '</td><td class="mono">' + p.internal + '</td>' + D.MARKETS.map(function (m) {
          var r = D.RATES.filter(function (x) { return x.plan === p.internal && x.market === m.name; })[0];
          return '<td class="num"><input type="number" step="0.001" data-plan="' + p.internal + '" data-market="' + m.name + '" value="' + (r ? r.energy.toFixed(3) : '') + '" aria-label="' + esc(p.name) + ' ' + m.short + '"></td>';
        }).join('') + '</tr>';
      })) + '</div>';
  };
  V['lando/update-rates'].init = function () {
    $$('[data-plan]').forEach(function (i) { i.dataset.orig = i.value; i.addEventListener('input', function () { i.classList.toggle('dirty', i.value !== i.dataset.orig); }); });
    $('#ur-bump').addEventListener('click', function () {
      var bg = document.createElement('div'); bg.className = 'modal-bg';
      bg.innerHTML = '<form class="modal"><h2>Adjust all rates</h2><p>Add or subtract an amount from every rate on this screen. Use a negative number to lower rates.</p><div class="field"><label for="adj">Change (¢/kWh)</label><input id="adj" type="number" step="0.001" value="0.100" required></div><div class="actions" style="justify-content:flex-end;margin-top:16px"><button type="button" class="btn ghost" data-x>Cancel</button><button class="btn cyan">Apply</button></div></form>';
      document.body.appendChild(bg);
      bg.addEventListener('click', function (e) { if (e.target === bg || e.target.hasAttribute('data-x')) bg.remove(); });
      $('form', bg).addEventListener('submit', function (e) {
        e.preventDefault(); var d = +$('#adj', bg).value;
        $$('[data-plan]').forEach(function (i) { if (i.value) { i.value = (+i.value + d).toFixed(3); i.classList.toggle('dirty', i.value !== i.dataset.orig); } });
        bg.remove(); toast('Adjusted — review, then Save Rates');
      });
    });
    $('#ur-save').addEventListener('click', function () {
      var n = 0, eff = $('#ur-eff').value;
      $$('[data-plan].dirty').forEach(function (i) {
        var r = D.RATES.filter(function (x) { return x.plan === i.dataset.plan && x.market === i.dataset.market; })[0];
        if (!r) { r = { plan: i.dataset.plan, market: i.dataset.market }; D.RATES.push(r); }
        r.energy = +i.value; r.effective = eff; i.dataset.orig = i.value; i.classList.remove('dirty'); n++;
      });
      persist(['RATES']); toast(n ? n + ' rates saved' : 'No changes to save');
    });
  };

  V['lando/tdsp'] = function () {
    return head('TDSP Fees', 'Utility delivery charges passed through on every bill. Changing a value sets a new effective date.', '<button class="btn cyan" id="tdsp-save">Save Fees</button>') +
      '<div class="panel">' + table(['Market', 'Utility', { label: 'Per kWh (¢)', num: 1 }, 'Effective', { label: 'Per Bill ($)', num: 1 }, 'Effective'], D.TDSP_FEES.map(function (f) {
        var m = D.MARKETS.filter(function (x) { return x.name === f.market; })[0];
        return '<tr><td class="mono">' + f.market + '</td><td>' + esc(m.desc) + '</td>' +
          '<td class="num"><input type="number" step="0.0001" data-k="perKwh" data-m="' + f.market + '" value="' + f.perKwh.toFixed(4) + '"></td>' +
          '<td><input type="date" data-k="kwhDate" data-m="' + f.market + '" value="' + f.kwhDate + '" disabled></td>' +
          '<td class="num"><input type="number" step="0.01" data-k="perBill" data-m="' + f.market + '" value="' + f.perBill.toFixed(2) + '"></td>' +
          '<td><input type="date" data-k="billDate" data-m="' + f.market + '" value="' + f.billDate + '" disabled></td></tr>';
      })) + '</div><p class="muted" style="font-size:.8rem">Sample values. Confirm against the current PUCT-approved tariffs before saving.</p>';
  };
  V['lando/tdsp'].init = function () {
    var today = new Date().toISOString().slice(0, 10);
    $$('input[data-k=perKwh], input[data-k=perBill]').forEach(function (i) {
      i.addEventListener('input', function () {
        i.classList.add('dirty');
        var d = $('input[data-m="' + i.dataset.m + '"][data-k=' + (i.dataset.k === 'perKwh' ? 'kwhDate' : 'billDate') + ']');
        d.disabled = false; if (d.value < today) d.value = today;
      });
    });
    $('#tdsp-save').addEventListener('click', function () {
      $$('input[data-m]').forEach(function (i) {
        var f = feeFor(i.dataset.m);
        f[i.dataset.k] = i.type === 'date' ? i.value : +i.value;
      });
      persist(['TDSP_FEES']); toast('TDSP fees saved'); route();
    });
  };

  V['lando/markets'] = function () {
    return head('Markets', 'Texas utility service territories (TDSPs).') +
      '<div class="panel">' + table(['ID', 'Name', 'Short', 'Type', 'Description', 'Region', 'Utility Phone', 'Commodity'], D.MARKETS.map(function (m) {
        return '<tr><td class="num">' + m.id + '</td><td class="mono"><b>' + m.name + '</b></td><td>' + m.short + '</td><td>tdsp</td><td>' + esc(m.desc) + '</td><td>' + m.region + '</td><td>' + m.phone + '</td><td>Electric</td></tr>';
      })) + '</div>';
  };

  // ===== ASTRO =====
  V['astro/term'] = function () {
    return head('Term & Discounts', 'Values are in ¢/kWh. A positive value lowers the rate; a negative value raises it. 1.0 is a one-cent discount.', '<button class="btn cyan" id="tm-save">Save Modifiers</button>') +
      '<div class="panel">' + table([{ label: 'Term', num: 1 }].concat(D.REGIONS.map(function (r) { return { label: r, num: 1 }; })), D.TERM_MODS.map(function (row) {
        return '<tr><td class="num"><b>' + row.term + '</b></td>' + D.REGIONS.map(function (r) {
          return '<td class="num"><input type="number" step="0.01" data-t="' + row.term + '" data-r="' + r + '" value="' + row[r].toFixed(2) + '" class="' + (row[r] < 0 ? 'neg' : '') + '" aria-label="Term ' + row.term + ' ' + r + '"></td>';
        }).join('') + '</tr>';
      })) + '</div>';
  };
  V['astro/term'].init = function () {
    $$('[data-t]').forEach(function (i) {
      var o = i.value;
      i.addEventListener('input', function () { i.classList.toggle('dirty', i.value !== o); i.classList.toggle('neg', +i.value < 0); });
    });
    $('#tm-save').addEventListener('click', function () {
      $$('[data-t]').forEach(function (i) { D.TERM_MODS[+i.dataset.t - 1][i.dataset.r] = +i.value; });
      persist(['TERM_MODS']); toast('Term modifiers saved'); route();
    });
  };
  V['astro/etf'] = function () {
    return head('ETF by Term', 'Early termination fee charged when a customer leaves a fixed plan early.', '<button class="btn cyan" id="etf-save">Save ETFs</button>') +
      '<div class="panel" style="max-width:520px">' + table([{ label: 'Term (months)', num: 1 }, { label: 'ETF ($)', num: 1 }], D.TERM_MODS.map(function (row) {
        return '<tr><td class="num">' + row.term + '</td><td class="num"><input type="number" step="1" min="0" data-etf="' + row.term + '" value="' + row.etf + '"></td></tr>';
      })) + '</div>';
  };
  V['astro/etf'].init = function () {
    $$('[data-etf]').forEach(function (i) { var o = i.value; i.addEventListener('input', function () { i.classList.toggle('dirty', i.value !== o); }); });
    $('#etf-save').addEventListener('click', function () {
      $$('[data-etf]').forEach(function (i) { D.TERM_MODS[+i.dataset.etf - 1].etf = +i.value; });
      persist(['TERM_MODS']); toast('ETFs saved'); route();
    });
  };
  V['astro/byop'] = function () {
    return head('BYOP Products', 'Add-ons offered in the Build Your Own Plan flow.') +
      '<div class="panel">' + table(['Product', 'Model', 'Step', 'Type', 'Value', 'Active'], D.BYOP_PRODUCTS.map(function (p) {
        return '<tr><td><b>' + esc(p.name) + '</b></td><td class="mono">' + p.model + '</td><td>' + esc(p.step) + '</td><td>' + p.type + '</td><td>' + esc(p.value) + '</td>' +
          '<td><label class="check"><input type="checkbox" data-prod="' + p.id + '"' + (p.active ? ' checked' : '') + '> ' + (p.active ? 'Shown' : 'Hidden') + '</label></td></tr>';
      })) + '</div>';
  };
  V['astro/byop'].init = function () {
    $$('[data-prod]').forEach(function (c) {
      c.addEventListener('change', function () {
        var p = D.BYOP_PRODUCTS.filter(function (x) { return String(x.id) === c.dataset.prod; })[0];
        p.active = c.checked; c.nextSibling.textContent = c.checked ? ' Shown' : ' Hidden';
        persist(['BYOP_PRODUCTS']); toast(p.name + (c.checked ? ' shown in BYOP' : ' hidden from BYOP'));
      });
    });
  };

  // ===== SHERIFF =====
  V['sheriff/users'] = function () {
    return head('Users', 'Who can sign in to the admin tools.', '<button class="btn" id="u-add">Add User</button>') +
      '<div class="panel">' + table(['Name', 'Email', 'Role', 'Apps', 'Last Sign-in', 'Status', ''], D.USERS.map(function (u) {
        return '<tr><td><b>' + esc(u.name) + '</b></td><td>' + esc(u.email) + '</td><td><select data-urole="' + u.id + '">' + D.ROLES.map(function (r) { return '<option' + (r.name === u.role ? ' selected' : '') + '>' + r.name + '</option>'; }).join('') + '</select></td>' +
          '<td>' + esc(u.apps) + '</td><td>' + u.last + '</td><td>' + (u.active ? pill('Active', 'ok') : pill('Disabled')) + '</td>' +
          '<td><button class="btn sm ghost" data-utoggle="' + u.id + '">' + (u.active ? 'Disable' : 'Enable') + '</button></td></tr>';
      })) + '</div>';
  };
  V['sheriff/users'].init = function () {
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
        D.USERS.push({ id: Date.now(), name: $('#nu-name', bg).value, email: $('#nu-email', bg).value, role: $('#nu-role', bg).value, apps: '—', last: 'Never', active: true });
        persist(['USERS']); bg.remove(); toast('User added — they will get an invite email once the backend is connected'); route();
      });
    });
  };
  V['sheriff/roles'] = function () {
    return head('Roles', 'What each role is allowed to do.', '<button class="btn cyan" id="roles-save">Save Roles</button>') +
      '<div class="panel">' + table(['Role'].concat(D.PERMS.map(function (p) { return p[1]; })), D.ROLES.map(function (r, ri) {
        return '<tr><td><b>' + r.name + '</b></td>' + D.PERMS.map(function (p) {
          return '<td style="text-align:center"><input type="checkbox" data-role="' + ri + '" data-perm="' + p[0] + '"' + (r.perms.indexOf(p[0]) > -1 ? ' checked' : '') + (r.name === 'Administrator' ? ' disabled' : '') + ' aria-label="' + r.name + ': ' + p[1] + '"></td>';
        }).join('') + '</tr>';
      })) + '</div>';
  };
  V['sheriff/roles'].init = function () {
    $('#roles-save').addEventListener('click', function () {
      D.ROLES.forEach(function (r, ri) {
        if (r.name === 'Administrator') return;
        r.perms = $$('[data-role="' + ri + '"]:checked').map(function (c) { return c.dataset.perm; });
      });
      persist(['ROLES']); toast('Roles saved');
    });
  };
  V['sheriff/apis'] = function () {
    return head('APIs', 'Third-party integrations.') +
      '<div class="banner-note">API keys and passwords are not stored in this site. Keep them in server environment variables or a secrets manager; this screen only shows whether each one is set.</div>' +
      '<div class="grid-3">' + D.APIS.map(function (a) {
        return '<div class="panel"><div class="panel-head"><h2>' + a.name + '</h2>' + pill('Not configured', 'warn') + '</div><div class="panel-body"><p class="muted" style="margin-bottom:10px">' + esc(a.purpose) + '</p>' +
          '<dl class="kv" style="grid-template-columns:1fr auto">' + a.fields.map(function (f) { return '<dt class="mono">' + f + '</dt><dd class="muted">—</dd>'; }).join('') + '</dl>' +
          '<div class="actions" style="margin-top:14px"><button class="btn sm ghost" data-test="' + a.name + '">Test Connection</button></div></div></div>';
      }).join('') + '</div>';
  };
  V['sheriff/apis'].init = function () {
    $$('[data-test]').forEach(function (b) { b.addEventListener('click', function () { toast(b.dataset.test + ': no credentials set on the server'); }); });
  };
  V['sheriff/crons'] = function () {
    return head('Crons', 'Scheduled background jobs.') +
      '<div class="panel">' + table(['Job', 'Schedule', 'Last Run', { label: 'Duration', num: 1 }, 'Status', ''], D.CRONS.map(function (c, i) {
        return '<tr><td><b>' + esc(c.name) + '</b></td><td class="mono">' + c.schedule + '</td><td>' + c.last + '</td><td class="num">' + (c.ms ? (c.ms / 1000).toFixed(1) + 's' : '—') + '</td>' +
          '<td>' + pill(c.status, c.status === 'OK' ? 'ok' : c.status === 'Warning' ? 'warn' : 'bad') + '</td><td><button class="btn sm ghost" data-run="' + i + '">Run Now</button></td></tr>';
      })) + '</div>';
  };
  V['sheriff/crons'].init = function () {
    $$('[data-run]').forEach(function (b) {
      b.addEventListener('click', function () {
        var c = D.CRONS[+b.dataset.run];
        c.last = new Date().toISOString().slice(0, 16).replace('T', ' '); c.status = 'OK'; c.ms = 1000 + Math.floor(Math.random() * 9000);
        toast(c.name + ' finished'); renderMenu(); route();
      });
    });
  };
  V['sheriff/data'] = function (q, name) {
    name = decodeURIComponent(name || '');
    var t = D.DATA_TABLES[name];
    if (!t) return '<div class="panel empty">Table not found.</div>';
    return head(name, 'Reference data used by enrollment, billing and the website.', '<button class="btn ghost" id="dt-add">Add Row</button><button class="btn cyan" id="dt-save">Save</button>') +
      '<div class="panel">' + table(t.cols.concat(['']), t.rows.map(function (r, ri) {
        return '<tr>' + r.map(function (v, ci) { return '<td><input data-r="' + ri + '" data-c="' + ci + '" value="' + esc(v) + '" style="width:100%;min-width:110px;text-align:left"></td>'; }).join('') + '<td><button class="btn sm ghost" data-del="' + ri + '">Remove</button></td></tr>';
      })) + '</div>';
  };
  V['sheriff/data'].init = function (q, name) {
    name = decodeURIComponent(name || '');
    var t = D.DATA_TABLES[name]; if (!t) return;
    function collect() { $$('[data-r]').forEach(function (i) { t.rows[+i.dataset.r][+i.dataset.c] = i.value; }); }
    $('#dt-add').addEventListener('click', function () { collect(); t.rows.push(t.cols.map(function () { return ''; })); route(); });
    $('#dt-save').addEventListener('click', function () { collect(); toast(name + ' saved'); });
    $$('[data-del]').forEach(function (b) { b.addEventListener('click', function () { collect(); t.rows.splice(+b.dataset.del, 1); route(); }); });
  };

  /* ---------- Shell, menu, router ---------- */
  function currentUser() {
    try { return (JSON.parse(sessionStorage.getItem(SESSION_KEY)) || {}).name || 'Admin Demo'; } catch (e) { return 'Admin Demo'; }
  }
  function parseHash() {
    var h = location.hash.replace(/^#\/?/, '') || 'corral/home';
    var qi = h.indexOf('?'), qs = {};
    if (qi > -1) { h.slice(qi + 1).split('&').forEach(function (kv) { var p = kv.split('='); qs[decodeURIComponent(p[0])] = decodeURIComponent(p[1] || ''); }); h = h.slice(0, qi); }
    var parts = h.split('/');
    return { app: parts[0], view: parts[1] || '', arg: parts.slice(2).join('/'), q: qs, path: h };
  }
  // Detail screens highlight their list in the menu
  var ALIAS = { plan: 'plans', block: 'blocks', page: 'pages', queue: 'queues', customer: 'home' };
  function renderMenu() {
    var r = parseHash(), app = APPS[r.app] ? r.app : 'corral';
    $('#app-switch').innerHTML = Object.keys(APPS).map(function (k) {
      var first = APPS[k].menu[0][1][0][0];
      return '<a href="#/' + k + '/' + first + '"' + (k === app ? ' class="on"' : '') + ' title="' + APPS[k].desc + '">' + ICONS[k] + APPS[k].name + '</a>';
    }).join('');
    $('#menu').innerHTML = APPS[app].menu.map(function (sec) {
      return '<h4>' + sec[0] + '</h4>' + sec[1].map(function (it) {
        var href = app + '/' + it[0];
        var cur = ALIAS[r.view] || r.view;
        var on = r.app === app && (r.path === href || (it[0] === cur && !(r.view === 'plan' && r.arg === 'new')));
        var n = it[2] ? it[2]() : '';
        return '<a href="#/' + href + '"' + (on ? ' class="on"' : '') + '>' + esc(it[1]) + (n ? '<span class="count' + (n > 9 || it[0] === 'crons' ? ' hot' : '') + '">' + n + '</span>' : '') + '</a>';
      }).join('');
    }).join('');
  }
  function route() {
    var r = parseHash();
    var key = r.app + '/' + r.view;
    var view = V[key];
    if (!view) { location.replace('#/corral/home'); return; }
    renderMenu();
    $('#crumbs').innerHTML = esc(APPS[r.app].name) + ' / <b>' + esc(document.querySelector('#menu a.on') ? document.querySelector('#menu a.on').firstChild.textContent : r.view) + '</b>';
    var c = $('#content');
    c.innerHTML = view(r.q, r.arg);
    if (view.init) view.init(r.q, r.arg);
    $$('[data-href]', c).forEach(function (tr) { tr.addEventListener('click', function (e) { if (!e.target.closest('a,button,input,select')) location.hash = tr.dataset.href; }); });
    $$('[data-noop]', c).forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); toast('Documents are served by the backend'); }); });
    $('#sidebar').classList.remove('open');
    document.title = APPS[r.app].name + ' · Energy Texas Admin';
  }

  function boot() {
    var sess = null;
    try { sess = JSON.parse(sessionStorage.getItem(SESSION_KEY)); } catch (e) { sess = { name: 'Admin Demo' }; }
    if (!sess) { location.replace('login.html'); return; }
    $('#user-name').textContent = sess.name;
    $('#user-initials').textContent = sess.name.split(' ').map(function (w) { return w[0]; }).join('').slice(0, 2);
    $('#menu-btn').addEventListener('click', function () { $('#sidebar').classList.toggle('open'); });
    $('#logout').addEventListener('click', function (e) { e.preventDefault(); try { sessionStorage.removeItem(SESSION_KEY); } catch (x) {} location.href = 'login.html'; });
    $('#reset').addEventListener('click', function (e) {
      e.preventDefault();
      confirmBox('Reset sample data?', 'This clears every change saved in this browser and restores the original sample data.', 'Reset Data', function () {
        try { localStorage.removeItem(STORE_KEY); } catch (x) {} location.reload();
      });
    });
    $('#find').addEventListener('input', function () {
      var v = this.value.toLowerCase();
      $$('#menu a').forEach(function (a) { a.hidden = v && a.textContent.toLowerCase().indexOf(v) < 0; });
    });
    window.addEventListener('hashchange', route);
    route();
  }
  document.addEventListener('DOMContentLoaded', boot);
})();
