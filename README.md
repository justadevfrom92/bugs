# Energy Texas — Website & Admin Tools

A static, responsive website styled after energytexas.com plus the internal admin tools (Corral, Lando, Astro, Sheriff). Plain HTML, CSS and JavaScript: no framework and no build step.

## Run it (Linux)
```sh
./serve.sh          # http://localhost:8000
./serve.sh 9000     # pick a port
```
Click **Admin** (top right of the website) to sign in. The admin opens in a new tab with a launcher listing the apps your role can use. Demo users: `admin@example.com` (everything), `csr@example.com` (Corral), `pricing@example.com` (Lando + Astro), `marketing@example.com` (Lando), `finance@example.com` (Corral + Sheriff). Any password works.

## How it's organized: one copy of everything
Shared code and data live in `shared/`. The website and every admin app read from it, so an edit happens in one place.

```
shared/
  boot.js              one <script> tag per page loads everything below, then the page's own files
  css/tokens.css       brand colors + fonts (website and admin)
  config/brand.js      company name, phone, hours, PUCT number, site nav
  config/catalog.js    markets, zip ranges, TDSP fees, plans, plan groups, rates, term discounts, BYOP products
  config/access.js     admin users and roles (which apps each role can open)
  config/apps.js       the admin app list shown in the launcher and app switcher
  js/core.js           data store, pricing (EFL average price), navigation helpers
  js/auth.js           admin sign-in, used by the website's Admin prompt and admin/login.html

index.html, plans.html, …   website pages
css/style.css, js/main.js   website styles and behaviour

admin/
  index.html           launcher (what the Admin button opens)
  login.html           sign-in page for direct visits
  _app/                the shared admin app
    app.html           the single page every admin app uses
    shell.js           sidebar, top bar, menu and router, built from the app's config.js
    admin.css          admin styles
    sample-data.js     fictional customers, CMS pages, queues, integrations
    views/<app>.js     each app's screens
  corral/  lando/  astro/  sheriff/
    index.html -> ../_app/app.html     (symlink)
    config.js                          that app's menu: the only file in the folder
```

**Adding an admin app:** add an entry to `shared/config/apps.js`, create `admin/<key>/config.js` with its menu, symlink `admin/<key>/index.html -> ../_app/app.html`, write its screens in `admin/_app/views/<key>.js`, and add the key to the roles that should see it.

The app folders use symlinks, which Linux and macOS handle natively. On Windows, clone with `git config core.symlinks true` (Developer Mode enabled) or serve from Linux.

### The website and admin share live data
Plans, rates, TDSP fees, plan groups, term discounts and Build Your Own Plan products come from `shared/config/catalog.js`. Change a rate in **Lando → Update Rates**, a plan's bullets in **Lando → Plans**, or hide a product in **Astro → BYOP Products**, and the website shows it on the next page load.

## Admin apps
| App | Screens |
| --- | --- |
| **Corral** (customer service) | Customer search with status/exception filters, account detail (service, billing, payments, notes, products), ESIID lookup, create order (resi & biz), renew/change plan, orders report, exception queues |
| **Lando** (CMS) | Page tree & page editor, templates, content blocks with live preview, plans list/editor (including website bullets and filters), plan groups, rate search with EFL average price, bulk rate editor, TDSP fees, markets |
| **Astro** (pricing) | Term discount grid by TDSP region, ETF by term, BYOP products (prices and visibility on the website) |
| **Sheriff** (settings) | Users, role permissions (which apps each role can open), API integrations, crons, reference data tables |

## Before going live
- **There is no backend yet.** Edits are saved in the browser's localStorage (sidebar → **Reset data** restores the defaults). `ET.data` / `ET.save` in `shared/js/core.js` are the single place to switch to real API calls.
- **Sign-in is a demo** with no password check. Replace `ET.auth.login` in `shared/js/auth.js` with real authentication and protect `admin/` on the server.
- Rates and TDSP fees are illustrative; the phone number and PUCT number in `shared/config/brand.js` are placeholders.
- Every customer and user is fictional. API keys are deliberately not stored in this repo; keep them in server environment variables.
