/* Corral — customer service. Screens: admin/_app/views/corral.js */
window.APP_CONFIG = {
  key: 'corral',
  home: 'home',
  menu: [
    ['Customers', [['home', 'Customer Search'], ['esiid', 'ESIID Lookup'], ['order', 'Create Order'], ['order-biz', 'Create Order - Biz']]],
    ['Reports', [['reports', 'Orders Report'], ['queues', 'Exception Queues', { badge: 'queues', hot: true }]]]
  ],
  aliases: { customer: 'home', queue: 'queues' }
};
