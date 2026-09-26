<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // Catch N+1 queries and typos in attribute names during development.
        Model::shouldBeStrict(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // The super admin passes every policy check. Everyone else is decided by policies.
        Gate::before(fn (User $user): ?bool => $user->isSuperAdmin() ? true : null);

        Password::defaults(fn (): Password => Password::min(8)->letters()->numbers()->max(255));
    }
}
