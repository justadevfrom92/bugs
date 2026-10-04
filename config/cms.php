<?php

/*
|--------------------------------------------------------------------------
| Lando website settings
|--------------------------------------------------------------------------
| Where page components (content blocks) can be placed, and which built-in
| pages are files in public/ rather than built from page content.
*/

return [

    // zone => label. Every page template has these four places.
    'zones' => [
        'top' => 'Top of page (under the banner)',
        'main' => 'Main content (after the page content)',
        'sidebar' => 'Sidebar (beside the page content)',
        'bottom' => 'Bottom of page (above the footer)',
    ],

    // Built-in pages: page path => file in public/. Their layout is in the file;
    // Lando adds components to their top and bottom zones.
    'files' => [
        '/' => 'index.html',
        'plans' => 'plans.html',
        'byop' => 'build-your-own-plan.html',
        'business' => 'business.html',
        'contact-us' => 'contact-us.html',
    ],

    // Paths a website page can never use (the admin, Laravel's own endpoints)
    'reserved' => ['admin', 'site', 'shared', 'admin-assets', 'css', 'js', 'contact', 'up', 'storage'],

];
