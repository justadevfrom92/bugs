/*
  Admin users and roles (FICTIONAL sample accounts).
  A role's perms list names the admin apps it can open, plus extra rights.
*/
window.ET = window.ET || {};
ET.defaults = ET.defaults || {};
ET.defaults.USERS = [
  { id: 1, name: 'Admin Demo', email: 'admin@example.com', role: 'Administrator', last: '2026-10-03 08:12', active: true },
  { id: 2, name: 'CSR Demo', email: 'csr@example.com', role: 'Customer Service', last: '2026-10-03 09:40', active: true },
  { id: 3, name: 'Pricing Demo', email: 'pricing@example.com', role: 'Pricing', last: '2026-10-02 16:05', active: true },
  { id: 4, name: 'Marketing Demo', email: 'marketing@example.com', role: 'Content Editor', last: '2026-09-29 11:30', active: true },
  { id: 5, name: 'Finance Demo', email: 'finance@example.com', role: 'Finance', last: '2026-09-30 13:22', active: true },
  { id: 6, name: 'Former Employee', email: 'former@example.com', role: 'Customer Service', last: '2026-03-14 10:00', active: false }
];
ET.defaults.ROLES = [
  { name: 'Administrator', perms: ['corral', 'lando', 'astro', 'sheriff', 'delete', 'refunds'] },
  { name: 'Customer Service', perms: ['corral'] },
  { name: 'Pricing', perms: ['lando', 'astro'] },
  { name: 'Content Editor', perms: ['lando'] },
  { name: 'Finance', perms: ['corral', 'sheriff', 'refunds'] }
];
ET.PERMS = [['corral', 'Corral (customers)'], ['lando', 'Lando (CMS & rates)'], ['astro', 'Astro (pricing modifiers)'], ['sheriff', 'Sheriff (settings)'], ['delete', 'Permanent deletes'], ['refunds', 'Refunds & reversals']];
