<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\MyAccount;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SitePageController;
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
Route::get('/site/components', [SitePageController::class, 'components']);

// My Account: the customer portal (guard "customer", separate from admin sign-in)
Route::prefix('myaccount')->name('myaccount.')->group(function () {
    Route::get('quickpay', [MyAccount\PortalController::class, 'quickpay'])->middleware('throttle:30,1')->name('quickpay');
    Route::middleware('guest:customer')->group(function () {
        Route::get('login', [MyAccount\AuthController::class, 'show'])->name('login');
        Route::post('login', [MyAccount\AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');
        Route::get('create-account', [MyAccount\AuthController::class, 'registerForm'])->name('register');
        Route::post('create-account', [MyAccount\AuthController::class, 'register'])->middleware('throttle:10,1')->name('register.store');
        Route::get('forgot-{what}', [MyAccount\AuthController::class, 'forgotForm'])->whereIn('what', ['password', 'username'])->name('forgot');
        Route::post('forgot-{what}', [MyAccount\AuthController::class, 'forgot'])->whereIn('what', ['password', 'username'])->middleware('throttle:5,1')->name('forgot.send');
        Route::get('reset-password/{token}', [MyAccount\AuthController::class, 'resetForm'])->name('reset');
        Route::post('reset-password', [MyAccount\AuthController::class, 'reset'])->middleware('throttle:10,1')->name('reset.store');
    });
    Route::middleware('auth:customer')->group(function () {
        Route::get('/', fn () => redirect()->route('myaccount.dashboard'));
        Route::post('logout', [MyAccount\AuthController::class, 'logout'])->name('logout');
        Route::get('dashboard', [MyAccount\PortalController::class, 'dashboard'])->name('dashboard');
        Route::get('bill-and-payments/view-bills', [MyAccount\PortalController::class, 'bills'])->name('bills');
        Route::get('bill-and-payments/pay-bill', [MyAccount\PortalController::class, 'payForm'])->name('pay');
        Route::post('bill-and-payments/pay-bill', [MyAccount\PortalController::class, 'pay'])->middleware('throttle:10,1')->name('pay.store');
        Route::get('bill-and-payments/payment-methods', [MyAccount\PortalController::class, 'methods'])->name('methods');
        Route::delete('payment-methods/{method}', [MyAccount\PortalController::class, 'removeMethod'])->name('methods.remove');
        Route::post('payment-methods/{method}/default', [MyAccount\PortalController::class, 'defaultMethod'])->name('methods.default');
        Route::get('energy-insights', [MyAccount\PortalController::class, 'insights'])->name('insights');
        Route::get('enroll/{product}', [MyAccount\PortalController::class, 'product'])->name('product');
        Route::post('enroll/{product}', [MyAccount\PortalController::class, 'toggleProduct'])->name('product.toggle');
        Route::get('profile-and-preferences', [MyAccount\PortalController::class, 'profile'])->name('profile');
        Route::put('profile-and-preferences', [MyAccount\PortalController::class, 'updateProfile'])->name('profile.update');
        Route::put('change-password', [MyAccount\PortalController::class, 'password'])->name('password');
        Route::post('authorized-users', [MyAccount\PortalController::class, 'authorizedUser'])->name('authorized.store');
        Route::delete('authorized-users/{index}', [MyAccount\PortalController::class, 'removeAuthorizedUser'])->whereNumber('index')->name('authorized.remove');
        Route::post('link-accounts', [MyAccount\PortalController::class, 'linkAccount'])->name('link');
        Route::get('plan-and-services/current-plan', [MyAccount\PortalController::class, 'plan'])->name('plan');
        Route::post('plan-and-services/renew-plan', [MyAccount\PortalController::class, 'renew'])->name('renew');
        Route::get('plan-and-services/transfer-service', [MyAccount\PortalController::class, 'transferForm'])->name('transfer');
        Route::post('plan-and-services/transfer-service', [MyAccount\PortalController::class, 'transfer'])->name('transfer.store');
        Route::get('rewards', [MyAccount\PortalController::class, 'rewards'])->name('rewards');
        Route::post('rewards', [MyAccount\PortalController::class, 'redeem'])->name('rewards.redeem');
        Route::get('refer-a-friend', [MyAccount\PortalController::class, 'refer'])->name('refer');
        Route::get('message-center', [MyAccount\PortalController::class, 'messages'])->name('messages');
    });
});

// Website sign-up (the original /checkout flow)
Route::prefix('checkout')->name('checkout')->group(function () {
    Route::get('/', [SignupController::class, 'start']);
    Route::post('address', [SignupController::class, 'address'])->name('.address');
    Route::get('plan', [SignupController::class, 'plans'])->name('.plan');
    Route::post('plan', [SignupController::class, 'plan'])->name('.plan.store');
    Route::get('about', [SignupController::class, 'aboutForm'])->name('.about');
    Route::post('about', [SignupController::class, 'about'])->name('.about.store');
    Route::get('review', [SignupController::class, 'review'])->name('.review');
    Route::post('review', [SignupController::class, 'submit'])->middleware('throttle:10,1')->name('.submit');
    Route::get('accepted', [SignupController::class, 'accepted'])->name('.accepted');
    Route::post('save', [SignupController::class, 'save'])->middleware('throttle:10,1')->name('.save');
    Route::get('saved', [SignupController::class, 'saved'])->name('.saved');
    Route::get('resume/{token}', [SignupController::class, 'resume'])->name('.resume');
    Route::get('deposit', [SignupController::class, 'deposit'])->middleware('throttle:30,1')->name('.deposit');
    Route::post('cancel', [SignupController::class, 'cancel'])->middleware('throttle:10,1')->name('.cancel');
    Route::get('{page}', [SignupController::class, 'page'])->whereIn('page', ['alternatives', 'frozen', 'start-call', 'cancel', 'error'])->name('.page');
});

/*
|--------------------------------------------------------------------------
| Admin tools
|--------------------------------------------------------------------------
| Menus for each app are in config/admin.php. Each app group checks that the
| user's role includes that app (middleware 'app:<key>').
*/
Route::prefix('admin')->group(function () {
    // Admin sign-in is the employee login (guard "web"); a My Account customer login never opens these pages
    Route::middleware('guest:web')->group(function () {
        Route::get('login', [Admin\AuthController::class, 'show'])->name('admin.login');
        Route::post('login', [Admin\AuthController::class, 'login'])->middleware('throttle:10,1')->name('admin.login.attempt');
    });

    Route::middleware('auth:web')->group(function () {
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
            Route::get('reports/download/{run}', [Admin\Corral\ReportController::class, 'download'])->name('reports.download');
            Route::get('reports/notes', [Admin\Corral\ReportController::class, 'notes'])->name('reports.notes');
            Route::get('reports/phonecalls', [Admin\Corral\ReportController::class, 'phonecalls'])->name('reports.phonecalls');
            Route::get('sms', [Admin\Corral\SmsController::class, 'index'])->name('sms');
            Route::post('sms', [Admin\Corral\SmsController::class, 'send'])->middleware('throttle:30,1')->name('sms.send');
            Route::get('ercot', [Admin\Corral\ErcotController::class, 'index'])->name('ercot');
            Route::get('queues', [Admin\Corral\QueueController::class, 'index'])->name('queues.index');
            Route::get('queues/{queue}', [Admin\Corral\QueueController::class, 'show'])->name('queues.show');
            Route::post('work-items/{item}/resolve', [Admin\Corral\QueueController::class, 'resolve'])->name('queues.resolve');
            Route::get('messages', [Admin\Corral\MessageController::class, 'index'])->name('messages.index');
            Route::post('messages/{message}/handled', [Admin\Corral\MessageController::class, 'handled'])->name('messages.handled');
        });

        // Website pages, plan groups and plans: Lando's screens, which Astro's menu also has.
        // Registered under each app's own URL and route names so neither links into the other.
        $websiteScreens = function () {
            Route::resource('pages', Admin\Lando\PageController::class)->except('show');
            Route::post('pages/{page}/components', [Admin\Lando\PageController::class, 'addComponent'])->name('pages.components.store');
            Route::post('page-components/{component}/move', [Admin\Lando\PageController::class, 'moveComponent'])->name('pages.components.move');
            Route::delete('page-components/{component}', [Admin\Lando\PageController::class, 'removeComponent'])->name('pages.components.destroy');
            Route::resource('plans', Admin\Lando\PlanController::class)->except(['show', 'destroy']);
            Route::resource('groups', Admin\Lando\PlanGroupController::class)->except(['show', 'destroy']);
            Route::post('groups/{group}/plans', [Admin\Lando\PlanGroupController::class, 'attach'])->name('groups.attach');
            Route::delete('groups/{group}/plans/{plan}', [Admin\Lando\PlanGroupController::class, 'detach'])->name('groups.detach');
        };

        // Lando — website CMS, plans and rates
        Route::prefix('lando')->name('lando.')->middleware('app:lando')->group(function () use ($websiteScreens) {
            Route::get('/', fn () => redirect()->route('lando.pages.index'));
            $websiteScreens();
            Route::post('sitemap', [Admin\Lando\PageController::class, 'sitemap'])->name('pages.sitemap');
            Route::resource('sites', Admin\Lando\SiteController::class)->except(['show', 'destroy']);
            Route::resource('templates', Admin\Lando\TemplateController::class)->except(['show', 'destroy']);
            Route::resource('markets', Admin\Lando\MarketController::class)->except(['show', 'destroy']);
            Route::resource('blocks', Admin\Lando\BlockController::class)->except(['show']);
            Route::post('block-categories', [Admin\Lando\BlockController::class, 'addCategory'])->name('blocks.categories.store');
            Route::get('rates', [Admin\Lando\RateController::class, 'index'])->name('rates.index');
            Route::get('rates/edit', [Admin\Lando\RateController::class, 'edit'])->name('rates.edit');
            Route::put('rates', [Admin\Lando\RateController::class, 'update'])->name('rates.update');
            Route::get('fees', [Admin\Lando\FeeController::class, 'index'])->name('fees.index');
            Route::put('fees', [Admin\Lando\FeeController::class, 'update'])->name('fees.update');
        });

        // Astro — pricing modifiers
        Route::prefix('astro')->name('astro.')->middleware('app:astro')->group(function () use ($websiteScreens) {
            Route::get('/', fn () => redirect()->route('astro.terms.edit'));
            Route::get('terms', [Admin\Astro\PricingController::class, 'terms'])->name('terms.edit');
            Route::put('terms', [Admin\Astro\PricingController::class, 'updateTerms'])->name('terms.update');
            Route::get('etfs', [Admin\Astro\PricingController::class, 'etfs'])->name('etfs.edit');
            Route::put('etfs', [Admin\Astro\PricingController::class, 'updateEtfs'])->name('etfs.update');
            Route::get('products', [Admin\Astro\PricingController::class, 'products'])->name('products.index');
            Route::put('products', [Admin\Astro\PricingController::class, 'updateProducts'])->name('products.update');
            Route::get('modifiers/products', [Admin\Astro\PricingController::class, 'modifierProducts'])->name('modifiers.products');
            Route::put('modifiers/products', [Admin\Astro\PricingController::class, 'updateModifierProducts'])->name('modifiers.products.update');
            $websiteScreens(); // Website → Pages, Groups and Plans → Plans, New Plan, at /admin/astro/…
            Route::get('uploads/byop', [Admin\Astro\PricingController::class, 'byopUpload'])->name('byop.upload');
            Route::post('uploads/byop', [Admin\Astro\PricingController::class, 'byopImport'])->name('byop.import');
        });

        // Walker — reporting: report runs by status, report library, uploaded reports
        Route::prefix('walker')->name('walker.')->middleware('app:walker')->group(function () {
            Route::get('/', [Admin\Walker\ReportController::class, 'home'])->name('home');
            Route::get('runs/{run}', [Admin\Walker\ReportController::class, 'show'])->name('runs.show');
            Route::post('runs/{run}/rerun', [Admin\Walker\ReportController::class, 'rerun'])->name('runs.rerun');
            Route::get('runs/{run}/download', [Admin\Walker\ReportController::class, 'download'])->name('runs.download');
            Route::get('reports', [Admin\Walker\ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/{key}', [Admin\Walker\ReportController::class, 'report'])->name('reports.show');
            Route::post('reports/{key}', [Admin\Walker\ReportController::class, 'run'])->name('reports.run');
            Route::get('uploads', [Admin\Walker\UploadController::class, 'index'])->name('uploads.index');
            Route::post('uploads', [Admin\Walker\UploadController::class, 'store'])->name('uploads.store');
            Route::get('uploads/{upload}', [Admin\Walker\UploadController::class, 'download'])->name('uploads.download');
            Route::delete('uploads/{upload}', [Admin\Walker\UploadController::class, 'destroy'])->name('uploads.destroy');
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
            Route::get('integrations/{integration}', [Admin\Sheriff\SystemController::class, 'integration'])->name('integrations.show');
            Route::get('sitemap', [Admin\Sheriff\SystemController::class, 'sitemap'])->name('sitemap');
            Route::post('sitemap', [Admin\Lando\PageController::class, 'sitemap'])->name('sitemap.run');
            Route::get('fees', [Admin\Lando\FeeController::class, 'index'])->name('fees.index');
            Route::put('fees', [Admin\Lando\FeeController::class, 'update'])->name('fees.update');
            Route::get('jobs', [Admin\Sheriff\SystemController::class, 'jobs'])->name('jobs.index');
            Route::post('jobs/run', [Admin\Sheriff\SystemController::class, 'runJob'])->name('jobs.run');
            Route::get('data/{table}', [Admin\Sheriff\SystemController::class, 'table'])->name('data.show');
            Route::put('data/{table}', [Admin\Sheriff\SystemController::class, 'updateTable'])->name('data.update');
        });

        // A custom app's home page, e.g. /admin/marketing (built-in app routes above take priority)
        Route::get('{appKey}', [Admin\AppController::class, 'show'])->where('appKey', '[a-z0-9][a-z0-9-]*')->name('custom.show');
    });
});

/*
|--------------------------------------------------------------------------
| Website pages built in Lando
|--------------------------------------------------------------------------
| Anything not matched above (and not a file in public/) is looked up as a
| page on the site for this host name. Kept last so it never shadows a route.
*/
Route::get('amp/{path}', [SitePageController::class, 'amp'])->where('path', '.*')->name('site.amp');
Route::get('{path}', [SitePageController::class, 'show'])->where('path', '.*')->name('site.page');
