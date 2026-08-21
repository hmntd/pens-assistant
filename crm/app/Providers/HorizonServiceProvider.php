<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Configure the Horizon authorization security check.
     * Overrides Horizon's default local bypass so that unauthenticated users
     * and non-admin users are strictly forbidden from accessing Horizon even on localhost.
     */
    protected function authorization(): void
    {
        Horizon::auth(function ($request) {
            $user = $request->user();

            return $user !== null && $user->hasRole('admin');
        });
    }

    /**
     * Register the Horizon gate.
     * Determine who can access Horizon. Strictly restricted to admin users.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            return $user && $user->hasRole('admin');
        });
    }
}
