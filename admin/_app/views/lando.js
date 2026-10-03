/* Lando views — loaded by admin/_app/shell.js for admin/lando/ */
(function () {
  'use strict';
  var A = window.A, D = A.D, V = A.V;
  var esc = A.esc, money = A.money, $ = A.$, $$ = A.$$, pill = A.pill, toast = A.toast, confirmBox = A.confirmBox,
      table = A.table, head = A.head, marketOptions = A.marketOptions, planByCode = A.planByCode,
      persist = A.persist, route = A.route, renderMenu = A.renderMenu, currentUser = A.currentUser, extraNotes = A.notes;

  // ===== LANDO =====
  V['pages'] = function () {
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
        var label = (p ? '<a href="#/page/' + p.id + '">' + esc(k) + '</a>' : esc(k)) + (p && p.redirect ? ' <span class="redir">(redirect to ' + esc(p.redirect) + ')</span>' : '') + (p ? ' <span class="muted">· ' + p.template + '</span>' : '');
        return '<li>' + (kids ? '<details open><summary>' + label + '</summary>' + render(n[k]._kids) + '</details>' : label) + '</li>';
      }).join('') + '</ul>';
    }
    return head('Pages for ' + ET.brand.name, 'Working on site: ' + ET.brand.domain, '<button class="btn ghost" id="sitemap">Rebuild Sitemap</button><a class="btn" href="#/page/new">Add a Page</a>') +
      '<div class="panel"><div class="panel-head"><h2>Site Structure</h2><span class="muted">' + D.PAGES.length + ' pages</span></div><div class="panel-body">' + render(tree) + '</div></div>';
  };
  V['pages'].init = function () { $('#sitemap').addEventListener('click', function () { toast('Sitemap rebuilt (' + D.PAGES.length + ' URLs)'); }); };
  V['page'] = function (q, id) {
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
      '</div><div class="actions" style="margin-top:20px"><button class="btn cyan">Save Page</button><a class="btn ghost" href="#/pages">Cancel</a>' +
      (id !== 'new' ? '<button type="button" class="btn red" id="pg-del" style="margin-left:auto">Delete</button>' : '') + '</div></div></form>';
  };
  V['page'].init = function (q, id) {
    $('#page-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var p = id === 'new' ? { id: Date.now() } : D.PAGES.filter(function (x) { return String(x.id) === id; })[0];
      p.title = $('#pg-title').value; p.path = $('#pg-path').value.replace(/^\/+/, '') || '/'; p.template = $('#pg-tpl').value;
      p.redirect = $('#pg-redir').value; p.status = $('#pg-status').value;
      if (id === 'new') D.PAGES.push(p);
      persist(['PAGES']); toast('Page saved'); A.go('#/pages');
    });
    if ($('#pg-del')) $('#pg-del').addEventListener('click', function () {
      confirmBox('Delete page?', 'This removes the page and its URL from the site.', 'Delete Page', function () {
        D.PAGES = D.PAGES.filter(function (x) { return String(x.id) !== id; });
        persist(['PAGES']); toast('Page deleted'); A.go('#/pages');
      });
    });
  };

  V['templates'] = function () {
    return head('Page Templates', 'Layouts that pages are built on.') +
      '<div class="panel">' + table(['Template', 'Description', { label: 'Pages', num: 1 }], D.TEMPLATES.map(function (t) {
        return '<tr><td class="mono"><b>' + t.name + '</b></td><td class="wrap">' + esc(t.desc) + '</td><td class="num">' + t.pages + '</td></tr>';
      })) + '</div>';
  };

  V['blocks'] = function () {
    return head('Content Blocks', 'Reusable HTML snippets placed into pages with shortcodes.', '<a class="btn" href="#/block/new">Add a Block</a>') +
      '<div class="panel">' + table(['ID', 'Name', 'Site', 'Updated', 'Shortcode', ''], D.BLOCKS.map(function (b) {
        return '<tr><td class="num">' + b.id + '</td><td><b>' + esc(b.name) + '</b></td><td>' + b.site + '</td><td>' + b.updated + '</td><td class="mono">[[block|id=' + b.id + ']]</td><td><a class="btn sm ghost" href="#/block/' + b.id + '">Edit</a></td></tr>';
      })) + '</div>';
  };
  V['block'] = function (q, id) {
    var b = id === 'new' ? { id: 'new', name: '', site: 'energytexas.com', html: '' } : D.BLOCKS.filter(function (x) { return String(x.id) === id; })[0];
    if (!b) return '<div class="panel empty">Block not found.</div>';
    return head(id === 'new' ? 'Add a Content Block' : 'Editing Content Block ' + b.id, esc(b.name)) +
      '<div class="grid-2"><form class="panel" id="block-form"><div class="panel-body"><div class="form-grid" style="grid-template-columns:1fr">' +
      '<label for="b-name">Name</label><input id="b-name" required value="' + esc(b.name) + '">' +
      '<label for="b-html">HTML</label><textarea id="b-html" spellcheck="false">' + esc(b.html) + '</textarea>' +
      '</div><div class="actions" style="margin-top:16px"><button class="btn cyan">Save Block</button><a class="btn ghost" href="#/blocks">Cancel</a></div><p class="muted" style="margin-top:10px;font-size:.8rem">Tip: Ctrl+S saves.</p></div></form>' +
      '<div class="panel"><div class="panel-head"><h2>Preview</h2><span class="muted">shortcodes shown as-is</span></div><div class="panel-body"><iframe id="b-prev" title="Block preview" sandbox="" style="width:100%;min-height:340px;border:1px solid var(--line);border-radius:6px;background:#fff"></iframe></div></div></div>';
  };
  V['block'].init = function (q, id) {
    var ta = $('#b-html'), fr = $('#b-prev');
    function prev() { fr.srcdoc = '<style>body{font-family:sans-serif;padding:12px;color:#102247}</style>' + ta.value; }
    ta.addEventListener('input', prev); prev();
    function save(e) {
      if (e) e.preventDefault();
      var b = id === 'new' ? { id: Math.max.apply(null, D.BLOCKS.map(function (x) { return x.id; })) + 1, site: 'energytexas.com' } : D.BLOCKS.filter(function (x) { return String(x.id) === id; })[0];
      b.name = $('#b-name').value || 'Untitled block'; b.html = ta.value; b.updated = new Date().toISOString().slice(0, 10);
      if (id === 'new') { D.BLOCKS.push(b); A.go('#/block/' + b.id); }
      persist(['BLOCKS']); toast('Block saved');
    }
    $('#block-form').addEventListener('submit', save);
    ta.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 's') save(e);
      if (e.key === 'Tab') { e.preventDefault(); var s = ta.selectionStart; ta.setRangeText('  ', s, ta.selectionEnd, 'end'); }
    });
  };

  V['plans'] = function () {
    function rows(active) {
      return D.PLANS.filter(function (p) { return p.active === active; }).sort(function (a, b) { return a.type.localeCompare(b.type) || a.term - b.term; }).map(function (p) {
        return '<tr><td>' + p.type + '</td><td class="num">' + p.term + '</td><td><b>' + esc(p.name) + '</b></td><td class="mono">' + p.internal + '</td><td class="mono">' + p.rolloff + '</td><td>' + p.etf + '</td><td class="num">' + (p.mrc ? money(p.mrc) : '-') + '</td><td class="num">' + p.green + '%</td>' +
          '<td><a class="btn sm ghost" href="#/plan/' + p.id + '">Edit</a> <a class="btn sm ghost" href="#/rates?plan=' + p.internal + '">Rates</a></td></tr>';
      });
    }
    var cols = ['Type', { label: 'Term', num: 1 }, 'Display Name', 'Internal Name', 'Rolloff', 'ETF', { label: 'MRC', num: 1 }, { label: 'Green', num: 1 }, ''];
    return head('Plans', 'Every plan the site can sell.', '<a class="btn" href="#/plan/new">Add a Plan</a>') +
      '<div class="panel"><div class="panel-head"><h2>Active Plans</h2><span class="muted">' + D.PLANS.filter(function (p) { return p.active; }).length + '</span></div>' + table(cols, rows(true)) + '</div>' +
      '<div class="panel"><div class="panel-head"><h2>Inactive Plans</h2><span class="muted">' + D.PLANS.filter(function (p) { return !p.active; }).length + '</span></div>' + table(cols, rows(false)) + '</div>';
  };
  V['plan'] = function (q, id) {
    var p = id === 'new' ? { id: 'new', type: 'Resi', term: 12, name: '', internal: '', slug: '', rolloff: 'JEY3', etf: '$250', mrc: 4.95, green: 100, active: true, tags: [], perks: [] } : D.PLANS.filter(function (x) { return String(x.id) === id; })[0];
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
      '<label for="p-tags">Website Filters</label><input id="p-tags" value="' + esc((p.tags || []).join(', ')) + '" placeholder="fixed, green, flex, month">' +
      '<label for="p-desc">Website Bullets</label><textarea id="p-desc" style="min-height:100px;font-family:inherit" placeholder="One per line">' + esc((p.perks || []).join('\n')) + '</textarea>' +
      '</div><div class="actions" style="margin-top:20px"><button class="btn cyan">Save Plan</button><a class="btn ghost" href="#/plans">Cancel</a></div></div></form>';
  };
  V['plan'].init = function (q, id) {
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
      p.tags = $('#p-tags').value.split(',').map(function (t) { return t.trim(); }).filter(Boolean);
      p.perks = $('#p-desc').value.split('\n').map(function (t) { return t.trim(); }).filter(Boolean);
      if (id === 'new') D.PLANS.push(p);
      persist(['PLANS']); toast('Plan saved'); A.go('#/plans');
    });
  };

  V['groups'] = function () {
    return head('Plan Groups', 'Groups control which plans show on each page (e.g. [[plans|group=featured]]).') +
      '<div class="grid-2">' + D.PLAN_GROUPS.map(function (g) {
        return '<div class="panel"><div class="panel-head"><h2>' + esc(g.name) + '</h2><span class="mono muted">' + g.slug + '</span></div><div class="panel-body">' +
          '<div class="actions" data-group="' + g.id + '">' + g.plans.map(function (c) { return '<span class="pill info">' + c + ' <a href="#" data-rm="' + c + '" aria-label="Remove ' + c + '">×</a></span>'; }).join('') + '</div>' +
          '<div class="form-row" style="margin-top:12px"><select data-add="' + g.id + '"><option value="">Add a plan…</option>' + D.PLANS.filter(function (p) { return p.active && g.plans.indexOf(p.internal) < 0; }).map(function (p) { return '<option value="' + p.internal + '">' + esc(p.name) + ' (' + p.internal + ')</option>'; }).join('') + '</select></div>' +
          '</div></div>';
      }).join('') + '</div>';
  };
  V['groups'].init = function () {
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
  V['rates'] = function (q) {
    return head('Search Rates', 'Energy charge plus TDSP delivery gives the average price customers see on the EFL.') +
      '<div class="panel"><div class="panel-body"><form id="rate-form" class="form-row">' +
      '<div class="field grow"><label for="r-plan">Plan</label><input id="r-plan" placeholder="Search Plan Name or code" value="' + esc(q.plan || '') + '"></div>' +
      '<div class="field"><label for="r-market">Market</label><select id="r-market">' + marketOptions(q.market, true) + '</select></div>' +
      '<div class="field"><label for="r-kwh">Usage</label><select id="r-kwh"><option>500</option><option selected>1000</option><option>2000</option></select></div>' +
      '<button class="btn">Search</button></form></div></div>' +
      '<div class="panel"><div class="panel-head"><h2>Current Rates</h2><span class="muted" id="rate-count"></span></div><div id="rate-res"></div></div>';
  };
  V['rates'].init = function () {
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

  V['update-rates'] = function () {
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
  V['update-rates'].init = function () {
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

  V['tdsp'] = function () {
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
  V['tdsp'].init = function () {
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

  V['markets'] = function () {
    return head('Markets', 'Texas utility service territories (TDSPs).') +
      '<div class="panel">' + table(['ID', 'Name', 'Short', 'Type', 'Description', 'Region', 'Utility Phone', 'Commodity'], D.MARKETS.map(function (m) {
        return '<tr><td class="num">' + m.id + '</td><td class="mono"><b>' + m.name + '</b></td><td>' + m.short + '</td><td>tdsp</td><td>' + esc(m.desc) + '</td><td>' + m.region + '</td><td>' + m.phone + '</td><td>Electric</td></tr>';
      })) + '</div>';
  };

  A.viewsLoaded = true;
})();
