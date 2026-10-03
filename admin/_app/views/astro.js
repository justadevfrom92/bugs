/* Astro views — loaded by admin/_app/shell.js for admin/astro/ */
(function () {
  'use strict';
  var A = window.A, D = A.D, V = A.V;
  var esc = A.esc, money = A.money, $ = A.$, $$ = A.$$, pill = A.pill, toast = A.toast, confirmBox = A.confirmBox,
      table = A.table, head = A.head, marketOptions = A.marketOptions, planByCode = A.planByCode,
      persist = A.persist, route = A.route, renderMenu = A.renderMenu, currentUser = A.currentUser, extraNotes = A.notes;

  // ===== ASTRO =====
  V['term'] = function () {
    return head('Term & Discounts', 'Values are in ¢/kWh. A positive value lowers the rate; a negative value raises it. 1.0 is a one-cent discount.', '<button class="btn cyan" id="tm-save">Save Modifiers</button>') +
      '<div class="panel">' + table([{ label: 'Term', num: 1 }].concat(D.REGIONS.map(function (r) { return { label: r, num: 1 }; })), D.TERM_MODS.map(function (row) {
        return '<tr><td class="num"><b>' + row.term + '</b></td>' + D.REGIONS.map(function (r) {
          return '<td class="num"><input type="number" step="0.01" data-t="' + row.term + '" data-r="' + r + '" value="' + row[r].toFixed(2) + '" class="' + (row[r] < 0 ? 'neg' : '') + '" aria-label="Term ' + row.term + ' ' + r + '"></td>';
        }).join('') + '</tr>';
      })) + '</div>';
  };
  V['term'].init = function () {
    $$('[data-t]').forEach(function (i) {
      var o = i.value;
      i.addEventListener('input', function () { i.classList.toggle('dirty', i.value !== o); i.classList.toggle('neg', +i.value < 0); });
    });
    $('#tm-save').addEventListener('click', function () {
      $$('[data-t]').forEach(function (i) { D.TERM_MODS[+i.dataset.t - 1][i.dataset.r] = +i.value; });
      persist(['TERM_MODS']); toast('Term modifiers saved'); route();
    });
  };
  V['etf'] = function () {
    return head('ETF by Term', 'Early termination fee charged when a customer leaves a fixed plan early.', '<button class="btn cyan" id="etf-save">Save ETFs</button>') +
      '<div class="panel" style="max-width:520px">' + table([{ label: 'Term (months)', num: 1 }, { label: 'ETF ($)', num: 1 }], D.TERM_MODS.map(function (row) {
        return '<tr><td class="num">' + row.term + '</td><td class="num"><input type="number" step="1" min="0" data-etf="' + row.term + '" value="' + row.etf + '"></td></tr>';
      })) + '</div>';
  };
  V['etf'].init = function () {
    $$('[data-etf]').forEach(function (i) { var o = i.value; i.addEventListener('input', function () { i.classList.toggle('dirty', i.value !== o); }); });
    $('#etf-save').addEventListener('click', function () {
      $$('[data-etf]').forEach(function (i) { D.TERM_MODS[+i.dataset.etf - 1].etf = +i.value; });
      persist(['TERM_MODS']); toast('ETFs saved'); route();
    });
  };
  V['byop'] = function () {
    return head('BYOP Products', 'Add-ons offered in the Build Your Own Plan flow on the website. Prices here are what the website shows.', '<button class="btn cyan" id="bp-save">Save Products</button>') +
      '<div class="panel">' + table(['Product', 'Model', 'Step', 'Type', { label: 'Rate change ¢/kWh', num: 1 }, { label: 'Monthly $', num: 1 }, 'Show on Website'], D.BYOP_PRODUCTS.map(function (p) {
        return '<tr><td><b>' + esc(p.name) + '</b></td><td class="mono">' + p.model + '</td><td>' + esc(p.step) + '</td><td>' + p.type + '</td>' +
          '<td class="num"><input type="number" step="0.01" data-bp="' + p.id + '" data-f="adj" value="' + p.adj.toFixed(2) + '" class="' + (p.adj < 0 ? 'neg' : '') + '"></td>' +
          '<td class="num"><input type="number" step="0.01" min="0" data-bp="' + p.id + '" data-f="monthly" value="' + p.monthly.toFixed(2) + '"></td>' +
          '<td><label class="check"><input type="checkbox" data-bp="' + p.id + '" data-f="active"' + (p.active ? ' checked' : '') + '> ' + (p.active ? 'Shown' : 'Hidden') + '</label></td></tr>';
      })) + '</div><p class="muted" style="font-size:.8rem">A negative rate change is a discount. Hidden products disappear from the website\'s Build Your Own Plan page.</p>';
  };
  V['byop'].init = function () {
    $$('[data-bp]').forEach(function (i) {
      var o = i.type === 'checkbox' ? i.checked : i.value;
      i.addEventListener('input', function () {
        if (i.type === 'checkbox') { i.nextSibling.textContent = i.checked ? ' Shown' : ' Hidden'; return; }
        i.classList.toggle('dirty', i.value !== o);
        if (i.dataset.f === 'adj') i.classList.toggle('neg', +i.value < 0);
      });
    });
    $('#bp-save').addEventListener('click', function () {
      $$('[data-bp]').forEach(function (i) {
        var p = D.BYOP_PRODUCTS.filter(function (x) { return String(x.id) === i.dataset.bp; })[0];
        p[i.dataset.f] = i.type === 'checkbox' ? i.checked : +i.value;
      });
      persist(['BYOP_PRODUCTS']); toast('Products saved — the website uses these prices now'); route();
    });
  };

  A.viewsLoaded = true;
})();
