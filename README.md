# Energy Texas — Static Website

A static, responsive website styled after energytexas.com (navy `#102247`, cyan `#00AEEF`, red `#C80611`, Montserrat + Raleway — taken from `et_style_2021.css`).

## Pages
- `index.html` — home: hero with zip-code lookup, featured plans, Build Your Own Plan promo, Rangler Rewards, testimonials, Get to Learnin' articles
- `plans.html` — all active residential plans with term / green filters and FAQ
- `build-your-own-plan.html` — 6-step BYOP wizard (Get Started → Lower Your Bill → Using Less → Save More → Protectin' → Thank You) with a live summary
- `business.html` — Takin' Care of Business, Homebuilder and Indexed plans
- `contact-us.html` — contact form (pre-fills when coming from a plan's Sign Up button)

Shared header, footer and plan data live in `js/main.js`; all styles are in `css/style.css`.

## Admin tools (`admin/`)
Open `admin/login.html` (also linked as **Employee Login** in the site footer). Sign-in is a demo: pick a sample user such as `admin@example.com` with any password.

| App | Screens |
| --- | --- |
| **Corral** (customer service) | Customer search with status/exception filters, account detail (service, billing, payments, notes, products), ESIID lookup, create order (resi & biz), renew/change plan, orders report, exception queues |
| **Lando** (CMS) | Page tree & page editor, templates, content blocks with live preview, plans list/editor, plan groups, rate search with EFL average price, bulk rate editor, TDSP fees, markets |
| **Astro** (pricing) | Term discount grid by TDSP region, ETF by term, BYOP products |
| **Sheriff** (settings) | Users, role permissions, API integrations, crons, reference data tables (deposit thresholds, tax rates, blackout days, promos…) |

The admin is front-end only. Edits are saved in the browser's localStorage (use **Reset data** in the sidebar to start over). Every customer and user is fictional sample data in `admin/js/data.js`. API keys are deliberately not stored anywhere in this repo; keep them in server environment variables. To make it real, replace the `persist()` calls in `admin/js/admin.js` with API requests and put the admin behind real authentication.

## Run locally
```sh
python3 -m http.server 8000
# open http://localhost:8000
```

## Before going live
- Plan names, terms and ETFs come from the active plan list; **rates are illustrative** — replace `PLANS` in `js/main.js` with live EFL pricing.
- The phone number (`1-800-555-0100`) and PUCT cert number are placeholders.
- The zip → TDSP lookup is a simplified range table; wire it to the real ESIID/market lookup.
- The contact and zip forms don't submit to a backend yet.
