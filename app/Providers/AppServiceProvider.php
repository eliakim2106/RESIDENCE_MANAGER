<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;
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
        Paginator::useBootstrapFive();

        Route::resourceVerbs([
            'create' => 'creer',
            'edit' => 'modifier',
        ]);

        Password::defaults(fn (): Password => $this->app->isProduction()
            ? Password::min(8)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8)->letters()->numbers());
    }
}
