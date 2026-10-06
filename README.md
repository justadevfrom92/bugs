# Energy Texas — Website & Admin Tools

The public website (with sign-up and the My Account portal) plus the internal admin tools (Corral, Lando, Astro, Walker, Caboose, Bounty, Rodeo, Sheriff), served by **Laravel 13** on PHP 8.3.
No Node, no React and no build step: the admin is server-rendered Blade with plain CSS and a small plain-JavaScript file.

## Run it on Linux

Requirements: PHP 8.3+ with `pdo_sqlite` (or `pdo_mysql`), and Composer.

```sh
composer setup          # install, create .env + app key, create the SQLite database, migrate and seed
php artisan serve       # http://localhost:8000
```

Click **Admin** (top right of the website). It asks you to sign in, then opens the launcher in a new tab.

Demo users (all fictional; password is `ADMIN_DEMO_PASSWORD`, default `password`):

| Email | Role | Apps |
| --- | --- | --- |
| admin@example.com | Administrator | everything, including deletes and refunds |
| csr@example.com | Customer Service | Corral, Bounty |
| pricing@example.com | Pricing | Lando, Astro |
| marketing@example.com | Content Editor | Lando, Rodeo |
| finance@example.com | Finance | Corral, Walker, Caboose, Sheriff, refunds |

Website customer login (My Account, at `/myaccount`): username `demo.customer`, password `password` (fictional account 1219000000).

Other commands: `php artisan test` (feature tests), `php artisan migrate:fresh --seed` (reset the sample data).

