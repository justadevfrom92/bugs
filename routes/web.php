<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Website
|--------------------------------------------------------------------------
| The website's pages are static files in public/. Laravel only supplies the
| data they load and handles the contact form.
*/
Route::get('/', [SiteController::class, 'home']);
Route::get('/shared/config/brand.js', [SiteController::class, 'brand']);
Route::get('/shared/config/catalog.js', [SiteController::class, 'catalog']);
Route::get('/site/session', [SiteController::class, 'session']);
Route::post('/contact', [SiteController::class, 'contact'])->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| Admin tools
|--------------------------------------------------------------------------
| Menus for each app are in config/admin.php. Each app group checks that the
| user's role includes that app (middleware 'app:<key>').
*/
Route::prefix('admin')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Admin\AuthController::class, 'show'])->name('admin.login');
        Route::post('login', [Admin\AuthController::class, 'login'])->middleware('throttle:10,1')->name('admin.login.attempt');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'logout'])->name('admin.logout');
        Route::get('/', Admin\LauncherController::class)->name('admin.launcher');

        // Admin apps created from the launcher's "New Admin App" button
        Route::get('apps/create', [Admin\AppController::class, 'create'])->name('apps.create');
        Route::post('apps', [Admin\AppController::class, 'store'])->name('apps.store');
        Route::get('apps/{app}/edit', [Admin\AppController::class, 'edit'])->name('apps.edit');
        Route::put('apps/{app}', [Admin\AppController::class, 'update'])->name('apps.update');
        Route::delete('apps/{app}', [Admin\AppController::class, 'destroy'])->name('apps.destroy');

        // Corral — customer service
        Route::prefix('corral')->name('corral.')->middleware('app:corral')->group(function () {
            Route::get('/', [Admin\Corral\CustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{customer}', [Admin\Corral\CustomerController::class, 'show'])->name('customers.show');
            Route::patch('customers/{customer}/status', [Admin\Corral\CustomerController::class, 'updateStatus'])->name('customers.status');
            Route::post('customers/{customer}/bookmark', [Admin\Corral\CustomerController::class, 'bookmark'])->name('customers.bookmark');
            Route::post('customers/{customer}/notes', [Admin\Corral\CustomerController::class, 'addNote'])->name('customers.notes');
            Route::post('payments/{payment}/reverse', [Admin\Corral\CustomerController::class, 'reversePayment'])->name('payments.reverse');
            Route::get('history/{item}', [Admin\HistoryController::class, 'show'])->name('history.show');
            Route::get('customers/{customer}/ticket', [Admin\HistoryController::class, 'ticket'])->name('customers.ticket');
            Route::get('customers/{customer}/logs/{log}', [Admin\HistoryController::class, 'log'])->name('customers.log');

            // Account page sections
            Route::get('customers/{customer}/actions/{action}', [Admin\Corral\AccountController::class, 'actionForm'])->name('customers.action');
            Route::post('customers/{customer}/actions/{action}', [Admin\Corral\AccountController::class, 'runAction'])->name('customers.action.run');
            Route::post('customers/{customer}/flags', [Admin\Corral\AccountController::class, 'addFlag'])->name('customers.flags.add');
            Route::delete('flags/{flag}', [Admin\Corral\AccountController::class, 'removeFlag'])->name('flags.remove');
            Route::post('customers/{customer}/products', [Admin\Corral\AccountController::class, 'addProduct'])->name('customers.products.add');
            Route::delete('products/{product}', [Admin\Corral\AccountController::class, 'removeProduct'])->name('products.remove');
            Route::delete('ledger/{entry}', [Admin\Corral\AccountController::class, 'deleteLedger'])->name('ledger.delete');
            Route::post('payment-methods/{method}/autopay', [Admin\Corral\AccountController::class, 'setAutopay'])->name('methods.autopay');
            Route::delete('payment-methods/{method}', [Admin\Corral\AccountController::class, 'removeMethod'])->name('methods.remove');
            Route::post('customers/{customer}/files', [Admin\Corral\AccountController::class, 'uploadFile'])->name('customers.files.upload');
            Route::get('files/{file}', [Admin\Corral\AccountController::class, 'downloadFile'])->name('files.download');
            Route::patch('customers/{customer}/marketing', [Admin\Corral\AccountController::class, 'updateMarketing'])->name('customers.marketing');
            Route::get('customers/{customer}/stars', [Admin\Corral\AccountController::class, 'stars'])->name('customers.stars');
            Route::post('customers/{customer}/stars', [Admin\Corral\AccountController::class, 'redeem'])->name('customers.stars.redeem');
            Route::post('customers/{customer}/stars/recalculate', [Admin\Corral\AccountController::class, 'recalculateStars'])->name('customers.stars.recalculate');
            Route::post('customers/{customer}/queues', [Admin\Corral\AccountController::class, 'addQueue'])->name('customers.queues.add');
            Route::patch('queue-logs/{log}', [Admin\Corral\AccountController::class, 'moveQueue'])->name('queues.move');
            Route::post('plan-terms/{term}', [Admin\Corral\AccountController::class, 'planTermAction'])->name('plan-terms.action');
            Route::patch('addresses/{address}', [Admin\Corral\AccountController::class, 'updateAddress'])->name('addresses.update');
            Route::post('ercot/{tx}', [Admin\Corral\AccountController::class, 'ercotAction'])->name('ercot.action');
            Route::get('customers/{customer}/contact-log', [Admin\Corral\AccountController::class, 'contactLog'])->name('customers.contact-log');
            Route::post('customers/{customer}/contact-log/marketing', [Admin\Corral\AccountController::class, 'toggleMarketing'])->name('customers.marketing-toggle');
            Route::get('customers/{customer}/api-log', [Admin\Corral\AccountController::class, 'apiLog'])->name('customers.api-log');
            Route::get('esiid', [Admin\Corral\EsiidController::class, 'index'])->name('esiid');
            Route::get('orders/create', [Admin\Corral\OrderController::class, 'create'])->name('orders.create');
            Route::get('orders/create-biz', [Admin\Corral\OrderController::class, 'createBiz'])->name('orders.create-biz');
            Route::post('orders', [Admin\Corral\OrderController::class, 'store'])->name('orders.store');
            Route::get('reports/orders', [Admin\Corral\ReportController::class, 'orders'])->name('reports.orders');
            Route::get('queues', [Admin\Corral\QueueController::class, 'index'])->name('queues.index');
            Route::get('queues/{queue}', [Admin\Corral\QueueController::class, 'show'])->name('queues.show');
            Route::post('work-items/{item}/resolve', [Admin\Corral\QueueController::class, 'resolve'])->name('queues.resolve');
            Route::get('messages', [Admin\Corral\MessageController::class, 'index'])->name('messages.index');
            Route::post('messages/{message}/handled', [Admin\Corral\MessageController::class, 'handled'])->name('messages.handled');
        });

        // Lando — website CMS, plans and rates
        Route::prefix('lando')->name('lando.')->middleware('app:lando')->group(function () {
            Route::get('/', fn () => redirect()->route('lando.pages.index'));
            Route::resource('pages', Admin\Lando\PageController::class)->except('show');
            Route::post('sitemap', [Admin\Lando\PageController::class, 'sitemap'])->name('pages.sitemap');
            Route::get('templates', [Admin\Lando\PageController::class, 'templates'])->name('templates.index');
            Route::resource('blocks', Admin\Lando\BlockController::class)->except(['show', 'destroy']);
            Route::resource('plans', Admin\Lando\PlanController::class)->except(['show', 'destroy']);
            Route::get('groups', [Admin\Lando\PlanGroupController::class, 'index'])->name('groups.index');
            Route::post('groups/{group}/plans', [Admin\Lando\PlanGroupController::class, 'attach'])->name('groups.attach');
            Route::delete('groups/{group}/plans/{plan}', [Admin\Lando\PlanGroupController::class, 'detach'])->name('groups.detach');
            Route::get('rates', [Admin\Lando\RateController::class, 'index'])->name('rates.index');
            Route::get('rates/edit', [Admin\Lando\RateController::class, 'edit'])->name('rates.edit');
            Route::put('rates', [Admin\Lando\RateController::class, 'update'])->name('rates.update');
            Route::get('fees', [Admin\Lando\FeeController::class, 'index'])->name('fees.index');
            Route::put('fees', [Admin\Lando\FeeController::class, 'update'])->name('fees.update');
            Route::get('markets', [Admin\Lando\FeeController::class, 'markets'])->name('markets.index');
        });

        // Astro — pricing modifiers
        Route::prefix('astro')->name('astro.')->middleware('app:astro')->group(function () {
            Route::get('/', fn () => redirect()->route('astro.terms.edit'));
            Route::get('terms', [Admin\Astro\PricingController::class, 'terms'])->name('terms.edit');
            Route::put('terms', [Admin\Astro\PricingController::class, 'updateTerms'])->name('terms.update');
            Route::get('etfs', [Admin\Astro\PricingController::class, 'etfs'])->name('etfs.edit');
            Route::put('etfs', [Admin\Astro\PricingController::class, 'updateEtfs'])->name('etfs.update');
            Route::get('products', [Admin\Astro\PricingController::class, 'products'])->name('products.index');
            Route::put('products', [Admin\Astro\PricingController::class, 'updateProducts'])->name('products.update');
        });

        // Sheriff — users, roles, integrations, jobs, reference data
        Route::prefix('sheriff')->name('sheriff.')->middleware('app:sheriff')->group(function () {
            Route::get('/', fn () => redirect()->route('sheriff.users.index'));
            Route::get('users', [Admin\Sheriff\UserController::class, 'index'])->name('users.index');
            Route::post('users', [Admin\Sheriff\UserController::class, 'store'])->name('users.store');
            Route::patch('users/{user}', [Admin\Sheriff\UserController::class, 'update'])->name('users.update');
            Route::get('users/{user}/history', [Admin\HistoryController::class, 'user'])->name('users.history');
            Route::get('history/{item}', [Admin\HistoryController::class, 'show'])->name('history.show');
            Route::get('roles', [Admin\Sheriff\RoleController::class, 'index'])->name('roles.index');
            Route::put('roles', [Admin\Sheriff\RoleController::class, 'update'])->name('roles.update');
            Route::get('integrations', [Admin\Sheriff\SystemController::class, 'integrations'])->name('integrations.index');
            Route::get('jobs', [Admin\Sheriff\SystemController::class, 'jobs'])->name('jobs.index');
            Route::post('jobs/run', [Admin\Sheriff\SystemController::class, 'runJob'])->name('jobs.run');
            Route::get('data/{table}', [Admin\Sheriff\SystemController::class, 'table'])->name('data.show');
            Route::put('data/{table}', [Admin\Sheriff\SystemController::class, 'updateTable'])->name('data.update');
        });

        // A custom app's home page, e.g. /admin/marketing (built-in app routes above take priority)
        Route::get('{appKey}', [Admin\AppController::class, 'show'])->where('appKey', '[a-z0-9][a-z0-9-]*')->name('custom.show');
    });
});
