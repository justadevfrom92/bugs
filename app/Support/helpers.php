<?php

use App\Support\AdminMenu;
use Illuminate\Support\Facades\Route;

if (! function_exists('app_route')) {
    /**
     * A route in the admin app the current page belongs to: on an Astro page,
     * app_route('pages.edit', $page) is astro.pages.edit; on a Lando page it is
     * lando.pages.edit. Screens shared by two apps use this so they never link
     * into the other app. Returns null when the current app has no such screen.
     */
    function app_route(string $name, mixed $parameters = []): ?string
    {
        $app = AdminMenu::currentApp() ?? 'lando';

        return Route::has("$app.$name") ? route("$app.$name", $parameters) : null;
    }
}
