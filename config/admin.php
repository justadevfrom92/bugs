<?php

/*
|--------------------------------------------------------------------------
| Admin tools configuration
|--------------------------------------------------------------------------
| One place that describes every admin app. The layout, launcher, app switcher
| and permission checks all read from here, so adding a menu item or a whole
| app is a config change, not a copy of another app's files.
|
| Menu items: [label, route name, optional badge key]
*/

$integration = fn (string $name, string $purpose, array $env) => [
    'name' => $name,
    'purpose' => $purpose,
    // Values are read once here (so `php artisan config:cache` works) and are
    // only ever checked for presence — the admin never displays them.
    'env' => collect($env)->mapWithKeys(fn ($key) => [$key => env($key)])->all(),
];

return [

    'apps' => [
        'corral' => [
            'name' => 'Corral',
            'desc' => 'Customer service: search accounts, create orders, work exception queues.',
            'icon' => 'users',
            'home' => 'corral.customers.index',
            'menu' => [
                'Customers' => [
                    ['Customer Search', 'corral.customers.index'],
                    ['ESIID Lookup', 'corral.esiid'],
                    ['Create Order', 'corral.orders.create'],
                    ['Create Order - Biz', 'corral.orders.create-biz'],
                    ['SMS', 'corral.sms', 'sms'],
                    ['Web Messages', 'corral.messages.index', 'messages'],
                ],
                'Reports' => [
                    ['Orders', 'corral.reports.orders'],
                    ['Notes', 'corral.reports.notes'],
                    ['Phonecalls', 'corral.reports.phonecalls'],
                ],
                'ERCOT' => [
                    ['ERCOT Search', 'corral.ercot'],
                ],
                'Exceptions' => 'queues', // one menu item per queue below, plus the overview
            ],
        ],
        'lando' => [
            'name' => 'Lando',
            'desc' => 'Website CMS: pages, content blocks, plans, rates and TDSP fees.',
            'icon' => 'layout',
            'home' => 'lando.pages.index',
            'menu' => [
                'Pages' => [
                    ['List Pages', 'lando.pages.index'],
                    ['Add a Page', 'lando.pages.create'],
                    ['Page Templates', 'lando.templates.index'],
                    ['Content Blocks', 'lando.blocks.index'],
                    ['Add a Content Block', 'lando.blocks.create'],
                ],
                'Sites' => [
                    ['Sites', 'lando.sites.index'],
                    ['New Site', 'lando.sites.create'],
                ],
                'Markets' => [
                    ['View Markets', 'lando.markets.index'],
                    ['Add a Market', 'lando.markets.create'],
                ],
                'Plans' => [
                    ['View Plans', 'lando.plans.index'],
                    ['Add a Plan', 'lando.plans.create'],
                    ['Plan Groups', 'lando.groups.index'],
                    ['Add a Group', 'lando.groups.create'],
                ],
                'Rates' => [
                    ['View Rates', 'lando.rates.index'],
                    ['Update Rates', 'lando.rates.edit'],
                    ['TDSP Fees', 'lando.fees.index'],
                ],
            ],
        ],
        'astro' => [
            'name' => 'Astro',
            'desc' => 'Pricing: term discounts, ETFs and Build Your Own Plan products.',
            'icon' => 'chart',
            'home' => 'astro.terms.edit',
            'menu' => [
                'Modifiers' => [
                    ['Term', 'astro.terms.edit'],
                    ['ETF', 'astro.etfs.edit'],
                    ['Products', 'astro.products.index'],
                ],
                // Same pages, plans and groups as Lando (one copy of the data and screens, Astro's own URLs)
                'Website' => [
                    ['Pages', 'astro.pages.index'],
                    ['Groups', 'astro.groups.index'],
                ],
                'Plans' => [
                    ['Plans', 'astro.plans.index'],
                    ['New Plan', 'astro.plans.create'],
                ],
                'Upload' => [
                    ['BYOP Discounts', 'astro.byop.upload'],
                ],
            ],
        ],
        'sheriff' => [
            'name' => 'Sheriff',
            'desc' => 'Settings: users, roles, API integrations, scheduled jobs and reference data.',
            'icon' => 'star',
            'home' => 'sheriff.users.index',
            'menu' => [
                'Access' => [
                    ['Users', 'sheriff.users.index'],
                    ['Roles', 'sheriff.roles.index'],
                ],
                'Config' => [
                    ['Crons', 'sheriff.jobs.index', 'failed_jobs'],
                    ['Update Sitemap', 'sheriff.sitemap'],
                ],
                'APIs' => 'integrations',        // one page per integration below, plus the overview
                'Data' => 'reference_tables',    // one page per table below, plus TDSP Fees
            ],
        ],
    ],

    // Extra permissions a role can hold besides app access
    'permissions' => [
        'delete' => 'Permanent deletes',
        'refunds' => 'Refunds & reversals',
    ],

    'customer_statuses' => [
        'Submitted' => 'info',
        'Pending - Credit' => 'warn',
        'Pending - Deposit Due' => 'warn',
        'Pending - No Deposit Due' => 'warn',
        'Pending - Utility Not Answered' => 'warn',
        'Good - On Flow' => 'ok',
        'Rejected - By Utility' => 'bad',
        'Dropped - Churned' => 'bad',
        'Pending - Disconnect' => 'warn',
        'Disconnected' => 'bad',
        'Moved Out' => 'bad',
        'Cancelled' => 'bad',
    ],

    'exceptions' => [
        'Not sent to UtiliBill', 'Not sent to Utility', 'No ESIID', 'Non-Resi Meter',
        'Permit Required', 'Switch Hold', 'Possible Duplicate', 'Other Exception',
    ],

    'queues' => [
        'ercot-exceptions' => ['ERCOT Exceptions', 'Enrollment transactions rejected or stuck at ERCOT'],
        'duplicate-ips' => ['Duplicate IPs', 'Orders placed from the same IP within 24 hours'],
        'duplicated-payments' => ['Duplicated Payments', 'Same card and amount charged twice'],
        'unapplied-deposits' => ['Unapplied Deposits', 'Deposits received but not applied to an account'],
        'unapplied-payments' => ['Unapplied Payments', 'Payments not yet posted to the billing system'],
        'unapplied-credits' => ['Unapplied Credits', 'Bill credits and promos waiting to post'],
        'unapplied-debits' => ['Unapplied Debits', 'Manual debits waiting to post'],
        'unapplied-plan-changes' => ['Unapplied Plan Changes', 'Renewals and plan switches not yet sent'],
        'unapplied-transfers' => ['Unapplied Transfers', 'Move-in / move-out transfers waiting'],
        'unbilled-orders' => ['Unbilled Orders', 'On-flow accounts with no first bill'],
        'unprocessed-autopays' => ['Unprocessed Autopays', 'AutoPay drafts that did not run'],
        'unprocessed-bills' => ['Unprocessed Bills', 'Bills received but not generated'],
        'unprocessed-orders' => ['Unprocessed Orders', 'Orders waiting to be sent to the utility'],
    ],

    // Sheriff → Data. Rows are stored in the reference_rows table.
    'reference_tables' => [
        'deposit-thresholds' => ['Deposit Thresholds', ['Credit Score From', 'Credit Score To', 'Deposit']],
        'max-deposits' => ['Max Deposits', ['Customer Type', 'Max Deposit']],
        'blackout-days' => ['Blackout Days', ['Date', 'Reason']],
        'tax-rates' => ['Tax Rates', ['City', 'Sales Tax', 'Gross Receipts', 'PUC Assessment']],
        'interest-rates' => ['Interest Rates', ['Year', 'Deposit Interest']],
        'bill-credits' => ['Bill Credits & Promos', ['Code', 'Description', 'Amount']],
        'fraud-indicators' => ['Fraud Indicators', ['Rule', 'Action']],
        'note-dispositions' => ['CIS Note Dispositions', ['Code', 'Label']],
        'charge-codes' => ['Charge Codes', ['Code', 'Description']],
        'ips' => ['IPs', ['IP Address', 'Action', 'Note']],
        'monthly-drawing' => ['Monthly Drawing', ['Month', 'Prize', 'Winner Account', 'Drawn On']],
        'rate-exports' => ['Rate Exports', ['Email', 'Customer Type', 'Markets', 'Frequency']],
    ],

    // Scheduled jobs (registered in routes/console.php). Run them on Linux with:
    //   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
    'jobs' => [
        'et:send-orders' => ['Send orders to utility', '*/15 * * * *'],
        'et:ercot-responses' => ['Pull ERCOT 814 responses', '*/10 * * * *'],
        'et:autopay' => ['Process AutoPay drafts', '0 6 * * *'],
        'et:sync-payments' => ['Sync payments to Utilibill', '*/30 * * * *'],
        'et:welcome-packets' => ['Generate welcome packets', '0 * * * *'],
        'et:rewards-stars' => ['Rangler Rewards stars', '0 2 * * *'],
        'et:export-rates' => ['Export rates file', '30 5 * * *'],
        'et:sitemap' => ['Rebuild sitemap', '0 3 * * 0'],
    ],

    // Third-party integrations. Put the values in .env — never in the database or repo.
    'integrations' => [
        'aig' => $integration('AIG', 'Home protection products', ['AIG_ENDPOINT', 'AIG_PARTNER_ID', 'AIG_API_KEY']),
        'amazon' => $integration('Amazon', 'S3 document storage & SES email', ['AWS_DEFAULT_REGION', 'AWS_BUCKET', 'AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY']),
        'apple' => $integration('Apple', 'Push notifications (APNs)', ['APNS_TEAM_ID', 'APNS_KEY_ID', 'APNS_BUNDLE_ID', 'APNS_PRIVATE_KEY']),
        'authnet' => $integration('Auth.net', 'Card processing (legacy)', ['AUTHNET_LOGIN_ID', 'AUTHNET_TRANSACTION_KEY']),
        'ibm' => $integration('IBM', 'Weather data for forecasting', ['IBM_WEATHER_ENDPOINT', 'IBM_WEATHER_API_KEY']),
        'innowatts' => $integration('Innowatts', 'Load forecasting', ['INNOWATTS_ENDPOINT', 'INNOWATTS_CLIENT_ID', 'INNOWATTS_CLIENT_SECRET']),
        'experian' => $integration('Experian', 'Credit checks', ['EXPERIAN_ENDPOINT', 'EXPERIAN_SUBSCRIBER_CODE', 'EXPERIAN_USERNAME', 'EXPERIAN_PASSWORD']),
        'plaid' => $integration('Plaid', 'Bank account linking', ['PLAID_ENV', 'PLAID_CLIENT_ID', 'PLAID_SECRET']),
        'quickbooks' => $integration('QuickBooks', 'Accounting journal entries', ['QUICKBOOKS_REALM_ID', 'QUICKBOOKS_CLIENT_ID', 'QUICKBOOKS_CLIENT_SECRET']),
        'salesforce' => $integration('SalesForce', 'Marketing Cloud email', ['SALESFORCE_SUBDOMAIN', 'SALESFORCE_CLIENT_ID', 'SALESFORCE_CLIENT_SECRET']),
        'stripe' => $integration('Stripe', 'Card & ACH payments', ['STRIPE_KEY', 'STRIPE_SECRET', 'STRIPE_WEBHOOK_SECRET']),
        'utilibill' => $integration('Utilibill', 'Billing system (UB)', ['UTILIBILL_ENDPOINT', 'UTILIBILL_USERNAME', 'UTILIBILL_PASSWORD']),
        'sms' => $integration('Twilio', 'Text messages (SMS)', ['TWILIO_SID', 'TWILIO_TOKEN', 'TWILIO_FROM']),
    ],

];
