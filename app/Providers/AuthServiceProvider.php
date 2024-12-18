<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate; // Add this line
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;


class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        //
    ];

    public function boot(): void
    {
        Gate::define('admin', function (User $user) {
            return $user->is_admin;
        });
    }
}
