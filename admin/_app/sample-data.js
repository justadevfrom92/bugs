/*
  Admin-only sample data: customers, CMS pages and blocks, queues, integrations.
  Every customer and transaction here is FICTIONAL — no real customer records.
  Plans, rates, markets, users and roles live in shared/config instead.
*/
window.ET = window.ET || {};
ET.defaults = ET.defaults || {};
ET.sample = (function () {
  'use strict';

  // Site structure (from the CMS page list)
  var PAGES = [
    ['/', 'Home'], ['404', '404'], ['about-us', 'About Us'], ['business', 'Business', '/business/plans'],
    ['business/plans', 'Business Plans'], ['byop', 'Build Your Own Plan'],
    ['careers', 'Careers'], ['careers/lead-developer', 'Lead Developer'], ['careers/staff-accountant', 'Staff Accountant'],
    ['checkout', 'Checkout'], ['checkout/accepted', 'Accepted'], ['checkout/deposit', 'Deposit'], ['checkout/alternatives', 'Deposit Alternatives'],
    ['checkout/error', 'Error'], ['checkout/frozen', 'Frozen'], ['checkout/save', 'Save'], ['checkout/start-call', 'Start Call'],
    ['come-back', 'Come Back'], ['contact-us', 'Contact Us'], ['ecobee', 'ecobee'], ['epicenter', 'Epicenter', '/plans?msid=130001'],
    ['experience-energy-savings-with-peak-perks-plus', 'Peak Perks Plus'], ['freedom-flex', 'Freedom Flex'],
    ['get-to-learnin', "Get to Learnin'"], ['get-to-learnin/how-to-read-a-texas-electricity-facts-label', 'How to Read an EFL'],
    ['get-to-learnin/how-to-keep-your-cool-in-extreme-heat', 'Keep Your Cool in Extreme Heat'],
    ['get-to-learnin/what-is-energy-choice', 'What Is Energy Choice'], ['get-to-learnin/how-solar-energy-works', 'How Solar Energy Works'],
    ['giddy-up', 'Giddy Up'], ['historical-efls', 'Historical EFLs'], ['locations', 'Locations', '/'],
    ['locations/houston', 'Houston'], ['locations/dallas', 'Dallas'], ['locations/fort-worth', 'Fort Worth'], ['locations/corpus-christi', 'Corpus Christi'],
    ['military-and-first-responders', 'Military & First Responders'], ['monthly-drawing-official-rules', 'Monthly Drawing Rules'],
    ['myaccount', 'My Account'], ['myaccount/dashboard', 'Dashboard'], ['myaccount/pay-bill', 'Pay Bill'], ['myaccount/autopay', 'AutoPay'],
    ['myaccount/paperless-billing', 'Paperless Billing'], ['myaccount/rewards', 'Rewards'], ['myaccount/renew-plan', 'Renew Plan'],
    ['myaccount/peak-perks', 'Peak Perks'], ['myaccount/login', 'Login'], ['plans', 'Plans'], ['promo', 'Promo'], ['refer-a-friend', 'Refer a Friend']
  ].map(function (r, i) {
    return { id: 1000 + i, path: r[0], title: r[1], redirect: r[2] || '', template: r[0].indexOf('myaccount') === 0 ? 'myaccount' : r[0].indexOf('checkout') === 0 ? 'checkout' : r[0].indexOf('get-to-learnin/') === 0 ? 'article' : 'default', status: 'Published' };
  });

  var TEMPLATES = [
    { id: 1, name: 'default', desc: 'Standard marketing page with header, banner and footer', pages: 0 },
    { id: 2, name: 'article', desc: "Get to Learnin' article layout with sidebar", pages: 0 },
    { id: 3, name: 'checkout', desc: 'Enrollment flow with progress steps', pages: 0 },
    { id: 4, name: 'myaccount', desc: 'Logged-in customer portal shell', pages: 0 },
    { id: 5, name: 'landing', desc: 'Promo landing page, no main navigation', pages: 0 }
  ];
  TEMPLATES.forEach(function (t) { t.pages = PAGES.filter(function (p) { return p.template === t.name; }).length; });

  var BLOCKS = [
    { id: 8, name: 'BYOP - Header Tabs', site: 'energytexas.com', updated: '2026-08-14', html: '<h1 class="content-center">Build Your Own Plan</h1>\n<div id="plan-tabs" class="nav inner-col">\n  <ul>\n    <li class="active" tab="started"><a><span>1</span><span class="desktop-only">Get Started</span></a></li>\n    <li tab="lower"><a><span>2</span><span class="desktop-only">Lower Your Bill</span></a></li>\n    <li tab="less"><a><span>3</span><span class="desktop-only">Using Less</span></a></li>\n    <li tab="savings"><a><span>4</span><span class="desktop-only">Save More</span></a></li>\n    <li tab="protection"><a><span>5</span><span class="desktop-only">Protectin\'</span></a></li>\n    <li tab="thanks"><a><span>6</span><span class="desktop-only">Thank You</span></a></li>\n  </ul>\n</div>' },
    { id: 16, name: 'BYOP - Form Tabs', site: 'energytexas.com', updated: '2026-08-14', html: '<div class="tab started hidden">\n  <h2>Let\'s Get Started</h2>\n  [[checkout|type=range|model=ItemElectricity_model|field=plan_term|value=12|min=1|max=36|label=First, Choose Your Plan Length:]]\n  [[checkout|type=checkbox|model=ItemProductAutopay_model|field=autopay]]\n  [[checkout|type=checkbox|model=ItemProductPaperless_model|field=paperless]]\n</div>' },
    { id: 21, name: 'Home - Hero', site: 'energytexas.com', updated: '2026-09-02', html: '<section class="hero">\n  <h1>Power that\'s <em>bigger</em> than Texas.</h1>\n  [[zip_form]]\n</section>' },
    { id: 22, name: 'Home - Featured Plans', site: 'energytexas.com', updated: '2026-09-02', html: '[[plans|group=featured|limit=3]]' },
    { id: 30, name: 'Footer - Links', site: 'energytexas.com', updated: '2026-06-20', html: '<ul class="footer-4-cols">\n  <li><a href="/plans">Residential Plans</a></li>\n  <li><a href="/byop">Build Your Own Plan</a></li>\n</ul>' },
    { id: 31, name: 'Footer - Legal', site: 'energytexas.com', updated: '2026-06-20', html: '<p>&copy; [[year]] Energy Texas. All rights reserved.</p>' },
    { id: 40, name: 'Rewards - Promo Band', site: 'energytexas.com', updated: '2026-07-11', html: '<div class="rewards-band">Earn Rangler Rewards stars on every on-time payment.</div>' }
  ];

  // FICTIONAL customers
  var FIRST = ['Avery', 'Jordan', 'Casey', 'Morgan', 'Riley', 'Quinn', 'Rowan', 'Harper', 'Emerson', 'Dakota', 'Reese', 'Sawyer', 'Hayden', 'Parker', 'Logan', 'Skyler', 'Cameron', 'Blake'];
  var LAST = ['Sample', 'Testcase', 'Example', 'Demo', 'Placeholder', 'Mockford', 'Fakeworth', 'Dummyton', 'Specimen'];
  var STREETS = ['Example Ln', 'Sample St', 'Test Ave', 'Demo Dr', 'Mockingbird Way', 'Placeholder Ct'];
  var CITIES = [['Houston', '77082', 'TX-E-CENTERPOINT', '1008901'], ['Dallas', '75201', 'TX-E-ONCOR', '1044372'], ['Fort Worth', '76102', 'TX-E-ONCOR', '1044372'], ['Corpus Christi', '78401', 'TX-E-AEPCENTRAL', '1003278'], ['Abilene', '79601', 'TX-E-AEPNORTH', '1020404'], ['League City', '77573', 'TX-E-TNMP', '1040051']];
  var STATUSES = [
    ['Submitted', 'info'], ['Pending - Credit', 'warn'], ['Pending - Deposit Due', 'warn'], ['Pending - No Deposit Due', 'warn'],
    ['Pending - Utility Not Answered', 'warn'], ['Good - On Flow', 'ok'], ['Good - On Flow', 'ok'], ['Good - On Flow', 'ok'],
    ['Rejected - By Utility', 'bad'], ['Dropped - Churned', 'bad']
  ];
  var EXC = ['', '', '', '', 'No ESIID', 'Switch Hold', 'Possible Duplicate', '', 'Not sent to Utility', ''];
  var SOURCES = ['Website', 'Phone', 'Texas Electricity Ratings (API)', 'Power to Choose', 'Referral'];
  var resiPlans = ET.defaults.PLANS.filter(function (p) { return p.active && p.type === 'Resi'; });

  function rnd(seed) { var x = Math.sin(seed) * 10000; return x - Math.floor(x); }
  var CUSTOMERS = [];
  for (var i = 0; i < 42; i++) {
    var c = CITIES[i % CITIES.length];
    var st = STATUSES[Math.floor(rnd(i + 1) * STATUSES.length)];
    var plan = resiPlans[Math.floor(rnd(i + 7) * resiPlans.length)];
    var biz = i % 9 === 4;
    var first = FIRST[i % FIRST.length], last = LAST[(i * 5) % LAST.length];
    var created = new Date(2026, 0, 3);
    created.setDate(created.getDate() + Math.floor(rnd(i + 3) * 270));
    var balance = st[0].indexOf('Good') === 0 ? Math.round(rnd(i + 11) * 26000) / 100 : 0;
    CUSTOMERS.push({
      account: String(1219000000 + i * 1373),
      ticket: '1765' + String(100000000000000 + i * 7919),
      name: biz ? last + ' ' + ['Hardware', 'Bakery', 'Auto Repair', 'Dental'][i % 4] + ' LLC' : first + ' ' + last,
      type: biz ? 'Small Business' : 'Residential',
      phone: '(555) 555-01' + String(i % 100).padStart(2, '0'),
      email: (first + '.' + last).toLowerCase() + '@example.com',
      address: (1000 + i * 47) + ' ' + STREETS[i % STREETS.length],
      city: c[0], zip: c[1], market: c[2],
      esiid: c[3] + String(100000000000000 + i * 7777777).slice(0, 15),
      plan: biz ? "Takin' Care of Business 12" : plan.name,
      planCode: biz ? 'TCB12' : plan.internal,
      status: st[0], statusTone: st[1],
      exception: EXC[i % EXC.length],
      source: SOURCES[i % SOURCES.length],
      created: created.toISOString().slice(0, 10),
      balance: balance,
      autopay: i % 3 === 0, paperless: i % 2 === 0,
      stars: Math.floor(rnd(i + 5) * 3000),
      bookmarked: i === 3 || i === 11
    });
  }

  function payments(c) {
    var out = [];
    for (var k = 0; k < 5; k++) {
      var d = new Date(c.created); d.setMonth(d.getMonth() + k + 1);
      out.push({ id: 'PAY-' + c.account.slice(-4) + k, date: d.toISOString().slice(0, 10), amount: Math.round((80 + rnd(+c.account + k) * 140) * 100) / 100, method: k % 2 ? 'Card' : 'ACH', source: k % 3 ? 'MyAccount' : 'AutoPay', status: k === 2 && c.statusTone === 'bad' ? 'Failed' : 'Success' });
    }
    return out;
  }
  function bills(c) {
    var out = [];
    for (var k = 0; k < 5; k++) {
      var d = new Date(c.created); d.setMonth(d.getMonth() + k + 1);
      var kwh = Math.round(700 + rnd(+c.account + k * 3) * 1500);
      out.push({ id: 'BILL-' + c.account.slice(-4) + k, date: d.toISOString().slice(0, 10), kwh: kwh, amount: Math.round(kwh * 0.142 * 100) / 100 });
    }
    return out;
  }
  function notes(c) {
    return [
      { by: 'System', at: c.created + ' 09:14', text: 'Enrollment submitted via ' + c.source + '.' },
      { by: 'System', at: c.created + ' 09:15', text: 'Credit check complete. ' + (c.status.indexOf('Deposit Due') > -1 ? 'Deposit required.' : 'No deposit required.') },
      { by: 'CSR Demo', at: c.created + ' 14:02', text: 'Customer called to confirm start date. Explained EFL and Rangler Rewards.' }
    ];
  }

  var QUEUES = [
    ['ERCOT Exceptions', 7, 'Enrollment transactions rejected or stuck at ERCOT'],
    ['Duplicate IPs', 3, 'Orders placed from the same IP within 24 hours'],
    ['Duplicated Payments', 1, 'Same card and amount charged twice'],
    ['Unapplied Deposits', 4, 'Deposits received but not applied to an account'],
    ['Unapplied Payments', 12, 'Payments not yet posted to the billing system'],
    ['Unapplied Credits', 5, 'Bill credits and promos waiting to post'],
    ['Unapplied Debits', 2, 'Manual debits waiting to post'],
    ['Unapplied Plan Changes', 6, 'Renewals and plan switches not yet sent'],
    ['Unapplied Transfers', 1, 'Move-in / move-out transfers waiting'],
    ['Unbilled Orders', 9, 'On-flow accounts with no first bill'],
    ['Unprocessed Autopays', 3, 'AutoPay drafts that did not run'],
    ['Unprocessed Bills', 0, 'Bills received but not generated'],
    ['Unprocessed Orders', 8, 'Orders waiting to be sent to the utility']
  ];


  // API integrations — values intentionally blank; secrets belong in server env vars, never in the repo.
  var APIS = [
    { name: 'AIG', purpose: 'Home protection products', fields: ['endpoint', 'partner_id', 'api_key'] },
    { name: 'Amazon', purpose: 'S3 document storage & SES email', fields: ['region', 'bucket', 'access_key_id', 'secret_access_key'] },
    { name: 'Apple', purpose: 'Push notifications (APNs)', fields: ['team_id', 'key_id', 'bundle_id', 'private_key'] },
    { name: 'Auth.net', purpose: 'Card processing (legacy)', fields: ['login_id', 'transaction_key'] },
    { name: 'IBM', purpose: 'Weather data for forecasting', fields: ['endpoint', 'api_key'] },
    { name: 'Innowatts', purpose: 'Load forecasting', fields: ['endpoint', 'client_id', 'client_secret'] },
    { name: 'Experian', purpose: 'Credit checks', fields: ['endpoint', 'subscriber_code', 'username', 'password'] },
    { name: 'Plaid', purpose: 'Bank account linking', fields: ['environment', 'client_id', 'secret'] },
    { name: 'QuickBooks', purpose: 'Accounting journal entries', fields: ['realm_id', 'client_id', 'client_secret'] },
    { name: 'SalesForce', purpose: 'Marketing Cloud email', fields: ['subdomain', 'client_id', 'client_secret'] },
    { name: 'Stripe', purpose: 'Card & ACH payments', fields: ['publishable_key', 'secret_key', 'webhook_secret'] },
    { name: 'Utilibill', purpose: 'Billing system (UB)', fields: ['endpoint', 'username', 'password'] }
  ];

  var CRONS = [
    { name: 'Send orders to utility', schedule: '*/15 * * * *', last: '2026-10-03 09:45', status: 'OK', ms: 4210 },
    { name: 'Pull ERCOT 814 responses', schedule: '*/10 * * * *', last: '2026-10-03 09:50', status: 'OK', ms: 2875 },
    { name: 'Process AutoPay drafts', schedule: '0 6 * * *', last: '2026-10-03 06:00', status: 'OK', ms: 61022 },
    { name: 'Sync payments to Utilibill', schedule: '*/30 * * * *', last: '2026-10-03 09:30', status: 'Warning', ms: 15480 },
    { name: 'Generate welcome packets', schedule: '0 * * * *', last: '2026-10-03 09:00', status: 'OK', ms: 8803 },
    { name: 'Rangler Rewards stars', schedule: '0 2 * * *', last: '2026-10-03 02:00', status: 'OK', ms: 3311 },
    { name: 'Export rates to Power to Choose', schedule: '30 5 * * *', last: '2026-10-03 05:30', status: 'Failed', ms: 0 },
    { name: 'Rebuild sitemap', schedule: '0 3 * * 0', last: '2026-09-28 03:00', status: 'OK', ms: 1290 }
  ];

  var DATA_TABLES = {
    'Deposit Thresholds': { cols: ['Credit Score From', 'Credit Score To', 'Deposit'], rows: [[0, 549, '$300'], [550, 599, '$200'], [600, 649, '$100'], [650, 900, '$0']] },
    'Max Deposits': { cols: ['Customer Type', 'Max Deposit'], rows: [['Residential', '$400'], ['Small Business', '$1,500']] },
    'Blackout Days': { cols: ['Date', 'Reason'], rows: [['2026-11-26', 'Thanksgiving'], ['2026-12-25', 'Christmas'], ['2027-01-01', "New Year's Day"]] },
    'Tax Rates': { cols: ['City', 'Sales Tax', 'Gross Receipts', 'PUC Assessment'], rows: [['Houston', '0.00%', '1.997%', '0.167%'], ['Dallas', '0.00%', '1.997%', '0.167%'], ['Corpus Christi', '0.00%', '1.997%', '0.167%'], ['Abilene', '0.00%', '1.581%', '0.167%']] },
    'Interest Rates': { cols: ['Year', 'Deposit Interest'], rows: [['2026', '3.99%'], ['2025', '4.15%']] },
    'Bill Credits & Promos': { cols: ['Code', 'Description', 'Amount'], rows: [['REFER50', 'Refer a Friend credit', '$50.00'], ['MOVE25', 'Moving Made Easy credit', '$25.00'], ['MIL10', 'Military & First Responders monthly credit', '$10.00']] },
    'Fraud Indicators': { cols: ['Rule', 'Action'], rows: [['3+ orders from one IP in 24h', 'Flag for review'], ['Prepaid card on deposit', 'Require ACH'], ['Address mismatch with ESIID', 'Hold order']] },
    'CIS Note Dispositions': { cols: ['Code', 'Label'], rows: [['BILLQ', 'Billing question'], ['PAYX', 'Payment arrangement'], ['MOVE', 'Move in / move out'], ['CANC', 'Cancellation request']] },
    'Charge Codes': { cols: ['Code', 'Description'], rows: [['ENG', 'Energy charge'], ['TDU', 'TDU delivery charge'], ['MRC', 'Base charge'], ['ETF', 'Early termination fee'], ['LATE', 'Late fee']] }
  };

  var REPORT_STATUSES = ['Exception', 'Submitted', 'Rejected', 'Deposit Due', 'Deposit Paid', 'Pending - Ready to send to Utility', 'Requested - Waiting for Utility Response', 'Accepted - Accepted by Utility', 'Flowing - On Flow', 'Churned'];
  var CUSTOMER_STATUSES = ['Submitted', 'Pending - Credit', 'Pending - Deposit Due', 'Pending - No Deposit Due', 'Pending - Utility Not Answered', 'Good - On Flow', 'Rejected - By Utility', 'Dropped - Churned'];
  var EXCEPTIONS = ['Not sent to UtiliBill', 'Not sent to Utility', 'No ESIID', 'Non-Resi Meter', 'Permit Required', 'Switch Hold', 'Possible Duplicate', 'Other Exception'];

  var d = ET.defaults;
  d.PAGES = PAGES; d.TEMPLATES = TEMPLATES; d.BLOCKS = BLOCKS; d.CUSTOMERS = CUSTOMERS; d.QUEUES = QUEUES;
  d.APIS = APIS; d.CRONS = CRONS; d.DATA_TABLES = DATA_TABLES; d.NOTES = {};
  return {
    REPORT_STATUSES: REPORT_STATUSES, CUSTOMER_STATUSES: CUSTOMER_STATUSES, EXCEPTIONS: EXCEPTIONS,
    payments: payments, bills: bills, notes: notes
  };
})();
