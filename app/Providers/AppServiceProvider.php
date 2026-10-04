<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\Filament\Http\Responses\Auth\Contracts\LogoutResponse::class, \App\Http\Responses\SiteLogoutResponse::class);
    }

    public function boot(): void
    {
        // Content missing in the visitor's language falls back to Azerbaijani, and if that is
        // missing too (e.g. an article written in English) to whichever language exists.
        Translatable::fallback(fallbackLocale: config('app.fallback_locale'), fallbackAny: true);

        Paginator::useBootstrapFive();
    }
}
