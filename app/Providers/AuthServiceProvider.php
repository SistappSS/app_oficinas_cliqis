<?php

namespace App\Providers;

use App\Models\Retails\Branch;
use App\Policies\BranchPolicy;
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
        Gate::policy(Branch::class, BranchPolicy::class);
    }
}
