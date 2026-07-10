<?php

namespace App\Providers;

use App\Services\GeoHierarchyService;
use App\Services\HashidService;
use App\Support\NavPermissions;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HashidService::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.wdf');
        Paginator::defaultSimpleView('vendor.pagination.simple-wdf');

        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        View::composer(['layouts.app', 'partials.sidebar', 'partials.user-profile-menu'], function ($view) {
            if (! auth()->check()) {
                return;
            }

            $user = auth()->user();
            $user->loadMissing(['roles:id,name', 'applicant:id,user_id,full_name,first_name']);

            $view->with('user', $user);
            $view->with('nav', NavPermissions::for($user));
        });

        View::composer(['applicants.create', 'applicants.edit', 'loan_applications.apply'], function ($view) {
            $view->with('geoApi', GeoHierarchyService::apiUrls());
        });
    }
}