### Production notes
- Point the web server's document root at `public/` (Apache or Nginx + PHP-FPM, the usual Laravel setup).
- Use MySQL by setting `DB_CONNECTION=mysql` and the `DB_*` values in `.env`, then `php artisan migrate --force`.
- Scheduled jobs need one cron entry: `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`
- Background reports (Walker, and Corral's "Output - Backend") need a queue worker, e.g. a systemd service or Supervisor running `php artisan queue:work --tries=1`. Without it they stay "Queued" and show as "Did not finish" after an hour.
- Set `APP_ENV=production`, `APP_DEBUG=false`, change or remove the demo users, and run `php artisan config:cache route:cache view:cache`.

## How it fits together

```
public/                    the website (static pages) + admin-assets/ (admin CSS and JS)
  shared/boot.js           one <script> per page; loads brand.js, catalog.js and core.js
  shared/css/tokens.css    brand colors and fonts for the website AND the admin
config/brand.php           company name, phone, PUCT number, website nav  → /shared/config/brand.js
config/admin.php           every admin app: name, icon, menu, queues, reference tables, jobs, integrations
app/Services/Catalog.php   current rates/fees and the EFL average price  → /shared/config/catalog.js
app/Http/Controllers/
  SiteController.php       website data, session/CSRF token, Contact Us form
  Admin/{Corral,Lando,Astro,Walker,Caboose,Bounty,Rodeo,Sheriff}/   one folder of controllers per app
  SignupController.php, MyAccount/, SurveyController.php   website sign-up, customer portal, surveys
app/Reports/               report definitions shared by Walker and Corral (config/walker.php lists them)
resources/views/admin/
  layouts/app.blade.php    the one layout every app uses (sidebar, switcher, menu from config/admin.php)
  {corral,lando,astro,walker,caboose,bounty,rodeo,sheriff}/   each app's screens
app/Console/Commands/      scheduled jobs (et:*), each run logged for Sheriff → Crons
database/migrations, seeders/data/*.json   schema and fictional sample data
```

**Configure, don't copy.** Apps share one layout, and their menus, queues, reference tables, jobs and integrations are entries in `config/admin.php`. Adding a menu item or a whole app is a config change plus its controller and views. Brand values live in `config/brand.php` and colors in `public/shared/css/tokens.css`, used by both the website and the admin.

**The website reads live data.** `/shared/config/catalog.js` is generated from the database on each request, so a rate saved in Lando, a plan's bullets, a hidden product in Astro or a changed ETF shows on the website on the next page load. Rates and TDSP fees keep history: saving adds rows with an effective date, and the website switches on that date.

## Admin apps
| App | What it does |
| --- | --- |
| **Corral** | Customer search (status/exception filters, bookmarks), account detail with billing, payments (reversal needs the *refunds* right), notes; ESIID lookup; phone orders (resi & biz) and renew/change plan; orders report (screen, summary, CSV); exception queues; website Contact Us messages |
| **Lando** | Page tree and editor (delete needs the *delete* right), sitemap rebuild, templates, content blocks with live preview, plans (incl. website bullets and filters), plan groups, rate search with EFL average price, bulk rate editor with effective dates, TDSP fees, markets |
| **Astro** | Term discounts by region, ETF by term, Build Your Own Plan products (price and whether the website shows them) |
| **Walker** | Reporting: home lists report runs that are running, completed, or errored / did not finish; each run has its own page (model reference, filters, preview, download) with **Rerun** top right; report library; uploaded report files |
| **Caboose** | Finance: dashboard, payments (record check/money order/cash/wire, reverse), pending credits & debits to apply, refunds (request → approve → paid; deciding needs the *refunds* right), deposits held and due, receivables aging, journal export (CSV/XLSX) |
| **Bounty** | Rewards: reward offers, star earning rules (used by the `et:rewards-stars` job), redemptions to fulfil, member star balances and adjustments, monthly drawing |
| **Rodeo** | Marketing: email/SMS campaigns with an audience builder, email templates with placeholders and **Send a Test** (to typed addresses, account numbers, your Corral bookmarks, an admin team, or a customer category; up to 25 people, subject marked [TEST]), customer surveys with results and NPS, MSIDs and promo codes |
| **Sheriff** | Users (add, change role, disable), role permissions, API integration status, scheduled jobs with Run Now and history, reference data tables |

## Website sign-up, My Account and surveys
- **Sign up** (`/checkout`): address → plan → about you → review → submit, with save-and-resume and a deposit step. Orders land in Corral like phone orders.
- **My Account** (`/myaccount`): dashboard, bills and payments, usage insights, pay a bill, payment methods, products, profile and password, authorized users, linked accounts, renew/change plan, transfer service, rewards, refer a friend, messages. QuickPay at `/myaccount/quickpay` needs no sign-in.
- **Surveys** (`/survey/<slug>`): built in Rodeo. Put `{{survey:<slug>}}` in an email template; each customer gets a signed link so their answer is tied to their account.

Test emails go out through Laravel's mailer: set `MAIL_MAILER` (e.g. `smtp`) and the `MAIL_*` values in `.env`; with the default `log` they are written to `storage/logs` instead of delivered. Card payments, campaign emails and texts need Stripe, Salesforce and Twilio keys in `.env`; until then payments are recorded as Pending and campaign messages are logged but not sent.

## Adding an admin app
On the launcher, **+ New Admin App** (roles with Sheriff, e.g. Administrator) creates a new app: name, description, icon, which roles can open it, and its sidebar links. Each link points to any existing admin screen or to a URL, grouped under headings. The app gets a tile on the launcher, opens at `/admin/<address>` in the shared admin layout, and appears as a column in Sheriff → Roles. Edit or delete it from its **Edit App** button. These apps are stored in the `admin_apps` table; the built-in apps stay in `config/admin.php`.

## History
Every change to accounts, payments, bills, notes, queues, plans, rates, fees, pricing, pages, blocks, users and roles is recorded automatically in `history_items`: who, when, the record's model name (the original system's naming, e.g. `ItemPayment_model`), each field's old → new value and a snapshot of the record. Sign-ins, plan-group edits, reference-table saves and job runs are recorded too. Passwords are never stored in history.

- **Corral → account → History:** counts per model grouped like the old *Logs* pages (Products, Payments, EDI Transactions, Emails…), the full timeline (filter by clicking a model), Usage History by month and Rewards History.
- **Each entry** has a detail page: its fields, changes, parent account and other entries of the same model (*Process Logs*).
- **Sheriff → Users → History:** everything one admin user did.

Which models are tracked, their model names and groups are set in `config/history.php`.

## Security
- Passwords are hashed; sign-in is rate limited; all forms are CSRF protected; app access and the *delete*/*refunds* rights are checked on the server.
- API keys go in `.env` only. Sheriff → APIs shows whether each is set, never the value.
- Jobs that need an outside service (Utilibill, Stripe, Amazon) report **Skipped** until their keys are set, and **Failed** with an explanation once keys are set, because those API clients still have to be written.

## Placeholders to replace before going live
- Rates and TDSP fees in the seed data are illustrative.
- `BRAND_PHONE` and `BRAND_PUCT` in `.env`.
- Every customer and user in the seed data is fictional.
