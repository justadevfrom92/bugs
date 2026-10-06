<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only admin users hold rights; a customer signed in to My Account never does.
        // Extra rights from config/admin.php (delete, refunds) become gates: @can('delete'), Gate::authorize('refunds')
        foreach (array_keys(config('admin.permissions')) as $perm) {
            Gate::define($perm, fn ($user) => $user instanceof User && $user->hasPerm($perm));
        }

        // Creating, editing and deleting admin apps from the launcher
        Gate::define('manage-apps', fn ($user) => $user instanceof User && $user->hasPerm('sheriff'));
    }
}
