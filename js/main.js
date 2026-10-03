/* Energy Texas — shared header/footer, plans data and page behaviour */
(function () {
  'use strict';
  document.documentElement.classList.add('js');

  var LOGO =
    '<svg class="logo-mark" viewBox="0 0 48 48" aria-hidden="true">' +
    '<circle cx="24" cy="24" r="23" fill="#102247"/>' +
    '<path d="M24 7l4.6 10.3 11.2 1.1-8.4 7.5 2.4 11L24 31.3 14.2 37l2.4-11-8.4-7.5 11.2-1.1z" fill="#00AEEF"/>' +
    '<path d="M26.5 14l-6 11h4.3l-2.3 9 7.5-12.2h-4.6z" fill="#fff"/>' +
    '</svg>' +
    '<span class="logo-text"><strong>ENERGY TEXAS</strong><span>POWERED BY TEXANS</span></span>';

  var PHONE = '1-800-555-0100'; // placeholder — replace with the real customer care number
  var PHONE_HREF = 'tel:18005550100';

  var NAV = [
    ['index.html', 'Home'],
    ['plans.html', 'Plans'],
    ['build-your-own-plan.html', 'Build Your Own Plan'],
    ['business.html', 'Business'],
    ['contact-us.html', 'Contact Us']
  ];

  function header() {
    var here = location.pathname.split('/').pop() || 'index.html';
    var links = NAV.map(function (n) {
      return '<a href="' + n[0] + '"' + (n[0] === here ? ' class="active"' : '') + '>' + n[1] + '</a>';
    }).join('');
    return (
      '<div class="utility"><div class="wrap">' +
      '<span>Proudly serving deregulated Texas &nbsp;·&nbsp; <a href="' + PHONE_HREF + '">' + PHONE + '</a></span>' +
      '<nav class="utility-links"><a href="#">Pay My Bill</a><a href="#">Report an Outage</a><a href="#">Español</a></nav>' +
      '</div></div>' +
      '<header class="site-header"><div class="wrap">' +
      '<a class="logo" href="index.html" aria-label="Energy Texas home">' + LOGO + '</a>' +
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
      '<li><a href="index.html#rewards">Rangler Rewards</a></li>' +
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
      '<div class="footer-bottom"><span>&copy; ' + year + ' Energy Texas. All rights reserved. PUCT Cert. No. XXXXX</span>' +
      '<nav><a href="#">Privacy Policy</a><a href="#">Terms of Service</a><a href="#">Your Rights as a Customer</a><a href="#">Accessibility</a></nav></div>' +
      '</div></footer>'
    );
  }

  /* Residential plan lineup (names/terms/ETFs from the active plan list).
     Rates shown are illustrative averages at 1,000 kWh — swap in live EFL pricing. */
  var PLANS = [
    { id: 'JEY', name: "Just Electricity, Y'all", term: 1, rate: 15.9, etf: 0, tags: ['month'],
      perks: ['No contract, no cancellation fee', 'Month-to-month flexibility', 'Rangler Rewards included'] },
    { id: 'ETM', name: 'Energy Texas Monthly', term: 1, rate: 15.4, etf: 0, tags: ['month'],
      perks: ['Leave anytime, no ETF', 'Great for renters & short stays', 'Paperless & AutoPay ready'] },
    { id: 'MME1', name: 'Moving Made Easy', term: 1, rate: 15.2, etf: 0, tags: ['month'],
      perks: ['Same-day service available', 'Built for movers', 'Switch to a fixed plan anytime'] },
    { id: 'CT12', name: 'Come & Take It 12', term: 12, rate: 13.4, etf: 250, tags: ['fixed'], featured: true,
      perks: ['Fixed rate locked for 12 months', 'Rangler Rewards on every bill', 'Free Peak Perks enrollment'] },
    { id: 'FreeF12', name: 'Freedom Flex 12', term: 12, rate: 13.9, etf: 250, tags: ['fixed', 'flex'],
      perks: ['Fixed rate for 12 months', 'Flex your due date', 'Rangler Rewards included'] },
    { id: 'PP12', name: 'Pardner Preferred 12', term: 12, rate: 13.6, etf: 250, tags: ['fixed'],
      perks: ['Fixed rate for 12 months', 'Priority customer care line', 'Rangler Rewards included'] },
    { id: 'G18', name: 'The Gruene 18', term: 18, rate: 13.3, etf: 275, tags: ['fixed', 'green'],
      perks: ['100% renewable energy', 'Fixed rate for 18 months', 'Rangler Rewards included'] },
    { id: 'PP18', name: 'Pardner Preferred 18', term: 18, rate: 13.2, etf: 275, tags: ['fixed'],
      perks: ['Fixed rate for 18 months', 'Priority customer care line', 'Rangler Rewards included'] },
    { id: 'BTT24', name: 'Bigger Than Texas 24', term: 24, rate: 12.9, etf: 400, tags: ['fixed'], featured: true,
      perks: ['Fixed rate locked for 2 years', 'Bonus Rangler Rewards stars', 'Free Peak Perks enrollment'] },
    { id: 'FreeF24', name: 'Freedom Flex 24', term: 24, rate: 13.1, etf: 400, tags: ['fixed', 'flex'],
      perks: ['Fixed rate for 24 months', 'Flex your due date', 'Rangler Rewards included'] },
    { id: 'PP24', name: 'Pardner Preferred 24', term: 24, rate: 12.8, etf: 400, tags: ['fixed'],
      perks: ['Fixed rate for 24 months', 'Priority customer care line', 'Rangler Rewards included'] },
    { id: '36IF', name: '36 Inflation Fix', term: 36, rate: 12.6, etf: 500, tags: ['fixed'], featured: true,
      perks: ['Beat inflation for 3 full years', 'Lowest long-term fixed rate', 'Rangler Rewards included'] },
    { id: 'ECG36', name: 'ecobee Clean & Green 36', term: 36, rate: 12.9, etf: 500, tags: ['fixed', 'green'],
      perks: ['FREE ecobee smart thermostat', '100% renewable energy', 'Fixed rate for 36 months'] },
    { id: 'FreeF36', name: 'Freedom Flex 36', term: 36, rate: 12.7, etf: 500, tags: ['fixed', 'flex'],
      perks: ['Fixed rate for 36 months', 'Flex your due date', 'Rangler Rewards included'] },
    { id: 'PP36', name: 'Pardner Preferred 36', term: 36, rate: 12.5, etf: 500, tags: ['fixed'],
      perks: ['Fixed rate for 36 months', 'Priority customer care line', 'Rangler Rewards included'] }
  ];

  function planCard(p) {
    var termLabel = p.term === 1 ? 'Month-to-Month' : p.term + ' Months · Fixed';
    return (
      '<article class="plan' + (p.featured ? ' featured' : '') + ' reveal">' +
      (p.featured ? '<span class="plan-flag">Popular Pick</span>' : '') +
      '<div class="plan-top">' +
      '<span class="plan-term">' + termLabel + '</span>' +
      '<h3>' + p.name + '</h3>' +
      '<div class="plan-rate"><strong>' + p.rate.toFixed(1) + '¢</strong><span>per kWh</span></div>' +
      '<p class="plan-rate-note">Avg. price at 1,000 kWh · ' + (p.etf ? '$' + p.etf + ' ETF' : 'No ETF') + '</p>' +
      '</div>' +
      '<div class="plan-body"><ul class="checks">' +
      p.perks.map(function (x) { return '<li>' + x + '</li>'; }).join('') +
      '</ul>' +
      '<a class="btn btn-block" href="contact-us.html?plan=' + encodeURIComponent(p.id) + '">Sign Up</a>' +
      '<div class="plan-links"><a href="#">Electricity Facts Label</a><a href="#">Terms of Service</a><a href="#">YRAC</a></div>' +
      '</div></article>'
    );
  }

  function renderPlans(el, list) {
    el.innerHTML = list.length
      ? list.map(planCard).join('')
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
  var TDSP = [
    [75000, 76999, 'Oncor'], [77000, 77599, 'CenterPoint'], [78500, 78599, 'AEP Texas Central'],
    [78300, 78499, 'AEP Texas Central'], [79500, 79699, 'AEP Texas North'], [77600, 77799, 'Entergy / TNMP'],
    [76500, 76599, 'Oncor'], [79700, 79799, 'Oncor'], [78600, 78699, 'Oncor']
  ];
  function lookupZip(zip) {
    var z = parseInt(zip, 10);
    for (var i = 0; i < TDSP.length; i++) {
      if (z >= TDSP[i][0] && z <= TDSP[i][1]) return TDSP[i][2];
    }
    return null;
  }

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
        var tdsp = lookupZip(zip);
        if (!tdsp) {
          msg.textContent = 'Shucks — ' + zip + " isn't in a deregulated area we serve yet.";
          msg.classList.add('error');
          return;
        }
        msg.textContent = 'Great news! We serve ' + zip + ' (' + tdsp + '). Loading plans…';
        msg.classList.add('ok');
        try { sessionStorage.setItem('et_zip', zip); } catch (err) {}
        setTimeout(function () {
          location.href = (biz ? 'business.html' : 'plans.html') + '?zip=' + zip;
        }, 700);
      });
    });
  }

  /* ---------- Plans page ---------- */
  function initPlans() {
    var grid = document.getElementById('plan-grid');
    if (!grid) return;
    var limit = parseInt(grid.getAttribute('data-limit') || '0', 10);
    if (limit) {
      renderPlans(grid, PLANS.filter(function (p) { return p.featured; }).slice(0, limit));
      return;
    }
    var chips = document.querySelectorAll('[data-filter]');
    function apply(f) {
      var list = PLANS.filter(function (p) {
        if (f === 'all') return true;
        if (f === 'month') return p.term === 1;
        if (/^\d+$/.test(f)) return p.term === +f;
        return p.tags.indexOf(f) > -1;
      });
      renderPlans(grid, list);
    }
    chips.forEach(function (c) {
      c.addEventListener('click', function () {
        chips.forEach(function (x) { x.classList.remove('on'); });
        c.classList.add('on');
        apply(c.getAttribute('data-filter'));
      });
    });
    apply('all');

    var zip = new URLSearchParams(location.search).get('zip');
    var zipNote = document.getElementById('zip-note');
    if (zip && zipNote && /^\d{5}$/.test(zip)) {
      zipNote.textContent = 'Showing plans for ' + zip + (lookupZip(zip) ? ' · ' + lookupZip(zip) : '');
    }
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

    function baseRate(term) { return 16.2 - Math.min(term, 36) * 0.09; }

    function update() {
      var term = TERMS[+range.value];
      termOut.textContent = term === 1 ? 'Month-to-Month' : term + ' months';
      var rate = baseRate(term);
      var adds = [];
      var monthly = 4.95;
      root.querySelectorAll('input[data-adj]:checked').forEach(function (i) {
        rate += parseFloat(i.getAttribute('data-adj'));
        monthly += parseFloat(i.getAttribute('data-mo') || 0);
        adds.push(i.getAttribute('data-name'));
      });
      document.getElementById('sum-term').textContent = termOut.textContent;
      document.getElementById('sum-adds').textContent = adds.length ? adds.length + ' selected' : 'None';
      document.getElementById('sum-mo').textContent = '$' + monthly.toFixed(2);
      document.getElementById('sum-etf').textContent = term === 1 ? '$0' : '$' + (term <= 12 ? 250 : term <= 18 ? 275 : term <= 24 ? 400 : 500);
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
      root.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    prev.addEventListener('click', function () { go(cur - 1); });
    next.addEventListener('click', function () { go(cur + 1); });
    root.addEventListener('input', update);
    root.addEventListener('change', update);
    update();
    go(0);
    window.scrollTo(0, 0);
  }

  /* ---------- Contact form ---------- */
  function initContact() {
    var form = document.getElementById('contact-form');
    if (!form) return;
    var params = new URLSearchParams(location.search);
    var plan = params.get('plan');
    var topic = params.get('topic');
    if (topic && form.querySelector('[name=topic] option[value="' + topic + '"]')) {
      form.querySelector('[name=topic]').value = topic;
    }
    if (plan) {
      var match = PLANS.filter(function (p) { return p.id === plan; })[0];
      if (match) {
        form.querySelector('[name=topic]').value = 'signup';
        form.querySelector('[name=message]').value = "I'd like to sign up for " + match.name + '.';
      }
    }
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var msg = form.querySelector('.form-msg');
      if (!form.checkValidity()) {
        msg.className = 'form-msg error';
        msg.textContent = 'Please fill in the required fields.';
        return;
      }
      msg.className = 'form-msg ok';
      msg.textContent = "Thanks, y'all! A member of our team will reach out within one business day.";
      form.reset();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var h = document.getElementById('site-header');
    var f = document.getElementById('site-footer');
    if (h) h.outerHTML = header();
    if (f) f.outerHTML = footer();

    var toggle = document.querySelector('.menu-toggle');
    var nav = document.getElementById('main-nav');
    if (toggle) toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open);
    });

    initZipForms();
    initPlans();
    initByop();
    initContact();
    observeReveals();
  });
})();
