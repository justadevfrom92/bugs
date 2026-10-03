/* Sheriff — settings & integrations. Screens: admin/_app/views/sheriff.js */
window.APP_CONFIG = {
  key: 'sheriff',
  home: 'users',
  menu: [
    ['Access', [['users', 'Users'], ['roles', 'Roles']]],
    ['System', [['apis', 'APIs'], ['crons', 'Crons', { badge: 'crons', hot: true }]]],
    ['Data', ['Deposit Thresholds', 'Max Deposits', 'Blackout Days', 'Tax Rates', 'Interest Rates', 'Bill Credits & Promos',
      'Fraud Indicators', 'CIS Note Dispositions', 'Charge Codes'].map(function (t) { return ['data/' + encodeURIComponent(t), t]; })]
  ]
};
