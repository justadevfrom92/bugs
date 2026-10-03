/*
  Website behaviour: header/footer, plan cards, zip lookup, Build Your Own Plan,
  contact form and the Admin sign-in prompt.
  Brand details, plans, rates and pricing come from Laravel (shared/config/*.js, via ET.*).
*/
(function () {
  'use strict';
  var ET = window.ET, B = ET.brand, esc = ET.esc;
  document.documentElement.classList.add('js');

  var LOGO =
    '<img class="logo-mark" src="' + ET.root + 'shared/img/logo-mark.svg" alt="" width="44" height="44">' +
    '<span class="logo-text"><strong>' + esc(B.wordmark) + '</strong><span>' + esc(B.tagline) + '</span></span>';

  var DEFAULT_MARKET = 'TX-E-ONCOR';

  function header() {
    var here = ET.page();
    var links = B.nav.map(function (n) {
      return '<a href="' + n[0] + '"' + (n[0] === here ? ' class="active"' : '') + '>' + n[1] + '</a>';
    }).join('');
    return (
      '<div class="utility"><div class="wrap">' +
      '<span>Proudly serving deregulated Texas &nbsp;·&nbsp; <a href="' + B.phoneHref + '">' + esc(B.phone) + '</a></span>' +
      '<div class="utility-right"><nav class="utility-links"><a href="#">Pay My Bill</a><a href="#">Report an Outage</a><a href="#">Español</a></nav>' +
      '<button type="button" class="admin-btn" data-admin>Admin</button></div>' +
      '</div></div>' +
      '<header class="site-header"><div class="wrap">' +
      '<a class="logo" href="index.html" aria-label="' + esc(B.name) + ' home">' + LOGO + '</a>' +
      '<button class="menu-toggle" aria-label="Menu" aria-expanded="false"><span></span></button>' +
      '<nav class="nav" id="main-nav">' + links +
      '<a class="btn" href="#">My Account</a>' +
      '</nav></div></header>'
    );
  }

  function footer() {
    var year = new Date().getFullYear();
    return (
      '<footer class="site-footer"><div class="wrap">' +
      '<div class="footer-cols">' +
      '<div><a class="logo" href="index.html">' + LOGO + '</a>' +
      '<p style="margin-top:18px">Texas-sized service, honest electricity plans and rewards just for keepin\' the lights on.</p>' +
      '<div class="footer-social">' +
      '<a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 8h3V4h-3c-2.8 0-4 1.8-4 4.3V10H7v4h3v8h4v-8h3l1-4h-4V8.6c0-.4.2-.6.6-.6z"/></svg></a>' +
      '<a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg></a>' +
      '<a href="#" aria-label="X"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.8 3h3.1l-6.8 7.8L22 21h-6.2l-4.9-6.4L5.3 21H2.2l7.3-8.3L2 3h6.4l4.4 5.8zm-1.1 16.2h1.7L7.4 4.7H5.6z"/></svg></a>' +
      '<a href="#" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 3a2 2 0 110 4 2 2 0 010-4zM3 9h3v12H3zm6 0h3v1.7c.5-.9 1.7-2 3.6-2 3.4 0 4.4 2.1 4.4 5.3V21h-3v-6.2c0-1.6-.3-3-2-3s-2.3 1.3-2.3 3V21H9z"/></svg></a>' +
      '</div></div>' +
      '<div><h4>Electricity</h4><ul>' +
      '<li><a href="plans.html">Residential Plans</a></li>' +
      '<li><a href="build-your-own-plan.html">Build Your Own Plan</a></li>' +
      '<li><a href="business.html">Business Plans</a></li>' +
      '<li><a href="plans.html#faq">Electricity Facts Labels</a></li>' +
      '</ul></div>' +
      '<div><h4>Perks</h4><ul>' +
      '<li><a href="index.html#rewards">' + esc(B.rewards) + '</a></li>' +
      '<li><a href="#">Peak Perks</a></li>' +
      '<li><a href="#">Refer a Friend</a></li>' +
      '<li><a href="#">Military &amp; First Responders</a></li>' +
      '</ul></div>' +
      '<div><h4>Company</h4><ul>' +
      '<li><a href="#">About Us</a></li>' +
      '<li><a href="index.html#learn">Get to Learnin\'</a></li>' +
      '<li><a href="#">Careers</a></li>' +
      '<li><a href="contact-us.html">Contact Us</a></li>' +
      '</ul></div>' +
      '</div>' +
      '<div class="footer-bottom"><span>&copy; ' + year + ' ' + esc(B.name) + '. All rights reserved. PUCT Cert. No. ' + esc(B.puct) + '</span>' +
      '<nav><a href="#">Privacy Policy</a><a href="#">Terms of Service</a><a href="#">Your Rights as a Customer</a><a href="#">Accessibility</a></nav></div>' +
      '</div></footer>'
    );
  }

  // Fill any element marked data-brand="phone" (etc.) from the brand config
  function fillBrand() {
    document.querySelectorAll('[data-brand]').forEach(function (el) {
      var k = el.getAttribute('data-brand');
      if (B[k] == null) return;
      el.textContent = B[k];
      if (k === 'phone' && el.tagName === 'A') el.href = B.phoneHref;
    });
  }

  /* ---------- Session (CSRF token + who's signed in), from Laravel ---------- */
  var session = null;
  function loadSession() {
    if (!session) {
      session = fetch(ET.root + 'site/session', { credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then(function (r) { return r.ok ? r.json() : { user: null, csrf: '' }; })
        .catch(function () { return { user: null, csrf: '' }; });
    }
    return session;
  }
  var known = null; // resolved session, so the Admin click can open a tab synchronously
  loadSession().then(function (s) { known = s; });

  /* ---------- Admin sign-in prompt ---------- */
  // The form posts straight to Laravel in a new tab; Laravel signs in and shows the launcher there.
  function openAdmin() {
    var admin = ET.root + 'admin';
    if (known && known.user) { window.open(admin, '_blank'); return; }
    var bg = document.createElement('div');
    bg.className = 'modal-bg';
    bg.innerHTML =
      '<form class="modal" role="dialog" aria-modal="true" aria-labelledby="adm-title" method="post" action="' + admin + '/login" target="_blank">' +
      '<h2 id="adm-title">Admin sign in</h2>' +
      '<p class="modal-note">Employees only. The admin opens in a new tab.</p>' +
      '<input type="hidden" name="_token" value="">' +
      '<div class="field"><label for="adm-email">Email</label><input id="adm-email" name="email" type="email" autocomplete="username" required></div>' +
      '<div class="field"><label for="adm-pw">Password</label><input id="adm-pw" name="password" type="password" autocomplete="current-password" required></div>' +
      '<div class="form-msg error" role="alert"></div>' +
      '<div class="modal-actions"><button type="button" class="btn btn-outline" data-x>Cancel</button><button class="btn" disabled>Sign In</button></div>' +
      '</form>';
    document.body.appendChild(bg);
    var form = bg.querySelector('form');
    var submit = form.querySelector('button:not([data-x])');
    function close() { bg.remove(); document.removeEventListener('keydown', onKey); }
    function onKey(e) { if (e.key === 'Escape') close(); }
    document.addEventListener('keydown', onKey);
    bg.addEventListener('click', function (e) { if (e.target === bg || e.target.hasAttribute('data-x')) close(); });
    loadSession().then(function (s) {
      if (!s.csrf) { form.querySelector('.form-msg').textContent = 'The admin server is not reachable right now.'; return; }
      form.querySelector('[name=_token]').value = s.csrf;
      submit.disabled = false;
    });
    form.addEventListener('submit', function (e) {
      if (!form.checkValidity()) { e.preventDefault(); form.querySelector('.form-msg').textContent = 'Enter your email and password.'; return; }
      session = null; known = null; // refresh after sign-in
      setTimeout(function () { close(); loadSession().then(function (s) { known = s; }); }, 300);
    });
    form.querySelector('#adm-email').focus();
  }

  /* ---------- Plan cards ---------- */
  function etfLabel(p) { return /\d/.test(p.etf) ? p.etf.split('/')[0] + ' ETF' : 'No ETF'; }

  function planCard(p, market, featured) {
    var termLabel = p.term === 1 ? 'Month-to-Month' : p.term + ' Months · Fixed';
    var price = ET.price(p.internal, market, 1000);
    return (
      '<article class="plan' + (featured ? ' featured' : '') + ' reveal">' +
      (featured ? '<span class="plan-flag">Popular Pick</span>' : '') +
      '<div class="plan-top">' +
      '<span class="plan-term">' + termLabel + '</span>' +
      '<h3>' + esc(p.name) + '</h3>' +
      '<div class="plan-rate"><strong>' + (price == null ? '—' : price.toFixed(1) + '¢') + '</strong><span>per kWh</span></div>' +
      '<p class="plan-rate-note">Avg. price at 1,000 kWh · ' + etfLabel(p) + '</p>' +
      '</div>' +
      '<div class="plan-body"><ul class="checks">' +
      p.perks.map(function (x) { return '<li>' + esc(x) + '</li>'; }).join('') +
      '</ul>' +
      '<a class="btn btn-block" href="contact-us.html?plan=' + encodeURIComponent(p.internal) + '">Sign Up</a>' +
      '<div class="plan-links"><a href="#">Electricity Facts Label</a><a href="#">Terms of Service</a><a href="#">YRAC</a></div>' +
      '</div></article>'
    );
  }

  function renderPlans(el, list, market) {
    var featured = ET.group('featured').map(function (p) { return p.internal; });
    el.innerHTML = list.length
      ? list.map(function (p) { return planCard(p, market, featured.indexOf(p.internal) > -1); }).join('')
      : '<p class="center" style="grid-column:1/-1">No plans match that filter, pardner. Try another.</p>';
    observeReveals(el);
  }

  /* ---------- Reveal on scroll ---------- */
  var io = 'IntersectionObserver' in window
    ? new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
        });
      }, { threshold: 0.12 })
    : null;
  function observeReveals(root) {
    (root || document).querySelectorAll('.reveal:not(.in)').forEach(function (el) {
      io ? io.observe(el) : el.classList.add('in');
    });
  }

  /* ---------- Zip lookup ---------- */
  function initZipForms() {
    document.querySelectorAll('[data-zip-form]').forEach(function (form) {
      var msg = form.querySelector('.form-msg');
      form.querySelectorAll('.toggle-group button').forEach(function (b) {
        b.addEventListener('click', function () {
          form.querySelectorAll('.toggle-group button').forEach(function (x) { x.classList.remove('on'); });
          b.classList.add('on');
        });
      });
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var zip = form.querySelector('input[name=zip]').value.trim();
        var biz = form.querySelector('.toggle-group button.on[data-type=business]');
        msg.className = 'form-msg';
        if (!/^\d{5}$/.test(zip)) {
          msg.textContent = 'Please enter a valid 5-digit Texas zip code.';
          msg.classList.add('error');
          return;
        }
        var m = ET.marketForZip(zip);
        if (!m) {
          msg.textContent = 'Shucks — ' + zip + " isn't in a deregulated area we serve yet.";
          msg.classList.add('error');
          return;
        }
        msg.textContent = 'Great news! We serve ' + zip + ' (' + m.desc + '). Loading plans…';
        msg.classList.add('ok');
        setTimeout(function () {
          ET.go((biz ? 'business.html' : 'plans.html') + '?zip=' + zip);
        }, 700);
      });
    });
  }

  function marketFromQuery() {
    var zip = ET.query().get('zip');
    var m = zip && /^\d{5}$/.test(zip) ? ET.marketForZip(zip) : null;
    return { zip: zip, market: m };
  }

  /* ---------- Plans page / featured plans ---------- */
  function initPlans() {
    var grid = document.getElementById('plan-grid');
    if (!grid) return;
    var q = marketFromQuery();
    var market = q.market ? q.market.name : DEFAULT_MARKET;
    var group = grid.getAttribute('data-group') || 'resi';
    var plans = ET.group(group);
    var chips = document.querySelectorAll('[data-filter]');
    function apply(f) {
      renderPlans(grid, plans.filter(function (p) {
        if (f === 'all') return true;
        if (f === 'month') return p.term === 1;
        if (/^\d+$/.test(f)) return p.term === +f;
        return p.tags.indexOf(f) > -1;
      }), market);
    }
    chips.forEach(function (c) {
      c.addEventListener('click', function () {
        chips.forEach(function (x) { x.classList.remove('on'); });
        c.classList.add('on');
        apply(c.getAttribute('data-filter'));
      });
    });
    apply('all');

    var zipNote = document.getElementById('zip-note');
    if (zipNote && q.market) zipNote.textContent = 'Showing plans for ' + q.zip + ' · ' + q.market.desc;
  }

  /* ---------- Build Your Own Plan ---------- */
  function initByop() {
    var root = document.getElementById('byop');
    if (!root) return;
    var tabs = root.querySelectorAll('.byop-tab');
    var steps = document.querySelectorAll('.steps li');
    var prev = document.getElementById('byop-prev');
    var next = document.getElementById('byop-next');
    var cur = 0;
    var range = document.getElementById('term');
    var termOut = document.getElementById('term-out');
    var TERMS = [1, 6, 12, 18, 24, 30, 36];
    var mods = ET.data('TERM_MODS');
    var market = DEFAULT_MARKET;
    var region = (ET.data('MARKETS').filter(function (m) { return m.name === market; })[0] || {}).region || 'Generic';
    var base12 = ET.price('BYOP', market, 1000);
    var products = {};
    ET.data('BYOP_PRODUCTS').forEach(function (p) { products[p.key] = p; });

    // Show each add-on's price from the catalog and hide ones switched off in Astro
    root.querySelectorAll('input[data-product]').forEach(function (i) {
      var p = products[i.getAttribute('data-product')];
      var opt = i.closest('.opt');
      if (!p || !p.active) { opt.hidden = true; i.checked = false; return; }
      var label = p.type === 'Included' ? 'Included' : p.monthly ? '+$' + p.monthly.toFixed(2) + '/mo' : p.adj ? (p.adj < 0 ? '−' : '+') + Math.abs(p.adj).toFixed(1) + '¢' : 'Free';
      opt.querySelector('.price').textContent = label;
    });

    function update() {
      var term = TERMS[+range.value];
      termOut.textContent = term === 1 ? 'Month-to-Month' : term + ' months';
      var mod = mods[term - 1], mod12 = mods[11];
      var rate = base12 + (mod12[region] - mod[region]);
      var monthly = ET.plan('BYOP').mrc, n = 0;
      root.querySelectorAll('input[data-product]:checked').forEach(function (i) {
        var p = products[i.getAttribute('data-product')];
        if (!p || !p.active) return;
        rate += p.adj; monthly += p.monthly;
        if (p.type !== 'Included') n++;
      });
      document.getElementById('sum-term').textContent = termOut.textContent;
      document.getElementById('sum-adds').textContent = n ? n + ' selected' : 'None';
      document.getElementById('sum-mo').textContent = '$' + monthly.toFixed(2);
      document.getElementById('sum-etf').textContent = '$' + mod.etf;
      document.getElementById('sum-rate').textContent = rate.toFixed(1) + '¢';
      document.getElementById('sum-bill').textContent = '$' + Math.round(rate * 10 + monthly);
    }

    function go(i) {
      cur = Math.max(0, Math.min(tabs.length - 1, i));
      tabs.forEach(function (t, j) { t.classList.toggle('on', j === cur); });
      steps.forEach(function (s, j) {
        s.classList.toggle('active', j === cur);
        s.classList.toggle('done', j < cur);
      });
      prev.style.visibility = cur === 0 ? 'hidden' : 'visible';
      next.style.display = cur === tabs.length - 1 ? 'none' : '';
    }

    prev.addEventListener('click', function () { go(cur - 1); root.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
    next.addEventListener('click', function () { go(cur + 1); root.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
    root.addEventListener('input', update);
    root.addEventListener('change', update);
    update();
    go(0);
  }

  /* ---------- Contact form ---------- */
  function initContact() {
    var form = document.getElementById('contact-form');
    if (!form) return;
    var params = ET.query();
    var topic = params.get('topic');
    if (topic && form.querySelector('[name=topic] option[value="' + topic + '"]')) {
      form.querySelector('[name=topic]').value = topic;
    }
    var p = params.get('plan') && ET.plan(params.get('plan'));
    if (p) {
      form.querySelector('[name=topic]').value = 'signup';
      form.querySelector('[name=message]').value = "I'd like to sign up for " + p.name + '.';
    }
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var msg = form.querySelector('.form-msg');
      if (!form.checkValidity()) {
        msg.className = 'form-msg error';
        msg.textContent = 'Please fill in the required fields.';
        return;
      }
      var button = form.querySelector('button[type=submit], button:not([type])');
      button.disabled = true;
      loadSession().then(function (s) {
        return fetch(ET.root + 'contact', {
          method: 'POST', credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': s.csrf },
          body: JSON.stringify(Object.fromEntries(new FormData(form)))
        });
      }).then(function (r) {
        if (!r.ok) throw r;
        msg.className = 'form-msg ok';
        msg.textContent = "Thanks, y'all! A member of our team will reach out within one business day.";
        form.reset();
      }).catch(function () {
        msg.className = 'form-msg error';
        msg.textContent = 'Your message could not be sent. Please try again, or call ' + B.phone + '.';
      }).then(function () { button.disabled = false; });
    });
  }

  ET.ready(function () {
    var h = document.getElementById('site-header');
    var f = document.getElementById('site-footer');
    if (h) h.outerHTML = header();
    if (f) f.outerHTML = footer();
    fillBrand();

    var toggle = document.querySelector('.menu-toggle');
    var nav = document.getElementById('main-nav');
    if (toggle) toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open);
    });
    document.addEventListener('click', function (e) {
      if (e.target.closest('[data-admin]')) { e.preventDefault(); openAdmin(); }
    });

    initZipForms();
    initPlans();
    initByop();
    initContact();
    observeReveals();
  });
})();
