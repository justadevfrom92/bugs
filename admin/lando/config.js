/* Lando — website CMS. Screens: admin/_app/views/lando.js */
window.APP_CONFIG = {
  key: 'lando',
  home: 'pages',
  menu: [
    ['Pages', [['pages', 'Pages List'], ['templates', 'Page Templates'], ['blocks', 'Content Blocks']]],
    ['Plans', [['plans', 'View Plans'], ['plan/new', 'Add a Plan'], ['groups', 'Plan Groups']]],
    ['Rates', [['rates', 'View Rates'], ['update-rates', 'Update Rates'], ['tdsp', 'TDSP Fees']]],
    ['Markets', [['markets', 'View Markets']]]
  ],
  aliases: { plan: 'plans', block: 'blocks', page: 'pages' }
};
