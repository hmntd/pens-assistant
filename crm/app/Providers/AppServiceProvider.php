<?php

namespace App\Providers;

use App\Auth\UuidEloquentUserProvider;
use App\Models\CalculatedPension;
use App\Policies\PensionCalculationPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use SocialiteProviders\Azure\AzureExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use Spatie\Permission\PermissionRegistrar;

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
        Gate::policy(CalculatedPension::class, PensionCalculationPolicy::class);
        Event::listen(SocialiteWasCalled::class, AzureExtendSocialite::class);
        $this->configureDefaults();

        Auth::provider('eloquent', function ($app, array $config) {
            return new UuidEloquentUserProvider($app['hash'], $config['model']);
        });

        if ($this->app->runningInConsole()) {
            $isMigrateCommand = false;
            foreach ($_SERVER['argv'] ?? [] as $arg) {
                if (str_contains($arg, 'migrate')) {
                    $isMigrateCommand = true;
                    break;
                }
            }

            if ($isMigrateCommand) {
                config([
                    'cache.default' => 'array',
                    'permission.cache.store' => 'array',
                ]);
                if ($this->app->bound(PermissionRegistrar::class)) {
                    $this->app->make(PermissionRegistrar::class)->initializeCache();
                }
            }
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
