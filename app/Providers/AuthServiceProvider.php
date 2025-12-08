<?php

namespace App\Providers;

use App\Models\Entities\Users\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability = null) {
            if ($user->isMasterCustomerForTenant()) {
                return true;
            }

            return null;
        });
    }
}
