<?php

namespace App\Providers;

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
        // ✅ Connect your custom permission system to Laravel @can / Gate
        Gate::before(function ($user, string $ability) {

            // ✅ same bypass as your middleware
            $role = $user->role ?? null;
            if (in_array($role, ['Developer', 'Admin'], true)) {
                return true;
            }

            // ✅ Use your custom permission checker
            if (method_exists($user, 'hasPermission')) {
                return $user->hasPermission($ability) ? true : null;
            }

            // fall back to default Gate behavior
            return null;
        });
    }
}