/* Corral views — loaded by admin/_app/shell.js for admin/corral/ */
(function () {
  'use strict';
  var A = window.A, D = A.D, V = A.V;
  var esc = A.esc, money = A.money, $ = A.$, $$ = A.$$, pill = A.pill, toast = A.toast, confirmBox = A.confirmBox,
      table = A.table, head = A.head, marketOptions = A.marketOptions, planByCode = A.planByCode,
      persist = A.persist, route = A.route, renderMenu = A.renderMenu, currentUser = A.currentUser, extraNotes = A.notes;

  // ===== CORRAL =====
  A.badges.queues = function () { return D.QUEUES.reduce(function (a, q) { return a + q[1]; }, 0); };

  V['home'] = function (q) {
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
      return '<tr class="click" data-href="#/customer/' + c.account + '">' +
        '<td class="mono">' + c.account + '</td><td>' + esc(c.created) + '</td><td>' + pill(c.status, c.statusTone) + (c.exception ? ' ' + pill(c.exception, 'bad') : '') + '</td>' +
        '<td><b>' + esc(c.name) + '</b><br><span class="muted">' + esc(c.type) + '</span></td>' +
        '<td>' + esc(c.phone) + '<br><span class="muted">' + esc(c.email) + '</span></td>' +
        '<td>' + esc(c.address) + '<br><span class="muted">' + esc(c.city) + ', TX ' + c.zip + '</span></td>' +
        '<td>' + esc(c.plan) + '<br><span class="muted">' + esc(c.market) + '</span></td><td>' + esc(c.source) + '</td></tr>';
    });
  }
  var CUST_COLS = ['Account', 'Created', 'Status', 'Customer', 'Contact', 'Address', 'Plan', 'Source'];
  V['home'].init = function () {
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

  V['customer'] = function (q, id) {
    var c = D.CUSTOMERS.filter(function (x) { return x.account === id; })[0];
    if (!c) return '<div class="panel empty">Account ' + esc(id) + ' was not found. <a href="#/home">Back to search</a></div>';
    var pays = D.payments(c), bills = D.bills(c);
    var notes = D.notes(c).concat(extraNotes[c.account] || []);
    return head(c.name, 'Account <span class="mono">' + c.account + '</span> · Ticket <span class="mono">' + c.ticket + '</span>',
      '<button class="btn ghost" id="bm">' + (c.bookmarked ? '★ Bookmarked' : '☆ Bookmark') + '</button>' +
      '<button class="btn ghost" id="sms">Send SMS</button><a class="btn" href="#/order?renew=' + c.account + '">Renew / Change Plan</a>') +
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
  V['customer'].init = function (q, id) {
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

  V['esiid'] = function () {
    return head('ESIID Lookup', 'Search the ERCOT premise database by address or ESIID.') +
      '<div class="panel"><div class="panel-body"><form id="esiid-form" class="form-row">' +
      '<div class="field grow"><label for="e-addr">Street Address or ESIID</label><input id="e-addr" required placeholder="e.g. 1047 Example Ln or 1008901…"></div>' +
      '<div class="field"><label for="e-zip">Zip</label><input id="e-zip" maxlength="5" inputmode="numeric" placeholder="77082" style="width:100px"></div>' +
      '<button class="btn">Look Up</button></form></div></div>' +
      '<div class="panel"><div class="panel-head"><h2>Results</h2></div><div id="esiid-res"><div class="empty">Enter an address or ESIID to search.</div></div></div>';
  };
  V['esiid'].init = function () {
    $('#esiid-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var a = $('#e-addr').value.toLowerCase(), z = $('#e-zip').value;
      var list = D.CUSTOMERS.filter(function (c) { return (c.address.toLowerCase().indexOf(a) > -1 || c.esiid.indexOf(a) > -1) && (!z || c.zip === z); });
      $('#esiid-res').innerHTML = table(['ESIID', 'Address', 'TDSP', 'Meter', 'Premise', 'Status', ''], list.map(function (c, i) {
        return '<tr><td class="mono">' + c.esiid + '</td><td>' + esc(c.address) + ', ' + esc(c.city) + ' ' + c.zip + '</td><td>' + c.market + '</td><td>' + (i % 4 ? 'AMS' : 'AMSR') + '</td><td>' + (c.type === 'Residential' ? 'Residential' : 'Small Non-Residential') + '</td><td>' + pill('Active', 'ok') + '</td>' +
          '<td><a class="btn sm" href="#/order?esiid=' + c.esiid + '">Start Order</a></td></tr>';
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
        '</div><div class="actions" style="margin-top:20px"><button class="btn cyan">Submit Order</button><a class="btn ghost" href="#/home">Cancel</a></div></div></form>';
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
      A.go('#/customer/' + acct);
    });
  }
  V['order'] = orderView(false); V['order'].init = orderInit;
  V['order-biz'] = orderView(true); V['order-biz'].init = orderInit;

  V['reports'] = function () {
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
  V['reports'].init = function () {
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

  V['queues'] = function () {
    return head('Exception Queues', 'Work items that need a person. Counts are sample data.') +
      '<div class="panel">' + table(['Queue', 'What it holds', { label: 'Open', num: 1 }, ''], D.QUEUES.map(function (qq, i) {
        return '<tr><td><b>' + qq[0] + '</b></td><td class="wrap muted">' + qq[2] + '</td><td class="num">' + (qq[1] ? pill(qq[1], qq[1] > 5 ? 'bad' : 'warn') : pill('0', 'ok')) + '</td>' +
          '<td><a class="btn sm ghost" href="#/queue/' + i + '">Open</a></td></tr>';
      })) + '</div>';
  };
  V['queue'] = function (q, i) {
    var qq = D.QUEUES[+i];
    if (!qq) return '<div class="panel empty">Queue not found.</div>';
    var items = D.CUSTOMERS.slice(+i, +i + qq[1]);
    return head(qq[0], qq[2], '<a class="btn ghost" href="#/queues">All Queues</a>') +
      '<div class="panel">' + table(['Account', 'Customer', 'Plan', 'Status', 'Age', ''], items.map(function (c, k) {
        return '<tr data-row><td class="mono"><a href="#/customer/' + c.account + '">' + c.account + '</a></td><td>' + esc(c.name) + '</td><td>' + esc(c.plan) + '</td><td>' + pill(c.status, c.statusTone) + '</td><td>' + (k + 1) + 'd</td>' +
          '<td><button class="btn sm cyan" data-resolve>Mark Fixed</button></td></tr>';
      }), { empty: 'Queue is clear.' }) + '</div>';
  };
  V['queue'].init = function (q, i) {
    $$('[data-resolve]').forEach(function (b) {
      b.addEventListener('click', function () {
        b.closest('tr').remove();
        D.QUEUES[+i][1] = Math.max(0, D.QUEUES[+i][1] - 1);
        renderMenu(); toast('Marked as fixed');
      });
    });
  };

  A.viewsLoaded = true;
})();
