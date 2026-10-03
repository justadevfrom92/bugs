/*
  Product catalog: markets, TDSP fees, plans, plan groups, rates and pricing modifiers.
  The website and the admin both read from here (through ET.data), so a plan or rate
  is defined exactly once.
*/
window.ET = window.ET || {};
ET.defaults = ET.defaults || {};
(function (d) {
  'use strict';

  d.MARKETS = [
    { id: 2, name: 'TX-E-ONCOR', short: 'ONCOR', desc: 'Oncor Electric Delivery', region: 'ONCOR', phone: '888-313-4747' },
    { id: 3, name: 'TX-E-CENTERPOINT', short: 'CNP', desc: 'CenterPoint Energy Houston Electric', region: 'CENTERPOINT', phone: '800-332-7143' },
    { id: 4, name: 'TX-E-AEPNORTH', short: 'AEPN', desc: 'AEP Texas North', region: 'NORTH', phone: '877-373-4858' },
    { id: 5, name: 'TX-E-AEPCENTRAL', short: 'AEPC', desc: 'AEP Texas Central', region: 'CENTRAL', phone: '877-373-4858' },
    { id: 6, name: 'TX-E-TNMP', short: 'TNMP', desc: 'Texas-New Mexico Power', region: 'TNMP', phone: '888-866-7456' }
  ];

  // Simplified zip → market ranges for the website's zip lookup. Replace with the ESIID lookup.
  d.ZIP_RANGES = [
    [75000, 76999, 'TX-E-ONCOR'], [77000, 77599, 'TX-E-CENTERPOINT'], [77600, 77799, 'TX-E-TNMP'],
    [78300, 78599, 'TX-E-AEPCENTRAL'], [78600, 78699, 'TX-E-ONCOR'], [79500, 79699, 'TX-E-AEPNORTH'], [79700, 79799, 'TX-E-ONCOR']
  ];

  // Illustrative TDSP delivery charges — replace with current PUCT tariff values.
  d.TDSP_FEES = [
    { market: 'TX-E-AEPCENTRAL', perKwh: 5.6436, perBill: 4.79, kwhDate: '2026-09-01', billDate: '2026-09-01' },
    { market: 'TX-E-AEPNORTH', perKwh: 5.2381, perBill: 4.79, kwhDate: '2026-09-01', billDate: '2026-09-01' },
    { market: 'TX-E-CENTERPOINT', perKwh: 5.4892, perBill: 4.39, kwhDate: '2026-09-01', billDate: '2026-09-01' },
    { market: 'TX-E-ONCOR', perKwh: 5.5730, perBill: 4.23, kwhDate: '2026-07-01', billDate: '2026-07-01' },
    { market: 'TX-E-TNMP', perKwh: 6.0410, perBill: 7.85, kwhDate: '2026-09-01', billDate: '2026-09-01' }
  ];

  var R = 'Rangler Rewards included';
  // type, term, display name, internal name, rolloff, ETF, MRC, green %, active, tags, website bullets
  d.PLANS = [
    ['Biz', 1, 'Mill Creek Variable', 'MCV', 'None', '-', 2.95, 100, true],
    ['Biz', 1, "Takin' Care of Business Monthly", 'TCBM', 'None', '-', 0, 100, true],
    ['Biz', 1, "Takin' Care of Business Variable", 'TCBV', 'None', '-', 0, 100, true],
    ['Biz', 12, 'Homebuilder 12', 'H12', 'None', '-', 2.95, 100, true],
    ['Biz', 12, 'Indexed 12', 'IDX12', 'None', '-', 0, 100, true],
    ['Biz', 12, "Takin' Care of Business 12", 'TCB12', 'TCBV', '$300/$50x', 0, 100, true],
    ['Biz', 24, 'Homebuilder 24', 'H24', 'None', '-', 2.95, 100, true],
    ['Biz', 24, "Takin' Care of Business 24", 'TCB24', 'TCBV', '$300/$50x', 0, 100, true],
    ['Biz', 36, 'Epicenter', 'Epicenter', 'None', '-', 0, 100, true],
    ['Biz', 36, "Takin' Care of Business 36", 'TCB36', 'TCBV', '$300/$50x', 0, 100, true],
    ['Resi', 1, 'Cinco de Mayo', 'CDMV1', 'None', '-', 4.95, 100, true],
    ['Resi', 1, 'Energy Texas Monthly', 'ETM', 'None', '-', 4.95, 100, true, ['month'], ['Leave anytime, no ETF', 'Great for renters & short stays', 'Paperless & AutoPay ready']],
    ['Resi', 1, 'Energy Texas Monthly', 'ETM2', 'ETM', '-', 4.95, 100, true],
    ['Resi', 1, "Just Electricity, Y'all", 'JEY', 'None', '-', 4.95, 100, true, ['month'], ['No contract, no cancellation fee', 'Month-to-month flexibility', R]],
    ['Resi', 1, "Just Electricity, Y'all", 'JEY2', 'JEY', '-', 4.95, 100, true],
    ['Resi', 1, "Just Electricity, Y'all", 'JEY3', 'JEY2', '-', 4.95, 100, true],
    ['Resi', 1, 'Moving Made Easy', 'MME1', 'None', '-', 4.95, 100, true, ['month'], ['Same-day service available', 'Built for movers', 'Switch to a fixed plan anytime']],
    ['Resi', 12, 'Build Your Own Plan', 'BYOP', 'BYOPV', '$250', 4.95, 100, true],
    ['Resi', 12, 'Come & Take It 12', 'CT12', 'JEY3', '$250', 4.95, 100, true, ['fixed'], ['Fixed rate locked for 12 months', 'Rangler Rewards on every bill', 'Free Peak Perks enrollment']],
    ['Resi', 12, 'Freedom Flex 12', 'FreeF12', 'JEY3', '$250', 4.95, 100, true, ['fixed', 'flex'], ['Fixed rate for 12 months', 'Flex your due date', R]],
    ['Resi', 12, 'Pardner Preferred 12', 'PP12', 'JEY3', '$250', 4.95, 100, true, ['fixed'], ['Fixed rate for 12 months', 'Priority customer care line', R]],
    ['Resi', 18, 'The Gruene 18', 'G18', 'JEY3', '$275', 4.95, 100, true, ['fixed', 'green'], ['100% renewable energy', 'Fixed rate for 18 months', R]],
    ['Resi', 18, 'Pardner Preferred 18', 'PP18', 'JEY3', '$275', 4.95, 100, true, ['fixed'], ['Fixed rate for 18 months', 'Priority customer care line', R]],
    ['Resi', 24, 'Bigger Than Texas 24', 'BTT24', 'JEY3', '$400', 4.95, 100, true, ['fixed'], ['Fixed rate locked for 2 years', 'Bonus Rangler Rewards stars', 'Free Peak Perks enrollment']],
    ['Resi', 24, 'Freedom Flex 24', 'FreeF24', 'JEY3', '$400', 4.95, 100, true, ['fixed', 'flex'], ['Fixed rate for 24 months', 'Flex your due date', R]],
    ['Resi', 24, 'Pardner Preferred 24', 'PP24', 'JEY3', '$400', 4.95, 100, true, ['fixed'], ['Fixed rate for 24 months', 'Priority customer care line', R]],
    ['Resi', 36, '36 Inflation Fix', '36IF', 'JEY3', '$500', 4.95, 100, true, ['fixed'], ['Beat inflation for 3 full years', 'Lowest long-term fixed rate', R]],
    ['Resi', 36, 'ecobee Clean & Green 36', 'ECG36', 'JEY3', '$500', 4.95, 100, true, ['fixed', 'green'], ['FREE ecobee smart thermostat', '100% renewable energy', 'Fixed rate for 36 months']],
    ['Resi', 36, 'Freedom Flex 36', 'FreeF36', 'JEY3', '$500', 4.95, 100, true, ['fixed', 'flex'], ['Fixed rate for 36 months', 'Flex your due date', R]],
    ['Resi', 36, 'Pardner Preferred 36', 'PP36', 'JEY3', '$500', 4.95, 100, true, ['fixed'], ['Fixed rate for 36 months', 'Priority customer care line', R]],
    ['Biz', 6, "Takin' Care of Business 6", 'TCB6', 'TCBV', '$300', 0, 100, false],
    ['Resi', 1, 'Build Your Own Plan', 'BYOPV', 'None', '$0', 4.95, 100, false],
    ['Resi', 3, 'Taste of Energy Texas 3', 'TET3', 'JEY3', '$100', 4.95, 100, false],
    ['Resi', 3, 'Texan Pride 3', 'TXP3', 'JEY3', '$100', 4.95, 100, false],
    ['Resi', 6, 'Lone Star 6', 'LS6', 'JEY3', '$175', 4.95, 100, false],
    ['Resi', 11, 'Texan 11', 'TX11', 'JEY3', '$300', 4.95, 100, false],
    ['Resi', 12, 'Friends & Family 12', 'FF12', 'JEY3', '$250', 4.95, 100, false],
    ['Resi', 12, "September Savin' 12", 'SS12', 'JEY3', '$250', 4.95, 100, false],
    ['Resi', 12, 'Very Important Texan 12', 'VIT12', 'JEY3', '$250', 4.95, 100, false],
    ['Resi', 12, 'True Texan 12', 'TT12', 'JEY3', '$300', 4.95, 100, false],
    ['Resi', 13, 'The Abilene 13', 'A13', 'JEY3', '$300', 4.95, 100, false],
    ['Resi', 14, "Fixin' to 14", 'F14', 'JEY3', '$275', 4.95, 100, false],
    ['Resi', 15, 'The Seguin 15', 'S15', 'JEY3', '$275', 4.95, 100, false]
  ].map(function (r, i) {
    return {
      id: 60 + i, type: r[0], term: r[1], name: r[2], internal: r[3], rolloff: r[4],
      etf: r[5], mrc: r[6], green: r[7], active: r[8], tags: r[9] || [], perks: r[10] || [],
      slug: r[2].toLowerCase().replace(/&/g, 'and').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
    };
  });

  // Plan groups decide which plans each website section shows.
  d.PLAN_GROUPS = [
    { id: 1, name: 'Homepage Featured', slug: 'featured', plans: ['CT12', 'BTT24', '36IF'] },
    { id: 2, name: 'Residential Plans Page', slug: 'resi', plans: ['JEY', 'ETM', 'MME1', 'CT12', 'FreeF12', 'PP12', 'G18', 'PP18', 'BTT24', 'FreeF24', 'PP24', '36IF', 'ECG36', 'FreeF36', 'PP36'] },
    { id: 3, name: 'Business', slug: 'business', plans: ['TCBV', 'TCB12', 'TCB24', 'TCB36', 'H12', 'H24', 'IDX12'] },
    { id: 4, name: 'Freedom Flex', slug: 'freedom-flex', plans: ['FreeF12', 'FreeF24', 'FreeF36'] },
    { id: 5, name: 'Renewal Offers', slug: 'renew', plans: ['PP12', 'PP24', 'PP36', '36IF'] },
    { id: 6, name: 'ecobee Partner', slug: 'ecobee', plans: ['ECG36'] }
  ];

  // Illustrative energy charges (¢/kWh) by plan and market.
  var ADJ = { 'TX-E-ONCOR': 0, 'TX-E-CENTERPOINT': 0.15, 'TX-E-AEPNORTH': 0.35, 'TX-E-AEPCENTRAL': 0.3, 'TX-E-TNMP': 0.5 };
  d.RATES = [];
  d.PLANS.filter(function (p) { return p.active; }).forEach(function (p) {
    d.MARKETS.forEach(function (m) {
      var base = p.term === 1 ? 11.2 : 10.6 - Math.min(p.term, 36) * 0.035;
      d.RATES.push({ plan: p.internal, market: m.name, energy: Math.round((base + ADJ[m.name] + (p.internal.length % 3) * 0.07) * 1000) / 1000, effective: '2026-09-15' });
    });
  });

  // Astro: term discounts (¢/kWh; positive lowers the rate) by region, and ETF by term.
  d.REGIONS = ['Generic', 'CENTRAL', 'NORTH', 'CENTERPOINT', 'ONCOR', 'TNMP'];
  d.TERM_MODS = [];
  for (var t = 1; t <= 36; t++) {
    var row = { term: t };
    d.REGIONS.forEach(function (r, j) {
      var v = t === 1 ? -0.5 : Math.round((t / 36) * 1.2 * 100) / 100 - (j === 5 ? 0.15 : 0) + (j === 3 ? 0.05 : 0);
      row[r] = Math.round(v * 100) / 100;
    });
    row.etf = t === 1 ? 0 : t <= 3 ? 100 : t <= 6 ? 175 : t <= 12 ? 250 : t <= 18 ? 275 : t <= 24 ? 400 : 500;
    d.TERM_MODS.push(row);
  }

  // Build Your Own Plan add-ons. `key` matches the option on the website's BYOP page.
  d.BYOP_PRODUCTS = [
    { id: 1, key: 'autopay', name: 'AutoPay', model: 'ItemProductAutopay_model', type: 'Discount', adj: -0.30, monthly: 0, step: 'Get Started', active: true },
    { id: 2, key: 'paperless', name: 'Paperless Billing', model: 'ItemProductPaperless_model', type: 'Discount', adj: -0.20, monthly: 0, step: 'Get Started', active: true },
    { id: 3, key: 'rewards', name: 'Rangler Rewards', model: 'ItemProductReward_model', type: 'Included', adj: 0, monthly: 0, step: 'Lower Your Bill', active: true },
    { id: 4, key: 'peakperks', name: 'Peak Perks', model: 'ItemProductPeakPerk_model', type: 'Discount', adj: -0.40, monthly: 0, step: 'Lower Your Bill', active: true },
    { id: 5, key: 'giddyup', name: 'Giddy Up Due Date', model: 'ItemProductGiddyup_model', type: 'Free', adj: 0, monthly: 0, step: 'Lower Your Bill', active: true },
    { id: 6, key: 'thermostat', name: 'ecobee Smart Thermostat', model: 'ItemProductThermostat_model', type: 'Monthly', adj: 0, monthly: 5.99, step: 'Using Less', active: true },
    { id: 7, key: 'solar', name: 'Solar Buy-Back', model: 'ItemProductSolar_model', type: 'Free', adj: 0, monthly: 0, step: 'Using Less', active: true },
    { id: 8, key: 'green', name: '100% Renewable', model: 'ItemProductGreen_model', type: 'Premium', adj: 0.30, monthly: 0, step: 'Using Less', active: true },
    { id: 9, key: 'refer', name: 'Refer a Friend', model: 'ItemProductReferral_model', type: 'Discount', adj: -0.20, monthly: 0, step: 'Save More', active: true },
    { id: 10, key: 'billpredictor', name: 'Bill Predictor Alerts', model: 'ItemProductBillPredictor_model', type: 'Free', adj: 0, monthly: 0, step: 'Save More', active: true },
    { id: 11, key: 'acwarranty', name: 'A/C Warranty', model: 'ItemProductWarrantyAc_model', type: 'Monthly', adj: 0, monthly: 14.99, step: "Protectin'", active: true },
    { id: 12, key: 'linewarranty', name: 'Line Warranty', model: 'ItemProductWarrantyLine_model', type: 'Monthly', adj: 0, monthly: 5.99, step: "Protectin'", active: true },
    { id: 13, key: 'evwarranty', name: 'EV Charger Warranty', model: 'ItemProductWarrantyCharger_model', type: 'Monthly', adj: 0, monthly: 7.99, step: "Protectin'", active: true }
  ];
})(ET.defaults);
